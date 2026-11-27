<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Concerns\RecordsEditors;
use Carbon\CarbonInterface;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * A piece of committee work, for the whole event or for one division, optionally part
 * of a work program or of a larger task.
 */
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    use RecordsEditors;

    /**
     * The attributes that are mass assignable. The status changes only through ChangeTaskStatus
     * and the result only through CompleteTask and SaveTaskResult.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'division_id',
        'work_program_id',
        'parent_id',
        'title',
        'description',
        'owner_membership_id',
        'priority',
        'starts_on',
        'due_on',
        'weight',
        'requires_review',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'todo',
        'priority' => 'medium',
        'weight' => 1,
        'requires_review' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'starts_on' => 'date',
            'due_on' => 'date',
            'weight' => 'integer',
            'requires_review' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Past its due date while still open. "Today" is the organization's today.
     */
    public function isOverdue(CarbonInterface $today): bool
    {
        return $this->due_on !== null && ! $this->status->isClosed() && $this->due_on->lt($today->copy()->startOfDay());
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNotIn($this->qualifyColumn('status'), [TaskStatus::Completed, TaskStatus::Cancelled]);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * @return BelongsTo<WorkProgram, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(WorkProgram::class, 'work_program_id');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<EventMembership, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(EventMembership::class, 'owner_membership_id');
    }

    /**
     * Collaborators besides the owner.
     *
     * @return BelongsToMany<EventMembership, $this>
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(EventMembership::class, 'task_assignees')->withPivot('event_id')->withTimestamps();
    }

    /**
     * Tasks that must be finished before this one can be.
     *
     * @return BelongsToMany<self, $this>
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'task_dependencies', 'task_id', 'depends_on_task_id')->withPivot(['event_id', 'created_by'])->withTimestamps();
    }

    /**
     * Tasks waiting for this one.
     *
     * @return BelongsToMany<self, $this>
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'task_dependencies', 'depends_on_task_id', 'task_id');
    }

    /**
     * @return HasMany<TaskStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(TaskStatusChange::class)->latest('id');
    }

    /**
     * Route parameter {comment} is resolved through this relation.
     *
     * @return HasMany<TaskComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->orderBy('id');
    }

    /**
     * Evidence and other files attached to the task.
     *
     * @return MorphToMany<Document, $this>
     */
    public function documents(): MorphToMany
    {
        return $this->morphToMany(Document::class, 'linkable', 'document_links')->withPivot(['purpose', 'linked_by'])->withTimestamps();
    }

    /**
     * Whether the person works on this task as its owner or as a collaborator.
     */
    public function isWorkedOnBy(?EventMembership $membership): bool
    {
        if ($membership === null) {
            return false;
        }

        if ($this->owner_membership_id === $membership->getKey()) {
            return true;
        }

        return $this->relationLoaded('assignees')
            ? $this->assignees->contains($membership->getKey())
            : $this->assignees()->whereKey($membership->getKey())->exists();
    }
}
