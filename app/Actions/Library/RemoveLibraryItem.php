<?php

namespace App\Actions\Library;

use App\Models\Document;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class RemoveLibraryItem
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Take an item out of the library. The document is archived (soft deleted); a stored file
     * stays for the audit trail.
     */
    public function handle(Document $document, User $actor): void
    {
        DB::transaction(function () use ($document, $actor): void {
            $document->delete();

            $this->audit->record(
                action: 'library.removed',
                auditable: $document,
                before: ['title' => $document->title, 'kind' => $document->kind, 'url' => $document->url],
                eventId: $document->event_id,
                organizationId: $document->organization_id,
                actor: $actor,
            );
        });
    }
}
