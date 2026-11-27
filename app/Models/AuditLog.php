<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'event_id',
        'actor_user_id',
        'actor_name',
        'effective_role',
        'action',
        'auditable_type',
        'auditable_id',
        'before',
        'after',
        'reason',
        'correlation_id',
        'ip_address',
    ];

    /**
     * Audit entries are append-only: once written they are never changed or removed.
     */
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Audit log entries cannot be modified.');
        });

        static::deleting(function (): never {
            throw new LogicException('Audit log entries cannot be deleted.');
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
