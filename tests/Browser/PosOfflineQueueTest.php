<?php

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Str;
use Pest\Browser\Api\AwaitableWebpage;

/*
|--------------------------------------------------------------------------
| Alur kritis: antrean transaksi POS offline → sinkronisasi otomatis
|--------------------------------------------------------------------------
*/

function posOfflineQueuePayload(Product $product, int $quantity = 1): array
{
    $total = $product->selling_price * $quantity;

    return [
        'customer_id' => null,
        'payment_method' => 'cash',
        'discount' => 0,
        'paid_amount' => $total,
        'client_reference' => 'offline-'.Str::uuid()->toString(),
        'items' => [[
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => $quantity,
            'price' => (int) $product->selling_price,
        ]],
    ];
}

/**
 * @param  array<int, array<string, mixed>>  $payloads
 */
function injectOfflineQueue(AwaitableWebpage $page, array $payloads): void
{
    $queue = array_map(
        fn (array $payload): array => [
            'reference' => $payload['client_reference'],
            'payload' => $payload,
            'total' => collect($payload['items'])->sum(
                fn (array $item): int|float => $item['price'] * $item['quantity'],
            ),
            'queuedAt' => now()->toISOString(),
            'status' => 'pending',
        ],
        $payloads,
    );

    $page->script(sprintf(
        'localStorage.setItem(%s, %s);',
        json_encode('sercomp.pos.offline-sales'),
        json_encode((string) json_encode($queue)),
    ));
}

it('syncs a queued offline sale automatically when the POS page loads', function () {
    $page = loginBrowserAdmin();

    $product = Product::factory()->create([
        'name' => 'RAM Corsair 16GB',
        'stock' => 5,
        'purchase_price' => 500000,
        'selling_price' => 750000,
    ]);

    injectOfflineQueue($page, [posOfflineQueuePayload($product)]);

    $page->navigate('/pos')
        ->wait(2)
        ->assertSee('berhasil dikirim');

    $sale = Sale::query()
        ->where('client_reference', 'like', 'offline-%')
        ->sole();

    expect($sale->invoice_number)->toStartWith('INV-');
    expect($sale->payment_status)->toBe('paid');
    expect($sale->total)->toBe('750000.00');
    expect($product->fresh()->stock)->toBe(4);

    $remaining = (int) $page->script(
        "JSON.parse(localStorage.getItem('sercomp.pos.offline-sales') ?? '[]').length",
    );

    expect($remaining)->toBe(0);
});

it('keeps the queued sale marked as failed when the server rejects it', function () {
    $page = loginBrowserAdmin();

    $product = Product::factory()->create([
        'name' => 'Keyboard Mekanik',
        'stock' => 1,
        'purchase_price' => 200000,
        'selling_price' => 300000,
    ]);

    injectOfflineQueue($page, [posOfflineQueuePayload($product, 10)]);

    $page->navigate('/pos')
        ->wait(2)
        ->assertSee('tidak mencukupi');

    expect(Sale::query()->where('client_reference', 'like', 'offline-%')->count())->toBe(0);
    expect($product->fresh()->stock)->toBe(1);

    $queue = json_decode(
        (string) $page->script("localStorage.getItem('sercomp.pos.offline-sales')"),
        true,
    );

    expect($queue)->toHaveCount(1);
    expect($queue[0]['status'])->toBe('failed');
});

it('restores the cart draft after a page reload', function () {
    $page = loginBrowserAdmin();

    Product::factory()->create([
        'name' => 'Mouse Gaming',
        'stock' => 8,
        'purchase_price' => 100000,
        'selling_price' => 180000,
    ]);

    $page->navigate('/pos')
        ->click('Mouse Gaming')
        ->assertSeeIn('#pos-cart', 'Mouse Gaming');

    $page->navigate('/pos')
        ->assertSeeIn('#pos-cart', 'Mouse Gaming');
});
