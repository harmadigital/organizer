<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A stored file or a link. Task evidence (category "evidence") is referenced by its task
 * through document_links; items of the event's shared media library have category
 * "library". Files live on the private disk and are only served through an authorized route.
 */
class Document extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['organization_id', 'event_id', 'division_id', 'title', 'kind', 'url', 'category', 'sensitivity', 'owner_user_id'];

    public const LIBRARY = 'library';

    public function isLink(): bool
    {
        return $this->kind === 'link';
    }

    public function isImage(): bool
    {
        return ! $this->isLink() && in_array($this->latestVersion?->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    public function isPdf(): bool
    {
        return ! $this->isLink() && $this->latestVersion?->mime_type === 'application/pdf';
    }

    /**
     * Photos and PDFs open in the browser; office documents can only be downloaded.
     */
    public function canPreview(): bool
    {
        return $this->isImage() || $this->isPdf();
    }

    /**
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * @return HasMany<DocumentVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    /**
     * @return HasOne<DocumentVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)->latestOfMany('version_no');
    }

    /**
     * @return HasMany<DocumentLink, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(DocumentLink::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
