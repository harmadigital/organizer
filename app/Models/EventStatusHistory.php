<?php

namespace App\Models;

use App\Enums\EventPhase;
use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only record of every status and phase change of an event.
 */
class EventStatusHistory extends Model
{
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'from_status',
        'to_status',
        'from_phase',
        'to_phase',
        'reason',
        'actor_user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => EventStatus::class,
            'to_status' => EventStatus::class,
            'from_phase' => EventPhase::class,
            'to_phase' => EventPhase::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
