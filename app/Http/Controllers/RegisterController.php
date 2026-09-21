<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisterController extends Controller
{
	public function __construct(
		private readonly UserService $userService,
	) {}

	public function create(): View
	{
		return view('register.create');
	}

	public function store(RegisterRequest $request): RedirectResponse
	{
		$validated = $request->validated();

		$this->userService->register(
			$validated['userid'],
			$validated['name'],
			$validated['password'],
		);

		// 自動ログインなし → ログイン画面へ
		return redirect('/Login')->with('status', '登録しました。ログインしてください。');
	}
}
