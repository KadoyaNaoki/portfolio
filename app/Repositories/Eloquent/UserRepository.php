<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
	/**
	 * ソート可能なカラムのホワイトリスト.
	 */
	private const SORTABLE = ['userid', 'name', 'created_at'];

	public function search(?string $keyword, string $sort, string $direction, int $perPage = 10): LengthAwarePaginator
	{
		if (! in_array($sort, self::SORTABLE, true)) {
			$sort = 'created_at';
		}
		if (! in_array($direction, ['asc', 'desc'], true)) {
			$direction = 'desc';
		}

		return User::query()
			->when($keyword, function ($query, $keyword) {
				$query->where(function ($q) use ($keyword) {
					$q->where('userid', 'like', "%{$keyword}%")
						->orWhere('name', 'like', "%{$keyword}%");
				});
			})
			->orderBy($sort, $direction)
			->paginate($perPage)
			->withQueryString();
	}

	public function findById(int $id): ?User
	{
		return User::find($id);
	}

	/**
	 * 指定 ID のユーザーをまとめて取得する.
	 *
	 * @param  array<int, int>  $ids
	 * @return Collection<int, User>
	 */
	public function findByIds(array $ids)
	{
		if ($ids === []) {
			return User::whereRaw('1 = 0')->get();
		}

		return User::whereIn('id', $ids)->get();
	}

	public function findByUserid(string $userid): ?User
	{
		return User::where('userid', $userid)->first();
	}

	public function create(array $attributes): User
	{
		return User::create($attributes);
	}

	public function update(User $user, array $attributes): User
	{
		$user->update($attributes);

		return $user;
	}

	public function existsByUserid(string $userid, ?int $exceptId = null): bool
	{
		return User::where('userid', $userid)
			->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
			->exists();
	}

	/**
	 * 指定 ID のユーザーをまとめて削除する.
	 *
	 * @param  array<int, int>  $ids
	 * @return int 削除した件数
	 */
	public function deleteByIds(array $ids): int
	{
		if ($ids === []) {
			return 0;
		}

		return User::whereIn('id', $ids)->delete();
	}
}
