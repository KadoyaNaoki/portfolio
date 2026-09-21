<?php

use App\Console\Commands\CreateGeneralUsersCommand;
use App\Console\Commands\PurgeLogsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
	$this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 |--------------------------------------------------------------------------
 | スケジュール
 |--------------------------------------------------------------------------
 |
 | 一般ユーザーを毎日 1 回作成するバッチ。
 | 引数なし = 1 件。件数を変えたい場合は ->runInBackground() の部分で指定する。
 |
 | 実行にはサーバー側で毎分 `php artisan schedule:run` を回す必要がある。
 | 手動確認: php artisan schedule:list
 */
Schedule::command(CreateGeneralUsersCommand::class)
	->dailyAt('02:00')
	->onOneServer()
	->withoutOverlapping();

/*
 | 120 日を経過したログファイルを毎日 1 回削除するバッチ。
 | 日数を変えたい場合は logs:purge の引数で指定する。
 */
Schedule::command(PurgeLogsCommand::class)
	->dailyAt('03:00')
	->onOneServer()
	->withoutOverlapping();
