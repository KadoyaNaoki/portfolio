<?php

namespace App\Services;

use App\Repositories\Contracts\LoginHistoryRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthService
{
	public function __construct(
		private readonly UserRepositoryInterface $users,
		private readonly LoginHistoryRepositoryInterface $histories,
	) {}

	/**
	 * ログインを試行し、成否にかかわらず履歴を記録する.
	 *
	 * @param  array{userid: string, password: string}  $credentials
	 */
	public function attempt(array $credentials, bool $remember = false, ?string $ipAddress = null, ?string $userAgent = null): bool
	{
		if (Auth::attempt($credentials, $remember)) {
			$this->recordHistory($credentials['userid'], true, $ipAddress, $userAgent, Auth::id());

			return true;
		}

		$user = $this->users->findByUserid($credentials['userid']);

		$this->recordHistory($credentials['userid'], false, $ipAddress, $userAgent, $user?->id);

		return false;
	}

	/**
	 * ログアウト（セッション破棄はController側で session に対して行う）.
	 */
	public function logout(): void
	{
		Auth::logout();
	}

	private function recordHistory(string $userid, bool $success, ?string $ipAddress, ?string $userAgent, ?int $userId): void
	{
		try {
			DB::transaction(function () use ($userId, $userid, $ipAddress, $userAgent, $success): void {
				$this->histories->create([
					'user_id' => $userId,
					'userid' => $userid,
					'ip_address' => $ipAddress,
					'user_agent' => $userAgent ? substr($userAgent, 0, 1000) : null,
					'success' => $success,
					'logged_in_at' => now(),
				]);
			});
		} catch (Throwable $e) {
			// 履歴記録の失敗でログイン自体を失敗させないよう、ログに残すだけにする
			Log::error('AuthService::recordHistory failed', [
				'userid' => $userid,
				'success' => $success,
				'error' => $e->getMessage(),
			]);
		}
	}
}
