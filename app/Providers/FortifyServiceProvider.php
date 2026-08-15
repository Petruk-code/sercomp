<?php

namespace App\Providers;

use App\Actions\Fortify\RedirectIfTwoFactorAuthenticatable;
use App\Actions\Fortify\ResetUserPassword;
use App\Support\AuditLogger;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->registerAuditListeners();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));

    }

    /**
     * Record two-factor authentication events in the audit log.
     */
    private function registerAuditListeners(): void
    {
        Event::listen(
            TwoFactorAuthenticationEnabled::class,
            fn ($event) => AuditLogger::record('two_factor.enabled', __('2FA TOTP diaktifkan.'), $event->user, [], [], $event->user),
        );

        Event::listen(
            TwoFactorAuthenticationDisabled::class,
            fn ($event) => AuditLogger::record('two_factor.disabled', __('2FA TOTP dinonaktifkan.'), $event->user, [], [], $event->user),
        );

        Event::listen(
            TwoFactorAuthenticationConfirmed::class,
            fn ($event) => AuditLogger::record('two_factor.confirmed', __('2FA TOTP dikonfirmasi.'), $event->user, [], [], $event->user),
        );

        Event::listen(
            RecoveryCodesGenerated::class,
            fn ($event) => AuditLogger::record('two_factor.recovery_codes_generated', __('Kode pemulihan 2FA dibuat.'), $event->user, [], [], $event->user),
        );

        Event::listen(
            ValidTwoFactorAuthenticationCodeProvided::class,
            fn ($event) => AuditLogger::record('two_factor.login', __('Login via kode 2FA.'), $event->user, [], [], $event->user),
        );
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

    }
}
