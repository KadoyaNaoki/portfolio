<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * アクセスログ出力の共通ヘルパー.
 *
 * アクセスログはアプリログ（daily チャンネル）に統合して出力する。
 * HTTP は LogAccess ミドルウェアが自動で記録し、
 * バッチ（CLI）は HTTP リクエストではないため、
 * 各コマンドからこのヘルパーを呼んで記録する。
 *
 * 1 アクセスにつき、以下の 5 行を出力する（区切り文字は 47 個の *）。
 * 先頭行は書き込んだ日時。
 *
 *   ***********************************************
 *   2026/09/21 07:40:05
 *   users/23/edit
 *   users.id : 23
 *   users.userid : admin_test
 *
 * 未ログインやバッチでは値が無いため "-" を出力する。
 *
 *   ***********************************************
 *   2026/09/21 02:00:01
 *   artisan users:create-general
 *   users.id : -
 *   users.userid : -
 */
final class AccessLog
{
    /**
     * 区切り文字（47 個のアスタリスク）.
     */
    private const MARKER = '***********************************************';

    /**
     * 値が無い場合の表示.
     */
    private const EMPTY_VALUE = '-';

    /**
     * 日時の書式（他のログと揃える）.
     */
    private const DATETIME_FORMAT = 'Y/m/d H:i:s';

    /**
     * アクセスログを 5 行で出力する.
     *
     * @param  string  $path  相対パス（例: users/23/edit、artisan users:create-general）
     * @param  int|null  $userId  users.id（未ログイン・バッチは null）
     * @param  string|null  $userid  users.userid（未ログイン・バッチは null）
     */
    public static function log(string $path, ?int $userId = null, ?string $userid = null): void
    {
        $lines = [
            self::MARKER,
            // 書き込んだ日時
            now()->format(self::DATETIME_FORMAT),
            ltrim($path, '/'),
            'users.id : '.self::value($userId),
            'users.userid : '.self::value($userid),
        ];
        // 5 行を 1 レコードとして書き込む（行間だけ改行し、末尾には付けない）
        if (! empty($path)) {
            Log::channel('daily')->info(implode(PHP_EOL, $lines));
        } else {
            Log::channel('stdout')->info(implode(PHP_EOL, $lines));
        }

    }

    /**
     * null / 空文字は "-" に置き換える.
     */
    private static function value(mixed $value): string
    {
        if ($value === null || $value === '') {
            return self::EMPTY_VALUE;
        }

        return (string) $value;
    }
}
