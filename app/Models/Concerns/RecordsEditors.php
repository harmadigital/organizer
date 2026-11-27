<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Stamps created_by and updated_by with the signed-in user.
 */
trait RecordsEditors
{
    public static function bootRecordsEditors(): void
    {
        static::creating(function (self $record): void {
            $record->created_by ??= Auth::id();
            $record->updated_by ??= Auth::id();
        });

        static::updating(function (self $record): void {
            if (Auth::id() !== null) {
                $record->updated_by = Auth::id();
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
