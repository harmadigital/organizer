<?php

namespace App\Enums;

/**
 * The few moments a committee member is told about in the lonceng. Stored as the type of a
 * notifications row; written only by App\Services\CommitteeNotifier.
 */
enum NoticeKind: string
{
    case CommitteeInvited = 'committee.invited';
    case TaskAssigned = 'task.assigned';
    case TaskCommented = 'task.commented';
    case TaskDueTomorrow = 'task.due';
    case MeetingScheduled = 'meeting.scheduled';
    case MeetingChanged = 'meeting.changed';
    case MeetingCancelled = 'meeting.cancelled';
    case MeetingTomorrow = 'meeting.tomorrow';
    case MinutesPublished = 'minutes.published';

    /**
     * Keenicons name shown in the circle next to the notice.
     */
    public function icon(): string
    {
        return match ($this) {
            self::CommitteeInvited => 'ki-people',
            self::TaskAssigned, self::TaskDueTomorrow => 'ki-check-square',
            self::TaskCommented => 'ki-message-text-2',
            self::MeetingScheduled, self::MeetingChanged, self::MeetingCancelled, self::MeetingTomorrow => 'ki-calendar',
            self::MinutesPublished => 'ki-document',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::TaskDueTomorrow, self::MeetingTomorrow => 'warning',
            self::MeetingCancelled => 'danger',
            self::MinutesPublished, self::CommitteeInvited => 'success',
            default => 'primary',
        };
    }
}
