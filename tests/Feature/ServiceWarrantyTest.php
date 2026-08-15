<?php

use App\Models\ServiceOrder;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }
});

test('completing a service order records completed_at and computes the warranty end date', function () {
    $user = User::factory()->create()->assignRole('cashier');
    $service = ServiceOrder::factory()->create([
        'status' => 'in_progress',
        'warranty_days' => 30,
        'warranty_notes' => 'Bebas biaya jasa',
    ]);

    $this->actingAs($user)
        ->patch(route('services.status', $service), ['status' => 'done'])
        ->assertRedirect();

    $service->refresh();

    expect($service->status)->toBe('done')
        ->and($service->completed_at)->not->toBeNull()
        ->and($service->warranty_end_date->toDateString())->toBe($service->completed_at->copy()->addDays(30)->toDateString())
        ->and($service->warrantyStatus())->toBe('active');
});

test('warranty end date stays null until the order is completed', function () {
    $user = User::factory()->create()->assignRole('cashier');
    $service = ServiceOrder::factory()->create([
        'status' => 'pending',
        'warranty_days' => 30,
    ]);

    $this->actingAs($user)
        ->patch(route('services.status', $service), ['status' => 'in_progress']);

    expect($service->refresh()->warranty_end_date)->toBeNull()
        ->and($service->warrantyStatus())->toBe('pending');
});

test('editing warranty days on a completed order recomputes the end date', function () {
    $user = User::factory()->create()->assignRole('cashier');
    $service = ServiceOrder::factory()->create([
        'status' => 'done',
        'completed_at' => now()->subDays(5),
        'warranty_days' => 30,
    ]);

    $this->actingAs($user)
        ->put(route('services.update', $service), [
            'customer_id' => $service->customer_id,
            'item_name' => $service->item_name,
            'complaint' => $service->complaint,
            'estimated_cost' => $service->estimated_cost,
            'labor_cost' => $service->labor_cost,
            'warranty_days' => 90,
            'warranty_notes' => 'Garansi diperpanjang',
        ])
        ->assertRedirect();

    $service->refresh();

    expect($service->warranty_days)->toBe(90)
        ->and($service->warranty_notes)->toBe('Garansi diperpanjang')
        ->and($service->warranty_end_date->toDateString())->toBe($service->completed_at->copy()->addDays(90)->toDateString());
});

test('service orders can be filtered by warranty status', function () {
    $user = User::factory()->create()->assignRole('admin');

    $expired = ServiceOrder::factory()->create([
        'status' => 'done',
        'completed_at' => now()->subDays(60),
        'warranty_days' => 30,
    ]);
    $expired->recomputeWarrantyEndDate();

    $active = ServiceOrder::factory()->create([
        'status' => 'done',
        'completed_at' => now()->subDays(5),
        'warranty_days' => 30,
    ]);
    $active->recomputeWarrantyEndDate();

    $pending = ServiceOrder::factory()->create([
        'status' => 'in_progress',
        'warranty_days' => 30,
    ]);

    ServiceOrder::factory()->create(['status' => 'done', 'warranty_days' => null]);

    $this->actingAs($user)
        ->get(route('services.index', ['warranty' => 'expired']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('services/index')
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $expired->id)
        );

    $this->actingAs($user)
        ->get(route('services.index', ['warranty' => 'active']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('services/index')
            ->has('orders.data', 2)
        );

    $this->actingAs($user)
        ->get(route('services.index', ['warranty' => 'none']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('services/index')
            ->has('orders.data', 1)
            ->where('orders.data.0.warranty_days', null)
        );
});

test('service orders without a warranty report no warranty status', function () {
    $user = User::factory()->create()->assignRole('cashier');
    $service = ServiceOrder::factory()->create(['warranty_days' => null]);

    expect($service->warrantyStatus())->toBe('none')
        ->and($service->warranty_end_date)->toBeNull();
});
