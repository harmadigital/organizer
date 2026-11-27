<?php

namespace App\Actions\Work;

use App\Models\Document;
use App\Models\Task;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachTaskDocument
{
    /**
     * Private disk: files are only ever served through an authorized download route.
     */
    public const DISK = 'local';

    public function __construct(private AuditLogger $audit) {}

    /**
     * Store an uploaded file as a new document and link it to the task as evidence.
     */
    public function handle(Task $task, UploadedFile $file, ?string $title, User $actor): Document
    {
        $event = $task->event;
        $path = $file->storeAs(
            'events/'.$event->getKey().'/documents',
            Str::uuid()->toString().'.'.strtolower($file->guessExtension() ?? $file->getClientOriginalExtension()),
            self::DISK,
        );

        try {
            return DB::transaction(function () use ($task, $event, $file, $title, $actor, $path): Document {
                $document = Document::create([
                    'organization_id' => $event->organization_id,
                    'event_id' => $event->getKey(),
                    'title' => filled($title) ? trim($title) : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'category' => 'evidence',
                    'owner_user_id' => $actor->getKey(),
                ]);

                $version = $document->versions()->create([
                    'version_no' => 1,
                    'disk' => self::DISK,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                    'uploaded_by' => $actor->getKey(),
                ]);

                $task->documents()->attach($document->getKey(), ['purpose' => 'evidence', 'linked_by' => $actor->getKey()]);

                $this->audit->record(
                    action: 'documents.uploaded',
                    auditable: $document,
                    after: [
                        'title' => $document->title,
                        'task_id' => $task->getKey(),
                        'original_name' => $version->original_name,
                        'size_bytes' => $version->size_bytes,
                        'sha256' => $version->sha256,
                    ],
                    eventId: $event->getKey(),
                    organizationId: $event->organization_id,
                    actor: $actor,
                );

                return $document;
            });
        } catch (\Throwable $exception) {
            Storage::disk(self::DISK)->delete($path);

            throw $exception;
        }
    }

    /**
     * Attach several files at once: either all are kept or none is, including the stored files.
     *
     * @param  list<UploadedFile>  $files
     * @return list<Document>
     */
    public function handleMany(Task $task, array $files, User $actor): array
    {
        $documents = [];

        try {
            DB::transaction(function () use ($task, $files, $actor, &$documents): void {
                foreach ($files as $file) {
                    $documents[] = $this->handle($task, $file, null, $actor);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk(self::DISK)->delete(array_map(fn (Document $document) => $document->latestVersion->path, $documents));

            throw $exception;
        }

        return $documents;
    }
}
