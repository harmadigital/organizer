---
paths:
  - 'app/Services/**'
---

# Services

## Formatted text goes through RichText; PDFs through dompdf services
HTML from the editor (meeting minutes) is sanitized with App\Services\RichText (symfony/html-sanitizer allowlist: formatting, lists, tables, http/https/mailto links, a short list of inline styles; no scripts, handlers, images or url()) before it is stored and again before it is shown or printed; only then may a view output it with {!! !!}. PDFs are rendered with dompdf/dompdf in a service such as App\Services\MinutesPdf with remote resources, PHP and JavaScript disabled and the DejaVu Sans font.

## CalendarFeed is the only place calendar items are assembled
App\Services\CalendarFeed builds the items of the event Kalender tab (forEvent) and Kalender Saya (forPerson, forOrganization). Meetings, task deadlines and event days are read live from their own tables and never copied into calendar_entries; only hand-added kegiatan are stored. Add a new source as one more reader method. Periods are [from, until): compare date columns with >= from and < until on Y-m-d strings, never <=, because SQLite stores a date cast as Y-m-d 00:00:00.
