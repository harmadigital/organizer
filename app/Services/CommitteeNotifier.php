<?php

namespace App\Services;

use App\Enums\MembershipStatus;
use App\Enums\NoticeKind;
use App\Enums\UserStatus;
use App\Models\CommitteeRole;
use App\Models\Division;
use App\Models\Event;
use App\Models\EventMembership;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/**
 * The only place notices for the lonceng are written, one method per fact.
 *
 * Notices go to active committee members who can sign in (an invitation goes to the invited
 * person), never to the person who acted, and never for an event that is no longer editable.
 * They are written right after the surrounding transaction commits, so a rolled-back change
 * tells nobody. Each row's id is derived from the fact and the person (UUIDv5), so writing the
 * same fact twice adds nothing.
 *
 * A notice carries only titles, names, dates and places: never comment or result text.
 *
 * Reminders for tomorrow are written when the person opens the app (remindersFor), because
 * notices are only seen inside the app anyway; no scheduler or queue worker is needed.
 */
class CommitteeNotifier
{
    /**
     * Namespace of the notice ids. It must never change, or notices written before and after
     * the change would no longer be recognised as the same.
     */
    private const ID_NAMESPACE = '6f1d3c52-9b7e-4d0a-8e45-2c7a1b9e4f60';

    public function __construct(private DisplayTime $time) {}

    public function invited(EventMembership $membership, CommitteeRole $role, ?Division $division, User $actor): void
    {
        $event = $membership->event;

        if ($membership->status !== MembershipStatus::Invited || ! $membership->user?->canSignIn()) {
            return;
        }

        $this->send(
            kind: NoticeKind::CommitteeInvited,
            event: $event,
            userIds: [$membership->user_id],
            title: 'Anda diundang menjadi '.$role->name.' di '.$event->name,
            line: $division?->name ?? 'Buka daftar event untuk menerima atau menolak',
            path: route('events.index', absolute: false),
            subject: null,
            key: 'invite:'.$membership->getKey().':'.($membership->invited_at?->getTimestamp() ?? 0),
            actor: $actor,
        );
    }

    /**
     * @param  list<int>  $membershipIds  people who just became owner or collaborator
     */
    public function taskAssigned(Task $task, array $membershipIds, User $actor): void
    {
        $this->send(
            kind: NoticeKind::TaskAssigned,
            event: $task->event,
            userIds: $this->usersOfMemberships($task->event, $membershipIds),
            title: 'Tugas baru untuk Anda: '.$task->title,
            line: $task->due_on ? 'Tenggat '.$this->day($task->due_on) : null,
            path: route('events.tasks.detail', [$task->event_id, $task], absolute: false),
            subject: $this->taskSubject($task),
            key: 'task.assigned:'.$task->getKey(),
            actor: $actor,
        );
    }

    /**
     * The owner, collaborators and creator of the task, and everyone who commented before.
     */
    public function taskCommented(TaskComment $comment): void
    {
        $task = $comment->task;
        $involved = collect([$task->created_by])
            ->merge($this->usersOfMemberships($task->event, [$task->owner_membership_id, ...$task->assignees()->pluck('event_memberships.id')->all()]))
            ->merge($task->comments()->where('id', '<', $comment->getKey())->pluck('user_id'));

        $this->send(
            kind: NoticeKind::TaskCommented,
            event: $task->event,
            userIds: $this->activeMembersAmong($task->event, $involved->filter()->all()),
            title: $comment->author?->name.' mengomentari tugas: '.$task->title,
            line: null,
            path: route('events.tasks.detail', [$task->event_id, $task], absolute: false).'#komentar',
            subject: $this->taskSubject($task),
            key: 'task.comment:'.$comment->getKey(),
            actor: $comment->author,
        );
    }

    public function meetingScheduled(Meeting $meeting, User $actor): void
    {
        if ($this->isPast($meeting)) {
            return;
        }

        $this->send(
            kind: NoticeKind::MeetingScheduled,
            event: $meeting->event,
            userIds: $this->meetingAudience($meeting),
            title: 'Rapat baru: '.$meeting->title,
            line: $this->meetingLine($meeting),
            path: route('events.meetings.detail', [$meeting->event_id, $meeting], absolute: false),
            subject: $this->meetingSubject($meeting),
            key: 'meeting.new:'.$meeting->getKey(),
            actor: $actor,
        );
    }

    /**
     * Told only when the day, time, place or division changed; the key holds the new values,
     * so saving the same change twice tells nobody twice.
     */
    public function meetingChanged(Meeting $meeting, User $actor): void
    {
        if ($this->isPast($meeting)) {
            return;
        }

        $this->send(
            kind: NoticeKind::MeetingChanged,
            event: $meeting->event,
            userIds: $this->meetingAudience($meeting),
            title: 'Jadwal rapat berubah: '.$meeting->title,
            line: $this->meetingLine($meeting),
            path: route('events.meetings.detail', [$meeting->event_id, $meeting], absolute: false),
            subject: $this->meetingSubject($meeting),
            key: 'meeting.changed:'.$meeting->getKey().':'.$this->meetingSchedule($meeting),
            actor: $actor,
        );
    }

    /**
     * Before the meeting row is deleted: its earlier notices go, and the audience of an
     * upcoming meeting is told it will not take place.
     */
    public function meetingCancelled(Meeting $meeting, User $actor): void
    {
        $this->forgetSubject($this->meetingSubject($meeting));

        if ($this->isPast($meeting)) {
            return;
        }

        $this->send(
            kind: NoticeKind::MeetingCancelled,
            event: $meeting->event,
            userIds: $this->meetingAudience($meeting),
            title: 'Rapat dibatalkan: '.$meeting->title,
            line: $this->day($meeting->meets_on).' · '.$meeting->timeLabel(),
            path: route('events.meetings', $meeting->event_id, absolute: false),
            subject: null,
            key: 'meeting.cancelled:'.$meeting->getKey(),
            actor: $actor,
        );
    }

    /**
     * Only the first save of the minutes tells anyone; later corrections are silent.
     */
    public function minutesPublished(Meeting $meeting, User $actor): void
    {
        $this->send(
            kind: NoticeKind::MinutesPublished,
            event: $meeting->event,
            userIds: $this->meetingAudience($meeting),
            title: 'Notulen tersedia: '.$meeting->title,
            line: $this->day($meeting->meets_on),
            path: route('events.meetings.detail', [$meeting->event_id, $meeting], absolute: false).'#notulen',
            subject: $this->meetingSubject($meeting),
            key: 'minutes:'.$meeting->getKey(),
            actor: $actor,
        );
    }

    /**
     * Notices about something that no longer exists, for everyone.
     */
    public function forgetSubject(string $subject): void
    {
        DB::table('notifications')->where('subject', $subject)->delete();
    }

    /**
     * Tomorrow's task deadlines and meetings for one person, written at most once an hour.
     */
    public function remindersFor(User $user): void
    {
        $now = CarbonImmutable::now($this->time->timezone());

        if (! Cache::add('notices.reminders.'.$user->getKey().'.'.$now->format('YmdH'), true, 3600)) {
            return;
        }

        $tomorrow = $now->startOfDay()->addDay();
        $memberships = EventMembership::query()
            ->where('user_id', $user->getKey())
            ->where('status', MembershipStatus::Active)
            ->with(['event', 'activeAssignments:id,event_membership_id,division_id'])
            ->get()
            ->filter(fn (EventMembership $membership): bool => $membership->event->isEditable());

        if ($memberships->isEmpty()) {
            return;
        }

        $membershipIds = $memberships->modelKeys();
        $events = $memberships->pluck('event')->keyBy('id');

        Task::query()
            ->open()
            ->whereIn('event_id', $events->keys())
            ->where('due_on', '>=', $tomorrow->toDateString())
            ->where('due_on', '<', $tomorrow->addDay()->toDateString())
            ->where(fn (Builder $query) => $query
                ->whereIn('owner_membership_id', $membershipIds)
                ->orWhereHas('assignees', fn (Builder $assignees) => $assignees->whereIn('event_memberships.id', $membershipIds)))
            ->get(['id', 'event_id', 'title', 'due_on'])
            ->each(function (Task $task) use ($user, $events): void {
                $task->setRelation('event', $events[$task->event_id]);
                $this->send(
                    kind: NoticeKind::TaskDueTomorrow,
                    event: $task->event,
                    userIds: [$user->getKey()],
                    title: 'Tenggat besok: '.$task->title,
                    line: $this->day($task->due_on),
                    path: route('events.tasks.detail', [$task->event_id, $task], absolute: false),
                    subject: $this->taskSubject($task),
                    key: 'task.due:'.$task->getKey().':'.$task->due_on->toDateString(),
                    actor: null,
                );
            });

        Meeting::query()
            ->whereIn('event_id', $events->keys())
            ->where('meets_on', '>=', $tomorrow->toDateString())
            ->where('meets_on', '<', $tomorrow->addDay()->toDateString())
            ->get()
            ->each(function (Meeting $meeting) use ($user, $events): void {
                $meeting->setRelation('event', $events[$meeting->event_id]);

                if (! in_array($user->getKey(), $this->meetingAudience($meeting), true)) {
                    return;
                }

                $this->send(
                    kind: NoticeKind::MeetingTomorrow,
                    event: $meeting->event,
                    userIds: [$user->getKey()],
                    title: 'Besok rapat: '.$meeting->title,
                    line: $this->meetingLine($meeting),
                    path: route('events.meetings.detail', [$meeting->event_id, $meeting], absolute: false),
                    subject: $this->meetingSubject($meeting),
                    key: 'meeting.tomorrow:'.$meeting->getKey().':'.$meeting->meets_on->toDateString(),
                    actor: null,
                );
            });
    }

    /**
     * People a meeting is about: its division and the divisions below it, or the whole
     * committee for a core team meeting (as on the attendance tab).
     *
     * @return list<int>
     */
    public function meetingAudience(Meeting $meeting): array
    {
        if ($meeting->division_id === null) {
            return $this->activeMemberUserIds($meeting->event);
        }

        $division = Division::query()->where('event_id', $meeting->event_id)->find($meeting->division_id);

        return $division === null ? [] : $this->membersOfDivisions($meeting->event, [$division->getKey(), ...$division->descendantIds()]);
    }

    /**
     * @param  iterable<int>  $userIds
     */
    private function send(
        NoticeKind $kind,
        Event $event,
        iterable $userIds,
        string $title,
        ?string $line,
        string $path,
        ?string $subject,
        string $key,
        ?User $actor,
    ): void {
        if (! $event->isEditable()) {
            return;
        }

        $recipients = collect($userIds)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->reject(fn (int $id): bool => $actor !== null && $id === $actor->getKey())
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        $data = json_encode([
            'kind' => $kind->value,
            'event_name' => $event->name,
            'title' => Str::limit($title, 120),
            'line' => $line === null ? null : Str::limit($line, 120),
            'path' => $path,
            'actor_name' => $actor?->name,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $eventId = $event->getKey();
        $notifiableType = (new User)->getMorphClass();

        DB::afterCommit(function () use ($recipients, $kind, $key, $notifiableType, $eventId, $subject, $data): void {
            $now = now();

            DB::table('notifications')->insertOrIgnore($recipients->map(fn (int $userId): array => [
                'id' => Uuid::uuid5(self::ID_NAMESPACE, $key.'|'.$userId)->toString(),
                'type' => $kind->value,
                'notifiable_type' => $notifiableType,
                'notifiable_id' => $userId,
                'event_id' => $eventId,
                'subject' => $subject,
                'data' => $data,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });
    }

    /**
     * Users of active members who can sign in, optionally only those holding a matching
     * active role.
     *
     * @param  (callable(Builder): mixed)|null  $assignmentFilter
     * @return list<int>
     */
    private function activeMemberUserIds(Event $event, ?callable $assignmentFilter = null): array
    {
        return $this->activeMemberships($event)
            ->when($assignmentFilter !== null, fn (Builder $query) => $query->whereHas('activeAssignments', $assignmentFilter))
            ->pluck('user_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $divisionIds
     * @return list<int>
     */
    private function membersOfDivisions(Event $event, array $divisionIds): array
    {
        return $divisionIds === [] ? [] : $this->activeMemberUserIds($event, fn (Builder $assignments) => $assignments->whereIn('division_id', $divisionIds));
    }

    /**
     * @param  list<int|null>  $membershipIds
     * @return list<int>
     */
    private function usersOfMemberships(Event $event, array $membershipIds): array
    {
        $membershipIds = array_values(array_filter($membershipIds));

        return $membershipIds === [] ? [] : $this->activeMemberships($event)->whereKey($membershipIds)->pluck('user_id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * @param  list<int>  $userIds
     * @return list<int>
     */
    private function activeMembersAmong(Event $event, array $userIds): array
    {
        return $userIds === [] ? [] : $this->activeMemberships($event)->whereIn('user_id', $userIds)->pluck('user_id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * @return Builder<EventMembership>
     */
    private function activeMemberships(Event $event): Builder
    {
        return EventMembership::query()
            ->where('event_id', $event->getKey())
            ->where('status', MembershipStatus::Active)
            ->whereHas('user', fn (Builder $users) => $users
                ->where('status', UserStatus::Active)
                ->whereNotNull('username')
                ->whereNotNull('password'));
    }

    private function isPast(Meeting $meeting): bool
    {
        return $meeting->meets_on->lt(CarbonImmutable::now($this->time->timezone())->startOfDay());
    }

    /**
     * "Sabtu, 5 Desember 2026 · 19.00 · Aula"
     */
    private function meetingLine(Meeting $meeting): string
    {
        return collect([$this->day($meeting->meets_on), $meeting->timeLabel(), $meeting->location])->filter()->implode(' · ');
    }

    /**
     * The day, times, place and division of a meeting as one comparable value.
     */
    public function meetingSchedule(Meeting $meeting): string
    {
        return implode('|', [
            $meeting->meets_on->toDateString(),
            substr((string) $meeting->starts_at, 0, 5),
            substr((string) $meeting->ends_at, 0, 5),
            trim((string) $meeting->location),
            (string) $meeting->division_id,
        ]);
    }

    private function day(DateTimeInterface $date): string
    {
        return CarbonImmutable::instance($date)->locale('id')->translatedFormat('l, j F Y');
    }

    private function taskSubject(Task $task): string
    {
        return 'task:'.$task->getKey();
    }

    private function meetingSubject(Meeting $meeting): string
    {
        return 'meeting:'.$meeting->getKey();
    }
}
