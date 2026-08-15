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

test('cashier is forbidden from managing roles', function () {
    $cashier = User::factory()->create()->assignRole('cashier');

    $this->actingAs($cashier)
        ->get(route('roles.index'))
        ->assertForbidden();
});

test('admin can view the roles index', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('roles/index')
            ->has('roles.data'));
});

test('admin can create a role', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('roles.store'), [
            'name' => 'supervisor',
            'label' => 'Supervisor',
        ])
        ->assertRedirect(route('roles.index'));

    $this->assertDatabaseHas('roles', [
        'name' => 'supervisor',
        'guard_name' => 'web',
        'label' => 'Supervisor',
    ]);
});

test('role name must be unique per guard', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('roles.store'), [
            'name' => 'admin',
            'label' => 'Administrator',
        ])
        ->assertSessionHasErrors('name');
});

test('built-in role cannot be renamed', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $adminRole = Role::query()->where('name', 'admin')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('roles.update', ['role' => $adminRole]), [
            'name' => 'superadmin',
            'label' => 'Super Admin',
        ])
        ->assertSessionHasErrors('name');

    $this->assertDatabaseHas('roles', ['id' => $adminRole->id, 'name' => 'admin']);
});

test('built-in role cannot be deleted', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $adminRole = Role::query()->where('name', 'admin')->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('roles.destroy', ['role' => $adminRole]));

    $this->assertDatabaseHas('roles', ['id' => $adminRole->id]);
});

test('role assigned to users cannot be deleted', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Role::query()->create(['name' => 'supervisor', 'guard_name' => 'web']);
    $supervisorRole = Role::query()->where('name', 'supervisor')->firstOrFail();

    $user = User::factory()->create()->assignRole('supervisor');

    $this->actingAs($admin)
        ->delete(route('roles.destroy', ['role' => $supervisorRole->id]));

    $this->assertDatabaseHas('roles', ['id' => $supervisorRole->id]);
    $this->assertTrue($user->hasRole('supervisor'));
});

test('custom role without users can be deleted', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $role = Role::query()->create(['name' => 'supervisor', 'guard_name' => 'web']);

    $this->actingAs($admin)
        ->delete(route('roles.destroy', ['role' => $role->id]));

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

test('roles count the number of assigned users', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('roles.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('roles.data.0', fn (Assert $role) => $role->where('users_count', 1)->etc()));
});
