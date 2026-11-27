---
paths:
  - 'app/Enums/**'
---

# Enums

## EventAbility is the catalogue of event permissions
Add a case to App\Enums\EventAbility (with label and group) in the batch that starts enforcing it, and add it to the defaults in ProvisionDefaultCommitteeRoles. Rules are written against CommitteeRoleKind, never a role id or name. An ability a supervisor may hold must pass isAllowedForSupervisor(); SaveCommitteeRole rejects anything else.
