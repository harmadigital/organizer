<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class DisplayTime
{
    public function __construct(
        private string $timezone,
        private string $locale,
    ) {}

    public function timezone(): string
    {
        return $this->timezone;
    }

    /**
     * Convert a stored (UTC) moment to the timezone people read it in.
     */
    public function toLocal(DateTimeInterface $moment): CarbonImmutable
    {
        return CarbonImmutable::instance($moment)->setTimezone($this->timezone)->locale($this->locale);
    }

    /**
     * Format a stored moment for display, with month and day names translated.
     */
    public function format(?DateTimeInterface $moment, string $format = 'j F Y H:i'): ?string
    {
        return $moment === null ? null : $this->toLocal($moment)->translatedFormat($format);
    }
}
