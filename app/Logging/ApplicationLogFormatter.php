<?php

namespace App\Logging;

use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

/**
 * アプリログ（daily チャンネル）用のフォーマッタ.
 *
 * アクセスログとアプリログを 1 ファイルに統合する。
 *
 * ■ 通常のログ（Log::debug() / info() など）
 *   先頭に「[レベル]日時 : 」を付け、そのあとに本文を出す。
 *
 *     [debug]2026/09/20 22:20:05 : 何かのデバッグ内容
 *     [info]2026/09/20 22:20:06 : 一般ユーザーを 1 件作成します...
 *
 *   複数行のメッセージは、2 行目以降を本文の位置にそろえてインデントする。
 *
 *     [info]2026/09/20 22:20:06 : 1行目
 *                              2行目
 *
 * ■ アクセスログ（App\Support\AccessLog）
 *   context に 'access' キーを持つ場合は専用の整形を行う。
 *   区切り文字・日時・パス・users.id・users.userid を 1 行ずつ並べる。
 *   （本文にすでに日時を含めているため、ここでは日時を付け足さない）
 *
 *     ***********************************************
 *     2026/09/20 22:20:05
 *     users/23/edit
 *     users.id : 23
 *     users.userid : admin_test
 *
 * ■ 改行の扱い
 *   ファイル末尾に改行を付けないため、レコードの「先頭」に改行を入れる。
 *   ファイル先頭だけは改行なしで始まる。
 *   （既存ファイルがあるかどうかで判定する）
 */
class ApplicationLogFormatter implements FormatterInterface
{
	/**
	 * 日時の書式（例: 2026/09/20 22:20:05）.
	 */
	private const DATETIME_FORMAT = 'Y/m/d H:i:s';

	/**
	 * プレフィックスと本文の区切り.
	 */
	private const PREFIX_SEPARATOR = ' : ';

	/**
	 * アクセスログを識別する context のキー.
	 */
	private const ACCESS_CONTEXT_KEY = 'access';

	/**
	 * このファイルに既に何か書き込んだか.
	 *
	 * レコード先頭の改行を「2 件目以降だけ」入れるために保持する。
	 */
	private static bool $wroteSomething = false;

	/**
	 * 1 レコードを整形する.
	 */
	public function format(LogRecord $record): string
	{
		$body = $this->hasAccessContext($record)
				? $this->formatAccessLog($record)
				: $this->formatApplicationLog($record);

		// このファイルに既に書いている（または既存ファイルがある）なら、
		// レコードの先頭に改行を入れて前の行と分離する。
		$separator = (self::$wroteSomething || $this->logFileExists()) ? PHP_EOL : '';
		self::$wroteSomething = true;

		return $separator.$body;
	}

	/**
	 * アクセスログかどうか（context に 'access' があるか）.
	 */
	private function hasAccessContext(LogRecord $record): bool
	{
		return array_key_exists(self::ACCESS_CONTEXT_KEY, $record->context);
	}

	/**
	 * アクセスログの整形.
	 *
	 * 本文は App\Support\AccessLog が組み立てた複数行の文字列。
	 * 日時はその中に含まれているので、ここでは何も付け足さない。
	 */
	private function formatAccessLog(LogRecord $record): string
	{
		return rtrim($record->message, "\r\n");
	}

	/**
	 * 通常のアプリログの整形.
	 *
	 * 先頭に「[レベル]日時 : 」を付け、複数行なら 2 行目以降をそろえる。
	 */
	private function formatApplicationLog(LogRecord $record): string
	{
		$prefix = '['.strtolower($record->level->getName()).']'
				.$record->datetime->format(self::DATETIME_FORMAT)
				.self::PREFIX_SEPARATOR;

		$lines = preg_split("/\r\n|\n|\r/", rtrim($record->message, "\r\n"));

		if ($lines === false || $lines === []) {
			return rtrim($prefix, ' ');
		}

		// 1 行目はプレフィックスの後ろに続ける
		$out = $prefix.array_shift($lines);

		// 2 行目以降はプレフィックスの幅ぶんインデントして見やすくする
		$indent = str_repeat(' ', mb_strwidth($prefix));

		foreach ($lines as $line) {
			$out .= PHP_EOL.$indent.$line;
		}

		return $out;
	}

	/**
	 * 現在の日次ファイルが既に存在し、中身があるか.
	 */
	private function logFileExists(): bool
	{
		$path = $this->currentLogPath();

		return $path !== null && is_file($path) && filesize($path) > 0;
	}

	/**
	 * daily チャンネルの「今まさに書こうとしているファイル」のパスを推定する.
	 */
	private function currentLogPath(): ?string
	{
		$candidate = storage_path('logs/laravel-'.date('Y-m-d').'.log');

		return is_file($candidate) ? $candidate : null;
	}

	/**
	 * 複数レコードをまとめて整形.
	 *
	 * レコード間は改行で区切り、最後のレコードには付けない。
	 *
	 * @param  array<int, LogRecord>  $records
	 */
	public function formatBatch(array $records): string
	{
		$parts = [];

		foreach ($records as $record) {
			$parts[] = $this->format($record);
		}

		return implode(PHP_EOL, $parts);
	}
}
