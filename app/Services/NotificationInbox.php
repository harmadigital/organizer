<?php

namespace App\Services;

use App\Enums\MembershipStatus;
use App\Enums\NoticeKind;
use App\Models\EventMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Reading the lonceng: a person's own notices, limited to events where they are still
 * invited or active (notices of an event they left disappear).
 */
class NotificationInbox
{
    public function __construct(private DisplayTime $time) {}

    /**
     * @return MorphMany<DatabaseNotification, User>
     */
    public function visibleTo(User $user): MorphMany
    {
        $relation = $user->notifications();

        $relation->where(fn (Builder $query) => $query
            ->whereNull('event_id')
            ->orWhereIn('event_id', EventMembership::query()
                ->select('event_id')
                ->where('user_id', $user->getKey())
                ->whereIn('status', [MembershipStatus::Invited, MembershipStatus::Active])));

        return $relation;
    }

    public function unreadCount(User $user): int
    {
        return $this->visibleTo($user)->whereNull('read_at')->count();
    }

    /**
     * @return list<array{id: string, kind: NoticeKind, title: string, line: ?string, event_name: string, when: string, day: string, is_unread: bool}>
     */
    public function latest(User $user, int $limit = 8): array
    {
        return $this->visibleTo($user)->limit($limit)->get()->map(fn (DatabaseNotification $notice): array => $this->present($notice))->all();
    }

    /**
     * One notice as the lonceng and the Notifikasi page show it.
     *
     * @return array{id: string, kind: NoticeKind, title: string, line: ?string, event_name: string, when: string, day: string, is_unread: bool}
     */
    public function present(DatabaseNotification $notice): array
    {
        $local = $this->time->toLocal($notice->created_at);
        $today = now($this->time->timezone())->startOfDay();

        return [
            'id' => $notice->id,
            'kind' => NoticeKind::tryFrom($notice->type) ?? NoticeKind::TaskAssigned,
            'title' => (string) ($notice->data['title'] ?? ''),
            'line' => $notice->data['line'] ?? null,
            'event_name' => (string) ($notice->data['event_name'] ?? ''),
            'when' => $local->diffForHumans(),
            'day' => match (true) {
                $local->isSameDay($today) => 'Hari ini',
                $local->isSameDay($today->subDay()) => 'Kemarin',
                default => $local->translatedFormat('l, j F Y'),
            },
            'is_unread' => $notice->read_at === null,
        ];
    }

    public function markAllRead(User $user): void
    {
        $this->visibleTo($user)->whereNull('read_at')->update(['read_at' => now()]);
    }

    /**
     * Opening a task or meeting page clears that person's notices about it.
     */
    public function markSubjectRead(User $user, string $subject): void
    {
        $user->notifications()->whereNull('read_at')->where('subject', $subject)->update(['read_at' => now()]);
    }
}
