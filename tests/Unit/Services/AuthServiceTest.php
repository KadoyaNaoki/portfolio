<?php

namespace Tests\Unit\Services;

use App\Enums\UserRole;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
	use RefreshDatabase;

	private AuthService $service;

	protected function setUp(): void
	{
		parent::setUp();

		$this->service = $this->app->make(AuthService::class);
	}

	private function makeUser(string $userid = 'loginuser', string $password = 'password123', UserRole $role = UserRole::USER): User
	{
		return User::factory()->create([
			'userid' => $userid,
			'password' => $password,
			'role' => $role,
		]);
	}

	// -----------------------------------------------------------------
	// attempt - 成功
	// -----------------------------------------------------------------

	public function test_attempt_returns_true_and_logs_in_on_valid_credentials(): void
	{
		$user = $this->makeUser();

		$result = $this->service->attempt(
			['userid' => 'loginuser', 'password' => 'password123'],
			false,
			'127.0.0.1',
			'TestAgent',
		);

		$this->assertTrue($result);
		$this->assertTrue(Auth::check());
		$this->assertSame($user->id, Auth::id());
	}

	public function test_attempt_records_successful_history(): void
	{
		$user = $this->makeUser();

		$this->service->attempt(
			['userid' => 'loginuser', 'password' => 'password123'],
			false,
			'10.0.0.1',
			'TestAgent',
		);

		$this->assertDatabaseHas('login_histories', [
			'user_id' => $user->id,
			'userid' => 'loginuser',
			'ip_address' => '10.0.0.1',
			'success' => true,
		]);

		$this->assertSame(1, LoginHistory::count());
	}

	public function test_attempt_stores_logged_in_at(): void
	{
		$this->makeUser();

		$this->service->attempt(['userid' => 'loginuser', 'password' => 'password123']);

		$this->assertNotNull(LoginHistory::first()->logged_in_at);
	}

	// -----------------------------------------------------------------
	// attempt - 失敗
	// -----------------------------------------------------------------

	public function test_attempt_returns_false_on_wrong_password(): void
	{
		$this->makeUser();

		$result = $this->service->attempt(
			['userid' => 'loginuser', 'password' => 'wrongpass'],
			false,
			'127.0.0.1',
			'TestAgent',
		);

		$this->assertFalse($result);
		$this->assertFalse(Auth::check());
	}

	public function test_attempt_records_failed_history_with_user_id_when_user_exists(): void
	{
		$user = $this->makeUser();

		$this->service->attempt(['userid' => 'loginuser', 'password' => 'wrongpass']);

		$this->assertDatabaseHas('login_histories', [
			'user_id' => $user->id,
			'userid' => 'loginuser',
			'success' => false,
		]);
	}

	public function test_attempt_records_failed_history_with_null_user_id_when_user_unknown(): void
	{
		$this->service->attempt(['userid' => 'ghost', 'password' => 'whatever']);

		$this->assertDatabaseHas('login_histories', [
			'user_id' => null,
			'userid' => 'ghost',
			'success' => false,
		]);
	}

	public function test_attempt_records_history_on_every_failed_attempt(): void
	{
		$this->makeUser();

		$this->service->attempt(['userid' => 'loginuser', 'password' => 'x1']);
		$this->service->attempt(['userid' => 'loginuser', 'password' => 'x2']);
		$this->service->attempt(['userid' => 'loginuser', 'password' => 'x3']);

		$this->assertSame(3, LoginHistory::where('success', false)->count());
	}

	// -----------------------------------------------------------------
	// ユーザーエージェントの切り詰め
	// -----------------------------------------------------------------

	public function test_attempt_truncates_long_user_agent_to_1000_chars(): void
	{
		$this->makeUser();

		$this->service->attempt(
			['userid' => 'loginuser', 'password' => 'password123'],
			false,
			'127.0.0.1',
			str_repeat('A', 5000),
		);

		$this->assertSame(1000, mb_strlen(LoginHistory::first()->user_agent));
	}

	public function test_attempt_stores_null_user_agent_when_not_given(): void
	{
		$this->makeUser();

		$this->service->attempt(['userid' => 'loginuser', 'password' => 'password123']);

		$this->assertNull(LoginHistory::first()->user_agent);
	}

	// -----------------------------------------------------------------
	// logout
	// -----------------------------------------------------------------

	public function test_logout_clears_authentication(): void
	{
		$user = $this->makeUser();
		Auth::login($user);
		$this->assertTrue(Auth::check());

		$this->service->logout();

		$this->assertFalse(Auth::check());
	}

	public function test_logout_does_not_delete_login_history(): void
	{
		$user = $this->makeUser();
		Auth::login($user);
		$this->service->attempt(['userid' => 'loginuser', 'password' => 'password123']);

		$this->service->logout();

		// 履歴は監査目的で残る
		$this->assertSame(1, LoginHistory::count());
	}
}
