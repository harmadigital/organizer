<?php

namespace App\Actions\Library;

use App\Models\Document;
use App\Models\Event;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DocumentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Add photos, documents or a link to the event's shared media library. Several files share
 * the title they were uploaded with; either all are kept or none is.
 */
class AddLibraryItems
{
    public function __construct(
        private AuditLogger $audit,
        private DocumentStorage $storage,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return list<Document>
     */
    public function files(Event $event, array $files, string $title, ?int $divisionId, User $actor): array
    {
        $paths = [];

        try {
            return DB::transaction(function () use ($event, $files, $title, $divisionId, $actor, &$paths): array {
                $documents = [];

                foreach ($files as $file) {
                    $paths[] = $path = $this->storage->put($event, $file);
                    $document = $this->newItem($event, $title, 'file', null, $divisionId, $actor);
                    $this->storage->recordVersion($document, $file, $path, $actor);
                    $documents[] = $document;
                }

                $this->audit->record(
                    action: 'library.files-added',
                    auditable: $documents[0] ?? null,
                    after: ['title' => trim($title), 'division_id' => $divisionId, 'files' => count($files)],
                    eventId: $event->getKey(),
                    organizationId: $event->organization_id,
                    actor: $actor,
                );

                return $documents;
            });
        } catch (\Throwable $exception) {
            $this->storage->forget($paths);

            throw $exception;
        }
    }

    public function link(Event $event, string $url, string $title, ?int $divisionId, User $actor): Document
    {
        return DB::transaction(function () use ($event, $url, $title, $divisionId, $actor): Document {
            $document = $this->newItem($event, $title, 'link', trim($url), $divisionId, $actor);

            $this->audit->record(
                action: 'library.link-added',
                auditable: $document,
                after: ['title' => $document->title, 'url' => $document->url, 'division_id' => $divisionId],
                eventId: $event->getKey(),
                organizationId: $event->organization_id,
                actor: $actor,
            );

            return $document;
        });
    }

    private function newItem(Event $event, string $title, string $kind, ?string $url, ?int $divisionId, User $actor): Document
    {
        return Document::create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->getKey(),
            'division_id' => $divisionId,
            'title' => trim($title),
            'kind' => $kind,
            'url' => $url,
            'category' => Document::LIBRARY,
            'owner_user_id' => $actor->getKey(),
        ]);
    }
}
