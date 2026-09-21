<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ユーザー一括削除のリクエスト.
 *
 * 一覧のチェックボックスで選んだ ID を配列で受け取る。
 * 実行できるのは管理者のみ（UserPolicy::delete）。
 */
class DeleteUsersRequest extends FormRequest
{
	/**
	 * 管理者のみ一括削除できる.
	 */
	public function authorize(): bool
	{
		return $this->user()?->can('delete', User::class) ?? false;
	}

	public function rules(): array
	{
		return [
			// 1 件以上選ばれていること
			'ids' => ['required', 'array', 'min:1'],
			// 各要素は users の既存 ID であること
			'ids.*' => ['integer', 'exists:users,id'],
		];
	}

	public function attributes(): array
	{
		return [
			'ids' => '削除対象',
			'ids.*' => '削除対象のユーザー',
		];
	}

	public function messages(): array
	{
		return [
			'ids.required' => '削除するユーザーを選択してください。',
			'ids.min' => '削除するユーザーを1件以上選択してください。',
			'ids.*.exists' => '存在しないユーザーが含まれています。',
		];
	}
}
