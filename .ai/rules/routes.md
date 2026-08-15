---
paths:
  - 'routes/**'
  - routes/console.php
---

# Routes

## `role` middleware alias is registered in bootstrap/app.php
RBAC route protection uses the `role` middleware alias (e.g. `middleware('role:admin,owner')`). The alias is registered in bootstrap/app.php -> `$middleware->alias(['role' => EnsureUserHasRole::class])` (Spatie permission, `hasAnyRole`). Never remove that alias — it is the only thing enforcing role checks on all module routes.

## Register scheduled commands in routes/console.php
Scheduled tasks (app:backup-database daily 02:00, app:send-alerts hourly) are registered via the Schedule facade in routes/console.php. New recurring jobs must be registered here, not in bootstrap/app.php. Tests assert these with Schedule::events().
