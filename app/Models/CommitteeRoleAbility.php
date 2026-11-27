<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitteeRoleAbility extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'committee_role_id',
        'ability',
    ];

    /**
     * @return BelongsTo<CommitteeRole, $this>
     */
    public function committeeRole(): BelongsTo
    {
        return $this->belongsTo(CommitteeRole::class);
    }
}
