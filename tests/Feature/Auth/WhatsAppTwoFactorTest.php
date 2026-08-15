<?php

use App\Models\User;
use App\Models\WhatsappOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

function fonnteFake(?string &$sentCode = null): void
{
    Http::fake(['api.fonnte.com/*' => function ($request) use (&$sentCode) {
        if (preg_match('/\*(\d{6})\*/', (string) ($request['message'] ?? ''), $matches)) {
            $sentCode = $matches[1];
        }

        return Http::response(['status' => true]);
    }]);

    config(['services.fonnte.api_key' => 'test-key']);
    config(['services.fonnte.otp_force_send' => true]);
}

test('whatsapp otp challenge screen can be rendered', function () {
    $user = User::factory()->withWhatsappTwoFactor()->create();

    $this->withSession(['login.id' => $user->id])
        ->get(route('two-factor.whatsapp'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/whatsapp-otp-challenge')
            ->has('phoneMasked'));
});

test('users with whatsapp 2fa enabled are redirected to the otp challenge on login', function () {
    fonnteFake();

    $user = User::factory()->withWhatsappTwoFactor()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.whatsapp'));

    $this->assertGuest();
});

test('users without whatsapp 2fa are not challenged', function () {
    fonnteFake();

    $user = User::factory()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('users with whatsapp 2fa enabled but no phone are not challenged', function () {
    fonnteFake();

    $user = User::factory()->create(['whatsapp_2fa_enabled' => true]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('otp is logged and never delivered to the gateway outside production', function () {
    Http::fake();

    config(['services.fonnte.api_key' => 'test-key']);

    $user = User::factory()->withWhatsappTwoFactor()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.whatsapp'));

    Http::assertNothingSent();

    expect(WhatsappOtp::where('user_id', $user->id)->count())->toBe(1);
});

test('users can authenticate with a valid whatsapp otp code', function () {
    $sentCode = null;
    fonnteFake($sentCode);

    $user = User::factory()->withWhatsappTwoFactor()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.whatsapp'));

    expect($sentCode)->not->toBeNull();

    $this->post(route('two-factor.whatsapp.verify'), ['code' => $sentCode])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);

    expect(WhatsappOtp::where('user_id', $user->id)->count())->toBe(0);
});

test('users can not authenticate with an invalid whatsapp otp code', function () {
    fonnteFake();

    $user = User::factory()->withWhatsappTwoFactor()->create();

    WhatsappOtp::query()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->withSession(['login.id' => $user->id])
        ->post(route('two-factor.whatsapp.verify'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $this->assertGuest();

    expect(WhatsappOtp::where('user_id', $user->id)->first()->attempts)->toBe(1);
});

test('otp codes are locked after exceeding the maximum attempts', function () {
    fonnteFake();

    $user = User::factory()->withWhatsappTwoFactor()->create();

    WhatsappOtp::query()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(5),
        'attempts' => 3,
    ]);

    $this->withSession(['login.id' => $user->id])
        ->post(route('two-factor.whatsapp.verify'), ['code' => '123456'])
        ->assertSessionHasErrors('code');

    $this->assertGuest();

    expect(WhatsappOtp::where('user_id', $user->id)->count())->toBe(0);
});

test('a new otp code can be resent', function () {
    $sentCode = null;
    fonnteFake($sentCode);

    $user = User::factory()->withWhatsappTwoFactor()->create();

    $this->withSession(['login.id' => $user->id])
        ->get(route('two-factor.whatsapp'))
        ->assertOk();

    $this->withSession(['login.id' => $user->id])
        ->post(route('two-factor.whatsapp.resend'))
        ->assertRedirect(route('two-factor.whatsapp'));

    expect($sentCode)->not->toBeNull();
    expect(WhatsappOtp::where('user_id', $user->id)->count())->toBe(1);
});

test('whatsapp 2fa can be enabled from settings when a phone number is set', function () {
    $user = User::factory()->create(['phone' => '6281234567890']);

    $this->actingAs($user)
        ->post(route('security.whatsapp-2fa.enable'))
        ->assertRedirect();

    expect($user->refresh()->whatsapp_2fa_enabled)->toBeTrue();
});

test('whatsapp 2fa can not be enabled without a phone number', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('security.whatsapp-2fa.enable'))
        ->assertRedirect()
        ->assertSessionHas('inertia.flash_data.toast');

    expect($user->refresh()->whatsapp_2fa_enabled)->toBeFalse();
});

test('whatsapp 2fa can be disabled from settings and clears pending codes', function () {
    $user = User::factory()->withWhatsappTwoFactor()->create();

    WhatsappOtp::query()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->actingAs($user)
        ->post(route('security.whatsapp-2fa.disable'))
        ->assertRedirect();

    expect($user->refresh()->whatsapp_2fa_enabled)->toBeFalse();
    expect(WhatsappOtp::where('user_id', $user->id)->count())->toBe(0);
});

test('security page exposes whatsapp 2fa state', function () {
    $user = User::factory()->withWhatsappTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('whatsapp2faEnabled', true)
            ->where('whatsapp2faPhone', $user->phone));
});
