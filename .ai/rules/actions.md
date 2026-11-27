---
paths:
  - 'app/Actions/**'
---

# Actions

## Audit sensitive changes through AuditLogger
Membership, approval, posting, reversal, configuration and document-version changes call App\Services\AuditLogger::record() inside the same database transaction as the change. Pass before/after arrays; unchanged keys are dropped and keys in config organizer.audit.redacted_keys are masked. Pass eventId and effectiveRole for event-scoped actions.

## Committee changes go through the event actions
Assign, end, revoke and respond only through App\Actions\Events (AssignCommitteeRole, EndAssignment, RevokeMembership, RespondToMembership). They enforce the single primary holder (primary_key), no duplicate active role (active_key), supervisor/executor separation and re-invitation, call EventAccess::forget() and write the audit entry. Throw ValidationException for rule violations; bootstrap/app.php renders it as the JSON envelope.
