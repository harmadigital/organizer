<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores uploaded files of an event on the private disk and records them as document versions.
 */
class DocumentStorage
{
    /**
     * Private disk: files are only ever served through an authorized download route.
     */
    public const DISK = 'local';

    /**
     * Put the file under events/{event}/documents with a random name. The caller must delete
     * the returned path if the surrounding transaction fails.
     */
    public function put(Event $event, UploadedFile $file): string
    {
        return $file->storeAs(
            'events/'.$event->getKey().'/documents',
            Str::uuid()->toString().'.'.strtolower($file->guessExtension() ?? $file->getClientOriginalExtension()),
            self::DISK,
        );
    }

    /**
     * Record the stored file as the first version of the document.
     */
    public function recordVersion(Document $document, UploadedFile $file, string $path, User $actor): void
    {
        $document->versions()->create([
            'version_no' => 1,
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by' => $actor->getKey(),
        ]);
    }

    /**
     * @param  list<string>  $paths
     */
    public function forget(array $paths): void
    {
        Storage::disk(self::DISK)->delete($paths);
    }

    /**
     * The title to use when none was given: the file name without its extension.
     */
    public function defaultTitle(UploadedFile $file): string
    {
        return pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    }
}
