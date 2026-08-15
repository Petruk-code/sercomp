<?php

namespace App\Support;

use App\Models\User;
use App\Models\WhatsappOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class WhatsAppOtpService
{
    /**
     * How many minutes an OTP code stays valid.
     */
    public static function expiryMinutes(): int
    {
        return (int) config('services.fonnte.otp_expiry_minutes', 5);
    }

    /**
     * How many failed verification attempts invalidate a code.
     */
    public static function maxAttempts(): int
    {
        return (int) config('services.fonnte.otp_attempts', 3);
    }

    /**
     * Generate a fresh code, store it hashed, and deliver it over WhatsApp.
     *
     * Outside production the code is only logged — never sent to the gateway —
     * so repeated testing does not trigger WhatsApp device restrictions.
     *
     * @return array<string, mixed>
     */
    public static function send(User $user): array
    {
        if (blank($user->phone) || ! WhatsApp::isConfigured()) {
            return ['status' => false];
        }

        $code = (string) random_int(100000, 999999);

        self::forget($user);

        WhatsappOtp::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::expiryMinutes()),
        ]);

        if (! app()->isProduction() && ! (bool) config('services.fonnte.otp_force_send', false)) {
            Log::info('WhatsApp OTP (not sent — development only)', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'code' => $code,
            ]);

            return ['status' => true, 'dev_code' => $code];
        }

        return WhatsApp::send($user->phone, self::message($code));
    }

    /**
     * Validate a submitted code against the user's latest pending OTP.
     */
    public static function verify(User $user, string $code): bool
    {
        $otp = WhatsappOtp::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (! $otp) {
            return false;
        }

        if ($otp->expires_at->isPast()) {
            $otp->delete();

            return false;
        }

        if ($otp->attempts >= self::maxAttempts()) {
            $otp->delete();

            return false;
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            return false;
        }

        $otp->delete();

        return true;
    }

    /**
     * Drop all pending codes for a user (disable/reset).
     */
    public static function forget(User $user): void
    {
        WhatsappOtp::query()->where('user_id', $user->id)->delete();
    }

    /**
     * Build the WhatsApp message carrying the verification code.
     */
    private static function message(string $code): string
    {
        return 'SerComp — kode verifikasi login Anda: *'.$code.'*. Berlaku '.self::expiryMinutes().' menit. Jangan bagikan kode ini kepada siapa pun.';
    }
}
