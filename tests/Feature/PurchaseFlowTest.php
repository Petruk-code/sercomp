<?php

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }
});

function actingAsPurchaseAdmin(): User
{
    $admin = User::factory()->create()->assignRole('admin');

    test()->actingAs($admin);

    return $admin;
}

function purchasePayload(Supplier $supplier, Product $product, int $quantity = 3, int $price = 10_000, array $overrides = []): array
{
    return array_merge([
        'supplier_id' => $supplier->id,
        'purchase_date' => '2026-08-01',
        'due_date' => '2026-08-15',
        'discount' => 0,
        'items' => [
            ['product_id' => $product->id, 'name' => $product->name, 'quantity' => $quantity, 'purchase_price' => $price],
        ],
    ], $overrides);
}

test('admin can record a purchase, generating a PO number and increasing stock', function () {
    $admin = actingAsPurchaseAdmin();
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create(['stock' => 10, 'purchase_price' => 10_000]);

    $this->post(route('supplier.purchases.store'), purchasePayload($supplier, $product))
        ->assertRedirect();

    $purchase = Purchase::query()->first();

    expect($purchase)->not->toBeNull()
        ->and($purchase->purchase_number)->toStartWith('PO-')
        ->and($purchase->user_id)->toBe($admin->id)
        ->and($purchase->payment_status)->toBe('unpaid')
        ->and((float) $purchase->paid_amount)->toBe(0.0)
        ->and((float) $purchase->total)->toBe(30_000.0)
        ->and($purchase->items()->count())->toBe(1)
        ->and($product->fresh()->stock)->toBe(13);
});

test('cashiers cannot record purchases', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($cashier)
        ->post(route('supplier.purchases.store'), purchasePayload($supplier, $product))
        ->assertForbidden();

    expect(Purchase::query()->count())->toBe(0);
});

test('purchase totals account for discount and multiple item subtotals', function () {
    actingAsPurchaseAdmin();
    $supplier = Supplier::factory()->create();
    $productA = Product::factory()->create(['purchase_price' => 10_000]);
    $productB = Product::factory()->create(['purchase_price' => 25_000]);

    $this->post(route('supplier.purchases.store'), [
        'supplier_id' => $supplier->id,
        'purchase_date' => '2026-08-01',
        'due_date' => null,
        'discount' => 5_000,
        'items' => [
            ['product_id' => $productA->id, 'name' => $productA->name, 'quantity' => 2, 'purchase_price' => 10_000],
            ['product_id' => $productB->id, 'name' => $productB->name, 'quantity' => 1, 'purchase_price' => 25_000],
        ],
    ])->assertRedirect();

    $purchase = Purchase::query()->first();

    expect((float) $purchase->subtotal)->toBe(45_000.0)
        ->and((float) $purchase->discount)->toBe(5_000.0)
        ->and((float) $purchase->total)->toBe(40_000.0)
        ->and($productA->fresh()->stock)->toBe($productA->stock + 2);
});

test('a partial payment moves the purchase to partial and a final one to paid', function () {
    actingAsPurchaseAdmin();
    $purchase = Purchase::factory()->create([
        'subtotal' => 100_000,
        'discount' => 0,
        'total' => 100_000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'due_date' => now()->addDays(14)->toDateString(),
    ]);

    $this->post(route('supplier.purchases.pay', $purchase), [
        'amount' => 40_000,
        'payment_method' => 'transfer',
    ])->assertRedirect();

    expect($purchase->fresh()->payment_status)->toBe('partial')
        ->and((float) $purchase->fresh()->paid_amount)->toBe(40_000.0)
        ->and($purchase->payments()->count())->toBe(1);

    $this->post(route('supplier.purchases.pay', $purchase), [
        'amount' => 60_000,
        'payment_method' => 'cash',
    ])->assertRedirect();

    expect($purchase->fresh()->payment_status)->toBe('paid')
        ->and((float) $purchase->fresh()->paid_amount)->toBe(100_000.0)
        ->and($purchase->payments()->count())->toBe(2);
});

test('a payment larger than the remaining balance is rejected', function () {
    actingAsPurchaseAdmin();
    $purchase = Purchase::factory()->create([
        'total' => 100_000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
    ]);

    $this->post(route('supplier.purchases.pay', $purchase), [
        'amount' => 150_000,
        'payment_method' => 'cash',
    ])->assertRedirect();

    expect($purchase->fresh()->payment_status)->toBe('unpaid')
        ->and($purchase->payments()->count())->toBe(0);
});

test('payment is rejected once the purchase is already paid', function () {
    actingAsPurchaseAdmin();
    $purchase = Purchase::factory()->paid()->create([
        'total' => 100_000,
        'paid_amount' => 100_000,
    ]);

    $this->post(route('supplier.purchases.pay', $purchase), [
        'amount' => 50_000,
        'payment_method' => 'cash',
    ])->assertStatus(422);
});

test('updating a purchase re-adjusts stock and totals', function () {
    actingAsPurchaseAdmin();
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create(['stock' => 10, 'purchase_price' => 10_000]);
    $purchase = Purchase::factory()->create([
        'supplier_id' => $supplier->id,
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

    $this->put(route('supplier.purchases.update', $purchase), [
        'supplier_id' => $supplier->id,
        'purchase_date' => '2026-08-02',
        'due_date' => null,
        'discount' => 0,
        'items' => [
            ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 5, 'purchase_price' => 12_000],
        ],
    ])->assertRedirect();

    $purchase->refresh();

    expect((float) $purchase->total)->toBe(60_000.0)
        ->and($purchase->items()->count())->toBe(1)
        ->and($purchase->items()->first()->quantity)->toBe(5)
        ->and($product->fresh()->stock)->toBe(15);
});

test('deleting a purchase reverses the stock it added', function () {
    actingAsPurchaseAdmin();
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);
    $purchase = Purchase::factory()->create([
        'supplier_id' => $supplier->id,
        'total' => 30_000,
    ]);
    $purchase->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 3,
        'purchase_price' => 10_000,
        'subtotal' => 30_000,
    ]);
    $product->increment('stock', 3);

    $this->delete(route('supplier.purchases.destroy', $purchase))->assertRedirect();

    expect(Purchase::query()->find($purchase->id))->toBeNull()
        ->and($product->fresh()->stock)->toBe(10);
});

test('the index lists overdue purchases when filtered', function () {
    actingAsPurchaseAdmin();
    $overdue = Purchase::factory()->create([
        'due_date' => now()->subDays(5)->toDateString(),
        'total' => 100_000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
    ]);
    Purchase::factory()->create([
        'due_date' => now()->addDays(5)->toDateString(),
        'total' => 100_000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
    ]);

    $this->get(route('supplier.purchases.index', ['overdue' => '1']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('supplier/purchases/index')
            ->has('purchases.data', 1)
            ->where('purchases.data.0.id', $overdue->id));
});

test('dashboard reports outstanding debt and overdue purchases', function () {
    Role::query()->updateOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
    $admin = User::factory()->create()->assignRole('admin');

    Purchase::factory()->create([
        'total' => 100_000,
        'paid_amount' => 40_000,
        'payment_status' => 'partial',
        'due_date' => now()->subDays(3)->toDateString(),
    ]);
    Purchase::factory()->create([
        'total' => 200_000,
        'paid_amount' => 200_000,
        'payment_status' => 'paid',
    ]);
    Purchase::factory()->create([
        'total' => 50_000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'due_date' => now()->addDays(10)->toDateString(),
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('stats.debt_outstanding', 110_000)
            ->where('stats.debt_overdue', 1));
});
