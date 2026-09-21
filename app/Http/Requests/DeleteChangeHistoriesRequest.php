<?php

namespace App\Http\Requests;

use App\Models\ChangeHistory;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 変更履歴の一括削除リクエスト.
 *
 * 一覧のチェックボックスで選んだ ID を配列で受け取る。
 * 実行できるのは管理者のみ（ChangeHistoryPolicy::delete）。
 */
class DeleteChangeHistoriesRequest extends FormRequest
{
	/**
	 * 管理者のみ一括削除できる.
	 */
	public function authorize(): bool
	{
		return $this->user()?->can('delete', ChangeHistory::class) ?? false;
	}

	public function rules(): array
	{
		return [
			'ids' => ['required', 'array', 'min:1'],
			'ids.*' => ['integer', 'exists:change_histories,id'],
		];
	}

	public function attributes(): array
	{
		return [
			'ids' => '削除対象',
			'ids.*' => '削除対象の履歴',
		];
	}

	public function messages(): array
	{
		return [
			'ids.required' => '削除する変更履歴を選択してください。',
			'ids.min' => '削除する変更履歴を1件以上選択してください。',
			'ids.*.exists' => '存在しない変更履歴が含まれています。',
		];
	}
}
