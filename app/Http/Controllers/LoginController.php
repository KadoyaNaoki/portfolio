<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
	public function __construct(
		private readonly AuthService $authService,
	) {}

	public function index(): View
	{
		return view('Login.index');
	}

	public function login(LoginRequest $request): RedirectResponse
	{
		$success = $this->authService->attempt(
			$request->only('userid', 'password'),
			$request->boolean('remember'),
			$request->ip(),
			$request->userAgent(),
		);

		if ($success) {
			$request->session()->regenerate();

			// intended() は「ログイン前に弾かれたURL」を優先するため、
			// 前のセッションに残った他人のページ（/users/8 など）へ飛んでしまう。
			// ログイン直後は必ず一覧へ送る。
			return redirect()->route('users.index');
		}

		return back()->withErrors([
			'userid' => 'ユーザーIDまたはパスワードが正しくありません。',
		])->onlyInput('userid');
	}

	public function logout(Request $request): RedirectResponse
	{
		$this->authService->logout();

		$request->session()->invalidate();
		$request->session()->regenerateToken();

		return redirect('/Login')->with('status', 'ログアウトしました。');
	}
}
