<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * フォームの二重送信を防ぐミドルウェア.
 *
 * 連打や再送信で同じフォームが複数回送られても、
 * 2 回目以降は無視して最初の送信結果をそのまま返す。
 *
 * 仕組み:
 *   フォームに埋め込んだトークン（_form_token）をキーにして、
 *   同じトークンの処理が既に走ったかどうかを記録する。
 *   - 1 回目: 処理を実行し、結果をキャッシュする
 *   - 2 回目: キャッシュした結果をそのまま返す（処理は実行しない）
 *
 * トークンは @csrf と一緒に view から埋め込む（formToken() 参照）。
 * トークンが無いリクエスト（API など）は対象外。
 *
 * ■ JS との役割分担
 *   JS 側でもボタンを無効化しているが、JS は無効化できるため
 *   サーバー側でも必ず弾く（ここが最終防衛線）。
 */
class PreventDuplicateSubmission
{
	/**
	 * フォームトークンのフィールド名.
	 */
	public const TOKEN_FIELD = '_form_token';

	/**
	 * 処理結果を保持する有効期限（秒）.
	 *
	 * 連打は数秒で収まるため、短めでよい。
	 */
	private const TTL_SECONDS = 10;

	public function handle(Request $request, Closure $next): Response
	{
		// 副作用のない GET / HEAD は対象外
		if ($request->isMethodSafe()) {
			return $next($request);
		}

		$token = $this->resolveToken($request);

		// トークンが無いリクエスト（API など）は素通しする
		if ($token === null) {
			return $next($request);
		}

		$cacheKey = $this->cacheKey($token);

		// すでに同じトークンの処理が完了している場合は、その結果を返す
		if (cache()->has($cacheKey)) {
			$cached = cache()->get($cacheKey);

			// 直前のレスポンス（リダイレクト先など）をそのまま返す
			return redirect($cached['target'])
				->with('status', $cached['status'] ?? null);
		}

		// 処理中のロックを取る（同じトークンの同時実行を防ぐ）
		if (! cache()->add($cacheKey, ['target' => null], self::TTL_SECONDS)) {
			// 先行リクエストが処理中。多重実行を避けて元の画面へ戻す。
			return back();
		}

		$response = $next($request);

		// 完了した結果を記録する（同じトークンの再送に同じ結果を返すため）
		cache()->put($cacheKey, [
			'target' => $response->headers->get('Location') ?? $request->fullUrl(),
			'status' => session()->get('status'),
		], self::TTL_SECONDS);

		return $response;
	}

	/**
	 * リクエストからフォームトークンを取り出す.
	 */
	private function resolveToken(Request $request): ?string
	{
		$token = $request->input(self::TOKEN_FIELD);

		if (! is_string($token) || $token === '') {
			return null;
		}

		return $token;
	}

	/**
	 * キャッシュのキーを作る.
	 *
	 * トークンだけだと別ユーザー間で衝突しうるため、セッション ID も混ぜる。
	 */
	private function cacheKey(string $token): string
	{
		return 'form-submitted:'.sha1(session()->getId().'|'.$token);
	}
}
