<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * 一覧画面のソートリンク生成ヘルパー.
 *
 * 現在のクエリ文字列を保ったまま sort / direction だけを差し替えた URL を作り、
 * 「同じ列を再クリックしたら昇順⇄降順を反転する」という分岐をここに閉じ込める。
 * View 側からは条件式と array_merge が消える。
 */
final class SortLink
{
	public const ASC = 'asc';

	public const DESC = 'desc';

	public function __construct(
		private readonly Request $request,
	) {}

	/**
	 * 現在のソート対象カラム.
	 */
	public function column(): string
	{
		return (string) $this->request->query('sort', 'created_at');
	}

	/**
	 * 現在のソート方向.
	 */
	public function direction(): string
	{
		return $this->request->query('direction') === self::ASC ? self::ASC : self::DESC;
	}

	/**
	 * このカラムをクリックしたときの遷移先 URL.
	 */
	public function url(string $column): string
	{
		return $this->request->fullUrlWithQuery([
			'sort' => $column,
			'direction' => $this->nextDirection($column),
			'page' => null, // 並び替えたらページ位置はリセット
		]);
	}

	/**
	 * このカラムが現在のソート対象か.
	 */
	public function isActive(string $column): bool
	{
		return $this->column() === $column;
	}

	/**
	 * 見出しに添える矢印（非アクティブなら空文字）.
	 */
	public function mark(string $column): string
	{
		if (! $this->isActive($column)) {
			return '';
		}

		return $this->direction() === self::ASC ? ' ▲' : ' ▼';
	}

	/**
	 * 同じ列なら方向を反転、違う列なら昇順から始める.
	 */
	private function nextDirection(string $column): string
	{
		if (! $this->isActive($column)) {
			return self::ASC;
		}

		return $this->direction() === self::ASC ? self::DESC : self::ASC;
	}
}
