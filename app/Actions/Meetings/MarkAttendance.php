<?php

namespace App\Actions\Meetings;

use App\Models\EventMembership;
use App\Models\Meeting;
use App\Services\DisplayTime;
use Illuminate\Validation\ValidationException;

/**
 * Mark one committee member present or not present. Members never marked count as absent.
 * Only possible on the meeting day until the meeting is over; after that it is locked.
 */
class MarkAttendance
{
    public function __construct(private DisplayTime $time) {}

    /**
     * @return int how many members are now marked present
     *
     * @throws ValidationException
     */
    public function handle(Meeting $meeting, EventMembership $membership, bool $present): int
    {
        $state = $meeting->attendanceState(now(), $this->time->timezone());

        if ($state !== 'open') {
            throw ValidationException::withMessages([
                'attendance' => $state === 'locked'
                    ? 'Absensi rapat ini sudah dikunci karena rapat sudah selesai.'
                    : 'Absensi baru dapat diisi pada hari rapat.',
            ]);
        }

        if ($present) {
            $meeting->attendees()->syncWithoutDetaching([$membership->getKey() => ['event_id' => $meeting->event_id]]);
        } else {
            $meeting->attendees()->detach($membership->getKey());
        }

        return $meeting->attendees()->count();
    }
}
