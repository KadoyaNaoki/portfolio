<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'userid' => ['required', 'string', 'min:4', 'max:30', 'alpha_dash', 'unique:users,userid'],
			'name' => ['required', 'string', 'max:50'],
			'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
		];
	}

	public function attributes(): array
	{
		return [
			'userid' => 'ユーザーID',
			'name' => '表示名',
			'password' => 'パスワード',
		];
	}
}
