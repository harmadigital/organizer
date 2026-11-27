---
paths:
  - 'app/Actions/**'
---

# Actions

## Audit sensitive changes through AuditLogger
Membership, approval, posting, reversal, configuration and document-version changes call App\Services\AuditLogger::record() inside the same database transaction as the change. Pass before/after arrays; unchanged keys are dropped and keys in config organizer.audit.redacted_keys are masked. Pass eventId and effectiveRole for event-scoped actions.

## Committee changes go through the event actions
Assign, end, revoke and respond only through App\Actions\Events (AssignCommitteeRole, EndAssignment, RevokeMembership, RespondToMembership). They enforce the single primary holder (primary_key), no duplicate active role (active_key), supervisor/executor separation and re-invitation, call EventAccess::forget() and write the audit entry. Throw ValidationException for rule violations; bootstrap/app.php renders it as the JSON envelope.

## Task status changes go through ChangeTaskStatus
A task status changes only through App\Actions\Work\ChangeTaskStatus (status is not fillable), called by CompleteTask. It locks the row, checks TaskAction::startsFrom(), refuses completion without a result or while prerequisites or subtasks are open, and writes task_status_changes and the audit entry. Who may finish is TaskAuthority::canWork()/canComplete(), checked by the caller.

## Tasks are finished only with a result
A task has no start or blocked step: it stays open (todo) until App\Actions\Work\CompleteTask saves its result, attaches any files and applies TaskAction::Complete in one transaction. ChangeTaskStatus refuses Complete while tasks.result is empty. Only Todo and Completed are in use; every other TaskStatus and every TaskAction except Complete is retired and kept only so old history reads. Never add a path back to them.

## Minutes are saved only through SaveMinutes
Meeting minutes, attendance, decisions and follow-ups are written in one call to App\Actions\Meetings\SaveMinutes: it locks the meeting row, refuses a save started from an older minutes_version (so double submits never create duplicate tasks), stores a meeting_minutes_versions row, replaces decisions, syncs attendees and turns each new follow-up into exactly one task (via SaveTask) in the meeting division or the core team. There are no invitations, RSVP or review steps.
