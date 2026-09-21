<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\LoginHistoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LoginHistoryService
{
	public function __construct(
		private readonly LoginHistoryRepositoryInterface $histories,
	) {}

	/**
	 * 閲覧権限に応じた履歴を取得.
	 *
	 * 管理者：全件＋キーワード絞り込み
	 * 一般　：自分の履歴のみ
	 */
	public function listFor(User $viewer, ?string $keyword): LengthAwarePaginator
	{
		if ($viewer->isAdmin()) {
			return $this->histories->search(null, $keyword);
		}

		return $this->histories->search($viewer->id, null);
	}
}
