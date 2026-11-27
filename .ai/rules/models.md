---
paths:
  - 'app/Models/**'
---

# Models

## Register a morph alias for every polymorphic target
Relation::enforceMorphMap is active in AppServiceProvider. A model used as the target of any polymorphic relation, including the subject of an AuditLogger entry, must be added there with a short snake_case alias or it throws ClassMorphViolationException. User keeps its class name as the alias because existing spatie and Sanctum rows store it that way; do not change it.

## Organization-owned models use BelongsToOrganization
Models with an organization_id use App\Models\Concerns\BelongsToOrganization (route binding then only finds records of the current organization, so foreign ids answer 404) and RecordsEditors. Master data has no soft deletes: retire with is_active or status, and let restrictOnDelete foreign keys block deletion of anything still referenced.

## Users are the single directory of people
There is no separate member table: App\Models\User holds everyone, and username, email and password are optional. Committee and other records refer to user_id. Use User::hasLoginAccess() for has credentials and User::canSignIn() for active with credentials; login (Fortify::authenticateUsing) and the EnsureUserCanSignIn web middleware both rely on canSignIn().
