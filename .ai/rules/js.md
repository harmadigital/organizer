---
paths:
  - 'public/assets/js/**'
---

# Js

## Page scripts use OrganizerHttp for AJAX
One plain JS file per page (no build step), loaded via @section("script"). New pages send AJAX through OrganizerHttp.request/submit from assets/js/organizer/http.js, which handles CSRF, 422 field errors, 403/409/419 and network failures. Show server messages as text (Swal text or textContent), never as HTML.

## List pages configure OrganizerMasterCrud
A list page script only calls OrganizerMasterCrud.create({resource, entity, columns, ...}) and init(). Text from the data endpoint is already HTML-escaped by yajra and may be placed in cell HTML; values from show endpoints are raw and go in through .value or textContent only. The bundled DataTables is 2.x (empty cell class is dt-empty).

## Use the shared picker and select helpers in page scripts
Fill or clear dates with OrganizerDatePicker.setValue/clearAll and remote dropdowns with OrganizerSelect.setValue(select, id, label)/reset/setParams; call OrganizerSelect.init(element) for dropdowns added after page load. Both helpers keep Esc for an open calendar or dropdown so a surrounding modal stays open. Long forms in a modal use the Metronic KTStepper; it swallows submit-button clicks, so listen to kt.stepper.submit.
