---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## JSON envelope with a real HTTP status
New controllers answer AJAX with $this->jsonSuccess($data) and $this->jsonFailure($message, $status) from the base Controller: envelope {status, data}, with 422/403/409 for failures instead of HTTP 200. Do not run input through the Sanitizer escape filter; store text as typed and let Blade escape on output. Only PermissionController is still on the legacy pattern (inline Validator, HTTP 200 failures); UserController and RoleController were rebuilt and are the reference.

## Master data screens extend MasterRecordController
A list-and-modal screen for records owned by the organization extends App\Http\Controllers\Master\MasterRecordController and uses its listing/store/modify/remove helpers, which scope to CurrentOrganization and audit through SaveMasterRecord and DeleteMasterRecord. Routes follow index, data, show/{x}, insert, update/{x}, delete/{x} with a can: middleware each, inside the organization.configured group.
