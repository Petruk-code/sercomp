<?php

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }
});

function actingAsReturnAdmin(): User
{
    $admin = User::factory()->create()->assignRole('admin');

    test()->actingAs($admin);

    return $admin;
}

/**
 * Build a purchase with one stock-linked item and apply its stock.
 */
function makeReturnablePurchase(Product $product, int $quantity = 3, int $price = 10_000): Purchase
{
    $purchase = Purchase::factory()->create([
        'subtotal' => $price * $quantity,
        'discount' => 0,
        'total' => $price * $quantity,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
    ]);
    $purchase->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => $quantity,
        'purchase_price' => $price,
        'subtotal' => $price * $quantity,
    ]);
    $product->increment('stock', $quantity);

    return $purchase;
}

function returnPayload(Purchase $purchase, array $overrides = []): array
{
    return array_merge([
        'return_date' => '2026-08-10',
        'result_type' => 'replacement',
        'items' => [
            ['purchase_item_id' => $purchase->items()->first()->id, 'quantity' => 2, 'price' => 10_000],
        ],
    ], $overrides);
}

test('admin can record a return, generating an RTN number and reducing stock', function () {
    $admin = actingAsReturnAdmin();
    $product = Product::factory()->create(['stock' => 10, 'purchase_price' => 10_000]);
    $purchase = makeReturnablePurchase($product, 3);

    $this->post(route('supplier.returns.store', $purchase), returnPayload($purchase))
        ->assertRedirect();

    $purchaseReturn = PurchaseReturn::query()->first();

    expect($purchaseReturn)->not->toBeNull()
        ->and($purchaseReturn->return_number)->toStartWith('RTN-')
        ->and($purchaseReturn->user_id)->toBe($admin->id)
        ->and($purchaseReturn->purchase_id)->toBe($purchase->id)
        ->and((float) $purchaseReturn->total)->toBe(20_000.0)
        ->and($purchaseReturn->items()->count())->toBe(1)
        ->and($product->fresh()->stock)->toBe(11);
});

test('cashiers cannot record returns', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $product = Product::factory()->create();
    $purchase = makeReturnablePurchase($product);

    $this->actingAs($cashier)
        ->post(route('supplier.returns.store', $purchase), returnPayload($purchase))
        ->assertForbidden();

    expect(PurchaseReturn::query()->count())->toBe(0);
});

test('a return quantity cannot exceed the purchased quantity', function () {
    actingAsReturnAdmin();
    $product = Product::factory()->create(['stock' => 10]);
    $purchase = makeReturnablePurchase($product, 3);

    $this->post(route('supplier.returns.store', $purchase), returnPayload($purchase, [
        'items' => [
            ['purchase_item_id' => $purchase->items()->first()->id, 'quantity' => 5, 'price' => 10_000],
        ],
    ]))->assertSessionHasErrors('items.0.quantity');

    expect(PurchaseReturn::query()->count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(13);
});

test('items from another purchase are rejected', function () {
    actingAsReturnAdmin();
    $product = Product::factory()->create();
    $purchaseA = makeReturnablePurchase($product, 3);
    $other = Product::factory()->create();
    $purchaseB = makeReturnablePurchase($other, 2);

    $this->post(route('supplier.returns.store', $purchaseA), [
        'return_date' => '2026-08-10',
        'result_type' => 'replacement',
        'items' => [
            ['purchase_item_id' => $purchaseB->items()->first()->id, 'quantity' => 1, 'price' => 10_000],
        ],
    ])->assertSessionHasErrors('items.0.purchase_item_id');

    expect(PurchaseReturn::query()->count())->toBe(0);
});

test('duplicate purchase items in a return are rejected', function () {
    actingAsReturnAdmin();
    $product = Product::factory()->create(['stock' => 10]);
    $purchase = makeReturnablePurchase($product, 5);
    $itemId = $purchase->items()->first()->id;

    $this->post(route('supplier.returns.store', $purchase), [
        'return_date' => '2026-08-10',
        'result_type' => 'replacement',
        'items' => [
            ['purchase_item_id' => $itemId, 'quantity' => 2, 'price' => 10_000],
            ['purchase_item_id' => $itemId, 'quantity' => 1, 'price' => 10_000],
        ],
    ])->assertSessionHasErrors('items.1.purchase_item_id');

    expect(PurchaseReturn::query()->count())->toBe(0);
});

test('a potong-hutang return reduces the purchase total and recomputes status', function () {
    actingAsReturnAdmin();
    $product = Product::factory()->create(['stock' => 10]);
    $purchase = makeReturnablePurchase($product, 3, 10_000);
    $purchase->payments()->create(['amount' => 10_000, 'payment_method' => 'cash', 'paid_at' => now()]);
    $purchase->update(['paid_amount' => 10_000, 'payment_status' => 'partial']);

    $this->post(route('supplier.returns.store', $purchase), returnPayload($purchase, [
        'result_type' => 'debt_deduction',
    ]))->assertRedirect();

    $purchase->refresh();

    expect((float) $purchase->total)->toBe(10_000.0)
        ->and((float) $purchase->paid_amount)->toBe(10_000.0)
        ->and($purchase->payment_status)->toBe('paid');
});

test('a potong-hutang return clamps paid amount to the new total', function () {
    actingAsReturnAdmin();
    $product = Product::factory()->create(['stock' => 10]);
    $purchase = makeReturnablePurchase($product, 3, 10_000);
    $purchase->update(['paid_amount' => 30_000, 'payment_status' => 'paid']);

    $this->post(route('supplier.returns.store', $purchase), returnPayload($purchase, [
        'result_type' => 'debt_deduction',
        'items' => [
            ['purchase_item_id' => $purchase->items()->first()->id, 'quantity' => 3, 'price' => 10_000],
        ],
    ]))->assertRedirect();

    $purchase->refresh();

    expect((float) $purchase->total)->toBe(0.0)
        ->and((float) $purchase->paid_amount)->toBe(0.0)
        ->and($purchase->payment_status)->toBe('paid');
});

test('deleting a return restores the stock it removed', function () {
    actingAsReturnAdmin();
    $product = Product::factory()->create(['stock' => 10]);
    $purchase = makeReturnablePurchase($product, 3);
    $purchaseReturn = PurchaseReturn::factory()->create([
        'purchase_id' => $purchase->id,
        'result_type' => 'replacement',
        'total' => 20_000,
    ]);
    $purchaseReturn->items()->create([
        'purchase_item_id' => $purchase->items()->first()->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 2,
        'price' => 10_000,
        'subtotal' => 20_000,
    ]);
    $product->decrement('stock', 2);

    $this->delete(route('supplier.returns.destroy', $purchaseReturn))->assertRedirect();

    expect(PurchaseReturn::query()->find($purchaseReturn->id))->toBeNull()
        ->and($product->fresh()->stock)->toBe(13);
});

test('deleting a potong-hutang return is blocked', function () {
    actingAsReturnAdmin();
    $product = Product::factory()->create();
    $purchase = makeReturnablePurchase($product, 3);
    $purchaseReturn = PurchaseReturn::factory()->create([
        'purchase_id' => $purchase->id,
        'result_type' => 'debt_deduction',
        'total' => 20_000,
    ]);

    $this->delete(route('supplier.returns.destroy', $purchaseReturn))->assertRedirect();

    expect(PurchaseReturn::query()->find($purchaseReturn->id))->not->toBeNull();
});

test('a purchase with returns cannot be deleted', function () {
    actingAsReturnAdmin();
    $product = Product::factory()->create(['stock' => 10]);
    $purchase = makeReturnablePurchase($product, 3);
    PurchaseReturn::factory()->create(['purchase_id' => $purchase->id, 'result_type' => 'replacement']);

    $this->delete(route('supplier.purchases.destroy', $purchase))->assertRedirect();

    expect(Purchase::query()->find($purchase->id))->not->toBeNull()
        ->and($product->fresh()->stock)->toBe(13);
});

test('the create page shows a purchase with available quantities', function () {
    actingAsReturnAdmin();
    $product = Product::factory()->create();
    $purchase = makeReturnablePurchase($product, 3);

    $this->get(route('supplier.returns.create', $purchase))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('supplier/returns/form')
            ->has('purchase.items', 1)
            ->has('alreadyReturned'));
});

test('the index lists returns and filters by result type', function () {
    actingAsReturnAdmin();
    $product = Product::factory()->create();
    $purchase = makeReturnablePurchase($product, 3);
    $refundReturn = PurchaseReturn::factory()->create([
        'purchase_id' => $purchase->id,
        'result_type' => 'refund',
    ]);
    PurchaseReturn::factory()->create([
        'purchase_id' => $purchase->id,
        'result_type' => 'replacement',
    ]);

    $this->get(route('supplier.returns.index', ['result_type' => 'refund']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('supplier/returns/index')
            ->has('returns.data', 1)
            ->where('returns.data.0.id', $refundReturn->id));
});
