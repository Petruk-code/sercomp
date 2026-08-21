<?php

/*
|--------------------------------------------------------------------------
| Smoke test seluruh route autentikasi
|--------------------------------------------------------------------------
|
| Sesuai rekomendasi pengembangan 2.1: setiap halaman utama dikunjungi
| dengan browser sungguhan sebagai admin, lalu dipastikan tidak ada
| error JavaScript maupun console error (assertNoSmoke).
|
*/

it('renders public pages without smoke issues', function () {
    visit(['/login', '/tracking'])->assertNoSmoke();
});

it('renders guest redirect for protected pages', function () {
    visit('/dashboard')->assertPathIs('/login');
});

it('smoke tests dashboard and master data pages as admin', function () {
    loginBrowserAdmin();

    visit([
        '/dashboard',
        '/products',
        '/products/create',
        '/categories',
        '/categories/create',
        '/customers',
        '/customers/create',
        '/suppliers',
        '/suppliers/create',
        '/catalog/services',
        '/catalog/services/create',
    ])->assertNoSmoke();
});

it('smoke tests operational pages as admin', function () {
    loginBrowserAdmin();

    visit([
        '/pos',
        '/services',
        '/services/create',
        '/sales',
        '/supplier/purchases',
        '/supplier/purchases/create',
        '/supplier/returns',
    ])->assertNoSmoke();
});

it('smoke tests report and administration pages as admin', function () {
    loginBrowserAdmin();

    visit([
        '/reports/sales',
        '/reports/services',
        '/reports/debts',
        '/reports/stock',
        '/reports/purchases',
        '/users',
        '/users/create',
        '/roles',
        '/roles/create',
        '/audit-logs',
        '/notifications',
        '/settings/profile',
        '/settings/security',
    ])->assertNoSmoke();
});
