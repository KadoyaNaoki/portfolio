<?php

namespace App\Services;

use App\Models\ChangeHistory;
use App\Models\User;
use App\Repositories\Contracts\ChangeHistoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class ChangeHistoryService
{
	public function __construct(
		private readonly ChangeHistoryRepositoryInterface $histories,
	) {}

	/**
	 * 一覧用の変更履歴を取得（一般・管理者とも閲覧可）.
	 */
	public function search(?string $keyword): LengthAwarePaginator
	{
		return $this->histories->search($keyword);
	}

	/**
	 * 変更履歴を一括削除する（管理者のみ）.
	 *
	 * @param  array<int, int|string>  $ids
	 * @return int 削除した件数
	 */
	public function deleteByIds(array $ids): int
	{
		$targets = collect($ids)
			->map(fn ($id) => (int) $id)
			->filter(fn (int $id) => $id > 0)
			->unique()
			->values()
			->all();

		if ($targets === []) {
			return 0;
		}

		$deleted = $this->histories->deleteByIds($targets);

		Log::info('ChangeHistoryService::deleteByIds', [
			'deleted' => $deleted,
			'ids' => $targets,
		]);

		return $deleted;
	}

	/**
	 * 変更履歴を登録（呼び出し側で管理者チェック済み）.
	 */
	public function create(User $author, string $body): ChangeHistory
	{
		Log::info('ChangeHistoryService::create', [
			'user_id' => $author->id,
			'userid' => $author->userid,
			'body_length' => mb_strlen($body),
		]);

		return $this->histories->create([
			'user_id' => $author->id,
			'userid' => $author->userid,
			'body' => $body,
			// 履歴登録日
			'registered_at' => now(),
		]);
	}
}
