<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The event's own copy of a committee role's rules, taken when the event is created,
 * so later changes to the organization's defaults do not alter a running event.
 */
class EventRoleRule extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'committee_role_id',
        'is_required',
        'is_singleton',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_singleton' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<CommitteeRole, $this>
     */
    public function committeeRole(): BelongsTo
    {
        return $this->belongsTo(CommitteeRole::class);
    }
}
