<?php

namespace App\Repositories\Eloquent;

use App\Models\ChangeHistory;
use App\Repositories\Contracts\ChangeHistoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ChangeHistoryRepository implements ChangeHistoryRepositoryInterface
{
	public function search(?string $keyword, int $perPage = 15): LengthAwarePaginator
	{
		return ChangeHistory::query()
			->with('user')
			->when($keyword, function ($query, $keyword) {
				$query->where(function ($q) use ($keyword) {
					$q->where('body', 'like', "%{$keyword}%")
						->orWhere('userid', 'like', "%{$keyword}%")
						->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$keyword}%"));
				});
			})
			->orderByDesc('registered_at')
			->orderByDesc('id')
			->paginate($perPage)
			->withQueryString();
	}

	public function create(array $attributes): ChangeHistory
	{
		return ChangeHistory::create($attributes);
	}

	/**
	 * 指定 ID の変更履歴をまとめて削除する.
	 *
	 * @param  array<int, int>  $ids
	 * @return int 削除した件数
	 */
	public function deleteByIds(array $ids): int
	{
		if ($ids === []) {
			return 0;
		}

		return ChangeHistory::whereIn('id', $ids)->delete();
	}
}
