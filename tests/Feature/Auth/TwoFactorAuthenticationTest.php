<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

test('two factor challenge screen can be rendered', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    $user = User::factory()->withTwoFactor()->create();

    $this->withSession(['login.id' => $user->id])
        ->get(route('two-factor.login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/two-factor-challenge'));
});

test('two factor authentication can be enabled', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->getTimestamp()])
        ->post(route('two-factor.enable'))
        ->assertSessionHas('status', 'two-factor-authentication-enabled');

    expect($user->refresh()->two_factor_secret)->not->toBeNull();
});

test('two factor authentication requires password confirmation to be enabled', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('two-factor.enable'))
        ->assertRedirect(route('password.confirm'));

    expect($user->refresh()->two_factor_secret)->toBeNull();
});

test('two factor authentication can be confirmed with a valid code', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->getTimestamp()])
        ->post(route('two-factor.enable'));

    $secret = Fortify::currentEncrypter()->decrypt($user->refresh()->two_factor_secret);
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $this->post(route('two-factor.confirm'), ['code' => $code])
        ->assertSessionHas('status', 'two-factor-authentication-confirmed');

    expect($user->refresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('two factor authentication can not be confirmed with an invalid code', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->getTimestamp()])
        ->post(route('two-factor.enable'));

    $this->post(route('two-factor.confirm'), ['code' => 'invalid-code'])
        ->assertSessionHasErrors(['code'], null, 'confirmTwoFactorAuthentication');

    expect($user->refresh()->two_factor_confirmed_at)->toBeNull();
});

test('users can authenticate with a recovery code', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $this->post(route('two-factor.login.store'), [
        'recovery_code' => 'code-1',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('users can authenticate with a valid totp code', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->getTimestamp()])
        ->post(route('two-factor.enable'));

    $secret = Fortify::currentEncrypter()->decrypt($user->refresh()->two_factor_secret);

    $google2fa = app(Google2FA::class);
    $code = $google2fa->getCurrentOtp($secret);

    $this->post(route('two-factor.confirm'), ['code' => $code])
        ->assertSessionHas('status', 'two-factor-authentication-confirmed');

    $this->post(route('logout'));

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $nextCode = $google2fa->oathTotp($secret, $google2fa->getTimestamp() + 1);

    $this->post(route('two-factor.login.store'), ['code' => $nextCode])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('users can not authenticate with an invalid code', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $this->post(route('two-factor.login.store'), ['code' => 'invalid-code'])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('two factor authentication can be disabled', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->getTimestamp()])
        ->delete(route('two-factor.disable'))
        ->assertSessionHas('status', 'two-factor-authentication-disabled');

    expect($user->refresh()->two_factor_secret)->toBeNull();
});
