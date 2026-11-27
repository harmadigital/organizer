---
paths:
  - 'database/seeders/**'
---

# Seeders

## Seeders are idempotent and never delete
Seeders add what is missing with firstOrCreate and givePermissionTo so they can be re-run on a live database. Never truncate or bulk-delete, and never sync a role down to a fixed list. New organization-level permissions go in PermissionSeeder::PERMISSIONS. Demo data lives in separate seeders that DatabaseSeeder does not call.
