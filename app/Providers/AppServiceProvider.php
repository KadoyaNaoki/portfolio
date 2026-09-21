<?php

namespace App\Providers;

use App\Http\Middleware\PreventDuplicateSubmission;
use App\Logging\QueryLogger;
use App\Models\ChangeHistory;
use App\Models\User;
use App\Policies\ChangeHistoryPolicy;
use App\Policies\UserPolicy;
use App\Support\SortLink;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 */
	public function register(): void
	{
		//
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot(): void
	{
		// ページャーの見た目を自前のビューに差し替える。
		// 既定は Tailwind 前提（pagination::tailwind）で、
		// このプロジェクトは Tailwind を読み込んでいないため
		// SVG が巨大化し、翻訳キーがそのまま表示されてしまう。
		Paginator::defaultView('vendor.pagination.custom');
		Paginator::defaultSimpleView('vendor.pagination.custom');

		// Policy の自動発見に依存せず明示的に登録（有効/無効を確実にする）
		Gate::policy(User::class, UserPolicy::class);
		Gate::policy(ChangeHistory::class, ChangeHistoryPolicy::class);

		// 実行された SQL をログに記録する（アプリログの daily チャンネル）
		// 有効/無効は config/logging.php の log_query で切り替える。
		// DB::listen() は Closure しか受け付けないため、__invoke を包んで渡す。
		if (config('logging.log_query')) {
			$queryLogger = new QueryLogger;
			DB::listen(function ($query) use ($queryLogger) {
				$queryLogger($query);
			});
		}

		// 一覧のソート見出し（URL生成と昇順/降順マーク）
		// 式は `@sortLink('name')` のように文字列リテラルで渡す想定。
		Blade::directive('sortLink', function (string $expression) {
			return "<?php echo e(app(\App\Support\SortLink::class, ['request' => request()])->url({$expression})); ?>";
		});

		Blade::directive('sortMark', function (string $expression) {
			return "<?php echo e(app(\App\Support\SortLink::class, ['request' => request()])->mark({$expression})); ?>";
		});

		// フォームの二重送信対策トークン（@csrf と一緒に使う）。
		// 1 画面 1 トークン。リロードすると新しい値になる。
		Blade::directive('formToken', function () {
			$field = PreventDuplicateSubmission::TOKEN_FIELD;

			return "<?php echo '<input type=\"hidden\" name=\"{$field}\" value=\"'.e(\App\Support\FormToken::issue()).'\">'; ?>";
		});
	}
}
