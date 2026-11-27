<?php

namespace App\Models;

use App\Models\Concerns\RecordsEditors;
use Database\Factories\CalendarEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A kegiatan a committee member added to the event calendar, for the core team (no division)
 * or for one division. Everyone in the event sees it; it has no status and no invitations.
 * Saved only through SaveCalendarEntry and removed through DeleteCalendarEntry.
 *
 * ends_on equals starts_on for a one-day kegiatan. Only a one-day kegiatan has times;
 * without a start time it lasts all day.
 */
class CalendarEntry extends Model
{
    /** @use HasFactory<CalendarEntryFactory> */
    use HasFactory;

    use RecordsEditors;

    /**
     * The attributes that are mass assignable. The event and the author are set by SaveCalendarEntry.
     *
     * @var array<int, string>
     */
    protected $fillable = ['division_id', 'title', 'starts_on', 'ends_on', 'starts_at', 'ends_at', 'location', 'notes'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function isAllDay(): bool
    {
        return $this->starts_at === null;
    }

    public function lastsSeveralDays(): bool
    {
        return ! $this->ends_on->isSameDay($this->starts_on);
    }

    /**
     * "09.00–11.00", "09.00", or "Seharian" without a start time.
     */
    public function timeLabel(): string
    {
        if ($this->isAllDay()) {
            return 'Seharian';
        }

        $format = fn (string $time): string => str_replace(':', '.', substr($time, 0, 5));

        return $format($this->starts_at).($this->ends_at ? '–'.$format($this->ends_at) : '');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * The committee member who added the kegiatan.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
