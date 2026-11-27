<?php

namespace App\Http\Controllers\Events;

use App\Actions\Library\AddLibraryItems;
use App\Actions\Library\RemoveLibraryItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Library\AddLibraryItemRequest;
use App\Models\Document;
use App\Models\Event;
use App\Services\DisplayTime;
use App\Services\LibraryAuthority;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The "Media & Dokumen" tab: photos, documents and links every committee member can share.
 */
class LibraryController extends Controller
{
    public function __construct(private LibraryAuthority $authority) {}

    public function index(Request $request, Event $event, DisplayTime $time): View
    {
        Gate::authorize('viewLibrary', $event);

        $actor = $request->user();
        $items = $event->documents()
            ->where('category', Document::LIBRARY)
            ->with(['latestVersion', 'owner:id,name', 'division:id,name'])
            ->latest('id')
            ->get()
            ->each(fn (Document $document) => $document->setRelation('event', $event));

        return view('events.library', [
            'event' => $event,
            'items' => $items,
            'canRemove' => $items->mapWithKeys(fn (Document $document) => [$document->getKey() => $this->authority->canRemove($actor, $document)]),
            'divisions' => $event->divisions()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'canAdd' => $this->authority->canAdd($actor, $event),
            'defaultDivisionId' => $this->authority->defaultDivisionId($actor, $event),
            'time' => $time,
        ]);
    }

    public function store(AddLibraryItemRequest $request, Event $event, AddLibraryItems $add): JsonResponse
    {
        if ($request->validated('kind') === 'link') {
            $add->link($event, $request->validated('url'), $request->validated('title'), $request->divisionId(), $request->user());

            return $this->jsonSuccess(['count' => 1], 201);
        }

        $documents = $add->files($event, $request->uploadedFiles(), $request->validated('title'), $request->divisionId(), $request->user());

        return $this->jsonSuccess(['count' => count($documents)], 201);
    }

    /**
     * Files are on the private disk; this is the only way to fetch one. Photos and PDFs may be
     * opened in the browser; everything else is downloaded.
     */
    public function download(Request $request, Event $event, Document $document): StreamedResponse
    {
        Gate::authorize('viewLibrary', $event);
        abort_unless($document->category === Document::LIBRARY && ! $document->isLink(), 404);

        $version = $document->latestVersion;
        abort_if($version === null || ! Storage::disk($version->disk)->exists($version->path), 404);

        if ($request->boolean('preview') && $document->canPreview()) {
            return Storage::disk($version->disk)->response($version->path, $version->original_name, ['X-Content-Type-Options' => 'nosniff']);
        }

        return Storage::disk($version->disk)->download($version->path, $version->original_name);
    }

    public function delete(Request $request, Event $event, Document $document, RemoveLibraryItem $remove): JsonResponse
    {
        abort_unless($document->category === Document::LIBRARY, 404);
        $document->setRelation('event', $event);
        abort_unless($this->authority->canRemove($request->user(), $document), 403);

        $remove->handle($document, $request->user());

        return $this->jsonSuccess();
    }
}
