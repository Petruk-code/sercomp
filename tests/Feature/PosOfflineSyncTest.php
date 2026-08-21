<?php

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;

function posSaleUser(): User
{
    seedAppRoles();

    return User::factory()->create()->assignRole('cashier');
}

function posSalePayload(Product $product, array $overrides = []): array
{
    return array_merge([
        'payment_method' => 'cash',
        'paid_amount' => 150000,
        'items' => [
            [
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => 1,
                'price' => 150000,
            ],
        ],
    ], $overrides);
}

test('store persists the offline client reference', function () {
    $cashier = posSaleUser();
    $product = Product::factory()->create(['stock' => 5, 'selling_price' => 150000]);

    $this->actingAs($cashier)
        ->post(route('sales.store'), posSalePayload($product, ['client_reference' => 'offline-abc-123']))
        ->assertRedirect();

    expect(Sale::query()->where('client_reference', 'offline-abc-123')->count())->toBe(1);
});

test('replaying the same client reference does not duplicate the sale or stock decrement', function () {
    $cashier = posSaleUser();
    $product = Product::factory()->create(['stock' => 5, 'selling_price' => 150000]);
    $payload = posSalePayload($product, ['client_reference' => 'offline-abc-123']);

    $this->actingAs($cashier)->post(route('sales.store'), $payload)->assertRedirect();
    $firstId = Sale::query()->where('client_reference', 'offline-abc-123')->sole()->id;

    $this->actingAs($cashier)->post(route('sales.store'), $payload)
        ->assertRedirect(route('sales.show', $firstId));

    expect(Sale::query()->count())->toBe(1);
    expect(Sale::query()->find($firstId)->items()->count())->toBe(1);
    expect($product->fresh()->stock)->toBe(4);
});

test('a different client reference creates a separate sale', function () {
    $cashier = posSaleUser();
    $product = Product::factory()->create(['stock' => 5, 'selling_price' => 150000]);

    $this->actingAs($cashier)
        ->post(route('sales.store'), posSalePayload($product, ['client_reference' => 'offline-abc-123']))
        ->assertRedirect();

    $this->actingAs($cashier)
        ->post(route('sales.store'), posSalePayload($product, ['client_reference' => 'offline-def-456']))
        ->assertRedirect();

    expect(Sale::query()->count())->toBe(2);
    expect($product->fresh()->stock)->toBe(3);
});

test('sales without a client reference keep working as before', function () {
    $cashier = posSaleUser();
    $product = Product::factory()->create(['stock' => 5, 'selling_price' => 150000]);

    $this->actingAs($cashier)
        ->post(route('sales.store'), posSalePayload($product))
        ->assertRedirect();

    expect(Sale::query()->count())->toBe(1);
    expect(Sale::query()->first()->client_reference)->toBeNull();
});
