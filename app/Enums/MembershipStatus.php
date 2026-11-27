<?php

namespace App\Enums;

/**
 * A person's place in an event committee. Only active members have access to the event.
 */
enum MembershipStatus: string
{
    case Invited = 'invited';
    case Active = 'active';
    case Declined = 'declined';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Invited => 'Menunggu konfirmasi',
            self::Active => 'Aktif',
            self::Declined => 'Menolak',
            self::Revoked => 'Dicabut',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Invited => 'warning',
            self::Active => 'success',
            self::Declined => 'secondary',
            self::Revoked => 'danger',
        };
    }
}
