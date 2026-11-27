---
paths:
  - 'app/Http/**'
---

# Http

## Never let someone grant more than they hold
Changing another account login access, password, role or 2FA, deleting it, assigning a role, or editing a role requires App\Services\AccountAuthority: the target account or role may hold no permission the actor lacks. Nobody may deactivate, revoke, re-role or delete their own account. Super Admin and Organization Admin (PermissionSeeder::SYSTEM_ROLES) keep their names and cannot be deleted; Super Admin cannot be edited.
