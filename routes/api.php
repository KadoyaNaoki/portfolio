<?php

use App\Http\Controllers\Api\AdminUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API ルート
|--------------------------------------------------------------------------
|
| 認証は固定の API キー（.env の ADMIN_API_KEY）で行う。
| ログインセッションは不要なので auth ミドルウェアは付けない。
| /api/* は専用のミドルウェアグループではなく web グループに載せており、
| CSRF も通常のフォームと同じ扱いになる。
|
| 管理画面のセッション認証が必要な API を将来足す場合は、
| ここで ->middleware('auth') を付ける。
|
*/

Route::middleware('web')->group(function () {
	// 管理者ユーザー作成
	// API キーは StoreAdminUserRequest::authorize() で照合する
	// POST /api/admin/users
	Route::post('/admin/users', [AdminUserController::class, 'store'])
		->name('api.admin.users.store');
});
