<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 管理者ユーザー作成 API.
 *
 * 認可は API キーのみ（ログイン不要）。
 */
class AdminUserApiTest extends TestCase
{
	use RefreshDatabase;

	/**
	 * テストで使う API キー（.env の ADMIN_API_KEY）.
	 */
	private function apiKey(): string
	{
		return (string) config('api.admin_user_api_key');
	}

	/**
	 * API キー付きで POST するヘルパー（ヘッダで渡す）.
	 */
	private function postWithKey(array $payload)
	{
		return $this->postJson('/api/admin/users', $payload, [
			'X-API-KEY' => $this->apiKey(),
		]);
	}

	// =================================================================
	// 正常系（ログインしていなくても作成できる）
	// =================================================================

	public function test_can_create_admin_user_with_valid_api_key(): void
	{
		$response = $this->postWithKey([
			'userid' => 'newadmin',
			'name' => '新しい管理者',
			'password' => 'password123',
		]);

		$response->assertStatus(201)
			->assertJsonPath('data.userid', 'newadmin')
			->assertJsonPath('data.name', '新しい管理者')
			->assertJsonPath('data.role', UserRole::ADMIN->value)
			->assertJsonPath('data.role_label', '管理者');

		$this->assertDatabaseHas('users', [
			'userid' => 'newadmin',
			'role' => UserRole::ADMIN->value,
		]);
	}

	public function test_created_admin_password_is_hashed(): void
	{
		$this->postWithKey([
			'userid' => 'hashedadmin',
			'name' => 'ハッシュ管理者',
			'password' => 'password123',
		])->assertStatus(201);

		$created = User::where('userid', 'hashedadmin')->firstOrFail();

		$this->assertNotSame('password123', $created->password);
		$this->assertTrue(Hash::check('password123', $created->password));
	}

	public function test_userid_is_auto_generated_when_omitted(): void
	{
		$response = $this->postWithKey([
			'name' => '自動採番',
			'password' => 'password123',
		]);

		$response->assertStatus(201);
		$this->assertSame('admin1', $response->json('data.userid'));
	}

	public function test_auto_generated_userid_avoids_collision(): void
	{
		User::factory()->create(['userid' => 'admin1']);

		$response = $this->postWithKey([
			'name' => '自動採番2',
			'password' => 'password123',
		]);

		$response->assertStatus(201);
		$this->assertSame('admin2', $response->json('data.userid'));
	}

	public function test_response_does_not_expose_password(): void
	{
		$response = $this->postWithKey([
			'name' => '秘匿確認',
			'password' => 'password123',
		]);

		$response->assertStatus(201);
		$this->assertArrayNotHasKey('password', $response->json('data'));
	}

	public function test_creating_admin_does_not_require_login(): void
	{
		// 未ログイン（actingAs なし）でも 201 になる
		$this->assertGuest();

		$this->postWithKey([
			'userid' => 'nologinadmin',
			'name' => 'ログイン不要',
			'password' => 'password123',
		])->assertStatus(201);
	}

	// =================================================================
	// API キー
	// =================================================================

	public function test_api_key_can_be_sent_in_header(): void
	{
		$this->postWithKey([
			'userid' => 'headerkey',
			'name' => 'ヘッダキー',
			'password' => 'password123',
		])->assertStatus(201);
	}

	public function test_api_key_can_be_sent_in_body(): void
	{
		$this->postJson('/api/admin/users', [
			'api_key' => $this->apiKey(),
			'userid' => 'bodykey',
			'name' => 'ボディキー',
			'password' => 'password123',
		])->assertStatus(201);
	}

	public function test_request_without_api_key_is_forbidden(): void
	{
		$this->postJson('/api/admin/users', [
			'name' => 'キー無し',
			'password' => 'password123',
		])->assertStatus(403);
	}

	public function test_request_with_wrong_api_key_is_forbidden(): void
	{
		$this->postJson('/api/admin/users', [
			'name' => 'キー不正',
			'password' => 'password123',
		], ['X-API-KEY' => 'wrong-key'])->assertStatus(403);
	}

	public function test_wrong_api_key_creates_no_record(): void
	{
		$this->postJson('/api/admin/users', [
			'name' => 'キー不正',
			'password' => 'password123',
		], ['X-API-KEY' => 'wrong-key'])->assertStatus(403);

		$this->assertSame(0, User::count());
	}

	public function test_admin_role_does_not_bypass_api_key(): void
	{
		// 管理者としてログインしていても、API キーが無ければ拒否される
		$admin = User::factory()->create(['role' => UserRole::ADMIN]);

		$this->actingAs($admin)->postJson('/api/admin/users', [
			'name' => 'キー無し管理者',
			'password' => 'password123',
		])->assertStatus(403);
	}

	// =================================================================
	// バリデーション
	// =================================================================

	public function test_name_is_required(): void
	{
		$this->postWithKey([
			'password' => 'password123',
		])->assertStatus(422)->assertJsonValidationErrors('name');
	}

	public function test_password_is_required(): void
	{
		$this->postWithKey([
			'name' => 'パスワードなし',
		])->assertStatus(422)->assertJsonValidationErrors('password');
	}

	public function test_password_must_be_at_least_8_chars(): void
	{
		$this->postWithKey([
			'name' => '短いパスワード',
			'password' => 'short',
		])->assertStatus(422)->assertJsonValidationErrors('password');
	}

	public function test_userid_must_be_unique(): void
	{
		User::factory()->create(['userid' => 'taken']);

		$this->postWithKey([
			'userid' => 'taken',
			'name' => '重複',
			'password' => 'password123',
		])->assertStatus(422)->assertJsonValidationErrors('userid');
	}

	public function test_userid_rejects_invalid_characters(): void
	{
		$this->postWithKey([
			'userid' => 'invalid id!',
			'name' => '記号',
			'password' => 'password123',
		])->assertStatus(422)->assertJsonValidationErrors('userid');
	}

	public function test_userid_must_be_at_least_4_chars(): void
	{
		$this->postWithKey([
			'userid' => 'abc',
			'name' => '短すぎ',
			'password' => 'password123',
		])->assertStatus(422)->assertJsonValidationErrors('userid');
	}

	public function test_cannot_create_general_user_through_admin_api(): void
	{
		$response = $this->postWithKey([
			'userid' => 'forcedadmin',
			'name' => '強制管理者',
			'password' => 'password123',
			'role' => UserRole::USER->value,
		]);

		$response->assertStatus(201);
		$this->assertSame(UserRole::ADMIN, User::where('userid', 'forcedadmin')->firstOrFail()->role);
	}
}
