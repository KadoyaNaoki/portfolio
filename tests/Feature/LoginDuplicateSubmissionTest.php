<?php

namespace Tests\Feature;

use App\Http\Middleware\PreventDuplicateSubmission;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ログインの連打（二重送信）対策.
 *
 * ログインフォームには @formToken が埋め込まれており、同じトークンでの
 * 2 回目以降の送信は PreventDuplicateSubmission が 1 回目の結果を返す。
 * これにより、同一トークンの連打で認証処理（履歴記録）が多重実行されない。
 */
class LoginDuplicateSubmissionTest extends TestCase
{
	use RefreshDatabase;

	/**
	 * フォームトークン付きのログイン payload を作る.
	 *
	 * @return array<string, string>
	 */
	private function payload(string $token, string $password = 'password123'): array
	{
		return [
			'userid' => 'loginuser',
			'password' => $password,
			PreventDuplicateSubmission::TOKEN_FIELD => $token,
		];
	}

	public function test_login_form_embeds_form_token(): void
	{
		$response = $this->get(route('login'));

		$response->assertStatus(200)
			->assertSee(PreventDuplicateSubmission::TOKEN_FIELD, false);
	}

	public function test_same_token_does_not_run_login_twice(): void
	{
		User::factory()->create([
			'userid' => 'loginuser',
			'password' => 'password123',
		]);

		$token = 'login-token-'.str_repeat('a', 30);

		// 1 回目（失敗）: 認証処理が 1 回走る
		$this->post(route('login.attempt'), $this->payload($token, 'wrongpass'));
		$this->assertSame(1, LoginHistory::count());

		// 2 回目: 同一トークンはキャッシュされ、認証処理は再実行されない
		$this->post(route('login.attempt'), $this->payload($token, 'wrongpass'));

		// 履歴が増えていなければ、多重実行されていない
		$this->assertSame(1, LoginHistory::count());
	}

	public function test_different_tokens_run_independently(): void
	{
		User::factory()->create([
			'userid' => 'loginuser',
			'password' => 'password123',
		]);

		$this->post(route('login.attempt'), $this->payload('token-one-'.str_repeat('a', 30), 'wrongpass'));
		$this->post(route('login.attempt'), $this->payload('token-two-'.str_repeat('b', 30), 'wrongpass'));

		// 別トークンならそれぞれ認証処理が走る
		$this->assertSame(2, LoginHistory::count());
	}

	public function test_successful_login_redirects_to_users_index(): void
	{
		User::factory()->create([
			'userid' => 'loginuser',
			'password' => 'password123',
		]);

		$this->post(route('login.attempt'), $this->payload('success-token-'.str_repeat('c', 30)))
			->assertRedirect(route('users.index'));

		$this->assertTrue(auth()->check());
	}

	public function test_request_without_token_is_processed_normally(): void
	{
		User::factory()->create([
			'userid' => 'loginuser',
			'password' => 'password123',
		]);

		// トークン無し（API などと同様）はミドルウェアの対象外
		$this->post(route('login.attempt'), [
			'userid' => 'loginuser',
			'password' => 'password123',
		])->assertRedirect(route('users.index'));

		$this->assertTrue(auth()->check());
	}

	public function test_repeated_submission_returns_same_redirect_target(): void
	{
		User::factory()->create([
			'userid' => 'loginuser',
			'password' => 'password123',
		]);

		$token = 'repeat-token-'.str_repeat('d', 30);

		$first = $this->post(route('login.attempt'), $this->payload($token, 'wrongpass'));
		$second = $this->post(route('login.attempt'), $this->payload($token, 'wrongpass'));

		// 2 回目はキャッシュされた結果（同じリダイレクト先）を返す
		$this->assertSame(
			$first->headers->get('Location'),
			$second->headers->get('Location'),
		);
	}
}
