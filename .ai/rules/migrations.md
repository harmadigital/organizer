---
paths:
  - 'database/migrations/**'
---

# Migrations

## Additive migrations only, never run destructive commands
Do not run migrate:fresh, migrate:refresh, migrate:reset, db:wipe or db:refresh against the dev database, and do not run plain migrate on it without the user asking. Check a new migration with migrate plus migrate:rollback on a throwaway SQLite file and with migrate --pretend for MySQL. audit_logs has no foreign keys on purpose so entries outlive what they point at.
