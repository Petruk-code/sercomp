<?php

use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }
});

test('the show page exposes the tracking card url', function () {
    $user = User::factory()->create()->assignRole('admin');
    $customer = Customer::factory()->create(['phone' => '081234567890']);
    $service = ServiceOrder::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($user)
        ->get(route('services.show', $service))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('services/show')
            ->where('cardUrl', route('tracking', ['token' => $service->tracking_token]))
        );
});

test('the show page renders for a customer without a phone number', function () {
    $user = User::factory()->create()->assignRole('admin');
    $customer = Customer::factory()->create(['phone' => null]);
    $service = ServiceOrder::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($user)
        ->get(route('services.show', $service))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('services/show'));
});
