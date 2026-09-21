<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeHistory extends Model
{
	protected $fillable = [
		'user_id',
		'userid',
		'body',
		'registered_at',
	];

	protected function casts(): array
	{
		return [
			'registered_at' => 'datetime',
		];
	}

	/**
	 * 登録者.
	 *
	 * @return BelongsTo<User, $this>
	 */
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}
}
