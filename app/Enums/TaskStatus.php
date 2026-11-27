<?php

namespace App\Enums;

/**
 * Where a task stands. The committee only records work that is done: a task is open
 * (Todo) until someone marks it finished with its result, and then it is Completed.
 *
 * Draft, InProgress, Blocked, PendingReview and Cancelled are retired: no action leads to
 * them anymore. They stay so that history written before 27 Nov 2026, and tasks cancelled
 * before then, can still be read.
 */
enum TaskStatus: string
{
    case Draft = 'draft';
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case PendingReview = 'pending_review';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Todo => 'Belum Selesai',
            self::InProgress => 'Dikerjakan',
            self::Blocked => 'Terhambat',
            self::PendingReview => 'Menunggu Review',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Todo, self::InProgress => 'info',
            self::Blocked => 'danger',
            self::PendingReview => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'dark',
        };
    }

    /**
     * Finished either way: no longer counts as open work.
     */
    public function isClosed(): bool
    {
        return $this === self::Completed || $this === self::Cancelled;
    }

    public function isRetired(): bool
    {
        return $this !== self::Todo && $this !== self::Completed;
    }
}
