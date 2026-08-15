<?php

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('staff can download the sales receipt as a PDF', function () {
    Role::query()->updateOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
    $cashier = User::factory()->create()->assignRole('cashier');
    $customer = Customer::factory()->create();
    $sale = Sale::factory()->create([
        'customer_id' => $customer->id,
        'user_id' => $cashier->id,
    ]);
    $sale->items()->create([
        'name' => 'Kabel HDMI',
        'quantity' => 2,
        'price' => 25000,
        'subtotal' => 50000,
    ]);

    $this->actingAs($cashier)
        ->get(route('sales.print', $sale))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('sales receipt renders item lines and totals', function () {
    Role::query()->updateOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
    $cashier = User::factory()->create()->assignRole('cashier');
    $sale = Sale::factory()->create(['user_id' => $cashier->id]);
    $sale->items()->create([
        'name' => 'Kabel HDMI',
        'quantity' => 2,
        'price' => 25000,
        'subtotal' => 50000,
    ]);

    $html = view('pdf.receipt', [
        'sale' => $sale,
        'paymentMethodLabels' => Sale::PAYMENT_METHOD_LABELS,
        'paymentStatusLabels' => Sale::PAYMENT_STATUS_LABELS,
    ])->render();

    expect($html)->toContain($sale->invoice_number)
        ->and($html)->toContain('Kabel HDMI')
        ->and($html)->toContain('Rp 50.000');
});
