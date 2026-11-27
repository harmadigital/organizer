<?php

namespace App\Actions\Meetings;

use App\Models\Meeting;
use App\Models\MeetingComment;
use App\Models\User;

class PostMeetingComment
{
    public function handle(Meeting $meeting, User $author, string $body): MeetingComment
    {
        return $meeting->comments()->create([
            'event_id' => $meeting->event_id,
            'user_id' => $author->getKey(),
            'body' => trim($body),
        ]);
    }
}
