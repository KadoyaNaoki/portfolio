<?php

use App\Http\Middleware\LogAccess;
use App\Http\Middleware\PreventDuplicateSubmission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
	->withRouting(
		web: __DIR__.'/../routes/web.php',
		api: __DIR__.'/../routes/api.php',
		commands: __DIR__.'/../routes/console.php',
		health: '/up',
	)
	->withMiddleware(function (Middleware $middleware): void {
		// guest ミドルウェアがログイン済みユーザーを送る先。
		// 既定の /dashboard は存在しないため、一覧を指定する（未指定だと404になる）。
		$middleware->redirectUsersTo('/users');

		// web グループの末尾に追加するミドルウェア。
		// ※ $middleware->web(append: [...]) を複数回呼ぶと後勝ちで上書きされるため、
		//   必ず 1 回の呼び出しにまとめる。
		//
		// LogAccess:
		//   すべてのリクエスト（Web / API）をアクセスログに記録する。
		//   クエリより先にログを出す必要があるため、リクエスト処理の前に書く。
		//   一方で users.id はセッションから復元されるため、
		//   StartSession より後（＝web グループの末尾）に置く。
		//
		// PreventDuplicateSubmission:
		//   フォームの二重送信（連打）を防ぐ。
		//   JS でもボタンを無効化しているが、JS は無効化できるため
		//   サーバー側でも必ず弾く。
		$middleware->web(append: [
			LogAccess::class,
			PreventDuplicateSubmission::class,
		]);

		// API は API キーで認可しており、ブラウザのセッションに依存しない。
		// フォームからの送信ではないため CSRF トークンも持たないので、
		// api/* は CSRF 検証の対象外にする（これがないと常に 419 になる）。
		$middleware->validateCsrfTokens(except: [
			'api/*',
		]);

		// API は同一オリジンの管理画面からの利用を想定しており、
		// セッション認証（guard: web）をそのまま使う。
		// CSRF トークンは Web 側のフォームと同じものを送るため、
		// API も web グループ経由で扱う（routes/api.php 参照）。
	})
	->withExceptions(function (Exceptions $exceptions): void {
		//
	})->create();
