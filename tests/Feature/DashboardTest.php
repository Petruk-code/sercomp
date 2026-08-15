<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('technicians see a personalized dashboard with their workload', function () {
    Role::query()->updateOrCreate(['name' => 'technician'], ['guard_name' => 'web']);
    $technician = User::factory()->create()->assignRole('technician');
    $customer = Customer::factory()->create();

    $mine = ServiceOrder::factory()->create([
        'customer_id' => $customer->id,
        'technician_id' => $technician->id,
        'status' => 'in_progress',
    ]);
    $incoming = ServiceOrder::factory()->create([
        'customer_id' => $customer->id,
        'technician_id' => null,
        'status' => 'pending',
    ]);
    ServiceOrder::factory()->create([
        'customer_id' => $customer->id,
        'technician_id' => $technician->id,
        'status' => 'taken',
    ]);

    $this->actingAs($technician)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/technician')
            ->where('stats.incoming', 1)
            ->where('stats.my_active', 1)
            ->where('stats.in_progress', 1)
            ->where('stats.done_pending_pickup', 0)
            ->has('incomingServices', 1, fn (Assert $order) => $order->where('id', $incoming->id)->etc())
            ->has('myServices', 2));
});

test('technician dashboard renders empty state without service orders', function () {
    Role::query()->updateOrCreate(['name' => 'technician'], ['guard_name' => 'web']);
    $technician = User::factory()->create()->assignRole('technician');

    $this->actingAs($technician)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/technician')
            ->where('stats.incoming', 0)
            ->where('stats.my_active', 0)
            ->has('incomingServices', 0)
            ->has('myServices', 0));
});

test('operations dashboard exposes sales and service trends per period', function () {
    Role::query()->updateOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
    $admin = User::factory()->create()->assignRole('admin');
    $customer = Customer::factory()->create();

    Sale::factory()->create(['customer_id' => $customer->id, 'total' => 150_000, 'created_at' => now()->subMonths(2)->startOfMonth()]);
    Sale::factory()->create(['customer_id' => $customer->id, 'total' => 50_000, 'created_at' => now()]);
    ServiceOrder::factory()->create(['customer_id' => $customer->id, 'created_at' => now()->subMonths(2)->startOfMonth()]);
    ServiceOrder::factory()->create(['customer_id' => $customer->id, 'created_at' => now()]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('trends.7d.sales', 7)
            ->has('trends.7d.services', 7)
            ->has('trends.30d.sales', 30)
            ->has('trends.30d.services', 30)
            ->has('trends.12m.sales', 12)
            ->has('trends.12m.services', 12)
            ->where('trends.7d.sales.6.value', 50_000)
            ->where('trends.7d.services.6.value', 1)
            ->where('trends.30d.sales.29.value', 50_000)
            ->where('trends.30d.services.29.value', 1)
            ->where('trends.12m.sales.11.value', 50_000)
            ->where('trends.12m.services.11.value', 1)
            ->where('trends.12m.sales.9.value', 150_000)
            ->where('trends.12m.services.9.value', 1));
});

test('operations dashboard lists low stock products and the service queue', function () {
    Role::query()->updateOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
    $admin = User::factory()->create()->assignRole('admin');
    $customer = Customer::factory()->create();

    $outOfStock = Product::factory()->create(['stock' => 0, 'min_stock' => 5]);
    $critical = Product::factory()->create(['stock' => 2, 'min_stock' => 10]);
    Product::factory()->create(['stock' => 20, 'min_stock' => 5]);
    Product::factory()->create(['stock' => 0, 'min_stock' => 0]);

    $queued = ServiceOrder::factory()->create(['customer_id' => $customer->id, 'status' => 'pending', 'created_at' => now()->subDays(3)]);
    ServiceOrder::factory()->create(['customer_id' => $customer->id, 'status' => 'done']);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('lowStockProducts', 2)
            ->where('lowStockProducts.0.id', $outOfStock->id)
            ->where('lowStockProducts.1.id', $critical->id)
            ->has('serviceQueue', 1)
            ->where('serviceQueue.0.id', $queued->id));
});
