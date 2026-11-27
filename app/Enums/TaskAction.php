<?php

namespace App\Enums;

/**
 * Ways a task's status has changed. Today there is one: Complete, marking an open task
 * finished with its result. Every other case is retired (no status lets it start) and stays
 * only so that history written before 27 Nov 2026 can still be read.
 */
enum TaskAction: string
{
    case Publish = 'publish';
    case Start = 'start';
    case Block = 'block';
    case Complete = 'complete';
    case Approve = 'approve';
    case Return = 'return';
    case Cancel = 'cancel';
    case Reopen = 'reopen';

    public function label(): string
    {
        return match ($this) {
            self::Publish => 'Siap Dikerjakan',
            self::Start => 'Mulai Kerjakan',
            self::Block => 'Tandai Terhambat',
            self::Complete => 'Tandai Selesai',
            self::Approve => 'Setujui Hasil',
            self::Return => 'Kembalikan untuk Perbaikan',
            self::Cancel => 'Batalkan',
            self::Reopen => 'Buka Kembali',
        };
    }

    /**
     * Statuses the action may start from. Open tasks left in a retired status can still be finished.
     *
     * @return list<TaskStatus>
     */
    public function startsFrom(): array
    {
        return match ($this) {
            self::Complete => [TaskStatus::Todo, TaskStatus::Draft, TaskStatus::InProgress, TaskStatus::Blocked],
            default => [],
        };
    }

    /**
     * Where the task ends up.
     */
    public function to(): TaskStatus
    {
        return match ($this) {
            self::Complete, self::Approve => TaskStatus::Completed,
            self::Publish, self::Return, self::Reopen => TaskStatus::Todo,
            self::Start => TaskStatus::InProgress,
            self::Block => TaskStatus::Blocked,
            self::Cancel => TaskStatus::Cancelled,
        };
    }
}
