<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Services\UserService;
use App\Support\AccessLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * =====================================================================
 * 一般ユーザー作成バッチ
 * =====================================================================
 *
 * ■ 概要
 *   一般ロール（UserRole::USER）のユーザーを指定件数だけ作成する。
 *
 * ■ 実行方法
 *   php artisan users:create-general		  … 1件作成（既定）
 *   php artisan users:create-general 5		… 5件作成
 *   php artisan users:create-general 10	   … 10件作成
 *
 *   引数は「作成する件数」。1 以上の整数のみ受け付ける。
 *   未指定の場合は 1 件作成する。
 *
 * ■ 自動実行（毎日1回）
 *   routes/console.php で毎日 02:00 にスケジュール登録している。
 *   サーバー側で 1 分ごとにスケジューラを回す必要がある。
 *
 *	 php artisan schedule:run		  … 1回だけ手動実行
 *	 php artisan schedule:list		 … 登録内容の確認
 *
 *   Windows のタスクスケジューラに以下を 1 分間隔で登録する例:
 *	 C:\xampp\php\php.exe C:\xampp\htdocs\portfolio\artisan schedule:run
 *
 * ■ userid の採番
 *   "user" + 連番（user1, user2, ...）で未使用の番号を自動採番する。
 *   既定パスワードは config で変更可能（下記 DEFAULT_PASSWORD）。
 *
 * ■ 終了コード
 *   0 正常終了 / 1 引数エラー
 */
class CreateGeneralUsersCommand extends Command
{
	/**
	 * 実行コマンド名.
	 *
	 * @var string
	 */
	protected $signature = 'users:create-general
	{count=1 : 作成する件数（1以上の整数）}';

	/**
	 * コマンドの説明.
	 *
	 * @var string
	 */
	protected $description = '一般ユーザーを指定件数作成する（既定1件・毎日1回自動実行）';

	/**
	 * 自動採番用の userid 接頭辞.
	 */
	private const USERID_PREFIX = 'user';

	/**
	 * 作成するユーザーの初期パスワード.
	 */
	private const DEFAULT_PASSWORD = 'password123';

	/**
	 * 表示名の接頭辞.
	 */
	private const NAME_PREFIX = '自動生成ユーザー';

	public function __construct(
		private readonly UserService $userService,
	) {
		parent::__construct();
	}

	public function handle(): int
	{
		$raw = (string) $this->argument('count');

		// アクセスログ（バッチも 1 実行 = 1 行で記録する）
		AccessLog::log('artisan '.$this->getName());

		// 1 以上の整数だけを受け付ける
		if (preg_match('/^\d+$/', $raw) !== 1) {
			$this->error("件数は 1 以上の整数で指定してください（指定値: {$raw}）");

			return self::FAILURE;
		}

		$count = (int) $raw;

		if ($count < 1) {
			$this->error("件数は 1 以上を指定してください（指定値: {$count}）");

			return self::FAILURE;
		}

		$this->info("一般ユーザーを {$count} 件作成します...");

		$created = [];

		for ($i = 0; $i < $count; $i++) {
			$userid = $this->userService->generateUniqueUserid(self::USERID_PREFIX);

			$user = $this->userService->createWithRole(
				$userid,
				self::NAME_PREFIX.$userid,
				self::DEFAULT_PASSWORD,
				UserRole::USER,
			);

			$created[] = $user->userid;
			$this->line("  作成: {$user->userid}（{$user->name}）");
		}

		Log::info('CreateGeneralUsersCommand: created', [
			'count' => count($created),
			'userids' => $created,
		]);

		$this->info(count($created).' 件の一般ユーザーを作成しました。');

		return self::SUCCESS;
	}
}
