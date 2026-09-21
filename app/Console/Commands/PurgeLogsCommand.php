<?php

namespace App\Console\Commands;

use App\Support\AccessLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * =====================================================================
 * ログ削除バッチ
 * =====================================================================
 *
 * ■ 概要
 *   storage/logs 配下のログファイルのうち、
 *   指定した日数を経過したものを削除する。
 *
 *   対象は「ファイル名に日付（YYYY-MM-DD）が含まれるもの」のみ。
 *   日付が判定できないファイルは、誤削除を避けるため対象外とする。
 *
 *   例: 120 日を経過したログを削除
 *     ・laravel-2026-05-01.log … 2026-05-01 から 120 日を過ぎていれば削除
 *     ・laravel.log           … 日付なしなので対象外
 *
 * ■ 実行方法
 *   php artisan logs:purge              … 既定 120 日を経過したログを削除
 *   php artisan logs:purge 30           … 30 日を経過したログを削除
 *   php artisan logs:purge 30 --dry-run … 対象を表示するだけ（削除しない）
 *
 *   引数は「経過日数」。1 以上の整数のみ受け付ける。
 *   未指定の場合は 120 日。
 *
 * ■ 自動実行（毎日1回）
 *   routes/console.php で毎日 03:00 にスケジュール登録している。
 *   サーバー側で 1 分ごとにスケジューラを回す必要がある。
 *
 *     php artisan schedule:run    … 1回だけ手動実行
 *     php artisan schedule:list   … 登録内容の確認
 *
 * ■ 終了コード
 *   0 正常終了 / 1 引数エラー
 */
class PurgeLogsCommand extends Command
{
	/**
	 * 実行コマンド名.
	 *
	 * @var string
	 */
	protected $signature = 'logs:purge
	{days=120 : 削除する経過日数（1以上の整数）}
	{--dry-run : 削除せずに対象ファイルを表示するだけ}';

	/**
	 * コマンドの説明.
	 *
	 * @var string
	 */
	protected $description = '指定日数を経過したログファイルを削除する（既定120日・毎日1回自動実行）';

	/**
	 * ログファイル名から日付を取り出す正規表現.
	 *
	 * laravel-2026-05-01.log、access-2026-05-01.log のような形式を想定。
	 */
	private const DATE_PATTERN = '/(\d{4}-\d{2}-\d{2})/';

	public function handle(): int
	{
		$raw = (string) $this->argument('days');
		$dryRun = (bool) $this->option('dry-run');

		// バッチも 1 実行 = 1 行でアクセスログに記録する
		AccessLog::log('artisan '.$this->getName());

		// 1 以上の整数だけを受け付ける
		if (preg_match('/^\d+$/', $raw) !== 1) {
			$this->error("経過日数は 1 以上の整数で指定してください（指定値: {$raw}）");

			return self::FAILURE;
		}

		$days = (int) $raw;

		if ($days < 1) {
			$this->error("経過日数は 1 以上を指定してください（指定値: {$days}）");

			return self::FAILURE;
		}

		// この日時より前に作られたファイルを削除対象とする
		$threshold = Carbon::now()->subDays($days)->startOfDay();

		$this->info("{$days} 日を経過したログを削除します（基準日: {$threshold->format('Y/m/d')} より前）");

		if ($dryRun) {
			$this->warn('--dry-run のため削除は行いません。');
		}

		$directory = storage_path('logs');

		if (! is_dir($directory)) {
			$this->error("ログディレクトリが見つかりません: {$directory}");

			return self::FAILURE;
		}

		$deleted = [];
		$skipped = [];
		$targets = [];

		foreach (glob($directory.'/*.log') ?: [] as $file) {
			$name = basename($file);

			// ファイル名から日付を取り出す
			if (preg_match(self::DATE_PATTERN, $name, $matches) !== 1) {
				// 日付が判定できないファイルは誤削除を避けて対象外にする
				$skipped[] = $name;

				continue;
			}

			$fileDate = Carbon::createFromFormat('Y-m-d', $matches[1])->startOfDay();

			// まだ経過していない
			if ($fileDate->greaterThanOrEqualTo($threshold)) {
				continue;
			}

			$targets[$name] = $file;
		}

		if ($targets === []) {
			$this->info('削除対象のログはありませんでした。');

			Log::channel('daily')->info('PurgeLogsCommand: no target', [
				'days' => $days,
				'threshold' => $threshold->toDateString(),
			]);

			$this->reportSkipped($skipped);

			return self::SUCCESS;
		}

		foreach ($targets as $name => $path) {
			if ($dryRun) {
				$deleted[] = $name;
				$this->line("  [dry-run] 削除対象: {$name}");

				continue;
			}

			if (@unlink($path)) {
				$deleted[] = $name;
				$this->line("  削除: {$name}");
			} else {
				$this->error("  削除失敗: {$name}");
			}
		}

		$summary = $dryRun
				? count($deleted).' 件が削除対象です（dry-run のため未削除）'
				: count($deleted).' 件のログを削除しました。';

		$this->info($summary);

		Log::channel('daily')->info('PurgeLogsCommand: finished', [
			'days' => $days,
			'threshold' => $threshold->toDateString(),
			'dry_run' => $dryRun,
			'deleted_count' => count($deleted),
			'deleted' => $deleted,
		]);

		$this->reportSkipped($skipped);

		return self::SUCCESS;
	}

	/**
	 * 日付が判定できず対象外にしたファイルを表示する.
	 *
	 * @param  array<int, string>  $skipped
	 */
	private function reportSkipped(array $skipped): void
	{
		if ($skipped === []) {
			return;
		}

		$this->comment('日付が判定できないため対象外にしました: '.implode(', ', $skipped));
	}
}
