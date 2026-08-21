<?php

use App\Models\Product;
use App\Models\Sale;

/*
|--------------------------------------------------------------------------
| Alur kritis: POS → checkout → nota
|--------------------------------------------------------------------------
*/

it('completes the POS checkout flow until the nota is shown', function () {
    loginBrowserAdmin();

    $product = Product::factory()->create([
        'name' => 'SSD Samsung 980 1TB',
        'stock' => 10,
        'purchase_price' => 100000,
        'selling_price' => 150000,
    ]);

    visit('/pos')
        ->click('SSD Samsung 980 1TB')
        ->fill('paid_amount', '150000')
        ->click('#pos-cart button.w-full')
        ->assertPathBeginsWith('/sales/')
        ->assertSee('Cetak Nota')
        ->assertSee('SSD Samsung 980 1TB')
        ->assertNoSmoke();

    $sale = Sale::query()->with('items')->sole();

    expect($sale->invoice_number)->toStartWith('INV-');
    expect($sale->payment_status)->toBe('paid');
    expect($sale->total)->toBe('150000.00');
    expect($sale->items->first()?->product_id)->toBe($product->id);
    expect($product->fresh()->stock)->toBe(9);
});
