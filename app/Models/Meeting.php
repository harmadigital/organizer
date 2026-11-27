<?php

namespace App\Models;

use App\Models\Concerns\RecordsEditors;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\MeetingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A committee meeting of the whole core team (no division) or of one division. Every
 * committee member sees it; there are no invitations. Its minutes, attendance, decisions
 * and follow-ups are written in one go and are readable by everyone once saved.
 */
class Meeting extends Model
{
    /** @use HasFactory<MeetingFactory> */
    use HasFactory;

    use RecordsEditors;

    /**
     * The attributes that are mass assignable. The minutes change only through SaveMinutes.
     *
     * @var array<int, string>
     */
    protected $fillable = ['event_id', 'division_id', 'title', 'meets_on', 'starts_at', 'ends_at', 'location', 'agenda'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meets_on' => 'date',
            'minutes_version' => 'integer',
            'minutes_updated_at' => 'datetime',
        ];
    }

    public function hasMinutes(): bool
    {
        return $this->minutes_version > 0;
    }

    /**
     * Upcoming until the end of its day in the organization's timezone.
     */
    public function isUpcoming(CarbonInterface $today): bool
    {
        return $this->meets_on->gte($today->copy()->startOfDay());
    }

    /**
     * Attendance is taken on the meeting day and locked once the meeting is over: at its
     * end time, or at the end of that day when no end time was given.
     *
     * @return 'not_open'|'open'|'locked'
     */
    public function attendanceState(CarbonInterface $now, string $timezone): string
    {
        $localNow = CarbonImmutable::instance($now)->setTimezone($timezone);

        return match (true) {
            $localNow->lt($this->attendanceOpensAt($timezone)) => 'not_open',
            $localNow->gte($this->attendanceClosesAt($timezone)) => 'locked',
            default => 'open',
        };
    }

    public function attendanceOpensAt(string $timezone): CarbonImmutable
    {
        return CarbonImmutable::parse($this->meets_on->toDateString(), $timezone)->startOfDay();
    }

    public function attendanceClosesAt(string $timezone): CarbonImmutable
    {
        $day = CarbonImmutable::parse($this->meets_on->toDateString(), $timezone);

        return $this->ends_at
            ? $day->setTimeFromTimeString($this->ends_at)
            : $day->endOfDay();
    }

    /**
     * "10.00" or "10.00–12.00".
     */
    public function timeLabel(): string
    {
        $format = fn (string $time): string => str_replace(':', '.', substr($time, 0, 5));

        return $format($this->starts_at).($this->ends_at ? '–'.$format($this->ends_at) : '');
    }

    /**
     * @return list<string>
     */
    public function agendaItems(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->agenda))));
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
     * @return BelongsTo<User, $this>
     */
    public function minutesAuthor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'minutes_updated_by');
    }

    /**
     * @return HasMany<MeetingMinutesVersion, $this>
     */
    public function minutesVersions(): HasMany
    {
        return $this->hasMany(MeetingMinutesVersion::class)->orderByDesc('version_no');
    }

    /**
     * @return BelongsToMany<EventMembership, $this>
     */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(EventMembership::class, 'meeting_attendees')->withPivot('event_id')->withTimestamps();
    }

    /**
     * @return HasMany<MeetingDecision, $this>
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(MeetingDecision::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<MeetingActionItem, $this>
     */
    public function actionItems(): HasMany
    {
        return $this->hasMany(MeetingActionItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Route parameter {comment} under a meeting is resolved through this relation.
     *
     * @return HasMany<MeetingComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(MeetingComment::class)->orderBy('id');
    }
}
