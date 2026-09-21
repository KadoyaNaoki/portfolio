<?php

namespace App\Enums;

enum UserRole: int
{
	case USER = 1;   // 一般
	case ADMIN = 2;  // 管理者

	public function label(): string
	{
		return match ($this) {
			self::USER => '一般',
			self::ADMIN => '管理者',
		};
	}

	public static function options(): array
	{
		return [
			self::USER->value => self::USER->label(),
			self::ADMIN->value => self::ADMIN->label(),
		];
	}
}
