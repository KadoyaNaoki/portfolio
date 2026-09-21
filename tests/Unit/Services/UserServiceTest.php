<?php

namespace Tests\Unit\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
	use RefreshDatabase;

	private UserService $service;

	protected function setUp(): void
	{
		parent::setUp();

		// 実際の Eloquent 実装をそのまま使う（Repository はモックしない）
		$this->service = $this->app->make(UserService::class);
	}

	/**
	 * GD 拡張が無い環境では画像生成テストをスキップする.
	 */
	private function requireGd(): void
	{
		if (! function_exists('imagecreatetruecolor')) {
			$this->markTestSkipped('GD 拡張が無効なため、画像アップロードのテストをスキップします。');
		}
	}

	// -----------------------------------------------------------------
	// register
	// -----------------------------------------------------------------

	public function test_register_creates_user_with_user_role(): void
	{
		$user = $this->service->register('newuser', '新規太郎', 'password123');

		$this->assertInstanceOf(User::class, $user);
		$this->assertDatabaseHas('users', [
			'userid' => 'newuser',
			'name' => '新規太郎',
			'role' => UserRole::USER->value,
		]);
	}

	public function test_register_hashes_the_password(): void
	{
		$user = $this->service->register('hashuser', 'ハッシュ', 'password123');

		// 平文では保存されていない
		$this->assertNotSame('password123', $user->fresh()->password);
		$this->assertTrue(Hash::check('password123', $user->fresh()->password));
	}

	public function test_register_does_not_allow_privilege_escalation(): void
	{
		// role を渡す口が無いことを確認（常に一般ユーザー）
		$user = $this->service->register('normaluser', '一般', 'password123');

		$this->assertSame(UserRole::USER, $user->fresh()->role);
		$this->assertFalse($user->fresh()->isAdmin());
	}

	// -----------------------------------------------------------------
	// search
	// -----------------------------------------------------------------

	public function test_search_returns_paginator(): void
	{
		User::factory()->create();

		$result = $this->service->search(null, 'created_at', 'desc');

		$this->assertInstanceOf(LengthAwarePaginator::class, $result);
	}

	public function test_search_filters_by_keyword_on_userid(): void
	{
		User::factory()->create(['userid' => 'alice', 'name' => 'アリス']);
		User::factory()->create(['userid' => 'bob', 'name' => 'ボブ']);

		$result = $this->service->search('alice', 'created_at', 'desc');

		$this->assertSame(1, $result->total());
		$this->assertSame('alice', $result->items()[0]->userid);
	}

	public function test_search_filters_by_keyword_on_name(): void
	{
		User::factory()->create(['userid' => 'alice', 'name' => 'アリス']);
		User::factory()->create(['userid' => 'bob', 'name' => 'ボブ']);

		$result = $this->service->search('ボブ', 'created_at', 'desc');

		$this->assertSame(1, $result->total());
		$this->assertSame('bob', $result->items()[0]->userid);
	}

	public function test_search_returns_all_when_keyword_is_empty(): void
	{
		User::factory()->count(3)->create();

		$this->assertSame(3, $this->service->search(null, 'created_at', 'desc')->total());
	}

	public function test_search_sorts_ascending_by_name(): void
	{
		User::factory()->create(['name' => 'b']);
		User::factory()->create(['name' => 'a']);
		User::factory()->create(['name' => 'c']);

		$names = collect($this->service->search(null, 'name', 'asc')->items())->pluck('name')->all();

		$this->assertSame(['a', 'b', 'c'], $names);
	}

	public function test_search_sorts_descending_by_name(): void
	{
		User::factory()->create(['name' => 'b']);
		User::factory()->create(['name' => 'a']);
		User::factory()->create(['name' => 'c']);

		$names = collect($this->service->search(null, 'name', 'desc')->items())->pluck('name')->all();

		$this->assertSame(['c', 'b', 'a'], $names);
	}

	public function test_search_falls_back_on_invalid_sort_column(): void
	{
		// SQL インジェクションを狙った列名でも安全に既定値へ落ちる
		User::factory()->count(2)->create();

		$result = $this->service->search(null, 'password; DROP TABLE users', 'desc');

		$this->assertSame(2, $result->total());
	}

	public function test_search_falls_back_on_invalid_direction(): void
	{
		User::factory()->count(2)->create();

		$result = $this->service->search(null, 'name', 'sideways');

		$this->assertSame(2, $result->total());
	}

	public function test_search_paginates_at_ten_per_page(): void
	{
		User::factory()->count(12)->create();

		$result = $this->service->search(null, 'created_at', 'desc');

		$this->assertSame(12, $result->total());
		$this->assertCount(10, $result->items());
		$this->assertSame(2, $result->lastPage());
	}

	// -----------------------------------------------------------------
	// updateProfile
	// -----------------------------------------------------------------

	public function test_update_profile_changes_userid_and_name(): void
	{
		$user = User::factory()->create(['userid' => 'before', 'name' => '旧名']);

		$updated = $this->service->updateProfile($user, [
			'userid' => 'after',
			'name' => '新名',
		]);

		$this->assertSame('after', $updated->fresh()->userid);
		$this->assertSame('新名', $updated->fresh()->name);
	}

	public function test_update_profile_keeps_password_when_not_provided(): void
	{
		$user = User::factory()->create();
		$original = $user->password;

		$this->service->updateProfile($user, [
			'userid' => $user->userid,
			'name' => '変更後',
		]);

		$this->assertSame($original, $user->fresh()->password);
	}

	public function test_update_profile_keeps_password_when_empty_string(): void
	{
		$user = User::factory()->create();
		$original = $user->password;

		$this->service->updateProfile($user, [
			'userid' => $user->userid,
			'name' => '変更後',
			'password' => '',
		]);

		$this->assertSame($original, $user->fresh()->password);
	}

	public function test_update_profile_updates_password_when_provided(): void
	{
		$user = User::factory()->create();

		$this->service->updateProfile($user, [
			'userid' => $user->userid,
			'name' => '変更後',
			'password' => 'brandnewpass',
		]);

		$this->assertTrue(Hash::check('brandnewpass', $user->fresh()->password));
	}

	public function test_update_profile_does_not_change_role_when_not_provided(): void
	{
		$user = User::factory()->create(['role' => UserRole::USER]);

		$this->service->updateProfile($user, [
			'userid' => $user->userid,
			'name' => '変更後',
		]);

		$this->assertSame(UserRole::USER, $user->fresh()->role);
	}

	public function test_update_profile_changes_role_when_provided(): void
	{
		$user = User::factory()->create(['role' => UserRole::USER]);

		$this->service->updateProfile($user, [
			'userid' => $user->userid,
			'name' => '変更後',
			'role' => UserRole::ADMIN->value,
		]);

		$this->assertSame(UserRole::ADMIN, $user->fresh()->role);
		$this->assertTrue($user->fresh()->isAdmin());
	}

	// -----------------------------------------------------------------
	// updateProfile / avatar
	// -----------------------------------------------------------------

	public function test_update_profile_stores_avatar(): void
	{
		$this->requireGd();
		Storage::fake('public');
		$user = User::factory()->create(['avatar_path' => null]);

		$file = UploadedFile::fake()->image('avatar.jpg');

		$updated = $this->service->updateProfile($user, [
			'userid' => $user->userid,
			'name' => '変更後',
		], $file);

		$this->assertNotNull($updated->fresh()->avatar_path);
		$this->assertStringStartsWith('avatars/', $updated->fresh()->avatar_path);
		Storage::disk('public')->assertExists($updated->fresh()->avatar_path);
	}

	public function test_update_profile_replaces_old_avatar_file(): void
	{
		$this->requireGd();
		Storage::fake('public');
		$user = User::factory()->create();

		// 1回目
		$this->service->updateProfile($user, [
			'userid' => $user->userid,
			'name' => '名前',
		], UploadedFile::fake()->image('first.jpg'));

		$firstPath = $user->fresh()->avatar_path;
		Storage::disk('public')->assertExists($firstPath);

		// 2回目（差し替え）
		$this->service->updateProfile($user, [
			'userid' => $user->userid,
			'name' => '名前',
		], UploadedFile::fake()->image('second.jpg'));

		$secondPath = $user->fresh()->avatar_path;

		$this->assertNotSame($firstPath, $secondPath);
		// 古いファイルは削除されている
		Storage::disk('public')->assertMissing($firstPath);
		Storage::disk('public')->assertExists($secondPath);
	}

	public function test_update_profile_keeps_avatar_when_no_file_given(): void
	{
		$this->requireGd();
		Storage::fake('public');
		$user = User::factory()->create();

		$this->service->updateProfile($user, [
			'userid' => $user->userid,
			'name' => '名前',
		], UploadedFile::fake()->image('keep.jpg'));

		$path = $user->fresh()->avatar_path;

		// 画像なしで更新
		$this->service->updateProfile($user, [
			'userid' => $user->userid,
			'name' => '名前2',
		]);

		$this->assertSame($path, $user->fresh()->avatar_path);
		Storage::disk('public')->assertExists($path);
	}

	// -----------------------------------------------------------------
	// Repository との協調（モックで委譲を確認）
	// -----------------------------------------------------------------

	public function test_search_delegates_to_repository(): void
	{
		$expected = $this->createMock(LengthAwarePaginator::class);

		$repo = Mockery::mock(UserRepositoryInterface::class);
		$repo->shouldReceive('search')
			->once()
			->with('kw', 'name', 'asc')
			->andReturn($expected);

		$service = new UserService($repo);

		$this->assertSame($expected, $service->search('kw', 'name', 'asc'));
	}

	public function test_register_delegates_to_repository_with_user_role(): void
	{
		$created = new User(['userid' => 'x', 'name' => 'y']);

		$repo = Mockery::mock(UserRepositoryInterface::class);
		$repo->shouldReceive('create')
			->once()
			->with(Mockery::on(function (array $attributes) {
				return $attributes['userid'] === 'x'
				&& $attributes['role'] === UserRole::USER;
			}))
			->andReturn($created);

		$service = new UserService($repo);

		$this->assertSame($created, $service->register('x', 'y', 'password123'));
	}

	protected function tearDown(): void
	{
		Mockery::close();
		parent::tearDown();
	}
}
