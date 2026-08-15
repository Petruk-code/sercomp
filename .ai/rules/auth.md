---
paths:
  - 'app/Http/Controllers/Auth/**'
---

# Auth

## WhatsApp 2FA login flow
WhatsApp 2FA is enforced by App\Actions\Fortify\RedirectIfTwoFactorAuthenticatable (registered via Fortify::redirectUserForTwoFactorAuthenticationUsing) BEFORE the TOTP logic: users with users.whatsapp_2fa_enabled = true AND a non-blank users.phone are redirected to route two-factor.whatsapp with session 'login.id'/'login.remember'. OTP codes are stored hashed in whatsapp_otps (code_hash, expires_at, attempts, used_at unused) and the pending row is DELETED on success, on max attempts (3), or when expired. Never log or store the plaintext code; it is only embedded in the WhatsApp message text.
