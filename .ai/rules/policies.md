---
paths:
  - 'app/Policies/**'
---

# Policies

## Event permissions come from EventAccess, never from global roles alone
Inside an event, authorize with EventPolicy or App\Services\EventAccess::for(user, event)->has(EventAbility, divisionId). Division-scoped abilities reach that division and its descendants only; has() without a division counts event-wide abilities only, hasAnywhere() counts both. Organization permissions events.view/update/manage-committee reach every event via EventAccess::ORGANIZATION_GRANTS. Who may hand out which committee role is App\Services\CommitteeAuthority.
