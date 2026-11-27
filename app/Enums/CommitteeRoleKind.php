<?php

namespace App\Enums;

/**
 * Structural classification of a committee role.
 *
 * Rules such as separation of duties are written against the kind, never
 * against a role's id or name, so an organization can rename its roles freely.
 */
enum CommitteeRoleKind: string
{
    case Chair = 'chair';
    case Deputy = 'deputy';
    case Secretary = 'secretary';
    case Treasurer = 'treasurer';
    case Supervisor = 'supervisor';
    case DivisionCoordinator = 'division_coordinator';
    case DivisionMember = 'division_member';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Chair => 'Ketua',
            self::Deputy => 'Wakil Ketua',
            self::Secretary => 'Sekretaris',
            self::Treasurer => 'Bendahara',
            self::Supervisor => 'Komite / Pengawas',
            self::DivisionCoordinator => 'Koordinator Seksi',
            self::DivisionMember => 'Anggota Seksi',
            self::Custom => 'Jabatan Tambahan',
        };
    }

    /**
     * Built-in roles carry committee rules, so they can be renamed or deactivated but not removed.
     */
    public function isBuiltIn(): bool
    {
        return $this !== self::Custom;
    }

    /**
     * A supervisor oversees the committee and therefore may not hold abilities that change its work.
     */
    public function isSupervisory(): bool
    {
        return $this === self::Supervisor;
    }
}
