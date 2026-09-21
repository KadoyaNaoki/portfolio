<?php

namespace App\Http\Middleware;

use App\Support\AccessLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * アクセスログを記録するミドルウェア.
 *
 * すべての HTTP リクエスト（Web / API の両方）について、
 * 1 アクセス = 5 行をアプリログ（storage/logs/laravel-YYYY-MM-DD.log）に出力する。
 *
 * 出力例:
 *   ***********************************************
 *   2026/09/21 07:40:05
 *   users/23/edit
 *   users.id : 23
 *   users.userid : admin_test
 *
 * ■ 出力タイミング
 *   $next() を呼ぶ前（＝リクエスト処理より前）に書き出す。
 *   これにより、続いて出力される SQL ログが
 *   「このアクセスに紐づくクエリ」として読める。
 *
 *     ***
 *     2026/09/21 07:40:05
 *     users
 *     users.id : 23
 *     users.userid : admin_test
 *     [debug]... SQL : select * from `users` ... (0.7ms)   ← このアクセス中に実行
 *
 * ■ users.id の解決
 *   セッションから復元されるため、セッション開始後でないと取得できない。
 *   そのため本ミドルウェアは web グループ内の StartSession より後
 *   （bootstrap/app.php の $middleware->web(append: [...])）に登録する。
 *
 * ログインしていない場合は users.id / users.userid が "-" になる。
 * バッチ（CLI）は HTTP ではないため自動記録されない。
 * 各コマンドから App\Support\AccessLog::log() を呼んで記録する。
 */
class LogAccess
{
	public function handle(Request $request, Closure $next): Response
	{
		$user = Auth::user();

		AccessLog::log(
			$request->path(),
			$user?->id,
			$user?->userid,
		);

		return $next($request);
	}
}
