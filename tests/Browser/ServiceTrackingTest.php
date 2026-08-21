<?php

use App\Models\ServiceOrder;

/*
|--------------------------------------------------------------------------
| Alur kritis: servis → status → tracking publik
|--------------------------------------------------------------------------
*/

it('creates a service order, updates its status and tracks it publicly', function () {
    loginBrowserAdmin();

    visit('/services/create')
        ->fill('item_name', 'Laptop Asus ROG')
        ->fill('complaint', 'Tidak bisa menyala sama sekali')
        ->press('Simpan Servis')
        ->assertSee('berhasil dibuat')
        ->assertPathBeginsWith('/services/')
        ->assertSee('Status Servis');

    $order = ServiceOrder::query()->latest('id')->first();

    expect($order->status)->toBe('pending');
    expect($order->tracking_token)->not->toBeNull();
    expect($order->invoice_number)->toStartWith('SRV-');

    visit('/services/'.$order->id)
        ->select('status', 'in_progress')
        ->press('Update Status')
        ->assertSee('Status servis menjadi');

    expect($order->fresh()->status)->toBe('in_progress');

    visit('/services/'.$order->id)
        ->select('status', 'done')
        ->press('Update Status')
        ->assertSee('Status servis menjadi');

    expect($order->fresh()->status)->toBe('done');
    expect($order->fresh()->completed_at)->not->toBeNull();

    visit("/tracking?token={$order->tracking_token}")
        ->assertSee('Laptop Asus ROG')
        ->assertSee($order->invoice_number)
        ->assertNoSmoke();
});

it('shows the not found state for an unknown tracking number', function () {
    visit('/tracking?no=SRV-20990101-9999')
        ->assertSee('Servis tidak ditemukan')
        ->assertNoSmoke();
});
