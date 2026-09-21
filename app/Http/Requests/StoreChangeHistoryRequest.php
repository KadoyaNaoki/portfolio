<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChangeHistoryRequest extends FormRequest
{
	/**
	 * 変更履歴を登録できるのは管理者のみ.
	 */
	public function authorize(): bool
	{
		return $this->user()?->isAdmin() ?? false;
	}

	public function rules(): array
	{
		return [
			// それなりに長い文章を想定（改行込みで最大 20000 文字）
			'body' => ['required', 'string', 'min:1', 'max:20000'],
		];
	}

	public function attributes(): array
	{
		return [
			'body' => '変更内容',
		];
	}

	public function messages(): array
	{
		return [
			'body.required' => '変更内容を入力してください。',
			'body.max' => '変更内容は20000文字以内で入力してください。',
			'body.min' => '変更内容を入力してください。',
		];
	}
}
