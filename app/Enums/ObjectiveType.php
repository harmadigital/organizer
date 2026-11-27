<?php

namespace App\Enums;

enum ObjectiveType: string
{
    case Goal = 'goal';
    case Target = 'target';
    case Indicator = 'indicator';

    public function label(): string
    {
        return match ($this) {
            self::Goal => 'Tujuan',
            self::Target => 'Sasaran',
            self::Indicator => 'Indikator Keberhasilan',
        };
    }
}
