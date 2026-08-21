<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Api\AwaitableWebpage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Browser Defaults
|--------------------------------------------------------------------------
|
| Halaman aplikasi memuat banyak modul via Vite; beri waktu lebih longgar
| daripada default 5 detik agar smoke test tidak flaky saat dev server dingin.
|
*/

pest()->browser()->timeout(15000);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Seed the four application roles (Spatie) — dipakai test Feature & Browser.
 */
function seedAppRoles(): void
{
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }
}

/**
 * Login sebagai admin lewat form login asli di browser (bukan actingAs),
 * sehingga session cookie browser valid untuk visit() berikutnya.
 *
 * PENTING: setiap panggilan visit() membuat context browser baru tanpa
 * cookie sesi. Karena itu helper ini mengembalikan halaman yang sudah
 * login — lanjutkan alur dari instance ini dan pindah halaman dengan
 * navigate(), jangan memanggil visit() lagi.
 */
function loginBrowserAdmin(): AwaitableWebpage
{
    seedAppRoles();

    $user = User::factory()->create();
    $user->assignRole('admin');

    $page = visit('/login')
        ->fill('email', $user->email)
        ->fill('password', 'password')
        ->click('@login-button')
        ->assertPathIs('/dashboard');

    return $page;
}
