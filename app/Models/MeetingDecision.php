<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A decision recorded in a meeting's minutes.
 */
class MeetingDecision extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['event_id', 'meeting_id', 'body', 'sort_order'];
}
