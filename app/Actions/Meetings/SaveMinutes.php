<?php

namespace App\Actions\Meetings;

use App\Actions\Work\SaveTask;
use App\Enums\TaskPriority;
use App\Enums\TaskSize;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\RichText;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Write a meeting's minutes in one go: the formatted text, the decisions and the follow-ups.
 * Saving publishes it to the whole committee; there is no review step. Attendance is kept
 * separately (MarkAttendance).
 *
 * The text comes from the editor and is sanitized before it is stored.
 *
 * Every new follow-up becomes exactly one task in the meeting's division (or the core team),
 * with its owner and due date. Follow-ups already turned into tasks are left alone: the task
 * carries on by itself.
 *
 * The caller sends the minutes version it started from. Saving from an older version is
 * refused, so a double submit or two people writing at once never creates duplicate tasks.
 */
class SaveMinutes
{
    public function __construct(
        private AuditLogger $audit,
        private SaveTask $saveTask,
        private RichText $richText,
    ) {}

    /**
     * @param  list<string>  $decisions
     * @param  list<array{title: string, owner_membership_id: ?int, due_on: ?string}>  $newActionItems
     * @return int number of tasks created
     *
     * @throws ValidationException
     */
    public function handle(Meeting $meeting, string $minutes, array $decisions, array $newActionItems, int $startedFromVersion, User $actor): int
    {
        $minutes = $this->richText->sanitize($minutes);

        return DB::transaction(function () use ($meeting, $minutes, $decisions, $newActionItems, $startedFromVersion, $actor): int {
            $currentVersion = (int) Meeting::query()->whereKey($meeting->getKey())->lockForUpdate()->value('minutes_version');

            if ($currentVersion !== $startedFromVersion) {
                throw ValidationException::withMessages([
                    'minutes' => 'Notulen ini sudah disimpan atau diubah sejak Anda membukanya. Tutup form, muat ulang halaman, lalu periksa kembali.',
                ]);
            }

            $event = $meeting->event;
            $version = $currentVersion + 1;

            $meeting->forceFill([
                'minutes' => $minutes,
                'minutes_version' => $version,
                'minutes_updated_at' => now(),
                'minutes_updated_by' => $actor->getKey(),
            ])->save();

            $meeting->minutesVersions()->create([
                'version_no' => $version,
                'body' => $minutes,
                'created_by' => $actor->getKey(),
            ]);

            $meeting->decisions()->delete();
            foreach (array_values($decisions) as $position => $decision) {
                $meeting->decisions()->create(['event_id' => $event->getKey(), 'body' => $decision, 'sort_order' => $position]);
            }

            $nextPosition = (int) $meeting->actionItems()->max('sort_order') + 1;

            foreach ($newActionItems as $offset => $item) {
                $task = $this->saveTask->handle($event, new Task, [
                    'title' => $item['title'],
                    'description' => 'Tindak lanjut '.$meeting->title.' ('.$meeting->meets_on->locale('id')->translatedFormat('j F Y').').',
                    'division_id' => $meeting->division_id,
                    'owner_membership_id' => $item['owner_membership_id'],
                    'due_on' => $item['due_on'],
                    'priority' => TaskPriority::Medium->value,
                    'weight' => TaskSize::Medium->value,
                ], [], $actor);

                $meeting->actionItems()->create([
                    'event_id' => $event->getKey(),
                    'title' => $item['title'],
                    'owner_membership_id' => $item['owner_membership_id'],
                    'due_on' => $item['due_on'],
                    'task_id' => $task->getKey(),
                    'sort_order' => $nextPosition + $offset,
                ]);
            }

            $this->audit->record(
                action: 'meetings.minutes-saved',
                auditable: $meeting,
                after: [
                    'minutes_version' => $version,
                    'decisions' => count($decisions),
                    'tasks_created' => count($newActionItems),
                ],
                eventId: $event->getKey(),
                organizationId: $event->organization_id,
                actor: $actor,
            );

            return count($newActionItems);
        });
    }
}
