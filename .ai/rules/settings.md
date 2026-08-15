---
paths:
  - 'app/Http/Controllers/Settings/*.php'
---

# Settings

## SecurityController 2FA props only when feature enabled
SecurityController::edit must only expose twoFactorEnabled and requiresConfirmation when Features::hasTwoFactorAuthentication() is active (existing SecurityTest asserts they are absent otherwise). canManageTwoFactor can be passed unconditionally. The 2FA UI is enabled only when the user has the 'manage-two-factor' permission; check with $request->user()->can('manage-two-factor').
