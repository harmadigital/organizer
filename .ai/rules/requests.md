---
paths:
  - 'app/Http/Requests/**'
---

# Requests

## New module requests extend JsonFormRequest
Validate new Organizer modules in Form Requests that extend App\Http\Requests\JsonFormRequest, not inline in the controller. A failed validation answers HTTP 422 as {status:false, data:<first message>, errors:{field:[...]}}. Only the legacy PermissionController still validates inline.
