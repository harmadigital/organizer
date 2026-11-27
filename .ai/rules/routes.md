---
paths:
  - 'routes/**'
---

# Routes

## Event-scoped routes live under /events/{event} with event.access and scopeBindings
Any route about one event goes inside the events prefix group with the event.access middleware (non-members get 404, not 403) and scopeBindings(), so child records resolve through the Event relation named after the parameter ({division} -> divisions(), {assignment} -> assignments(), {membership} -> memberships()). tests/Feature/Events/EventIsolationTest covers every such route automatically; add the new parameter to its records map.

## Task child records resolve through the task
Under /events/{event}/tasks/{task}, the parameters {dependency}, {document} and {comment} resolve through Task::dependencies(), documents() and comments() via scopeBindings, so they must belong to that task. Add new task children the same way and add the parameter to EventIsolationTest::recordsOf().
