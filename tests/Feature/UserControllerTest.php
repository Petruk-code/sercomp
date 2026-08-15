<?php

use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(
            ['name' => $role, 'guard_name' => 'web'],
            ['label' => ucfirst($role)],
        );
    }
});

test('cashier is forbidden from managing users', function () {
    $cashier = User::factory()->create()->assignRole('cashier');

    $this->actingAs($cashier)
        ->get(route('users.create'))
        ->assertForbidden();
});

test('admin can view the users index with roles', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/index')
            ->has('users.data')
            ->has('roles'));
});

test('admin can create a user', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@sercomp.test',
            'password' => 'secret-password',
            'role' => 'cashier',
        ])
        ->assertRedirect(route('users.index'));

    $user = User::query()->where('email', 'budi@sercomp.test')->firstOrFail();
    expect($user->hasRole('cashier'))->toBeTrue();
});

test('user email must be unique', function () {
    $admin = User::factory()->create()->assignRole('admin');
    User::factory()->create(['email' => 'budi@sercomp.test']);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@sercomp.test',
            'password' => 'secret-password',
            'role' => 'cashier',
        ])
        ->assertSessionHasErrors('email');
});

test('admin can update a user', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $user = User::factory()->create()->assignRole('cashier');

    $this->actingAs($admin)
        ->put(route('users.update', ['user' => $user]), [
            'name' => 'Budi Baru',
            'email' => $user->email,
            'role' => 'technician',
        ])
        ->assertRedirect(route('users.index'));

    $user->refresh();
    expect($user->name)->toBe('Budi Baru');
    expect($user->hasRole('technician'))->toBeTrue();
});

test('admin cannot demote the last admin', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('users.update', ['user' => $admin]), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'cashier',
        ]);

    expect($admin->refresh()->hasRole('admin'))->toBeTrue();
});

test('admin cannot delete themselves', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->delete(route('users.destroy', ['user' => $admin]));

    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('admin cannot delete the last admin', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->delete(route('users.destroy', ['user' => $admin]));

    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('admin can delete another user', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $cashier = User::factory()->create()->assignRole('cashier');

    $this->actingAs($admin)
        ->delete(route('users.destroy', ['user' => $cashier]));

    $this->assertDatabaseMissing('users', ['id' => $cashier->id]);
});
