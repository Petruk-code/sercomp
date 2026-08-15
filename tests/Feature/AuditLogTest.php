<?php

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\Supplier;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }
});

function actingAsAuditAdmin(): User
{
    $admin = User::factory()->create()->assignRole('admin');

    test()->actingAs($admin);

    return $admin;
}

function auditPayload(Product $product, array $overrides = []): array
{
    return array_merge([
        'type' => 'retail',
        'items' => [
            ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 2, 'price' => 100_000],
        ],
        'payment_method' => 'cash',
        'paid_amount' => 0,
        'total' => 200_000,
        'payment_status' => 'unpaid',
    ], $overrides);
}

function makeAuditPurchase(Product $product): Purchase
{
    $purchase = Purchase::factory()->create([
        'subtotal' => 30_000,
        'discount' => 0,
        'total' => 30_000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
    ]);
    $purchase->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 3,
        'purchase_price' => 10_000,
        'subtotal' => 30_000,
    ]);
    $product->increment('stock', 3);

    return $purchase;
}

test('admin can view the audit log page', function () {
    actingAsAuditAdmin();

    AuditLog::query()->create([
        'action' => 'sale.created',
        'description' => 'Test entry',
        'new_values' => ['total' => 100_000],
    ]);

    $this->get(route('audit-logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audit-logs/index')
            ->has('logs.data', 1)
            ->has('actionLabels'));
});

test('non-admin roles cannot view the audit log page', function () {
    $cashier = User::factory()->create()->assignRole('cashier');

    $this->actingAs($cashier)
        ->get(route('audit-logs.index'))
        ->assertForbidden();
});

test('retail sale creation and payment are audited', function () {
    $admin = actingAsAuditAdmin();
    $product = Product::factory()->create(['stock' => 10, 'selling_price' => 100_000]);

    $this->post(route('sales.store'), auditPayload($product))
        ->assertRedirect();

    $sale = Sale::query()->first();

    $created = AuditLog::query()->where('action', 'sale.created')->where('auditable_id', $sale->id)->first();
    $stockLog = AuditLog::query()->where('action', 'product.stock_changed')->where('auditable_id', $product->id)->first();

    expect($created)->not->toBeNull()
        ->and($created->user_id)->toBe($admin->id)
        ->and($created->auditable_type)->toBe(Sale::class)
        ->and((float) $created->new_values['total'])->toBe(200_000.0)
        ->and($created->old_values)->toBeNull()
        ->and($stockLog)->not->toBeNull()
        ->and((int) $stockLog->old_values['stock'])->toBe(10)
        ->and((int) $stockLog->new_values['stock'])->toBe(8);

    $this->post(route('sales.payment', $sale), ['paid_amount' => 200_000])
        ->assertRedirect();

    $payment = AuditLog::query()->where('action', 'sale.payment')->where('auditable_id', $sale->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->new_values['payment_status'])->toBe('paid')
        ->and($payment->old_values['payment_status'])->toBe('unpaid')
        ->and((float) $payment->old_values['paid_amount'])->toBe(0.0);
});

test('purchase store, payment, and deletion are audited', function () {
    $admin = actingAsAuditAdmin();
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create(['stock' => 10, 'purchase_price' => 10_000]);

    $this->post(route('supplier.purchases.store'), [
        'supplier_id' => $supplier->id,
        'purchase_date' => '2026-08-01',
        'due_date' => '2026-08-15',
        'discount' => 0,
        'items' => [
            ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 3, 'purchase_price' => 10_000],
        ],
    ])->assertRedirect();

    $purchase = Purchase::query()->first();

    expect(AuditLog::query()->where('action', 'purchase.created')->where('auditable_id', $purchase->id)->exists())->toBeTrue();

    $this->post(route('supplier.purchases.pay', $purchase), [
        'amount' => 30_000,
        'payment_method' => 'cash',
    ])->assertRedirect();

    $payment = AuditLog::query()->where('action', 'purchase.payment')->where('auditable_id', $purchase->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->new_values['payment_status'])->toBe('paid')
        ->and($payment->old_values['payment_status'])->toBe('unpaid')
        ->and($payment->user_id)->toBe($admin->id);

    $this->delete(route('supplier.purchases.destroy', $purchase))
        ->assertRedirect();

    expect(AuditLog::query()->where('action', 'purchase.deleted')->where('auditable_id', $purchase->id)->exists())->toBeTrue();
});

test('purchase return creation and deletion are audited', function () {
    $admin = actingAsAuditAdmin();
    $product = Product::factory()->create(['stock' => 10, 'purchase_price' => 10_000]);
    $purchase = makeAuditPurchase($product);

    $this->post(route('supplier.returns.store', $purchase), [
        'return_date' => '2026-08-10',
        'result_type' => 'replacement',
        'items' => [
            ['purchase_item_id' => $purchase->items()->first()->id, 'quantity' => 2, 'price' => 10_000],
        ],
    ])->assertRedirect();

    $return = PurchaseReturn::query()->first();

    expect(AuditLog::query()->where('action', 'purchase_return.created')->where('auditable_id', $return->id)->exists())->toBeTrue();

    $this->delete(route('supplier.returns.destroy', $return))
        ->assertRedirect();

    expect(AuditLog::query()->where('action', 'purchase_return.deleted')->where('auditable_id', $return->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'purchase_return.deleted')->first()->user_id)->toBe($admin->id);
});

test('service order status change is audited', function () {
    actingAsAuditAdmin();

    $service = ServiceOrder::factory()->create(['status' => 'pending']);

    $this->patch(route('services.status', $service), ['status' => 'done'])
        ->assertRedirect();

    $log = AuditLog::query()->where('action', 'service.status_changed')->where('auditable_id', $service->id)->first();

    expect($log)->not->toBeNull()
        ->and($log->old_values['status'])->toBe('pending')
        ->and($log->new_values['status'])->toBe('done');
});

test('service sparepart add and remove audit stock changes', function () {
    actingAsAuditAdmin();

    $service = ServiceOrder::factory()->create(['status' => 'pending']);
    $product = Product::factory()->create(['stock' => 10]);

    $this->post(route('services.items.store', $service), [
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 50_000,
    ])->assertRedirect();

    $added = AuditLog::query()->where('action', 'product.stock_changed')->where('auditable_id', $product->id)->first();

    expect($added)->not->toBeNull()
        ->and($added->old_values['stock'])->toBe(10)
        ->and($added->new_values['stock'])->toBe(8);

    $item = $service->items()->first();

    $this->delete(route('services.items.destroy', [$service, $item]))
        ->assertRedirect();

    $removed = AuditLog::query()->where('action', 'product.stock_changed')->where('auditable_id', $product->id)->latest('id')->first();

    expect($removed->new_values['stock'])->toBe(10);
});

test('whatsapp 2fa enable and disable are audited', function () {
    $admin = User::factory()->create(['phone' => '081234567890'])->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('security.whatsapp-2fa.enable'))
        ->assertRedirect();

    expect($admin->fresh()->whatsapp_2fa_enabled)->toBeTrue()
        ->and(AuditLog::query()->where('action', 'whatsapp_2fa.enabled')->where('user_id', $admin->id)->exists())->toBeTrue();

    $this->post(route('security.whatsapp-2fa.disable'))
        ->assertRedirect();

    expect($admin->fresh()->whatsapp_2fa_enabled)->toBeFalse()
        ->and(AuditLog::query()->where('action', 'whatsapp_2fa.disabled')->where('user_id', $admin->id)->exists())->toBeTrue();
});

test('audit log index filters by action', function () {
    actingAsAuditAdmin();

    AuditLog::query()->create(['action' => 'sale.created', 'description' => 'sale one']);
    AuditLog::query()->create(['action' => 'purchase.created', 'description' => 'purchase one']);

    $this->get(route('audit-logs.index', ['action' => 'sale.created']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audit-logs/index')
            ->has('logs.data', 1)
            ->where('logs.data.0.action', 'sale.created'));
});
