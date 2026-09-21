<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
	/**
	 * プロフィールを編集できるか.
	 *
	 * - 自分のプロフィール：編集可
	 * - 他人のプロフィール：管理者のみ編集可
	 */
	public function update(User $authUser, User $targetUser): bool
	{
		if ($this->isSelf($authUser, $targetUser)) {
			return true;
		}

		// 他人の編集は管理者だけ
		return $authUser->isAdmin();
	}

	/**
	 * ロールを変更できるか（管理者のみ）.
	 */
	public function changeRole(User $authUser, User $targetUser): bool
	{
		return $authUser->isAdmin();
	}

	/**
	 * ユーザーを削除できるか（管理者のみ）.
	 *
	 * 一覧のチェックボックスから一括削除する用途を想定している。
	 */
	public function delete(User $authUser): bool
	{
		return $authUser->isAdmin();
	}

	private function isSelf(User $authUser, User $targetUser): bool
	{
		return $authUser->id === $targetUser->id;
	}
}
