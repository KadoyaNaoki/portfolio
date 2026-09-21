<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 管理者ユーザー作成 API のリクエスト.
 *
 * 認可は API キーの照合のみで行う（ログインは不要）。
 * API キーは以下のいずれかで受け付ける（優先順）。
 *   - リクエストヘッダ: X-API-KEY
 *   - ボディ / クエリ : api_key
 *
 * 一致しない場合は認可エラー（403）となる。
 */
class StoreAdminUserRequest extends FormRequest
{
	/**
	 * API キーを受け取るヘッダ名.
	 */
	private const API_KEY_HEADER = 'X-API-KEY';

	/**
	 * API キーを受け取るボディ / クエリのキー名.
	 */
	private const API_KEY_FIELD = 'api_key';

	/**
	 * この API を呼べるか.
	 *
	 * API キーが一致していればよい（ログインは不要）。
	 */
	public function authorize(): bool
	{
		return $this->hasValidApiKey();
	}

	/**
	 * 送られてきた API キーが設定値と一致するか.
	 */
	public function hasValidApiKey(): bool
	{
		$expected = (string) config('api.admin_user_api_key');

		if ($expected === '') {
			return false;
		}

		$given = $this->header(self::API_KEY_HEADER)
			?? $this->input(self::API_KEY_FIELD);

		if (! is_string($given) || $given === '') {
			return false;
		}

		// タイミング攻撃を避けて比較する
		return hash_equals($expected, $given);
	}

	public function rules(): array
	{
		return [
			// API キー（ヘッダで渡す場合はボディ不要なので nullable）
			self::API_KEY_FIELD => ['nullable', 'string'],
			// 未指定なら userid はサーバー側で自動採番する
			'userid' => ['nullable', 'string', 'min:4', 'max:30', 'alpha_dash', 'unique:users,userid'],
			'name' => ['required', 'string', 'max:50'],
			'password' => ['required', 'string', 'min:8', 'max:255'],
		];
	}

	public function attributes(): array
	{
		return [
			'api_key' => 'APIキー',
			'userid' => 'ユーザーID',
			'name' => '表示名',
			'password' => 'パスワード',
		];
	}

	public function messages(): array
	{
		return [
			'userid.required' => 'ユーザーIDを入力してください。',
			'userid.min' => 'ユーザーIDは4文字以上で入力してください。',
			'userid.max' => 'ユーザーIDは30文字以内で入力してください。',
			'userid.alpha_dash' => 'ユーザーIDは半角英数字・ハイフン・アンダースコアのみ使用できます。',
			'userid.unique' => 'そのユーザーIDは既に使用されています。',
			'name.required' => '表示名を入力してください。',
			'name.max' => '表示名は50文字以内で入力してください。',
			'password.required' => 'パスワードを入力してください。',
			'password.min' => 'パスワードは8文字以上で入力してください。',
		];
	}
}
