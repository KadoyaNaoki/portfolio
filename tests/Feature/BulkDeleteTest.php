<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ChangeHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 一覧からの一括削除（ユーザー / 変更履歴）.
 *
 * 管理者のみ実行可能で、自分自身は削除対象から除外される。
 */
class BulkDeleteTest extends TestCase
{
	use RefreshDatabase;

	private function admin(): User
	{
		return User::factory()->create(['role' => UserRole::ADMIN]);
	}

	private function general(): User
	{
		return User::factory()->create(['role' => UserRole::USER]);
	}

	// =================================================================
	// ユーザー一括削除
	// =================================================================

	public function test_admin_can_delete_selected_users(): void
	{
		$admin = $this->admin();
		$a = User::factory()->create(['userid' => 'victim_a']);
		$b = User::factory()->create(['userid' => 'victim_b']);
		$keep = User::factory()->create(['userid' => 'survivor']);

		$this->actingAs($admin)
			->delete(route('users.destroy'), ['ids' => [$a->id, $b->id]])
			->assertRedirect(route('users.index'));

		$this->assertDatabaseMissing('users', ['id' => $a->id]);
		$this->assertDatabaseMissing('users', ['id' => $b->id]);
		$this->assertDatabaseHas('users', ['id' => $keep->id]);
	}

	public function test_admin_cannot_delete_self(): void
	{
		$admin = $this->admin();
		$other = User::factory()->create();

		// 自分と他人を一緒に選択しても、自分だけは残る
		$this->actingAs($admin)
			->delete(route('users.destroy'), ['ids' => [$admin->id, $other->id]])
			->assertRedirect(route('users.index'));

		$this->assertDatabaseHas('users', ['id' => $admin->id]);
		$this->assertDatabaseMissing('users', ['id' => $other->id]);
	}

	public function test_selecting_only_self_deletes_nothing(): void
	{
		$admin = $this->admin();

		$this->actingAs($admin)
			->delete(route('users.destroy'), ['ids' => [$admin->id]])
			->assertRedirect(route('users.index'));

		$this->assertDatabaseHas('users', ['id' => $admin->id]);
	}

	public function test_general_user_cannot_delete_users(): void
	{
		$general = $this->general();
		$victim = User::factory()->create();

		$this->actingAs($general)
			->delete(route('users.destroy'), ['ids' => [$victim->id]])
			->assertStatus(403);

		$this->assertDatabaseHas('users', ['id' => $victim->id]);
	}

	public function test_guest_cannot_delete_users(): void
	{
		$victim = User::factory()->create();

		$this->delete(route('users.destroy'), ['ids' => [$victim->id]])
			->assertRedirect(route('login'));

		$this->assertDatabaseHas('users', ['id' => $victim->id]);
	}

	public function test_user_delete_requires_at_least_one_id(): void
	{
		$admin = $this->admin();

		$this->actingAs($admin)
			->delete(route('users.destroy'), ['ids' => []])
			->assertSessionHasErrors('ids');
	}

	public function test_user_delete_rejects_nonexistent_id(): void
	{
		$admin = $this->admin();

		$this->actingAs($admin)
			->delete(route('users.destroy'), ['ids' => [99999]])
			->assertSessionHasErrors('ids.0');
	}

	// =================================================================
	// 変更履歴の一括削除
	// =================================================================

	private function history(User $author, string $body): ChangeHistory
	{
		return ChangeHistory::create([
			'user_id' => $author->id,
			'userid' => $author->userid,
			'body' => $body,
			'registered_at' => now(),
		]);
	}

	public function test_admin_can_delete_selected_histories(): void
	{
		$admin = $this->admin();
		$h1 = $this->history($admin, '削除対象1');
		$h2 = $this->history($admin, '削除対象2');
		$keep = $this->history($admin, '残す履歴');

		$this->actingAs($admin)
			->delete(route('change-histories.destroy'), ['ids' => [$h1->id, $h2->id]])
			->assertRedirect(route('change-histories.index'));

		$this->assertDatabaseMissing('change_histories', ['id' => $h1->id]);
		$this->assertDatabaseMissing('change_histories', ['id' => $h2->id]);
		$this->assertDatabaseHas('change_histories', ['id' => $keep->id]);
	}

	public function test_general_user_cannot_delete_histories(): void
	{
		$general = $this->general();
		$history = $this->history($general, '消せない履歴');

		$this->actingAs($general)
			->delete(route('change-histories.destroy'), ['ids' => [$history->id]])
			->assertStatus(403);

		$this->assertDatabaseHas('change_histories', ['id' => $history->id]);
	}

	public function test_guest_cannot_delete_histories(): void
	{
		$admin = $this->admin();
		$history = $this->history($admin, '履歴');

		$this->delete(route('change-histories.destroy'), ['ids' => [$history->id]])
			->assertRedirect(route('login'));

		$this->assertDatabaseHas('change_histories', ['id' => $history->id]);
	}

	public function test_history_delete_requires_at_least_one_id(): void
	{
		$admin = $this->admin();

		$this->actingAs($admin)
			->delete(route('change-histories.destroy'), ['ids' => []])
			->assertSessionHasErrors('ids');
	}

	public function test_history_delete_rejects_nonexistent_id(): void
	{
		$admin = $this->admin();

		$this->actingAs($admin)
			->delete(route('change-histories.destroy'), ['ids' => [99999]])
			->assertSessionHasErrors('ids.0');
	}

	// =================================================================
	// 画面表示（チェックボックスの有無）
	// =================================================================

	public function test_admin_sees_checkboxes_on_users_list(): void
	{
		$admin = $this->admin();

		$response = $this->actingAs($admin)->get(route('users.index'));

		$response->assertStatus(200)
			->assertSee('users-delete-form', false)
			->assertSee('name="ids[]"', false)
			->assertSee('選択したユーザーを削除');
	}

	public function test_general_user_does_not_see_delete_controls(): void
	{
		$general = $this->general();

		$response = $this->actingAs($general)->get(route('users.index'));

		$response->assertStatus(200)
			->assertDontSee('選択したユーザーを削除')
			->assertDontSee('name="ids[]"', false);
	}

	public function test_admin_sees_checkboxes_on_histories_list(): void
	{
		$admin = $this->admin();
		$this->history($admin, '履歴');

		$response = $this->actingAs($admin)->get(route('change-histories.index'));

		$response->assertStatus(200)
			->assertSee('histories-delete-form', false)
			->assertSee('選択した履歴を削除');
	}

	public function test_general_user_does_not_see_history_delete_controls(): void
	{
		$admin = $this->admin();
		$general = $this->general();
		$this->history($admin, '履歴');

		$response = $this->actingAs($general)->get(route('change-histories.index'));

		$response->assertStatus(200)
			->assertDontSee('選択した履歴を削除')
			->assertDontSee('name="ids[]"', false);
	}

	// =================================================================
	// サイドメニュー
	// =================================================================

	public function test_admin_sees_user_management_label(): void
	{
		$admin = $this->admin();

		$this->actingAs($admin)->get(route('users.index'))
			->assertSee('ユーザー管理');
	}

	public function test_general_user_sees_user_list_label(): void
	{
		$general = $this->general();

		$this->actingAs($general)->get(route('users.index'))
			->assertSee('ユーザー一覧');
	}

	public function test_sidebar_has_accordion_toggle(): void
	{
		$admin = $this->admin();

		$this->actingAs($admin)->get(route('users.index'))
			->assertSee('sidebar-toggle-plus', false)
			->assertSee('sidebar-submenu', false);
	}

	public function test_sidebar_order_is_users_then_login_then_change(): void
	{
		$admin = $this->admin();

		$html = $this->actingAs($admin)->get(route('users.index'))->getContent();

		$usersPos = strpos($html, 'sidebar-users-sub');
		$loginPos = strpos($html, 'ログイン履歴');
		$changePos = strpos($html, '変更履歴');

		$this->assertLessThan($loginPos, $usersPos, 'ユーザー一覧がログイン履歴より先にある');
		$this->assertLessThan($changePos, $loginPos, 'ログイン履歴が変更履歴より先にある');
	}
}
