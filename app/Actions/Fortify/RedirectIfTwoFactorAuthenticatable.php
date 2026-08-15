<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\WhatsAppOtpService;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable as BaseAction;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfTwoFactorAuthenticatable extends BaseAction
{
    /**
     * Handle the incoming request.
     *
     * @param  callable  $next
     */
    public function handle($request, $next): mixed
    {
        $user = $this->validateCredentials($request);

        if ($user instanceof User && $user->whatsapp_2fa_enabled && filled($user->phone)) {
            return $this->whatsappOtpChallengeResponse($request, $user);
        }

        return parent::handle($request, $next);
    }

    /**
     * Redirect to the WhatsApp OTP challenge and dispatch a fresh code.
     *
     * @param  mixed  $user
     */
    protected function whatsappOtpChallengeResponse(Request $request, $user): Response
    {
        $request->session()->put([
            'login.id' => $user->getKey(),
            'login.remember' => $request->boolean('remember'),
        ]);

        WhatsAppOtpService::send($user);

        TwoFactorAuthenticationChallenged::dispatch($user);

        return $request->wantsJson()
            ? response()->json(['two_factor' => true, 'method' => 'whatsapp'])
            : redirect()->route('two-factor.whatsapp');
    }
}
