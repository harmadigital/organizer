<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Notulen {{ $meeting->title }}</title>
    <style>
        @page { margin: 2cm 2cm 2.2cm 2cm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5pt; color: #1d2327; line-height: 1.45; }
        .organization { font-size: 9pt; color: #646970; text-transform: uppercase; letter-spacing: .05em; }
        .event { font-size: 12pt; font-weight: bold; margin-top: 2px; }
        .header { border-bottom: 2px solid #1d2327; padding-bottom: 8px; margin-bottom: 14px; }
        h1 { font-size: 15pt; margin: 0 0 10px; }
        h2 { font-size: 11pt; margin: 18px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #dcdcde; }
        .meta td { padding: 2px 12px 2px 0; vertical-align: top; }
        .meta td.label { color: #646970; width: 110px; white-space: nowrap; }
        .minutes table { border-collapse: collapse; width: 100%; }
        .minutes td, .minutes th { border: 1px solid #c3c4c7; padding: 4px 6px; }
        .minutes p { margin: 0 0 6px; }
        .minutes blockquote { margin: 6px 0 6px 12px; padding-left: 8px; border-left: 3px solid #dcdcde; color: #50575e; }
        table.list { border-collapse: collapse; width: 100%; }
        table.list th { text-align: left; background: #f0f0f1; font-size: 9pt; }
        table.list th, table.list td { border: 1px solid #dcdcde; padding: 5px 6px; vertical-align: top; }
        ol, ul { margin: 0 0 6px; padding-left: 18px; }
        .muted { color: #646970; }
        .footer { margin-top: 22px; font-size: 8.5pt; color: #646970; border-top: 1px solid #dcdcde; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="organization">{{ $event->organization?->name }}</div>
        <div class="event">{{ $event->name }}</div>
    </div>

    <h1>Notulen {{ $meeting->title }}</h1>
    <table class="meta">
        <tr><td class="label">Hari, tanggal</td><td>{{ $meeting->meets_on->locale('id')->translatedFormat('l, j F Y') }}</td></tr>
        <tr><td class="label">Waktu</td><td>{{ $meeting->timeLabel() }}</td></tr>
        <tr><td class="label">Tempat</td><td>{{ $meeting->location ?? '-' }}</td></tr>
        <tr><td class="label">Rapat untuk</td><td>{{ $meeting->division?->name ?? 'Pengurus Inti / seluruh panitia' }}</td></tr>
    </table>

    @if ($meeting->agendaItems() !== [])
        <h2>Agenda</h2>
        <ol>
            @foreach ($meeting->agendaItems() as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ol>
    @endif

    <h2>Yang Hadir ({{ $meeting->attendees->count() }})</h2>
    <div>{{ $meeting->attendees->map(fn ($attendee) => $attendee->user->name)->sort()->implode(', ') ?: 'Tidak dicatat.' }}</div>

    <h2>Isi Rapat</h2>
    {{-- Sanitized by RichText: only formatting elements and safe inline styles remain. --}}
    <div class="minutes">{!! $minutesHtml !!}</div>

    <h2>Keputusan</h2>
    @if ($meeting->decisions->isEmpty())
        <div class="muted">Tidak ada keputusan yang dicatat.</div>
    @else
        <ol>
            @foreach ($meeting->decisions as $decision)
                <li>{{ $decision->body }}</li>
            @endforeach
        </ol>
    @endif

    <h2>Tindak Lanjut</h2>
    @if ($meeting->actionItems->isEmpty())
        <div class="muted">Tidak ada tindak lanjut.</div>
    @else
        <table class="list">
            <thead><tr><th style="width: 46%">Tindak lanjut</th><th>Penanggung jawab</th><th>Tenggat</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($meeting->actionItems as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td>{{ $item->owner?->user->name ?? '-' }}</td>
                        <td>{{ $item->due_on?->locale('id')->translatedFormat('j M Y') ?? '-' }}</td>
                        <td>{{ $item->task?->status->label() ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Notulen ditulis oleh {{ $meeting->minutesAuthor?->name ?? '-' }}, terakhir diperbarui {{ $updatedAt }} (versi {{ $meeting->minutes_version }}). Dicetak {{ $printedAt }}.
    </div>
</body>
</html>
