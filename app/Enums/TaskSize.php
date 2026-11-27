<?php

namespace App\Enums;

/**
 * How big a task is, stored in tasks.weight. It decides how much the task counts in
 * progress: finishing a heavy task moves progress three times as much as a light one.
 */
enum TaskSize: int
{
    case Light = 1;
    case Medium = 2;
    case Heavy = 3;

    public function label(): string
    {
        return match ($this) {
            self::Light => 'Ringan',
            self::Medium => 'Sedang',
            self::Heavy => 'Berat',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Light => 'Sekali kerja beres',
            self::Medium => 'Perlu beberapa kali kerja',
            self::Heavy => 'Butuh banyak waktu atau persiapan',
        };
    }

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
