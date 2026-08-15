<?php

use App\Models\Customer;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }
});

test('staff can view the customer profile with their service history', function () {
    $user = User::factory()->create()->assignRole('cashier');
    $customer = Customer::factory()->create();
    $orders = ServiceOrder::factory()->count(3)->create(['customer_id' => $customer->id]);
    $latest = $orders->sortByDesc('id')->first();

    $this->actingAs($user)
        ->get(route('master.customers.show', $customer))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('master/customers/show')
            ->where('customer.name', $customer->name)
            ->where('customer.service_orders_count', 3)
            ->where('serviceOrders.data.0.invoice_number', $latest->invoice_number)
            ->has('serviceOrders.data', 3)
            ->has('statusLabels')
        );
});

test('the customer profile reports total paid from settled sales', function () {
    $user = User::factory()->create()->assignRole('admin');
    $customer = Customer::factory()->create();
    Sale::factory()->create([
        'customer_id' => $customer->id,
        'type' => 'service',
        'payment_status' => 'paid',
        'total' => 250000,
    ]);
    Sale::factory()->create([
        'customer_id' => $customer->id,
        'type' => 'retail',
        'payment_status' => 'unpaid',
        'total' => 100000,
    ]);

    $this->actingAs($user)
        ->get(route('master.customers.show', $customer))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('master/customers/show')
            ->where('totalPaid', fn (float $total) => $total === 250000.0)
        );
});

test('customers without service history show an empty list', function () {
    $user = User::factory()->create()->assignRole('admin');
    $customer = Customer::factory()->create();

    $this->actingAs($user)
        ->get(route('master.customers.show', $customer))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('master/customers/show')
            ->has('serviceOrders.data', 0)
        );
});

test('technicians cannot access the customer profile', function () {
    $user = User::factory()->create()->assignRole('technician');
    $customer = Customer::factory()->create();

    $this->actingAs($user)
        ->get(route('master.customers.show', $customer))
        ->assertForbidden();
});
