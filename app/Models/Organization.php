<?php

namespace App\Models;

use App\Models\Concerns\RecordsEditors;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    use RecordsEditors;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'short_name',
        'timezone',
        'address',
        'city',
        'phone',
        'email',
        'website',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'timezone' => 'Asia/Jakarta',
        'currency_code' => 'IDR',
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
     * @return HasMany<OrganizationUnit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(OrganizationUnit::class);
    }

    /**
     * @return HasMany<CommitteeRole, $this>
     */
    public function committeeRoles(): HasMany
    {
        return $this->hasMany(CommitteeRole::class);
    }
}
