<?php

use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Models\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Spatie\Permission\Models\Role;

test('staff can download the service receipt as a PDF', function () {
    Role::query()->updateOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
    $cashier = User::factory()->create()->assignRole('cashier');
    $customer = Customer::factory()->create();
    $order = ServiceOrder::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($cashier)
        ->get(route('services.print', $order))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('service receipt embeds a scannable tracking QR code', function () {
    $order = ServiceOrder::factory()->create();

    $trackingUrl = route('tracking', ['token' => $order->tracking_token]);

    $qr = (new Builder(
        writer: new PngWriter,
        data: $trackingUrl,
        errorCorrectionLevel: ErrorCorrectionLevel::High,
        size: 220,
        margin: 10,
    ))->build();

    $html = view('pdf.service_receipt', [
        'order' => $order,
        'statusLabels' => ServiceOrder::STATUS_LABELS,
        'trackingUrl' => $trackingUrl,
        'qrDataUri' => $qr->getDataUri(),
    ])->render();

    expect($html)->toContain($trackingUrl)
        ->and($html)->toContain('data:image/png;base64,')
        ->and($html)->toContain($order->invoice_number)
        ->and($html)->toMatch('/<img[^>]+src="data:image\/png;base64,/');
});
