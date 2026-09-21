<?php

namespace Tests\Feature\Requests;

use App\Enums\UserRole;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UserRequestRulesTest extends TestCase
{
	use RefreshDatabase;

	private function requireGd(): void
	{
		if (! function_exists('imagecreatetruecolor')) {
			$this->markTestSkipped('GD 拡張が無効なため、画像バリデーションのテストをスキップします。');
		}
	}

	private function makeRequest(string $requestClass, ?User $authUser, User $routeUser)
	{
		$request = new $requestClass;

		$route = new Route(['PATCH'], '/users/{user}', []);
		$route->bind($request);
		$route->setParameter('user', $routeUser);

		$request->setRouteResolver(function () use ($route) {
			return $route;
		});

		// route('user') は Route::parameter() 経由で見るため、両方から取れるようにする
		$request->setUserResolver(function () use ($authUser) {
			return $authUser;
		});

		return $request;
	}

	private function rulesFor(string $requestClass, User $routeUser): array
	{
		return $this->makeRequest($requestClass, null, $routeUser)->rules();
	}

	private function authorizedRequest(string $requestClass, User $authUser, User $routeUser)
	{
		return $this->makeRequest($requestClass, $authUser, $routeUser);
	}

	// =================================================================
	// UpdateUserRequest - rules()
	// =================================================================

	public function test_update_rules_required_fields(): void
	{
		$target = User::factory()->create();

		$rules = $this->rulesFor(UpdateUserRequest::class, $target);

		$validator = Validator::make([], $rules);

		$this->assertTrue($validator->errors()->has('userid'));
		$this->assertTrue($validator->errors()->has('name'));
	}

	public function test_update_rules_accept_valid_payload(): void
	{
		$target = User::factory()->create(['userid' => 'targetuser']);

		$rules = $this->rulesFor(UpdateUserRequest::class, $target);

		$validator = Validator::make([
			'userid' => 'validuser',
			'name' => '有効な名前',
		], $rules);

		$this->assertFalse($validator->errors()->has('userid'));
		$this->assertFalse($validator->errors()->has('name'));
	}

	public function test_update_rules_reject_userid_shorter_than_4(): void
	{
		$target = User::factory()->create();

		$rules = $this->rulesFor(UpdateUserRequest::class, $target);

		$validator = Validator::make(['userid' => 'abc', 'name' => '名前'], $rules);

		$this->assertTrue($validator->errors()->has('userid'));
	}

	public function test_update_rules_reject_userid_longer_than_30(): void
	{
		$target = User::factory()->create();

		$rules = $this->rulesFor(UpdateUserRequest::class, $target);

		$validator = Validator::make(['userid' => str_repeat('a', 31), 'name' => '名前'], $rules);

		$this->assertTrue($validator->errors()->has('userid'));
	}

	public function test_update_rules_reject_userid_with_symbols(): void
	{
		$target = User::factory()->create();

		$rules = $this->rulesFor(UpdateUserRequest::class, $target);

		$validator = Validator::make(['userid' => 'bad user!', 'name' => '名前'], $rules);

		$this->assertTrue($validator->errors()->has('userid'));
	}

	public function test_update_rules_allow_hyphen_and_underscore_in_userid(): void
	{
		$target = User::factory()->create();

		$rules = $this->rulesFor(UpdateUserRequest::class, $target);

		$validator = Validator::make(['userid' => 'good-user_01', 'name' => '名前'], $rules);

		$this->assertFalse($validator->errors()->has('userid'));
	}

	public function test_update_rules_reject_duplicate_userid(): void
	{
		User::factory()->create(['userid' => 'taken']);
		$target = User::factory()->create(['userid' => 'targetuser']);

		$rules = $this->rulesFor(UpdateUserRequest::class, $target);

		$validator = Validator::make(['userid' => 'taken', 'name' => '名前'], $rules);

		$this->assertTrue($validator->errors()->has('userid'));
	}

	public function test_update_rules_allow_keeping_own_userid(): void
	{
		// 自分自身の userid は unique 制約から除外されること
		$target = User::factory()->create(['userid' => 'myself']);

		$rules = $this->rulesFor(UpdateUserRequest::class, $target);

		$validator = Validator::make(['userid' => 'myself', 'name' => '名前'], $rules);

		$this->assertFalse($validator->errors()->has('userid'));
	}

	public function test_update_rules_reject_short_password(): void
	{
		$target = User::factory()->create();

		$rules = $this->rulesFor(UpdateUserRequest::class, $target);

		$validator = Validator::make([
			'userid' => 'validuser',
			'name' => '名前',
			'password' => 'short',
			'password_confirmation' => 'short',
		], $rules);

		$this->assertTrue($validator->errors()->has('password'));
	}

	public function test_update_rules_reject_unconfirmed_password(): void
	{
		$target = User::factory()->create();

		$rules = $this->rulesFor(UpdateUserRequest::class, $target);

		$validator = Validator::make([
			'userid' => 'validuser',
			'name' => '名前',
			'password' => 'password123',
			'password_confirmation' => 'different123',
		], $rules);

		$this->assertTrue($validator->errors()->has('password'));
	}

	public function test_update_rules_accept_confirmed_password(): void
	{
		$target = User::factory()->create();

		$rules = $this->rulesFor(UpdateUserRequest::class, $target);

		$validator = Validator::make([
			'userid' => 'validuser',
			'name' => '名前',
			'password' => 'password123',
			'password_confirmation' => 'password123',
		], $rules);

		$this->assertFalse($validator->errors()->has('password'));
	}

	public function test_update_rules_allow_omitting_password(): void
	{
		$target = User::factory()->create();

		$rules = $this->rulesFor(UpdateUserRequest::class, $target);

		$validator = Validator::make(['userid' => 'validuser', 'name' => '名前'], $rules);

		$this->assertFalse($validator->errors()->has('password'));
	}

	public function test_update_rules_reject_oversized_avatar(): void
	{
		$this->requireGd();
		$target = User::factory()->create();

		$rules = $this->rulesFor(UpdateUserRequest::class, $target);

		$validator = Validator::make([
			'userid' => 'validuser',
			'name' => '名前',
			'avatar' => UploadedFile::fake()->image('big.jpg')->size(3000),
		], $rules);

		$this->assertTrue($validator->errors()->has('avatar'));
	}

	public function test_update_rules_reject_non_image_avatar(): void
	{
		$target = User::factory()->create();

		$rules = $this->rulesFor(UpdateUserRequest::class, $target);

		$validator = Validator::make([
			'userid' => 'validuser',
			'name' => '名前',
			'avatar' => UploadedFile::fake()->create('document.pdf', 100),
		], $rules);

		$this->assertTrue($validator->errors()->has('avatar'));
	}

	public function test_update_rules_accept_valid_image_avatar(): void
	{
		$this->requireGd();
		$target = User::factory()->create();

		$rules = $this->rulesFor(UpdateUserRequest::class, $target);

		$validator = Validator::make([
			'userid' => 'validuser',
			'name' => '名前',
			'avatar' => UploadedFile::fake()->image('ok.jpg')->size(1000),
		], $rules);

		$this->assertFalse($validator->errors()->has('avatar'));
	}

	// -----------------------------------------------------------------
	// role ルール（管理者のみ）
	// -----------------------------------------------------------------

	public function test_role_rule_is_absent_for_general_user(): void
	{
		$target = User::factory()->create();
		$general = User::factory()->create(['role' => UserRole::USER]);

		$request = $this->authorizedRequest(UpdateUserRequest::class, $general, $target);
		$rules = $request->rules();

		// 一般ユーザーには role ルール自体が付かない
		$this->assertArrayNotHasKey('role', $rules);
	}

	public function test_role_rule_is_present_for_admin(): void
	{
		$target = User::factory()->create();
		$admin = User::factory()->create(['role' => UserRole::ADMIN]);

		$request = $this->authorizedRequest(UpdateUserRequest::class, $admin, $target);
		$rules = $request->rules();

		$this->assertArrayHasKey('role', $rules);
	}

	public function test_admin_role_rule_accepts_enum_values(): void
	{
		$target = User::factory()->create();
		$admin = User::factory()->create(['role' => UserRole::ADMIN]);

		$request = $this->authorizedRequest(UpdateUserRequest::class, $admin, $target);

		foreach ([UserRole::USER->value, UserRole::ADMIN->value] as $value) {
			$validator = Validator::make([
				'userid' => 'validuser',
				'name' => '名前',
				'role' => $value,
			], $request->rules());

			$this->assertFalse($validator->errors()->has('role'), "role={$value} が拒否された");
		}
	}

	public function test_admin_role_rule_rejects_unknown_value(): void
	{
		$target = User::factory()->create();
		$admin = User::factory()->create(['role' => UserRole::ADMIN]);

		$request = $this->authorizedRequest(UpdateUserRequest::class, $admin, $target);

		$validator = Validator::make([
			'userid' => 'validuser',
			'name' => '名前',
			'role' => 99,
		], $request->rules());

		// Enum に無い値（99）は拒否される＝マジックナンバーの混入を防ぐ
		$this->assertTrue($validator->errors()->has('role'));
	}

	public function test_admin_must_supply_role(): void
	{
		$target = User::factory()->create();
		$admin = User::factory()->create(['role' => UserRole::ADMIN]);

		$request = $this->authorizedRequest(UpdateUserRequest::class, $admin, $target);

		$validator = Validator::make([
			'userid' => 'validuser',
			'name' => '名前',
		], $request->rules());

		$this->assertTrue($validator->errors()->has('role'));
	}

	// =================================================================
	// UpdateUserRequest - authorize()（他人の編集は管理者のみ）
	// =================================================================

	public function test_authorize_allows_self_update(): void
	{
		$user = User::factory()->create(['role' => UserRole::USER]);

		$request = $this->authorizedRequest(UpdateUserRequest::class, $user, $user);

		$this->assertTrue($request->authorize());
	}

	public function test_authorize_denies_general_user_editing_others(): void
	{
		$general = User::factory()->create(['role' => UserRole::USER]);
		$other = User::factory()->create(['role' => UserRole::USER]);

		$request = $this->authorizedRequest(UpdateUserRequest::class, $general, $other);

		$this->assertFalse($request->authorize());
	}

	public function test_authorize_allows_admin_editing_others(): void
	{
		$admin = User::factory()->create(['role' => UserRole::ADMIN]);
		$other = User::factory()->create(['role' => UserRole::USER]);

		$request = $this->authorizedRequest(UpdateUserRequest::class, $admin, $other);

		$this->assertTrue($request->authorize());
	}

	public function test_authorize_allows_admin_editing_another_admin(): void
	{
		$admin = User::factory()->create(['role' => UserRole::ADMIN]);
		$otherAdmin = User::factory()->create(['role' => UserRole::ADMIN]);

		$request = $this->authorizedRequest(UpdateUserRequest::class, $admin, $otherAdmin);

		$this->assertTrue($request->authorize());
	}

	// =================================================================
	// RegisterRequest
	// =================================================================

	public function test_register_rules_required_fields(): void
	{
		$request = new RegisterRequest;
		$validator = Validator::make([], $request->rules());

		$this->assertTrue($validator->errors()->has('userid'));
		$this->assertTrue($validator->errors()->has('name'));
		$this->assertTrue($validator->errors()->has('password'));
	}

	public function test_register_rules_accept_valid_payload(): void
	{
		$request = new RegisterRequest;

		$validator = Validator::make([
			'userid' => 'newuser',
			'name' => '新規',
			'password' => 'password123',
			'password_confirmation' => 'password123',
		], $request->rules());

		$this->assertEmpty($validator->errors()->all());
	}

	public function test_register_rules_reject_duplicate_userid(): void
	{
		User::factory()->create(['userid' => 'taken']);

		$request = new RegisterRequest;

		$validator = Validator::make([
			'userid' => 'taken',
			'name' => '新規',
			'password' => 'password123',
			'password_confirmation' => 'password123',
		], $request->rules());

		$this->assertTrue($validator->errors()->has('userid'));
	}

	public function test_register_rules_reject_unconfirmed_password(): void
	{
		$request = new RegisterRequest;

		$validator = Validator::make([
			'userid' => 'newuser',
			'name' => '新規',
			'password' => 'password123',
			'password_confirmation' => 'mismatch123',
		], $request->rules());

		$this->assertTrue($validator->errors()->has('password'));
	}

	public function test_register_rules_do_not_accept_role_field(): void
	{
		// 新規登録で role を送っても無視される（権限昇格の防止）
		$request = new RegisterRequest;

		$this->assertArrayNotHasKey('role', $request->rules());
	}

	// =================================================================
	// LoginRequest
	// =================================================================

	public function test_login_rules_required_fields(): void
	{
		$request = new LoginRequest;
		$validator = Validator::make([], $request->rules());

		$this->assertTrue($validator->errors()->has('userid'));
		$this->assertTrue($validator->errors()->has('password'));
	}

	public function test_login_rules_accept_any_non_empty_string(): void
	{
		// ログイン時は存在チェックをしない（ユーザー列挙を防ぐ）
		$request = new LoginRequest;

		$validator = Validator::make([
			'userid' => 'notexist',
			'password' => 'anything',
		], $request->rules());

		$this->assertEmpty($validator->errors()->all());
	}
}
