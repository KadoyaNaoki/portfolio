<?php

namespace Tests\Unit\Services;

use App\Enums\UserRole;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\LoginHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginHistoryServiceTest extends TestCase
{
	use RefreshDatabase;

	private LoginHistoryService $service;

	protected function setUp(): void
	{
		parent::setUp();

		$this->service = $this->app->make(LoginHistoryService::class);
	}

	private function history(User $user, bool $success = true, ?string $at = null): LoginHistory
	{
		return LoginHistory::create([
			'user_id' => $user->id,
			'userid' => $user->userid,
			'ip_address' => '127.0.0.1',
			'user_agent' => 'TestAgent',
			'success' => $success,
			'logged_in_at' => $at ? now()->parse($at) : now(),
		]);
	}

	// -----------------------------------------------------------------
	// 一般ユーザー
	// -----------------------------------------------------------------

	public function test_general_user_sees_only_own_history(): void
	{
		$alice = User::factory()->create(['userid' => 'alice']);
		$bob = User::factory()->create(['userid' => 'bob']);

		$this->history($alice);
		$this->history($alice);
		$this->history($bob);

		$result = $this->service->listFor($alice, null);

		$this->assertSame(2, $result->total());
		foreach ($result->items() as $item) {
			$this->assertSame($alice->id, $item->user_id);
		}
	}

	public function test_general_user_sees_zero_when_no_history(): void
	{
		$alice = User::factory()->create();
		User::factory()->create();

		$this->assertSame(0, $this->service->listFor($alice, null)->total());
	}

	public function test_general_user_keyword_is_ignored(): void
	{
		// 一般ユーザーは絞り込み不可。自分の履歴がそのまま返る。
		$alice = User::factory()->create(['userid' => 'alice', 'name' => 'アリス']);
		$bob = User::factory()->create(['userid' => 'bob', 'name' => 'ボブ']);

		$this->history($alice);
		$this->history($bob);

		$result = $this->service->listFor($alice, 'bob');

		// 他人の履歴が keyword 経由で漏れないこと
		$this->assertSame(1, $result->total());
		$this->assertSame($alice->id, $result->items()[0]->user_id);
	}

	// -----------------------------------------------------------------
	// 管理者
	// -----------------------------------------------------------------

	public function test_admin_sees_all_history(): void
	{
		$admin = User::factory()->create(['role' => UserRole::ADMIN]);
		$alice = User::factory()->create();
		$bob = User::factory()->create();

		$this->history($alice);
		$this->history($bob);
		$this->history($admin);

		$this->assertSame(3, $this->service->listFor($admin, null)->total());
	}

	public function test_admin_can_filter_by_userid_keyword(): void
	{
		$admin = User::factory()->create(['role' => UserRole::ADMIN]);
		$alice = User::factory()->create(['userid' => 'alice']);
		$bob = User::factory()->create(['userid' => 'bob']);

		$this->history($alice);
		$this->history($alice);
		$this->history($bob);

		$result = $this->service->listFor($admin, 'alice');

		$this->assertSame(2, $result->total());
	}

	public function test_admin_can_filter_by_user_name_keyword(): void
	{
		$admin = User::factory()->create(['role' => UserRole::ADMIN]);
		$alice = User::factory()->create(['userid' => 'alice', 'name' => 'アリス']);
		$bob = User::factory()->create(['userid' => 'bob', 'name' => 'ボブ']);

		$this->history($alice);
		$this->history($bob);

		$result = $this->service->listFor($admin, 'ボブ');

		$this->assertSame(1, $result->total());
		$this->assertSame('bob', $result->items()[0]->userid);
	}

	public function test_admin_keyword_matching_nothing_returns_empty(): void
	{
		$admin = User::factory()->create(['role' => UserRole::ADMIN]);
		$this->history($admin);

		$this->assertSame(0, $this->service->listFor($admin, 'zzz')->total());
	}

	// -----------------------------------------------------------------
	// 並び順・ページネーション
	// -----------------------------------------------------------------

	public function test_history_is_ordered_newest_first(): void
	{
		$admin = User::factory()->create(['role' => UserRole::ADMIN]);
		$user = User::factory()->create();

		$this->history($user, true, '2026-01-01 10:00:00');
		$this->history($user, true, '2026-03-01 10:00:00');
		$this->history($user, true, '2026-02-01 10:00:00');

		$result = $this->service->listFor($admin, null);

		$dates = collect($result->items())->map(fn ($h) => $h->logged_in_at->format('Y-m-d'))->all();

		$this->assertSame(['2026-03-01', '2026-02-01', '2026-01-01'], $dates);
	}

	public function test_history_paginates_at_fifteen_per_page(): void
	{
		$admin = User::factory()->create(['role' => UserRole::ADMIN]);
		$user = User::factory()->create();

		for ($i = 0; $i < 17; $i++) {
			$this->history($user);
		}

		$result = $this->service->listFor($admin, null);

		$this->assertSame(17, $result->total());
		$this->assertCount(15, $result->items());
		$this->assertSame(2, $result->lastPage());
	}

	public function test_history_eager_loads_user_relation(): void
	{
		$admin = User::factory()->create(['role' => UserRole::ADMIN]);
		$user = User::factory()->create();
		$this->history($user);

		$result = $this->service->listFor($admin, null);

		$this->assertTrue($result->items()[0]->relationLoaded('user'));
	}
}
