<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

/**
 * =====================================================================
 * 管理者ユーザー作成 API
 * =====================================================================
 *
 * ■ 概要
 *   管理者ロール（UserRole::ADMIN）のユーザーを新規作成する。
 *   一般ユーザー作成用の口は用意しておらず、このAPIでは必ず role=2 になる。
 *
 * ■ エンドポイント
 *   POST /api/admin/users
 *
 * ■ 認証・認可
 *   API キーの照合のみで行う（ログイン不要）。
 *   API キーは次のいずれかで渡す（優先順）。
 *     - ヘッダ : X-API-KEY
 *     - ボディ : api_key
 *   実値は .env の ADMIN_API_KEY で管理する（config/api.php 経由で読み込む）。
 *
 *   API キー不一致・未設定 → 403
 *
 * ■ リクエスト例
 *   curl -X POST http://localhost/api/admin/users \
 *     -H "Content-Type: application/json" \
 *     -H "Accept: application/json" \
 *     -H "X-API-KEY: <ADMIN_API_KEY の値>" \
 *     -d '{"userid":"admin02","name":"管理者2","password":"password123"}'
 *
 *   ※ ヘッダではなくボディでも可:
 *     -d '{"api_key":"<ADMIN_API_KEY の値>","name":"管理者2","password":"password123"}'
 *
 * ■ レスポンス例（201 Created）
 *   {
 *     "data": {
 *       "id": 12,
 *       "userid": "admin02",
 *       "name": "管理者2",
 *       "role": 2,
 *       "role_label": "管理者",
 *       "created_at": "2026-01-01T00:00:00.000Z"
 *     }
 *   }
 *
 * ■ ステータスコード
 *   201 作成成功
 *   403 API キー不一致（未設定含む）
 *   422 バリデーションエラー（userid 重複・文字数など）
 */
class AdminUserController extends Controller
{
	public function __construct(
		private readonly UserService $userService,
	) {}

	/**
	 * 管理者ユーザーを 1 件作成する.
	 *
	 * userid を省略した場合は "admin" + 連番（admin1, admin2, ...）で自動採番する。
	 */
	public function store(StoreAdminUserRequest $request): JsonResponse
	{
		$validated = $request->validated();

		// userid 未指定なら "admin" + 未使用の連番を採番する
		$userid = $validated['userid']
			?? $this->userService->generateUniqueUserid('admin');

		$user = $this->userService->createWithRole(
			$userid,
			$validated['name'],
			$validated['password'],
			UserRole::ADMIN,
		);

		return response()->json([
			'data' => [
				'id' => $user->id,
				'userid' => $user->userid,
				'name' => $user->name,
				'role' => $user->role->value,
				'role_label' => $user->role->label(),
				'created_at' => $user->created_at?->toJSON(),
			],
		], 201);
	}
}
