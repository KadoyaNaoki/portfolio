<?php

namespace App\Repositories\Contracts;

use App\Models\ChangeHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ChangeHistoryRepositoryInterface
{
	/**
	 * 変更履歴を取得（新しい登録日順）.
	 *
	 * $keyword が指定されたら本文・ユーザーID・表示名で絞り込む。
	 */
	public function search(?string $keyword, int $perPage = 15): LengthAwarePaginator;

	public function create(array $attributes): ChangeHistory;

	/**
	 * 指定 ID の変更履歴をまとめて削除し、削除件数を返す.
	 *
	 * @param  array<int, int>  $ids
	 */
	public function deleteByIds(array $ids): int;
}
