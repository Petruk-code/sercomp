<?php

use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }
});

test('forbidden access renders the error page with status 403', function () {
    $cashier = User::factory()->create()->assignRole('cashier');

    $this->actingAs($cashier)
        ->get(route('users.index'))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('status', 403));
});

test('missing routes render the error page with status 404', function () {
    $this->get('/tidak-ada')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('status', 404));
});
