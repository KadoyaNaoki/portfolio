<?php

namespace App\Repositories\Contracts;

use App\Models\LoginHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LoginHistoryRepositoryInterface
{
	/**
	 * 履歴を取得.
	 *
	 * $onlyUserId が指定された場合はそのユーザーの履歴のみ、
	 * null の場合は全件（管理者向け）を返す。
	 */
	public function search(?int $onlyUserId, ?string $keyword, int $perPage = 15): LengthAwarePaginator;

	public function create(array $attributes): LoginHistory;
}
