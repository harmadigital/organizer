<?php

namespace App\Models;

use Database\Factories\EventRoleAssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One committee role held by a member, for the whole event or for one division.
 */
class EventRoleAssignment extends Model
{
    /** @use HasFactory<EventRoleAssignmentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'event_membership_id',
        'committee_role_id',
        'division_id',
        'is_primary',
        'job_description',
        'assigned_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'ends_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->ends_at === null;
    }

    /**
     * Fill the keys the database uses to refuse duplicate active assignments and a
     * second primary holder of a singleton role. Both are cleared when the assignment ends.
     */
    public function refreshUniquenessKeys(bool $isSingleton): void
    {
        if (! $this->isActive()) {
            $this->active_key = null;
            $this->primary_key = null;

            return;
        }

        $division = $this->division_id ?? 0;

        $this->active_key = "{$this->event_membership_id}:{$this->committee_role_id}:{$division}";
        $this->primary_key = $isSingleton && $this->is_primary ? "{$this->event_id}:{$this->committee_role_id}:{$division}" : null;
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull($this->qualifyColumn('ends_at'));
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<EventMembership, $this>
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(EventMembership::class, 'event_membership_id');
    }

    /**
     * @return BelongsTo<CommitteeRole, $this>
     */
    public function committeeRole(): BelongsTo
    {
        return $this->belongsTo(CommitteeRole::class);
    }

    /**
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }
}
