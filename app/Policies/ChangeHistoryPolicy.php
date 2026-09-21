<?php

namespace App\Policies;

use App\Models\User;

class ChangeHistoryPolicy
{
	/**
	 * 変更履歴を登録できるのは管理者のみ.
	 */
	public function create(User $user): bool
	{
		return $user->isAdmin();
	}

	/**
	 * 変更履歴を削除できるのは管理者のみ.
	 *
	 * 一覧のチェックボックスから一括削除する用途を想定している。
	 */
	public function delete(User $user): bool
	{
		return $user->isAdmin();
	}
}
