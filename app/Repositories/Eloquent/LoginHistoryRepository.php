<?php

namespace App\Repositories\Eloquent;

use App\Models\LoginHistory;
use App\Repositories\Contracts\LoginHistoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LoginHistoryRepository implements LoginHistoryRepositoryInterface
{
	public function search(?int $onlyUserId, ?string $keyword, int $perPage = 15): LengthAwarePaginator
	{
		return LoginHistory::query()
			->with('user')
			->when($onlyUserId, fn ($query) => $query->where('user_id', $onlyUserId))
			->when($keyword, function ($query, $keyword) {
				$query->where(function ($q) use ($keyword) {
					$q->where('userid', 'like', "%{$keyword}%")
						->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$keyword}%"));
				});
			})
			->latest('logged_in_at')
			->latest('id')
			->paginate($perPage)
			->withQueryString();
	}

	public function create(array $attributes): LoginHistory
	{
		return LoginHistory::create($attributes);
	}
}
