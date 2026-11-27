<?php

namespace App\Services;

use App\Enums\EventAbility;
use App\Enums\TaskAction;
use App\Models\Event;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\WorkProgram;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Who may see and change work programs and tasks inside an event.
 *
 * - Every active committee member sees every program and task of the event, whatever their
 *   division; so does the organization through ProgramView / TaskView.
 * - Every active committee member may comment on any task.
 * - Changing a task ("Ubah") needs TaskManage for its division, or event-wide when the task
 *   belongs to no division (the core team: Ketua, Wakil, Sekretaris, Bendahara).
 * - A core team member who works on tasks event-wide but may not manage them (by default the
 *   Bendahara) may add and change core team tasks they own themselves.
 * - Core team programs (no division) are managed by whoever manages programs or tasks
 *   event-wide; division programs by ProgramManage for that division.
 * - Finishing a task ("Selesai") and keeping its result and files up to date needs a task
 *   manager, or TaskWork held by the task's owner or a collaborator. There is no review.
 * - Nothing changes once the event is completed, cancelled or archived.
 */
class TaskAuthority
{
    public function __construct(private EventAccess $access) {}

    public function canViewWork(User $user, Event $event): bool
    {
        $permissions = $this->access->for($user, $event);

        return $permissions->isActiveMember()
            || $permissions->hasAnywhere(EventAbility::ProgramView)
            || $permissions->hasAnywhere(EventAbility::TaskView);
    }

    /**
     * Whether the person may create tasks somewhere in the event; decides if the "add" button shows.
     */
    public function canCreateTasks(User $user, Event $event): bool
    {
        return $event->isEditable() && $this->access->for($user, $event)->hasAnywhere(EventAbility::TaskManage);
    }

    /**
     * Core team members who may not manage tasks but do work on them event-wide may still
     * add tasks for themselves.
     */
    public function canCreateOwnTasks(User $user, Event $event): bool
    {
        $permissions = $this->access->for($user, $event);

        return $event->isEditable() && $permissions->isActiveMember() && $permissions->has(EventAbility::TaskWork);
    }

    public function ownMembershipId(User $user, Event $event): ?int
    {
        $permissions = $this->access->for($user, $event);

        return $permissions->isActiveMember() ? $permissions->membership->getKey() : null;
    }

    public function canCreatePrograms(User $user, Event $event): bool
    {
        $permissions = $this->access->for($user, $event);

        return $event->isEditable()
            && ($permissions->hasAnywhere(EventAbility::ProgramManage) || $permissions->has(EventAbility::TaskManage));
    }

    public function canManageTasksIn(User $user, Event $event, ?int $divisionId): bool
    {
        return $event->isEditable() && $this->access->for($user, $event)->has(EventAbility::TaskManage, $divisionId);
    }

    /**
     * Without a division the program belongs to the core team.
     */
    public function canManageProgramsIn(User $user, Event $event, ?int $divisionId): bool
    {
        if (! $event->isEditable()) {
            return false;
        }

        $permissions = $this->access->for($user, $event);

        return $divisionId === null
            ? $permissions->has(EventAbility::ProgramManage) || $permissions->has(EventAbility::TaskManage)
            : $permissions->has(EventAbility::ProgramManage, $divisionId);
    }

    public function canManageProgram(User $user, WorkProgram $program): bool
    {
        return $this->canManageProgramsIn($user, $program->event, $program->division_id);
    }

    public function canView(User $user, Task $task): bool
    {
        $permissions = $this->access->for($user, $task->event);

        return $permissions->isActiveMember() || $permissions->has(EventAbility::TaskView, $task->division_id);
    }

    public function canManage(User $user, Task $task): bool
    {
        return $this->canManageTasksIn($user, $task->event, $task->division_id) || $this->ownsCoreTask($user, $task);
    }

    /**
     * Whether someone who may only add tasks for themselves may add this one: a core team
     * task (no division) that they own.
     */
    public function mayCreateAsOwnTask(User $user, Event $event, ?int $divisionId, ?int $ownerMembershipId): bool
    {
        return $divisionId === null
            && $this->canCreateOwnTasks($user, $event)
            && $ownerMembershipId !== null
            && $ownerMembershipId === $this->ownMembershipId($user, $event);
    }

    private function ownsCoreTask(User $user, Task $task): bool
    {
        return $this->mayCreateAsOwnTask($user, $task->event, $task->division_id, $task->owner_membership_id);
    }

    /**
     * Finishing the task and maintaining its result and files.
     */
    public function canWork(User $user, Task $task): bool
    {
        if (! $task->event->isEditable()) {
            return false;
        }

        if ($this->canManage($user, $task)) {
            return true;
        }

        $permissions = $this->access->for($user, $task->event);

        return $permissions->isActiveMember()
            && $permissions->hasAnywhere(EventAbility::TaskWork)
            && $task->isWorkedOnBy($permissions->membership);
    }

    /**
     * Whether the "Selesai" button shows: the task is still open and the person may finish it.
     */
    public function canComplete(User $user, Task $task): bool
    {
        return in_array($task->status, TaskAction::Complete->startsFrom(), true) && $this->canWork($user, $task);
    }

    /**
     * Any active committee member, and whoever manages the task, may comment.
     */
    public function canComment(User $user, Task $task): bool
    {
        return $task->event->isEditable()
            && ($this->access->for($user, $task->event)->isActiveMember() || $this->canManage($user, $task));
    }

    /**
     * A comment is removed by its author, or by a task manager.
     */
    public function canDeleteComment(User $user, TaskComment $comment): bool
    {
        return $comment->task->event->isEditable()
            && ($comment->user_id === $user->getKey() || $this->canManage($user, $comment->task));
    }

    /**
     * Tasks of the event the person may see: all of them for committee members and for
     * event-wide TaskView, otherwise those of the divisions where TaskView is held.
     *
     * @return HasMany<Task, Event>
     */
    public function visibleTasks(User $user, Event $event): HasMany
    {
        $permissions = $this->access->for($user, $event);
        $query = $event->tasks();

        if ($permissions->isActiveMember() || $permissions->has(EventAbility::TaskView)) {
            return $query;
        }

        $divisionIds = $permissions->divisionsWith(EventAbility::TaskView);

        return $query->whereIn('division_id', $divisionIds === [] ? [0] : $divisionIds);
    }

    /**
     * @return HasMany<WorkProgram, Event>
     */
    public function visiblePrograms(User $user, Event $event): HasMany
    {
        $permissions = $this->access->for($user, $event);
        $query = $event->programs();

        if ($permissions->isActiveMember() || $permissions->has(EventAbility::ProgramView)) {
            return $query;
        }

        $divisionIds = $permissions->divisionsWith(EventAbility::ProgramView);

        return $query->whereIn('division_id', $divisionIds === [] ? [0] : $divisionIds);
    }
}
