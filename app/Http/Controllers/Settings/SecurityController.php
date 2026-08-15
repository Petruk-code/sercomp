<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class SecurityController extends Controller
{
    /**
     * Show the user's security settings page.
     */
    public function edit(TwoFactorAuthenticationRequest $request): Response
    {
        $canManageTwoFactor = Features::enabled(Features::twoFactorAuthentication());

        $props = [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'canManageTwoFactor' => $canManageTwoFactor,
            'whatsapp2faEnabled' => (bool) $request->user()->whatsapp_2fa_enabled,
            'whatsapp2faPhone' => $request->user()->phone ?? '',
        ];

        if ($canManageTwoFactor) {
            $props['twoFactorEnabled'] = $request->user()->hasEnabledTwoFactorAuthentication();
            $props['requiresConfirmation'] = Fortify::confirmsTwoFactorAuthentication();
        }

        $props['whatsapp2faPhoneMasked'] = filled($request->user()->phone)
            ? WhatsApp::maskPhone($request->user()->phone)
            : '';

        return Inertia::render('settings/security', $props);
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }
}
