<?php

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

function actingAsCashier(): User
{
    $cashier = User::factory()->create()->assignRole('cashier');

    test()->actingAs($cashier);

    return $cashier;
}

test('checkout at completion confirms the final labor fee and marks the service taken when paid in full', function () {
    $cashier = actingAsCashier();

    $service = ServiceOrder::factory()->create([
        'status' => 'done',
        'labor_cost' => 0,
        'total_cost' => 0,
    ]);
    ServiceOrderItem::create([
        'service_order_id' => $service->id,
        'name' => 'Sparepart X',
        'quantity' => 1,
        'price' => 50_000,
        'subtotal' => 50_000,
    ]);

    $this->post(route('sales.checkout', $service), [
        'payment_method' => 'cash',
        'paid_amount' => 150_000,
        'labor_cost' => 100_000,
        'discount' => 0,
    ])->assertRedirect();

    $service->refresh();

    expect((float) $service->labor_cost)->toBe(100_000.0)
        ->and((float) $service->total_cost)->toBe(150_000.0)
        ->and($service->status)->toBe('taken');

    $sale = $service->sale()->first();

    expect($sale)->not->toBeNull()
        ->and($sale->payment_status)->toBe('paid')
        ->and($sale->type)->toBe('service')
        ->and((float) $sale->total)->toBe(150_000.0)
        ->and($sale->items()->where('name', 'like', 'Jasa servis:%')->count())->toBe(1);

    expect($service->statusHistories()->where('status', 'taken')->exists())->toBeTrue();
});

test('partial checkout keeps the service done until the remainder is settled', function () {
    $cashier = actingAsCashier();

    $service = ServiceOrder::factory()->create([
        'status' => 'done',
        'labor_cost' => 100_000,
        'total_cost' => 100_000,
    ]);

    $this->post(route('sales.checkout', $service), [
        'payment_method' => 'transfer',
        'paid_amount' => 40_000,
    ])->assertRedirect();

    $service->refresh();

    expect($service->status)->toBe('done');

    $sale = $service->sale()->first();

    expect($sale->payment_status)->toBe('partial')
        ->and((float) $sale->paid_amount)->toBe(40_000.0);

    $this->post(route('sales.payment', $sale), [
        'paid_amount' => 60_000,
    ])->assertRedirect();

    $service->refresh();

    expect($service->status)->toBe('taken')
        ->and($sale->fresh()->payment_status)->toBe('paid')
        ->and((float) $sale->fresh()->paid_amount)->toBe(100_000.0);

    expect($service->statusHistories()->where('status', 'taken')->exists())->toBeTrue();
});

test('complete payment is rejected once the sale is already paid', function () {
    $cashier = actingAsCashier();

    $service = ServiceOrder::factory()->create(['status' => 'done', 'labor_cost' => 100_000, 'total_cost' => 100_000]);
    $sale = Sale::factory()->create([
        'type' => 'service',
        'service_order_id' => $service->id,
        'payment_status' => 'paid',
        'total' => 100_000,
        'paid_amount' => 100_000,
    ]);

    $this->post(route('sales.payment', $sale), ['paid_amount' => 50_000])
        ->assertStatus(422);
});

test('a service cannot be marked taken unless its sale is paid', function () {
    $cashier = actingAsCashier();

    $service = ServiceOrder::factory()->create(['status' => 'done', 'labor_cost' => 100_000, 'total_cost' => 100_000]);
    Sale::factory()->create([
        'type' => 'service',
        'service_order_id' => $service->id,
        'payment_status' => 'unpaid',
        'total' => 100_000,
        'paid_amount' => 0,
    ]);

    $this->patch(route('services.status', $service), ['status' => 'taken'])->assertRedirect();

    expect($service->fresh()->status)->toBe('done');

    $sale = $service->sale()->first();
    $sale->update(['payment_status' => 'paid', 'paid_amount' => 100_000]);

    $this->patch(route('services.status', $service), ['status' => 'taken'])->assertRedirect();

    expect($service->fresh()->status)->toBe('taken');
});

test('technicians cannot check out a completed service', function () {
    $technician = User::factory()->create()->assignRole('technician');

    $service = ServiceOrder::factory()->create(['status' => 'done', 'labor_cost' => 50_000, 'total_cost' => 50_000]);

    $this->actingAs($technician)
        ->post(route('sales.checkout', $service), ['payment_method' => 'cash', 'paid_amount' => 50_000])
        ->assertForbidden();

    expect($service->sale()->exists())->toBeFalse();
});
