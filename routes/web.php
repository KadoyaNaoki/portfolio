<?php

use App\Http\Controllers\ChangeHistoryController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LoginHistoryController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ゲストのみ
Route::middleware('guest')->group(function () {
	Route::get('/register', [RegisterController::class, 'create'])->name('register');
	Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

	Route::get('/', [LoginController::class, 'index']);
	Route::get('/Login', [LoginController::class, 'index'])->name('login');
	Route::post('/login', [LoginController::class, 'login'])
		->middleware('throttle:5,1')
		->name('login.attempt');
});

// 認証必須
Route::middleware('auth')->group(function () {
	Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

	// ユーザー一覧・詳細・編集（一覧/詳細は全員、編集はPolicyで本人or管理者）
	Route::get('/users', [UserController::class, 'index'])->name('users.index');
	Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
	Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
	Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');

	// ユーザー一括削除（管理者のみ。チェックボックスで選択した分を消す）
	Route::delete('/users', [UserController::class, 'destroy'])->name('users.destroy');

	// ログイン履歴（一般=自分のみ、管理者=全件：Controller内で分岐）
	Route::get('/login-histories', [LoginHistoryController::class, 'index'])->name('login-histories.index');

	// 変更履歴：一覧は全員、登録は管理者のみ（Policy / FormRequest で制御）
	Route::get('/change-histories', [ChangeHistoryController::class, 'index'])->name('change-histories.index');
	Route::get('/change-histories/create', [ChangeHistoryController::class, 'create'])->name('change-histories.create');
	Route::post('/change-histories', [ChangeHistoryController::class, 'store'])->name('change-histories.store');

	// 変更履歴の一括削除（管理者のみ。チェックボックスで選択した分を消す）
	Route::delete('/change-histories', [ChangeHistoryController::class, 'destroy'])->name('change-histories.destroy');
});
