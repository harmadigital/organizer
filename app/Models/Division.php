<?php

namespace App\Models;

use App\Models\Concerns\RecordsEditors;
use Database\Factories\DivisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A section (seksi) of an event committee, optionally nested under another one.
 */
class Division extends Model
{
    /** @use HasFactory<DivisionFactory> */
    use HasFactory;

    use RecordsEditors;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'parent_id',
        'division_template_id',
        'name',
        'description',
        'responsibilities',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<EventRoleAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(EventRoleAssignment::class);
    }

    /**
     * @return HasMany<CalendarEntry, $this>
     */
    public function calendarEntries(): HasMany
    {
        return $this->hasMany(CalendarEntry::class);
    }

    /**
     * Ids of every division below this one, at any depth.
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $descendantIds = [];
        $parentIds = [$this->getKey()];

        while ($parentIds !== []) {
            $parentIds = self::query()
                ->where('event_id', $this->event_id)
                ->whereIn('parent_id', $parentIds)
                ->whereNotIn('id', $descendantIds)
                ->pluck('id')
                ->all();

            $descendantIds = [...$descendantIds, ...$parentIds];
        }

        return $descendantIds;
    }
}
