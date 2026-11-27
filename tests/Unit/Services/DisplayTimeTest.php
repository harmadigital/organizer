<?php

namespace Tests\Unit\Services;

use App\Services\DisplayTime;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class DisplayTimeTest extends TestCase
{
    public function test_it_converts_a_stored_utc_moment_to_the_display_timezone(): void
    {
        $displayTime = new DisplayTime('Asia/Jakarta', 'id');

        $local = $displayTime->toLocal(CarbonImmutable::parse('2026-08-16 18:30:00', 'UTC'));

        $this->assertSame('Asia/Jakarta', $local->timezoneName);
        $this->assertSame('2026-08-17 01:30', $local->format('Y-m-d H:i'));
    }

    public function test_it_formats_with_indonesian_month_and_day_names(): void
    {
        $displayTime = new DisplayTime('Asia/Jakarta', 'id');

        $formatted = $displayTime->format(CarbonImmutable::parse('2026-08-16 18:30:00', 'UTC'), 'l, j F Y H:i');

        $this->assertSame('Senin, 17 Agustus 2026 01:30', $formatted);
    }

    public function test_it_does_not_change_the_moment_it_was_given(): void
    {
        $stored = CarbonImmutable::parse('2026-01-01 00:00:00', 'UTC');

        (new DisplayTime('Asia/Jayapura', 'id'))->toLocal($stored);

        $this->assertSame('UTC', $stored->timezoneName);
    }

    public function test_it_returns_null_when_there_is_no_moment_to_format(): void
    {
        $this->assertNull((new DisplayTime('Asia/Jakarta', 'id'))->format(null));
    }
}
