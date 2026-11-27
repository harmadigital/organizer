<?php

namespace App\Enums;

/**
 * What the committee is working on. Informational: phases may overlap in practice,
 * so moving between them is free and only recorded in the status history.
 */
enum EventPhase: string
{
    case Initiation = 'initiation';
    case Formation = 'formation';
    case Planning = 'planning';
    case Preparation = 'preparation';
    case FinalReadiness = 'final_readiness';
    case Execution = 'execution';
    case Evaluation = 'evaluation';
    case Reporting = 'reporting';

    public function label(): string
    {
        return match ($this) {
            self::Initiation => 'Inisiasi & Otorisasi',
            self::Formation => 'Pembentukan Panitia',
            self::Planning => 'Perencanaan & Anggaran',
            self::Preparation => 'Persiapan',
            self::FinalReadiness => 'Kesiapan Akhir',
            self::Execution => 'Pelaksanaan',
            self::Evaluation => 'Evaluasi & Penyelesaian',
            self::Reporting => 'Pelaporan & Arsip',
        };
    }
}
