---
paths:
  - 'resources/views/layouts/**'
---

# Layouts

## Sidebar order
Order in layouts/partials/sidebar-menu.blade.php: Dashboard, then the User & Akses group (User, Role & Permission), then the Master Data group as flat items (Profil Organisasi, Unit Pelayanan, Jabatan Panitia, Template Seksi, Venue; no accordion), then the Event group last (Event, Kategori Event). Pages inside one event are reached through the tabs in the event header and the header event switcher, never through sidebar entries. New organization master screens go in the Master Data array.
