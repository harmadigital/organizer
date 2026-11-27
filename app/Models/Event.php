<?php

namespace App\Models;

use App\Enums\EventPhase;
use App\Enums\EventStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\RecordsEditors;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use RecordsEditors;

    /**
     * The attributes that are mass assignable. Status and phase change only through ChangeEventStatus.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'event_category_id',
        'venue_id',
        'organization_unit_id',
        'responsible_user_id',
        'code',
        'name',
        'theme',
        'subtheme',
        'description',
        'background',
        'target_participants',
        'starts_on',
        'ends_on',
        'committee_starts_on',
        'committee_ends_on',
        'location_text',
        'budget_target_amount',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'phase' => 'initiation',
        'currency_code' => 'IDR',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'phase' => EventPhase::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'committee_starts_on' => 'date',
            'committee_ends_on' => 'date',
            'target_participants' => 'integer',
            'budget_target_amount' => 'integer',
            'activated_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * "17 Agustus 2026" for one day, "24 Des 2026 – 26 Des 2026" for several.
     */
    public function dateRangeLabel(): string
    {
        $starts = $this->starts_on->locale('id');
        $ends = $this->ends_on->locale('id');

        return $starts->isSameDay($ends)
            ? $starts->translatedFormat('j F Y')
            : $starts->translatedFormat('j M Y').' – '.$ends->translatedFormat('j M Y');
    }

    /**
     * @return BelongsTo<EventCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * @return BelongsTo<OrganizationUnit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnit::class, 'organization_unit_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * @return HasMany<EventObjective, $this>
     */
    public function objectives(): HasMany
    {
        return $this->hasMany(EventObjective::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<EventStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(EventStatusHistory::class)->latest('id');
    }

    /**
     * @return HasMany<EventRoleRule, $this>
     */
    public function roleRules(): HasMany
    {
        return $this->hasMany(EventRoleRule::class);
    }

    /**
     * Route parameter {division} is resolved through this relation, so it must belong to the event.
     *
     * @return HasMany<Division, $this>
     */
    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    /**
     * Route parameter {membership} is resolved through this relation.
     *
     * @return HasMany<EventMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(EventMembership::class);
    }

    /**
     * Route parameter {assignment} is resolved through this relation.
     *
     * @return HasMany<EventRoleAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(EventRoleAssignment::class);
    }
}
