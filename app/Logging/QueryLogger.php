<?php

namespace App\Logging;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

/**
 * 実行された SQL をログに記録するリスナー.
 *
 * DB::listen() から呼ばれ、実行したクエリ・バインド値・実行時間を
 * アプリログ（daily チャンネル）に 1 行で書き出す。
 *
 * 出力例:
 *   [debug]2026/09/20 22:20:05 : SQL : select * from `users` where `userid` = ? [admin_test] (1.23ms)
 *
 * 日時・レベルは ApplicationLogFormatter が付けるため、
 * ここでは本文だけを渡す（Log::debug の第 2 引数にすると
 * 配列展開されてしまうので、整形済みの文字列をメッセージにする）。
 */
class QueryLogger
{
	/**
	 * バインド値の最大表示長（長すぎる値でログを埋めないため）.
	 */
	private const MAX_BINDING_LENGTH = 200;

	/**
	 * 1 クエリ分をログに書き出す.
	 */
	public function __invoke(QueryExecuted $query): void
	{
		$sql = $this->interpolate($query->sql, $query->bindings);

		$line = sprintf(
			'SQL : %s (%s)',
			$sql,
			$this->formatTime($query->time),
		);

		Log::channel('daily')->debug($line);
	}

	/**
	 * プレースホルダにバインド値を埋め込んだ表示用の SQL を作る.
	 *
	 * 実際に実行された SQL そのものではなく、人が読むための整形。
	 *
	 * @param  array<int, mixed>  $bindings
	 */
	private function interpolate(string $sql, array $bindings): string
	{
		// バインド値を 1 つずつ ? に置き換える
		foreach ($bindings as $binding) {
			$value = $this->formatBinding($binding);

			// ? を 1 つだけ置換する（str_replace だと全部置換されてしまう）
			$pos = strpos($sql, '?');
			if ($pos === false) {
				break;
			}

			$sql = substr($sql, 0, $pos).$value.substr($sql, $pos + 1);
		}

		return $sql;
	}

	/**
	 * バインド値を文字列化する.
	 */
	private function formatBinding(mixed $binding): string
	{
		if ($binding === null) {
			return 'NULL';
		}

		if (is_bool($binding)) {
			return $binding ? 'true' : 'false';
		}

		if (is_int($binding) || is_float($binding)) {
			return (string) $binding;
		}

		if (is_array($binding) || is_object($binding)) {
			$binding = json_encode($binding, JSON_UNESCAPED_UNICODE);
		}

		$text = (string) $binding;

		// 値は引用符で囲み、長すぎる場合は切り詰める
		if (mb_strlen($text) > self::MAX_BINDING_LENGTH) {
			$text = mb_substr($text, 0, self::MAX_BINDING_LENGTH).'…';
		}

		// 改行はログを崩すのでエスケープする
		$text = str_replace(["\r\n", "\r", "\n"], '\n', $text);

		return "'".$text."'";
	}

	/**
	 * 実行時間を読みやすい文字列にする.
	 */
	private function formatTime(?float $milliseconds): string
	{
		if ($milliseconds === null) {
			return 'time: unknown';
		}

		return number_format($milliseconds, 2).'ms';
	}
}
