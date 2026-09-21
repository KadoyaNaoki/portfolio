<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
	/**
	 * 検索・ソート・ページネーション付きでユーザーを取得.
	 */
	public function search(?string $keyword, string $sort, string $direction, int $perPage = 10): LengthAwarePaginator;

	public function findById(int $id): ?User;

	/**
	 * 指定 ID のユーザーをまとめて取得する.
	 *
	 * @param  array<int, int>  $ids
	 * @return Collection<int, User>
	 */
	public function findByIds(array $ids);

	public function findByUserid(string $userid): ?User;

	/**
	 * ユーザーを作成（role未指定時は一般）.
	 */
	public function create(array $attributes): User;

	/**
	 * ユーザーを更新.
	 */
	public function update(User $user, array $attributes): User;

	public function existsByUserid(string $userid, ?int $exceptId = null): bool;

	/**
	 * 指定 ID のユーザーをまとめて削除し、削除件数を返す.
	 *
	 * @param  array<int, int>  $ids
	 */
	public function deleteByIds(array $ids): int;
}
