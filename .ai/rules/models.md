---
paths:
  - app/Models/Role.php
  - 'app/Models/**'
  - app/Models/User.php
---

# Models

## Role::users() morphToMany wajib inverse=true
Relasi balik dari Role ke User lewat tabel model_has_roles: `morphToMany(User::class, 'model', ..., 'role_id', 'model_id', null, null, null, true)`. Tanpa `$inverse = true`, morphClass mengikuti Role ('App\Models\Role') sehingga query count 0 dan guard "role masih dipakai" gagal. Jangan balik urutan pivot key.

## Date/datetime model docblocks use Carbon\CarbonImmutable
The app calls Date::use(CarbonImmutable::class) in AppServiceProvider, so date/datetime casts return CarbonImmutable instances. PHPDoc @property types for date/datetime fields (created_at, completed_at, warranty_end_date, etc.) must be Carbon\CarbonImmutable|null — using Illuminate\Support\Carbon triggers PHPStan assign.propertyType errors.

## WhatsApp 2FA lockout recovery via tinker
To force-recover a user locked out of WhatsApp 2FA (e.g. restricted number, no OTP arriving), run: App\Models\User::find($id)?->update(['whatsapp_2fa_enabled' => false]); App\Models\WhatsappOtp::where('user_id', $id)->delete(); This removes the challenge without needing the OTP.
