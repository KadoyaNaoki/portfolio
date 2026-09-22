<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * フォームの二重送信対策トークン.
 *
 * 画面を表示するたびに新しいトークンを発行し、
 * そのトークンで「このフォームが一度送信されたか」を判定する。
 *
 * App\Http\Middleware\PreventDuplicateSubmission がこのトークンを見て、
 * 2 回目以降の送信を無視する。
 */
final class FormToken
{
	/**
	 * セッション内でトークンを保持するキー.
	 */
	private const SESSION_KEY = 'form_tokens';

	/**
	 * 1 画面で保持するトークンの最大数（古いものから捨てる）.
	 */
	private const MAX_TOKENS = 20;

	/**
	 * 新しいトークンを発行する（画面表示のたびに呼ぶ）.
	 */
	public static function issue(): string
	{
		$token = Str::random(40);

		$tokens = session()->get(self::SESSION_KEY, []);

		// 新しいトークンを先頭に積み、古いものは捨てる
		array_unshift($tokens, $token);
		$tokens = array_slice($tokens, 0, self::MAX_TOKENS);

		session()->put(self::SESSION_KEY, $tokens);

		return $token;
	}
}
