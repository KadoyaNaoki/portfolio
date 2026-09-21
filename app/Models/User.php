<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * アプリのユーザー.
 *
 * cast した型を伝えるため、属性の型を明示する。
 * これがないと Larastan が $attributes の初期値から int と推論し、
 * UserRole との比較を「常に false」と誤判定する。
 *
 * @property int $id
 * @property string $userid
 * @property string $name
 * @property string $password
 * @property UserRole $role
 * @property string|null $avatar_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable
{
	/** @use HasFactory<UserFactory> */
	use HasFactory, Notifiable;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var list<string>
	 */
	protected $fillable = [
		'userid',
		'name',
		'password',
		'role',
		'avatar_path',
	];

	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var list<string>
	 */
	protected $hidden = [
		'password',
	];

	protected $attributes = [
		'role' => UserRole::USER->value, // DB のデフォルトと揃える
	];

	/**
	 * Get the attributes that should be cast.
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'password' => 'hashed',
			'role' => UserRole::class,
		];
	}

	public function isAdmin(): bool
	{
		return $this->role === UserRole::ADMIN;
	}

	/**
	 * このユーザーのログイン履歴.
	 *
	 * @return HasMany<LoginHistory, $this>
	 */
	public function loginHistories(): HasMany
	{
		return $this->hasMany(LoginHistory::class);
	}

	public function avatarUrl(): ?string
	{
		if (! $this->avatar_path) {
			return null;
		}

		return Storage::disk('public')->url($this->avatar_path);
	}
}
