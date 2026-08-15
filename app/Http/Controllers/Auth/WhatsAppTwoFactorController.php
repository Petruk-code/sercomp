<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\WhatsApp;
use App\Support\WhatsAppOtpService;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppTwoFactorController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected StatefulGuard $guard) {}

    /**
     * Show the WhatsApp OTP challenge screen.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $this->challengedUser($request);

        return Inertia::render('auth/whatsapp-otp-challenge', [
            'phoneMasked' => WhatsApp::maskPhone($user->phone ?? ''),
        ]);
    }

    /**
     * Verify the submitted OTP code and sign the user in.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $this->challengedUser($request);

        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        if (! WhatsAppOtpService::verify($user, $request->string('code')->toString())) {
            throw ValidationException::withMessages([
                'code' => __('Kode OTP salah atau sudah kedaluwarsa. Silakan minta kode baru.'),
            ]);
        }

        $this->guard->login($user, $request->session()->pull('login.remember', false));

        AuditLogger::record('whatsapp_2fa.login', __('Login via OTP WhatsApp.'), $user, [], [], $user);

        $request->session()->forget('login.id');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Resend a fresh OTP code to the user's WhatsApp number.
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $this->challengedUser($request);

        $result = WhatsAppOtpService::send($user);

        if (($result['status'] ?? false) !== true) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Kode OTP gagal dikirim. Silakan coba lagi.')]);

            return back();
        }

        $message = __('Kode OTP baru telah dikirim ke WhatsApp Anda.');

        if (! app()->isProduction() && isset($result['dev_code'])) {
            $message .= ' (Dev preview: '.$result['dev_code'].')';
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    /**
     * Resolve the user currently mid two-factor challenge, or bounce back to login.
     */
    protected function challengedUser(Request $request): User
    {
        $user = $request->session()->has('login.id')
            ? User::query()->find($request->session()->get('login.id'))
            : null;

        abort_unless($user instanceof User && $user->whatsapp_2fa_enabled && filled($user->phone), redirect()->route('login'));

        return $user;
    }
}
