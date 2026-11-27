<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\RecordsEditors;
use Database\Factories\OrganizationUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationUnit extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<OrganizationUnitFactory> */
    use HasFactory;

    use RecordsEditors;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'parent_id',
        'name',
        'description',
        'is_active',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
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
            'is_active' => 'boolean',
        ];
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
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Ids of every unit below this one, at any depth.
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $descendantIds = [];
        $parentIds = [$this->getKey()];

        while ($parentIds !== []) {
            $parentIds = self::query()
                ->whereIn('parent_id', $parentIds)
                ->whereNotIn('id', $descendantIds)
                ->pluck('id')
                ->all();

            $descendantIds = [...$descendantIds, ...$parentIds];
        }

        return $descendantIds;
    }
}
