<?php

use App\Models\Customer;
use App\Models\ServiceOrder;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

test('visitors can download the tracking card PDF for a valid token', function () {
    $customer = Customer::factory()->create();
    $order = ServiceOrder::factory()->create([
        'customer_id' => $customer->id,
        'item_name' => 'HP Samsung A52',
        'serial_number' => 'IMEI12345',
    ]);

    $this->get(route('tracking.card', ['token' => $order->tracking_token]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'attachment; filename=kartu-lacak-'.$order->invoice_number.'.pdf');
});

test('tracking card download returns 404 for an unknown token', function () {
    $this->get(route('tracking.card', ['token' => '00000000-0000-0000-0000-000000000000']))
        ->assertNotFound();
});

test('tracking card download returns 404 when the token is missing', function () {
    $this->get(route('tracking.card'))
        ->assertNotFound();
});

test('tracking card embeds the QR code, tracking URL and order details', function () {
    $customer = Customer::factory()->create();
    $order = ServiceOrder::factory()->create([
        'customer_id' => $customer->id,
        'item_name' => 'HP Samsung A52',
        'serial_number' => 'IMEI12345',
        'status' => 'in_progress',
    ]);

    $trackingUrl = route('tracking', ['token' => $order->tracking_token]);

    $html = view('pdf.tracking_card', [
        'order' => $order,
        'statusLabels' => ServiceOrder::STATUS_LABELS,
        'trackingUrl' => $trackingUrl,
        'qrDataUri' => 'data:image/png;base64,ZmFrZQ==',
    ])->render();

    expect($html)->toContain($trackingUrl)
        ->and($html)->toContain('data:image/png;base64,')
        ->and($html)->toContain($order->invoice_number)
        ->and($html)->toContain($order->item_name)
        ->and($html)->toContain($order->serial_number)
        ->and($html)->toContain('Proses')
        ->and($html)->toMatch('/<img[^>]+src="data:image\/png;base64,/');
});

test('tracking card downloads are rate limited per IP', function () {
    RateLimiter::for('pdf-downloads', fn (Request $request): Limit => Limit::perMinute(3)->by($request->ip()));

    $order = ServiceOrder::factory()->create();

    foreach (range(1, 3) as $i) {
        $this->get(route('tracking.card', ['token' => $order->tracking_token]))
            ->assertOk();
    }

    $this->get(route('tracking.card', ['token' => $order->tracking_token]))
        ->assertStatus(429);
});
