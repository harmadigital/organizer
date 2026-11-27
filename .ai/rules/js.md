---
paths:
  - 'public/assets/js/**'
---

# Js

## Page scripts use OrganizerHttp for AJAX
One plain JS file per page (no build step), loaded via @section("script"). New pages send AJAX through OrganizerHttp.request/submit from assets/js/organizer/http.js, which handles CSRF, 422 field errors, 403/409/419 and network failures. Show server messages as text (Swal text or textContent), never as HTML.

## List pages configure OrganizerMasterCrud
A list page script only calls OrganizerMasterCrud.create({resource, entity, columns, ...}) and init(). Text from the data endpoint is already HTML-escaped by yajra and may be placed in cell HTML; values from show endpoints are raw and go in through .value or textContent only. The bundled DataTables is 2.x (empty cell class is dt-empty).
