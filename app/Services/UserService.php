<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UserService
{
	private const AVATAR_DIRECTORY = 'avatars';

	public function __construct(
		private readonly UserRepositoryInterface $users,
	) {}

	/**
	 * 一覧用の検索結果を取得.
	 */
	public function search(?string $keyword, string $sort, string $direction): LengthAwarePaginator
	{
		return $this->users->search($keyword, $sort, $direction);
	}

	/**
	 * プロフィール更新.
	 *
	 * @param  array{userid: string, name: string, password?: string|null, role?: int|string|null}  $attributes
	 */
	public function updateProfile(User $user, array $attributes, ?UploadedFile $avatar = null): User
	{
		$data = [
			'userid' => $attributes['userid'],
			'name' => $attributes['name'],
		];

		if (! empty($attributes['password'])) {
			$data['password'] = $attributes['password'];
		}

		if (isset($attributes['role'])) {
			$data['role'] = $attributes['role'];
		}

		if ($avatar) {
			$data['avatar_path'] = $this->replaceAvatar($user, $avatar);
		}

		try {
			return DB::transaction(fn () => $this->users->update($user, $data));
		} catch (Throwable $e) {
			Log::error('UserService::updateProfile failed', [
				'user_id' => $user->id,
				'error' => $e->getMessage(),
			]);

			throw $e;
		}
	}

	/**
	 * 新規ユーザー登録（一般ロール固定）.
	 */
	public function register(string $userid, string $name, string $password): User
	{
		try {
			return DB::transaction(fn () => $this->users->create([
				'userid' => $userid,
				'name' => $name,
				'password' => $password,
				'role' => UserRole::USER,
			]));
		} catch (Throwable $e) {
			Log::error('UserService::register failed', [
				'userid' => $userid,
				'error' => $e->getMessage(),
			]);

			throw $e;
		}
	}

	/**
	 * ロールを指定してユーザーを作成する（API・バッチから利用）.
	 */
	public function createWithRole(string $userid, string $name, string $password, UserRole $role): User
	{
		try {
			return DB::transaction(fn () => $this->users->create([
				'userid' => $userid,
				'name' => $name,
				'password' => $password,
				'role' => $role,
			]));
		} catch (Throwable $e) {
			Log::error('UserService::createWithRole failed', [
				'userid' => $userid,
				'role' => $role->value,
				'error' => $e->getMessage(),
			]);

			throw $e;
		}
	}

	/**
	 * 未使用のユーザーIDを生成する（連番 + 接頭辞）.
	 *
	 * 既存と衝突した場合は連番を進めて再試行する。
	 */
	public function generateUniqueUserid(string $prefix = 'user', int $start = 1): string
	{
		$n = $start;

		while ($this->users->existsByUserid($prefix.$n)) {
			$n++;
		}

		return $prefix.$n;
	}

	/**
	 * ユーザーを一括削除する.
	 *
	 * 安全のため次は削除しない。
	 *   - 存在しない ID
	 *   - 自分自身（操作中のアカウントを消すとログインできなくなるため）
	 *
	 * @param  array<int, int|string>  $ids
	 * @param  int  $actingUserId  操作中のユーザー ID
	 * @return int 実際に削除した件数
	 */
	public function deleteByIds(array $ids, int $actingUserId): int
	{
		// 数値だけを残し、操作者自身を除外する
		$targets = collect($ids)
			->map(fn ($id) => (int) $id)
			->filter(fn (int $id) => $id > 0 && $id !== $actingUserId)
			->unique()
			->values()
			->all();

		if ($targets === []) {
			return 0;
		}

		// アバター画像も一緒に片付ける
		foreach ($this->users->findByIds($targets) as $user) {
			if ($user->avatar_path) {
				Storage::disk('public')->delete($user->avatar_path);
			}
		}

		try {
			return DB::transaction(fn () => $this->users->deleteByIds($targets));
		} catch (Throwable $e) {
			Log::error('UserService::deleteByIds failed', [
				'ids' => $targets,
				'acting_user_id' => $actingUserId,
				'error' => $e->getMessage(),
			]);

			throw $e;
		}
	}

	/**
	 * 旧画像を削除して新しい画像を保存し、保存パスを返す.
	 */
	private function replaceAvatar(User $user, UploadedFile $avatar): string
	{
		if ($user->avatar_path) {
			Storage::disk('public')->delete($user->avatar_path);
		}

		return $avatar->store(self::AVATAR_DIRECTORY, 'public');
	}
}
