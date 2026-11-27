---
paths:
  - 'app/Policies/**'
---

# Policies

## Event permissions come from EventAccess, never from global roles alone
Inside an event, authorize with EventPolicy or App\Services\EventAccess::for(user, event)->has(EventAbility, divisionId). Division-scoped abilities reach that division and its descendants only; has() without a division counts event-wide abilities only, hasAnywhere() counts both. Organization permissions events.view/update/manage-committee reach every event via EventAccess::ORGANIZATION_GRANTS. Who may hand out which committee role is App\Services\CommitteeAuthority.

## Program and task authority lives in TaskAuthority
App\Services\TaskAuthority decides who sees, changes (Ubah), finishes (Selesai) and comments on tasks, and visibleTasks()/visiblePrograms() scope listings. Every active committee member sees and may comment on every task of the event. Changing needs TaskManage/ProgramManage for the division; no division means the core team (Pengurus Inti), managed by event-wide TaskManage or ProgramManage, and a core member with event-wide TaskWork only (the Bendahara) may add and change core tasks they own (mayCreateAsOwnTask). Finishing needs a manager or TaskWork held by the owner or a collaborator. There is no review or approval step. Organization permission events.manage-work grants ProgramManage and TaskManage on every event.

## Meeting authority lives in MeetingAuthority
App\Services\MeetingAuthority: every active committee member (or MeetingView holder) sees every meeting and its minutes and may comment; scheduling, editing and writing minutes needs MeetingManage for the meeting division, or event-wide for core team meetings (division_id null). events.manage-work also grants MeetingManage on every event.
