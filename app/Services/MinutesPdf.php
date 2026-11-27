<?php

namespace App\Services;

use App\Models\Meeting;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;

/**
 * The minutes of a meeting as a PDF that can be shared outside the committee.
 */
class MinutesPdf
{
    public function __construct(
        private RichText $richText,
        private DisplayTime $time,
    ) {}

    /**
     * The printable document, before it is turned into a PDF.
     */
    public function html(Meeting $meeting): string
    {
        $meeting->loadMissing([
            'event.organization',
            'division:id,name',
            'minutesAuthor:id,name',
            'attendees.user:id,name',
            'decisions',
            'actionItems.owner.user:id,name',
            'actionItems.task:id,status',
        ]);

        return view('events.pdf.minutes', [
            'meeting' => $meeting,
            'event' => $meeting->event,
            'minutesHtml' => $this->richText->sanitize($meeting->minutes),
            'updatedAt' => $this->time->format($meeting->minutes_updated_at),
            'printedAt' => $this->time->format(now()),
        ])->render();
    }

    public function render(Meeting $meeting): string
    {
        $options = (new Options)
            ->setIsRemoteEnabled(false)
            ->setIsPhpEnabled(false)
            ->setIsJavascriptEnabled(false)
            ->setDefaultFont('DejaVu Sans')
            ->setChroot([resource_path()]);

        $pdf = new Dompdf($options);
        $pdf->loadHtml($this->html($meeting), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        $pdf->getCanvas()->page_text(500, 810, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', 'DejaVu Sans', 8, [0.4, 0.4, 0.4]);

        return (string) $pdf->output();
    }

    /**
     * "notulen-rapat-pleno-1-2026-11-10.pdf"
     */
    public function filename(Meeting $meeting): string
    {
        return 'notulen-'.Str::slug($meeting->title).'-'.$meeting->meets_on->toDateString().'.pdf';
    }
}
