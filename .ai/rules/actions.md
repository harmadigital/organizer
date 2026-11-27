---
paths:
  - 'app/Actions/**'
---

# Actions

## Audit sensitive changes through AuditLogger
Membership, approval, posting, reversal, configuration and document-version changes call App\Services\AuditLogger::record() inside the same database transaction as the change. Pass before/after arrays; unchanged keys are dropped and keys in config organizer.audit.redacted_keys are masked. Pass eventId and effectiveRole for event-scoped actions.
