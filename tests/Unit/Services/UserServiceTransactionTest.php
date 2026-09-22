<?php

namespace Tests\Unit\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * DB 更新処理がトランザクションで囲まれていることの検証.
 *
 * リポジトリが例外を投げた場合、そのトランザクションがロールバックされ、
 * 例外は呼び出し側へ再送出されることを確認する。
 */
class UserServiceTransactionTest extends TestCase
{
	use RefreshDatabase;

	private function serviceWith(UserRepositoryInterface $repository): UserService
	{
		return new UserService($repository);
	}

	public function test_register_wraps_create_in_transaction_and_propagates(): void
	{
		$repo = Mockery::mock(UserRepositoryInterface::class);
		$repo->shouldReceive('create')
			->once()
			->andThrow(new RuntimeException('DB failure'));

		$service = $this->serviceWith($repo);

		$this->expectException(RuntimeException::class);

		try {
			$service->register('newuser', '新規', 'password123');
		} finally {
			// 例外が再送出されても DB には何も残っていない
			$this->assertDatabaseCount('users', 0);
			Mockery::close();
		}
	}

	public function test_create_with_role_rolls_back_on_failure(): void
	{
		$repo = Mockery::mock(UserRepositoryInterface::class);
		$repo->shouldReceive('create')
			->once()
			->andThrow(new RuntimeException('DB failure'));

		$service = $this->serviceWith($repo);

		$this->expectException(RuntimeException::class);

		try {
			$service->createWithRole('admin1', '管理者', 'password123', UserRole::ADMIN);
		} finally {
			$this->assertDatabaseCount('users', 0);
			Mockery::close();
		}
	}

	public function test_update_profile_rolls_back_on_failure(): void
	{
		$user = User::factory()->create(['userid' => 'target', 'name' => '旧名']);

		$repo = Mockery::mock(UserRepositoryInterface::class);
		$repo->shouldReceive('update')
			->once()
			->andThrow(new RuntimeException('DB failure'));

		$service = $this->serviceWith($repo);

		$this->expectException(RuntimeException::class);

		try {
			$service->updateProfile($user, ['userid' => 'target', 'name' => '新名']);
		} finally {
			// 名前は更新されていない（ロールバック）
			$this->assertSame('旧名', $user->fresh()->name);
			Mockery::close();
		}
	}

	public function test_delete_by_ids_rolls_back_on_failure(): void
	{
		$admin = User::factory()->create(['role' => UserRole::ADMIN]);
		$victim = User::factory()->create();

		$repo = Mockery::mock(UserRepositoryInterface::class);
		$repo->shouldReceive('findByIds')->andReturn(collect());
		$repo->shouldReceive('deleteByIds')
			->once()
			->andThrow(new RuntimeException('DB failure'));

		$service = $this->serviceWith($repo);

		$this->expectException(RuntimeException::class);

		try {
			$service->deleteByIds([$victim->id], $admin->id);
		} finally {
			// 削除されていない（ロールバック）
			$this->assertDatabaseHas('users', ['id' => $victim->id]);
			Mockery::close();
		}
	}

	public function test_successful_register_commits(): void
	{
		$service = $this->app->make(UserService::class);

		$user = $service->register('commituser', 'コミット', 'password123');

		$this->assertDatabaseHas('users', ['id' => $user->id, 'userid' => 'commituser']);
	}
}
