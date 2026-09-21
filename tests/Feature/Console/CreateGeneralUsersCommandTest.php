<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateGeneralUsersCommandTest extends TestCase
{
	use RefreshDatabase;

	// =================================================================
	// 件数の指定
	// =================================================================

	public function test_creates_one_user_by_default(): void
	{
		$this->artisan('users:create-general')
			->assertExitCode(0);

		$this->assertSame(1, User::count());
	}

	public function test_creates_requested_number_of_users(): void
	{
		$this->artisan('users:create-general', ['count' => 5])
			->assertExitCode(0);

		$this->assertSame(5, User::count());
	}

	public function test_creates_single_user_when_one_is_passed(): void
	{
		$this->artisan('users:create-general', ['count' => 1])
			->assertExitCode(0);

		$this->assertSame(1, User::count());
	}

	public function test_creates_large_batch(): void
	{
		$this->artisan('users:create-general', ['count' => 25])
			->assertExitCode(0);

		$this->assertSame(25, User::count());
	}

	// =================================================================
	// ロール
	// =================================================================

	public function test_created_users_have_user_role(): void
	{
		$this->artisan('users:create-general', ['count' => 3])
			->assertExitCode(0);

		$this->assertSame(3, User::where('role', UserRole::USER->value)->count());
		$this->assertSame(0, User::where('role', UserRole::ADMIN->value)->count());
	}

	public function test_created_users_are_not_admins(): void
	{
		$this->artisan('users:create-general', ['count' => 2])
			->assertExitCode(0);

		foreach (User::all() as $user) {
			$this->assertFalse($user->isAdmin());
		}
	}

	// =================================================================
	// userid の採番
	// =================================================================

	public function test_creates_users_with_sequential_userids(): void
	{
		$this->artisan('users:create-general', ['count' => 3])
			->assertExitCode(0);

		$userids = User::orderBy('id')->pluck('userid')->all();

		$this->assertSame(['user1', 'user2', 'user3'], $userids);
	}

	public function test_userid_continues_from_existing_users(): void
	{
		User::factory()->create(['userid' => 'user1']);
		User::factory()->create(['userid' => 'user2']);

		$this->artisan('users:create-general', ['count' => 2])
			->assertExitCode(0);

		$userids = User::orderBy('id')->pluck('userid')->all();

		$this->assertContains('user3', $userids);
		$this->assertContains('user4', $userids);
	}

	public function test_userids_do_not_collide(): void
	{
		$this->artisan('users:create-general', ['count' => 10])
			->assertExitCode(0);

		$userids = User::pluck('userid')->all();

		$this->assertSame(count($userids), count(array_unique($userids)));
	}

	// =================================================================
	// 引数のバリデーション
	// =================================================================

	public function test_rejects_zero(): void
	{
		$this->artisan('users:create-general', ['count' => 0])
			->assertExitCode(1);

		$this->assertSame(0, User::count());
	}

	public function test_rejects_negative_number(): void
	{
		$this->artisan('users:create-general', ['count' => -3])
			->assertExitCode(1);

		$this->assertSame(0, User::count());
	}

	public function test_rejects_non_numeric_argument(): void
	{
		$this->artisan('users:create-general', ['count' => 'abc'])
			->assertExitCode(1);

		$this->assertSame(0, User::count());
	}

	public function test_rejects_decimal_argument(): void
	{
		$this->artisan('users:create-general', ['count' => '1.5'])
			->assertExitCode(1);

		$this->assertSame(0, User::count());
	}

	public function test_rejects_empty_argument(): void
	{
		$this->artisan('users:create-general', ['count' => ''])
			->assertExitCode(1);

		$this->assertSame(0, User::count());
	}

	// =================================================================
	// パスワード
	// =================================================================

	public function test_created_users_password_is_hashed(): void
	{
		$this->artisan('users:create-general', ['count' => 1])
			->assertExitCode(0);

		$user = User::firstOrFail();

		$this->assertNotSame('password123', $user->password);
		$this->assertTrue(Hash::check('password123', $user->password));
	}

	public function test_all_created_users_can_authenticate(): void
	{
		$this->artisan('users:create-general', ['count' => 3])
			->assertExitCode(0);

		foreach (User::all() as $user) {
			$this->assertTrue(
				Auth::attempt([
					'userid' => $user->userid,
					'password' => 'password123',
				]),
				"userid={$user->userid} でログインできなかった",
			);
			Auth::logout();
		}
	}
}
