<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
	protected $fillable = [
		'user_id',
		'userid',
		'ip_address',
		'user_agent',
		'success',
		'logged_in_at',
	];

	protected function casts(): array
	{
		return [
			'success' => 'boolean',
			'logged_in_at' => 'datetime',
		];
	}

	/**
	 * ログインしたユーザー.
	 *
	 * @return BelongsTo<User, $this>
	 */
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}
}
