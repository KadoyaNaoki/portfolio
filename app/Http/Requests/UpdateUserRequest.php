<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
	public function authorize(): bool
	{
		$user = $this->route('user');

		// 本人 or 管理者のみ（UserPolicy::update）
		return $this->user()->can('update', $user);
	}

	public function rules(): array
	{
		$user = $this->route('user');

		$rules = [
			'name' => ['required', 'string', 'max:50'],
			'userid' => ['required', 'string', 'min:4', 'max:30', 'alpha_dash', Rule::unique('users', 'userid')->ignore($user->id)],
			'password' => ['nullable', 'string', 'min:8', 'max:255', 'confirmed'],
			'avatar' => ['nullable', 'image', 'max:2048'],
		];

		// role変更は管理者のみ（UserPolicy::changeRole と同じ基準）
		// 未認証で rules() が評価された場合（テスト等）は付与しない
		if ($this->user()?->can('changeRole', $user)) {
			$rules['role'] = ['required', Rule::enum(UserRole::class)];
		}

		return $rules;
	}

	public function attributes(): array
	{
		return [
			'userid' => 'ユーザーID',
			'name' => '表示名',
			'password' => 'パスワード',
			'avatar' => 'プロフィール画像',
			'role' => 'ロール',
		];
	}
}
