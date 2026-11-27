---
paths:
  - 'app/Services/**'
---

# Services

## Formatted text goes through RichText; PDFs through dompdf services
HTML from the editor (meeting minutes) is sanitized with App\Services\RichText (symfony/html-sanitizer allowlist: formatting, lists, tables, http/https/mailto links, a short list of inline styles; no scripts, handlers, images or url()) before it is stored and again before it is shown or printed; only then may a view output it with {!! !!}. PDFs are rendered with dompdf/dompdf in a service such as App\Services\MinutesPdf with remote resources, PHP and JavaScript disabled and the DejaVu Sans font.
