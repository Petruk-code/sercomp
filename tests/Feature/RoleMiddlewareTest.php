<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }
});

test('guests are redirected to login for protected pages', function () {
    $this->get(route('master.products.index'))->assertRedirect(route('login'));
    $this->get(route('master.categories.index'))->assertRedirect(route('login'));
});

test('admin can access admin-only pages', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('master.products.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('reports.sales'))
        ->assertOk();
});

test('cashier cannot access admin-only pages', function () {
    $cashier = User::factory()->create()->assignRole('cashier');

    $this->actingAs($cashier)
        ->get(route('master.products.index'))
        ->assertForbidden();

    $this->actingAs($cashier)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('cashier can access cashier pages', function () {
    $cashier = User::factory()->create()->assignRole('cashier');

    $this->actingAs($cashier)
        ->get(route('sales.create'))
        ->assertOk();

    $this->actingAs($cashier)
        ->get(route('master.customers.index'))
        ->assertOk();
});

test('technician can access service pages but not admin pages', function () {
    $technician = User::factory()->create()->assignRole('technician');

    $this->actingAs($technician)
        ->get(route('services.index'))
        ->assertOk();

    $this->actingAs($technician)
        ->get(route('master.products.index'))
        ->assertForbidden();
});

test('user without any role is forbidden from role-protected pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('master.products.index'))
        ->assertForbidden();
});
