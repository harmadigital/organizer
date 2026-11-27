<?php

namespace App\Models;

use App\Enums\CommitteeRoleKind;
use App\Enums\CommitteeRoleScope;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\RecordsEditors;
use Database\Factories\CommitteeRoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A position in an event committee (Ketua, Sekretaris, Koordinator Seksi, ...).
 */
class CommitteeRole extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<CommitteeRoleFactory> */
    use HasFactory;

    use RecordsEditors;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'key',
        'name',
        'kind',
        'scope',
        'description',
        'is_required_default',
        'is_singleton_default',
        'sort_order',
        'is_active',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'kind' => CommitteeRoleKind::Custom->value,
        'scope' => CommitteeRoleScope::Event->value,
        'is_required_default' => false,
        'is_singleton_default' => false,
        'sort_order' => 0,
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => CommitteeRoleKind::class,
            'scope' => CommitteeRoleScope::class,
            'is_required_default' => 'boolean',
            'is_singleton_default' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CommitteeRoleAbility, $this>
     */
    public function abilities(): HasMany
    {
        return $this->hasMany(CommitteeRoleAbility::class);
    }

    /**
     * @return list<string>
     */
    public function abilityValues(): array
    {
        return $this->abilities()->orderBy('ability')->pluck('ability')->all();
    }
}
