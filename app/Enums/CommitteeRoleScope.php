<?php

namespace App\Enums;

/**
 * How far the abilities of a committee role reach inside an event.
 */
enum CommitteeRoleScope: string
{
    case Event = 'event';
    case Division = 'division';

    public function label(): string
    {
        return match ($this) {
            self::Event => 'Seluruh event',
            self::Division => 'Seksi yang ditugaskan',
        };
    }
}
