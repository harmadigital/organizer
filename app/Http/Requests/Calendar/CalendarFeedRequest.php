<?php

namespace App\Http\Requests\Calendar;

use App\Http\Requests\JsonFormRequest;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The period (and optional division) the event calendar asks for. The end date is exclusive,
 * as FullCalendar sends it. Who may see the calendar is checked by the controller.
 */
class CalendarFeedRequest extends JsonFormRequest
{
    /**
     * The longest period one request may ask for; a month view with its leading and trailing weeks fits.
     */
    public const MAX_DAYS = 62;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    public function rules(): array
    {
        return [
            ...$this->rangeRules(),
            'division' => ['nullable', function (string $attribute, mixed $value, Closure $fail): void {
                $isKnown = $value === 'none'
                    || (is_string($value) && ctype_digit($value) && $this->event()->divisions()->whereKey((int) $value)->exists());

                if (! $isKnown) {
                    $fail('Seksi tidak ditemukan di event ini.');
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'start' => 'tanggal awal',
            'end' => 'tanggal akhir',
            'division' => 'seksi',
            'scope' => 'tampilan',
        ];
    }

    public function from(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->validated('start'));
    }

    /**
     * Exclusive: the first day that is no longer part of the period.
     */
    public function until(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->validated('end'));
    }

    /**
     * '' for every division, 'none' for the core team, or a division id.
     */
    public function division(): ?string
    {
        return $this->validated('division');
    }

    /**
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    protected function rangeRules(): array
    {
        return [
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => ['required', 'date_format:Y-m-d', 'after:start', function (string $attribute, mixed $value, Closure $fail): void {
                $start = $this->input('start');

                if (! is_string($start) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) !== 1 || ! is_string($value)) {
                    return;
                }

                if (CarbonImmutable::createFromFormat('!Y-m-d', $start)->diffInDays(CarbonImmutable::createFromFormat('!Y-m-d', $value)) > self::MAX_DAYS) {
                    $fail('Rentang kalender paling lama '.self::MAX_DAYS.' hari.');
                }
            }],
        ];
    }

    private function event(): Event
    {
        return $this->route('event');
    }
}
