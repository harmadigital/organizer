<?php

namespace App\Http\Controllers\Events;

use App\Actions\Work\AddTaskDependency;
use App\Actions\Work\AttachTaskDocument;
use App\Actions\Work\CompleteTask;
use App\Actions\Work\DetachTaskDocument;
use App\Actions\Work\PostTaskComment;
use App\Actions\Work\RemoveTaskDependency;
use App\Actions\Work\SaveTask;
use App\Actions\Work\SaveTaskResult;
use App\Enums\TaskPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\Work\AddTaskDependencyRequest;
use App\Http\Requests\Work\CompleteTaskRequest;
use App\Http\Requests\Work\PostTaskCommentRequest;
use App\Http\Requests\Work\SaveTaskRequest;
use App\Http\Requests\Work\SaveTaskResultRequest;
use App\Http\Requests\Work\UploadTaskDocumentRequest;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventMembership;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\DisplayTime;
use App\Services\TaskAuthority;
use App\Services\WorkProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * One task: its page, editing, completion with its result and files, status changes and prerequisites.
 */
class TaskController extends Controller
{
    public function __construct(private TaskAuthority $authority) {}

    public function store(SaveTaskRequest $request, Event $event, SaveTask $save): JsonResponse
    {
        $task = $save->handle($event, new Task, $request->taskAttributes(), $request->assigneeIds(), $request->user());

        return $this->jsonSuccess(['id' => $task->getKey(), 'url' => route('events.tasks.detail', [$event, $task])], 201);
    }

    public function detail(Request $request, Event $event, Task $task, WorkProgress $progress, DisplayTime $time): View
    {
        abort_unless($this->authority->canView($request->user(), $task), 403);

        $actor = $request->user();
        $task->load([
            'division:id,name',
            'program:id,title',
            'parent:id,title,status',
            'owner.user:id,name',
            'assignees.user:id,name',
            'children' => fn ($children) => $children->with('owner.user:id,name')->orderBy('id'),
            'dependencies:id,title,status',
            'dependents:id,title,status',
            'documents' => fn ($documents) => $documents->with(['latestVersion', 'owner:id,name'])->orderByDesc('document_links.id'),
            'statusChanges.actor:id,name',
            'comments.author:id,name',
        ]);

        $canManage = $this->authority->canManage($actor, $task);

        return view('events.task', [
            'event' => $event,
            'task' => $task,
            'today' => now($time->timezone())->startOfDay(),
            'canComplete' => $this->authority->canComplete($actor, $task),
            'canComment' => $this->authority->canComment($actor, $task),
            'subtaskProgress' => $task->children->isEmpty() ? null : $progress->of($task->children),
            'canManage' => $canManage,
            'canWork' => $this->authority->canWork($actor, $task),
            'canCreateSubtask' => $canManage && $task->parent_id === null,
            'priorities' => TaskPriority::cases(),
            'divisions' => $event->divisions()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'maxUploadKilobytes' => UploadTaskDocumentRequest::MAX_KILOBYTES,
            'allowedExtensions' => UploadTaskDocumentRequest::ALLOWED_EXTENSIONS,
            'maxFiles' => UploadTaskDocumentRequest::MAX_FILES,
            'selfOnlyMembership' => $this->authority->canCreateTasks($actor, $event) ? null : ['id' => $this->authority->ownMembershipId($actor, $event), 'name' => $actor->name],
        ]);
    }

    /**
     * The task as the edit form needs it, with labels for the remote dropdowns.
     */
    public function show(Request $request, Event $event, Task $task): JsonResponse
    {
        abort_unless($this->authority->canManage($request->user(), $task), 403);

        $task->load(['owner.user:id,name', 'assignees.user:id,name', 'program:id,title', 'parent:id,title']);

        return $this->jsonSuccess([
            ...$task->only(['id', 'title', 'description', 'division_id', 'work_program_id', 'parent_id', 'owner_membership_id', 'weight']),
            'priority' => $task->priority->value,
            'starts_on' => $task->starts_on?->toDateString(),
            'due_on' => $task->due_on?->toDateString(),
            'owner_membership_id_label' => $task->owner?->user->name,
            'work_program_id_label' => $task->program?->title,
            'parent_id_label' => $task->parent?->title,
            'assignees' => $task->assignees->map(fn (EventMembership $assignee) => ['id' => $assignee->getKey(), 'text' => $assignee->user->name])->values(),
        ]);
    }

    public function update(SaveTaskRequest $request, Event $event, Task $task, SaveTask $save): JsonResponse
    {
        $save->handle($event, $task, $request->taskAttributes(), $request->assigneeIds(), $request->user());

        return $this->jsonSuccess();
    }

    public function addDependency(AddTaskDependencyRequest $request, Event $event, Task $task, AddTaskDependency $add): JsonResponse
    {
        $add->handle($task, $event->tasks()->findOrFail($request->validated('depends_on_task_id')), $request->user());

        return $this->jsonSuccess(null, 201);
    }

    /**
     * The {dependency} parameter is resolved through the task's own prerequisites.
     */
    public function removeDependency(Request $request, Event $event, Task $task, Task $dependency, RemoveTaskDependency $remove): JsonResponse
    {
        abort_unless($this->authority->canManage($request->user(), $task), 403);

        $remove->handle($task, $dependency, $request->user());

        return $this->jsonSuccess();
    }

    public function postComment(PostTaskCommentRequest $request, Event $event, Task $task, PostTaskComment $post): JsonResponse
    {
        $comment = $post->handle($task, $request->user(), $request->validated('body'));

        return $this->jsonSuccess(['id' => $comment->getKey()], 201);
    }

    /**
     * The {comment} parameter is resolved through the task's own comments.
     */
    public function deleteComment(Request $request, Event $event, Task $task, TaskComment $comment): JsonResponse
    {
        abort_unless($this->authority->canDeleteComment($request->user(), $comment), 403);

        $comment->delete();

        return $this->jsonSuccess();
    }

    /**
     * Mark the task finished with its result and any photos or documents, in one step.
     */
    public function complete(CompleteTaskRequest $request, Event $event, Task $task, CompleteTask $complete): JsonResponse
    {
        $task = $complete->handle($task, $request->validated('result'), $request->uploadedFiles(), $request->user());

        return $this->jsonSuccess(['status' => $task->status->value, 'label' => $task->status->label()]);
    }

    public function saveResult(SaveTaskResultRequest $request, Event $event, Task $task, SaveTaskResult $save): JsonResponse
    {
        $save->handle($task, $request->validated('result'), $request->user());

        return $this->jsonSuccess();
    }

    public function uploadDocument(UploadTaskDocumentRequest $request, Event $event, Task $task, AttachTaskDocument $attach): JsonResponse
    {
        $documents = $attach->handleMany($task, $request->uploadedFiles(), $request->user());

        return $this->jsonSuccess(['count' => count($documents)], 201);
    }

    /**
     * Files are on the private disk; this is the only way to fetch one.
     */
    public function downloadDocument(Request $request, Event $event, Task $task, Document $document): StreamedResponse
    {
        abort_unless($this->authority->canView($request->user(), $task), 403);

        $version = $document->latestVersion;
        abort_if($version === null || ! Storage::disk($version->disk)->exists($version->path), 404);

        // Photos may be shown on the page as a preview; everything else is downloaded.
        if ($request->boolean('preview') && in_array($version->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return Storage::disk($version->disk)->response($version->path, $version->original_name);
        }

        return Storage::disk($version->disk)->download($version->path, $version->original_name);
    }

    /**
     * Whoever uploaded the file, or a task manager, may take it off the task.
     */
    public function deleteDocument(Request $request, Event $event, Task $task, Document $document, DetachTaskDocument $detach): JsonResponse
    {
        $isUploader = $document->owner_user_id === $request->user()->getKey();

        abort_unless($task->event->isEditable() && (($isUploader && $this->authority->canWork($request->user(), $task)) || $this->authority->canManage($request->user(), $task)), 403);

        $detach->handle($task, $document, $request->user());

        return $this->jsonSuccess();
    }
}
