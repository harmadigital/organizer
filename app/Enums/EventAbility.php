<?php

namespace App\Enums;

/**
 * What a committee role may do inside an event.
 *
 * The catalogue lives in code because code checks it; which role holds which
 * ability is configuration (committee_role_abilities). A role scoped to a
 * division holds its abilities for that division only. Each batch that adds
 * an event feature adds its abilities here.
 */
enum EventAbility: string
{
    case EventView = 'event.view';
    case EventUpdate = 'event.update';

    case CommitteeView = 'committee.view';
    case CommitteeManage = 'committee.manage';
    case DivisionManage = 'division.manage';

    case ProgramView = 'program.view';
    case ProgramManage = 'program.manage';
    case ProgramSubmit = 'program.submit';
    case ProgramReview = 'program.review';
    case ProgramApprove = 'program.approve';

    case TaskView = 'task.view';
    case TaskManage = 'task.manage';
    case TaskWork = 'task.work';

    case MeetingView = 'meeting.view';
    case MeetingManage = 'meeting.manage';
    case MinutesPublish = 'minutes.publish';
    case DecisionManage = 'decision.manage';

    case CoordinationView = 'coordination.view';
    case CoordinationManage = 'coordination.manage';

    case IssueView = 'issue.view';
    case IssueReport = 'issue.report';
    case IssueManage = 'issue.manage';

    case DocumentView = 'document.view';
    case DocumentManage = 'document.manage';

    case AnnouncementManage = 'announcement.manage';

    public function label(): string
    {
        return match ($this) {
            self::EventView => 'Melihat ringkasan event',
            self::EventUpdate => 'Mengubah profil dan pengaturan event',
            self::CommitteeView => 'Melihat struktur panitia',
            self::CommitteeManage => 'Mengelola struktur, seksi, dan penugasan panitia',
            self::DivisionManage => 'Mengelola anggota dan data seksi',
            self::ProgramView => 'Melihat program kerja',
            self::ProgramManage => 'Menyusun dan mengubah program kerja',
            self::ProgramSubmit => 'Mengajukan program kerja',
            self::ProgramReview => 'Meninjau program kerja',
            self::ProgramApprove => 'Menyetujui program kerja',
            self::TaskView => 'Melihat tugas',
            self::TaskManage => 'Membuat, menugaskan, dan mengubah tugas',
            self::TaskWork => 'Memperbarui tugas yang ditugaskan kepadanya',
            self::MeetingView => 'Melihat rapat dan notulen',
            self::MeetingManage => 'Mengelola rapat, kehadiran, dan notulen',
            self::MinutesPublish => 'Mempublikasikan notulen',
            self::DecisionManage => 'Mencatat keputusan',
            self::CoordinationView => 'Melihat permintaan koordinasi',
            self::CoordinationManage => 'Membuat dan menindaklanjuti permintaan koordinasi',
            self::IssueView => 'Melihat isu dan risiko',
            self::IssueReport => 'Melaporkan isu',
            self::IssueManage => 'Mengelola isu dan risiko',
            self::DocumentView => 'Melihat dokumen',
            self::DocumentManage => 'Mengunggah dan mengelola dokumen',
            self::AnnouncementManage => 'Membuat pengumuman',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::EventView, self::EventUpdate => 'Event',
            self::CommitteeView, self::CommitteeManage, self::DivisionManage => 'Kepanitiaan',
            self::ProgramView, self::ProgramManage, self::ProgramSubmit, self::ProgramReview, self::ProgramApprove => 'Program Kerja',
            self::TaskView, self::TaskManage, self::TaskWork => 'Tugas',
            self::MeetingView, self::MeetingManage, self::MinutesPublish, self::DecisionManage => 'Rapat & Keputusan',
            self::CoordinationView, self::CoordinationManage => 'Koordinasi',
            self::IssueView, self::IssueReport, self::IssueManage => 'Isu & Risiko',
            self::DocumentView, self::DocumentManage => 'Dokumen',
            self::AnnouncementManage => 'Komunikasi',
        };
    }

    /**
     * Whether a supervisory role may hold this ability: reading, plus recording its own decisions.
     */
    public function isAllowedForSupervisor(): bool
    {
        return str_ends_with($this->value, '.view') || $this === self::DecisionManage;
    }

    /**
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::cases() as $ability) {
            $groups[$ability->group()][] = $ability;
        }

        return $groups;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
