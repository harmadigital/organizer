<?php

namespace App\Enums;

/**
 * Whether a person is still part of the organization. Only active people can sign in.
 */
enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Nonaktif',
        };
    }
}
