<?php

namespace App\Enums;

/**
 * Where an event stands. The status decides what may still change; the phase
 * (EventPhase) only describes what the committee is working on.
 */
enum EventStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Active => 'Aktif',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::Archived => 'Diarsipkan',
        };
    }

    /**
     * Metronic colour for the status badge.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Active => 'success',
            self::Completed => 'primary',
            self::Cancelled => 'danger',
            self::Archived => 'secondary',
        };
    }

    /**
     * Only draft and active events may still be edited.
     */
    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::Active;
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Active, self::Cancelled],
            self::Active => [self::Completed, self::Cancelled],
            self::Completed => [self::Active, self::Archived],
            self::Cancelled => [self::Draft],
            self::Archived => [],
        };
    }

    public function canMoveTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Cancelling and going back a step need a written reason.
     */
    public function requiresReasonToMoveTo(self $target): bool
    {
        return $target === self::Cancelled
            || ($this === self::Completed && $target === self::Active)
            || ($this === self::Cancelled && $target === self::Draft);
    }

    /**
     * What the button that moves an event into this status says.
     */
    public function actionLabel(): string
    {
        return match ($this) {
            self::Draft => 'Buka Kembali sebagai Draf',
            self::Active => 'Aktifkan',
            self::Completed => 'Tandai Selesai',
            self::Cancelled => 'Batalkan Event',
            self::Archived => 'Arsipkan',
        };
    }
}
