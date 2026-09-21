<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\DeleteUsersRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
	public function __construct(
		private readonly UserService $userService,
	) {}

	public function index(Request $request): View
	{
		$sort = $request->query('sort', 'created_at');
		$direction = $request->query('direction', 'desc');

		$users = $this->userService->search($request->query('keyword'), $sort, $direction);

		return view('users.index', compact('users', 'sort', 'direction'));
	}

	public function show(User $user): View
	{
		return view('users.show', compact('user'));
	}

	public function edit(User $user): View
	{
		Gate::authorize('update', $user);

		$roles = UserRole::options();

		return view('users.edit', compact('user', 'roles'));
	}

	public function update(UpdateUserRequest $request, User $user): RedirectResponse
	{
		$this->userService->updateProfile(
			$user,
			$request->validated(),
			$request->file('avatar'),
		);

		return redirect()->route('users.show', $user)->with('status', 'プロフィールを更新しました。');
	}

	/**
	 * チェックされたユーザーを一括削除する（管理者のみ）.
	 *
	 * 操作者自身が選択に含まれていても、Service 側で除外される。
	 */
	public function destroy(DeleteUsersRequest $request): RedirectResponse
	{
		$deleted = $this->userService->deleteByIds(
			$request->validated()['ids'],
			Auth::id(),
		);

		if ($deleted === 0) {
			return redirect()->route('users.index')
				->with('status', '削除できるユーザーがありませんでした（自分自身は削除できません）。');
		}

		return redirect()->route('users.index')
			->with('status', $deleted.' 件のユーザーを削除しました。');
	}
}
