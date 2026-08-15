<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use App\Support\WhatsAppOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WhatsappTwoFactorController extends Controller
{
    /**
     * Enable WhatsApp two-factor authentication for the current user.
     */
    public function enable(Request $request): RedirectResponse
    {
        if (blank($request->user()->phone)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Atur nomor WhatsApp pada akun Anda terlebih dahulu sebelum mengaktifkan 2FA WhatsApp.'),
            ]);

            return back();
        }

        $request->user()->update(['whatsapp_2fa_enabled' => true]);

        AuditLogger::record('whatsapp_2fa.enabled', __('WhatsApp 2FA diaktifkan.'), $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Autentikasi dua faktor WhatsApp telah diaktifkan.'),
        ]);

        return back();
    }

    /**
     * Disable WhatsApp two-factor authentication for the current user.
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->user()->update(['whatsapp_2fa_enabled' => false]);

        WhatsAppOtpService::forget($request->user());

        AuditLogger::record('whatsapp_2fa.disabled', __('WhatsApp 2FA dinonaktifkan.'), $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Autentikasi dua faktor WhatsApp telah dinonaktifkan.'),
        ]);

        return back();
    }
}
