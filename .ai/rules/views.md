---
paths:
  - 'resources/views/**'
---

# Views

## App pages follow docs/ui-ux_brief.md
Pages extend layouts.main_layout and build their structure with the ui-* classes in public/assets/css/ui.css (ui-page, ui-page-header, ui-panel, ui-toolbar, ui-stat-card); actions and status use Metronic classes (btn-primary, badge-light-*). No Tailwind or Livewire in app pages. Sidebar entries go in layouts/partials/sidebar-menu.blade.php, each wrapped in @can.

## List pages are built from the x-ui components
A list page uses x-ui.page-header, x-ui.list-toolbar, x-ui.master-table, x-ui.form-modal, x-ui.active-switch and x-ui.guide-modal, and loads assets/js/organizer/master-crud.js before its own page script. New master screens are added to the Master Data array in layouts/partials/sidebar-menu.blade.php and to master/partials/subnav.blade.php.

## Dates use flatpickr, dropdowns use select2
Never use native date/time inputs: write <input type="text" data-date-picker> (initialised by assets/js/organizer/date-picker.js with the Indonesian locale; value YYYY-MM-DD, shown as 3 April 2027; data-date-min="#other" links a minimum). Every <select> has data-control="select2", or data-select-remote="url" when options come from the server (paged search answering {results:[{id,text,description}], more}); show endpoints send <field>_label so remote selects can display the stored value. FoundationTest fails on any view that breaks either rule.
