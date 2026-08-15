<?php

use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }
});

test('spareparts cannot be added once the service has been checked out', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $this->actingAs($cashier);

    $product = Product::factory()->create(['stock' => 10, 'min_stock' => 0]);
    $service = ServiceOrder::factory()->create(['status' => 'done', 'labor_cost' => 100_000, 'total_cost' => 100_000]);
    Sale::factory()->create([
        'type' => 'service',
        'service_order_id' => $service->id,
        'payment_status' => 'unpaid',
        'total' => 100_000,
        'paid_amount' => 0,
    ]);

    $this->post(route('services.items.store', $service), [
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 50_000,
    ])->assertRedirect();

    expect($service->items()->count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(10);
});

test('spareparts can still be added before checkout', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $this->actingAs($cashier);

    $product = Product::factory()->create(['stock' => 10, 'min_stock' => 0]);
    $service = ServiceOrder::factory()->create(['status' => 'pending']);

    $this->post(route('services.items.store', $service), [
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 50_000,
    ])->assertRedirect();

    expect($service->items()->count())->toBe(1)
        ->and($product->fresh()->stock)->toBe(8);
});

test('spareparts cannot be removed once the service has been checked out', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $this->actingAs($cashier);

    $product = Product::factory()->create(['stock' => 10, 'min_stock' => 0]);
    $service = ServiceOrder::factory()->create(['status' => 'done', 'labor_cost' => 100_000, 'total_cost' => 100_000]);
    $item = ServiceOrderItem::create([
        'service_order_id' => $service->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 2,
        'price' => 50_000,
        'subtotal' => 100_000,
    ]);
    Sale::factory()->create([
        'type' => 'service',
        'service_order_id' => $service->id,
        'payment_status' => 'paid',
        'total' => 200_000,
        'paid_amount' => 200_000,
    ]);

    $this->delete(route('services.items.destroy', [$service, $item]))->assertRedirect();

    expect($service->items()->count())->toBe(1)
        ->and($product->fresh()->stock)->toBe(10);
});

test('spareparts can still be removed before checkout, restoring stock', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $this->actingAs($cashier);

    $product = Product::factory()->create(['stock' => 10, 'min_stock' => 0]);
    $service = ServiceOrder::factory()->create(['status' => 'in_progress']);
    $item = ServiceOrderItem::create([
        'service_order_id' => $service->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 3,
        'price' => 50_000,
        'subtotal' => 150_000,
    ]);
    $product->decrement('stock', 3);

    $this->delete(route('services.items.destroy', [$service, $item]))->assertRedirect();

    expect($service->items()->count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(10);
});

test('a checked-out service cannot be cancelled', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $this->actingAs($cashier);

    $service = ServiceOrder::factory()->create(['status' => 'done', 'labor_cost' => 100_000, 'total_cost' => 100_000]);
    Sale::factory()->create([
        'type' => 'service',
        'service_order_id' => $service->id,
        'payment_status' => 'partial',
        'total' => 100_000,
        'paid_amount' => 40_000,
    ]);

    $this->patch(route('services.status', $service), ['status' => 'cancelled'])->assertRedirect();

    expect($service->fresh()->status)->toBe('done')
        ->and($service->fresh()->items()->count())->toBe(0);
});

test('a taken service cannot be cancelled', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $this->actingAs($cashier);

    $service = ServiceOrder::factory()->create(['status' => 'taken', 'labor_cost' => 100_000, 'total_cost' => 100_000]);

    $this->patch(route('services.status', $service), ['status' => 'cancelled'])->assertRedirect();

    expect($service->fresh()->status)->toBe('taken');
});

test('a checked-in service can still be cancelled, restoring parts to stock', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $this->actingAs($cashier);

    $product = Product::factory()->create(['stock' => 5, 'min_stock' => 0]);
    $service = ServiceOrder::factory()->create(['status' => 'pending']);
    ServiceOrderItem::create([
        'service_order_id' => $service->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 2,
        'price' => 50_000,
        'subtotal' => 100_000,
    ]);
    $product->decrement('stock', 2);

    $this->patch(route('services.status', $service), ['status' => 'cancelled'])->assertRedirect();

    expect($service->fresh()->status)->toBe('cancelled')
        ->and($product->fresh()->stock)->toBe(5)
        ->and($service->fresh()->items()->count())->toBe(0);
});

test('service invoice numbers are sequential per day', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $this->actingAs($cashier);

    $this->post(route('services.store'), ['item_name' => 'Laptop ASUS', 'complaint' => 'Tidak menyala'])->assertRedirect();
    $this->post(route('services.store'), ['item_name' => 'Laptop Lenovo', 'complaint' => 'Blue screen'])->assertRedirect();

    $orders = ServiceOrder::query()->orderBy('id')->get();

    expect($orders)->toHaveCount(2)
        ->and($orders[0]->invoice_number)->toMatch('/^SRV-\d{8}-\d{4}$/')
        ->and($orders[1]->invoice_number)->toMatch('/^SRV-\d{8}-\d{4}$/')
        ->and($orders[0]->invoice_number)->not->toBe($orders[1]->invoice_number);
});
