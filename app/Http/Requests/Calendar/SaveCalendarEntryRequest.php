<?php

namespace App\Http\Requests\Calendar;

use App\Http\Requests\JsonFormRequest;
use App\Models\CalendarEntry;
use App\Models\Event;
use App\Services\CalendarAuthority;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class SaveCalendarEntryRequest extends JsonFormRequest
{
    /**
     * The longest kegiatan, counting the first and the last day.
     */
    public const MAX_DAYS = 31;

    /**
     * Any active committee member adds a kegiatan for any division. Changing one needs the
     * right to change it; moving someone else's kegiatan to another division also needs the
     * right to manage kegiatan there.
     */
    public function authorize(CalendarAuthority $authority): bool
    {
        $entry = $this->route('calendarEntry');

        if (! $entry instanceof CalendarEntry) {
            return $authority->canAdd($this->user(), $this->event());
        }

        if (! $authority->canChange($this->user(), $entry)) {
            return false;
        }

        $divisionId = $this->filled('division_id') ? $this->integer('division_id') : null;

        return $divisionId === $entry->division_id
            || $entry->user_id === $this->user()->getKey()
            || $authority->canManageIn($this->user(), $this->event(), $divisionId);
    }

    /**
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on', function (string $attribute, mixed $value, Closure $fail): void {
                $days = $this->daysBetweenStartAndEnd();

                if ($days !== null && $days >= self::MAX_DAYS) {
                    $fail('Kegiatan paling lama '.self::MAX_DAYS.' hari.');
                }
            }],
            'starts_at' => ['nullable', 'date_format:H:i', 'required_with:ends_at', function (string $attribute, mixed $value, Closure $fail): void {
                if (($this->daysBetweenStartAndEnd() ?? 0) > 0) {
                    $fail('Kegiatan beberapa hari dicatat seharian; kosongkan jam.');
                }
            }],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:200'],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')->where('event_id', $this->event()->getKey())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ends_at.after' => 'Jam selesai harus setelah jam mulai.',
            'starts_at.required_with' => 'Isi jam mulai bila jam selesai diisi.',
            'ends_on.after_or_equal' => 'Sampai tanggal tidak boleh sebelum tanggal.',
            'starts_at.date_format' => 'Pilih jam mulai, misalnya 09:00.',
            'ends_at.date_format' => 'Pilih jam selesai, misalnya 11:00.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'starts_on' => 'tanggal',
            'ends_on' => 'sampai tanggal',
            'starts_at' => 'jam mulai',
            'ends_at' => 'jam selesai',
            'location' => 'tempat',
            'division_id' => 'seksi',
            'notes' => 'keterangan',
        ];
    }

    /**
     * Days from the start date to the end date (0 for one day), or null while either is missing or malformed.
     */
    private function daysBetweenStartAndEnd(): ?int
    {
        $startsOn = $this->dateInput('starts_on');
        $endsOn = $this->dateInput('ends_on');

        return $startsOn === null || $endsOn === null ? null : (int) $startsOn->diffInDays($endsOn, false);
    }

    private function dateInput(string $key): ?CarbonImmutable
    {
        $value = $this->input($key);

        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', $value) ?: null;
    }

    private function event(): Event
    {
        return $this->route('event');
    }
}
