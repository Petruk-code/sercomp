<?php

use App\Models\Customer;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Support\Str;
use Pest\Browser\Api\AwaitableWebpage;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Alur: cetak struk thermal (ESC/POS) dari halaman detail
|--------------------------------------------------------------------------
*/

function thermalBrowserAdmin(): AwaitableWebpage
{
    Role::query()->updateOrCreate(['name' => 'admin'], ['guard_name' => 'web']);

    return loginBrowserAdmin();
}

it('opens the thermal print dialog from the sale detail page', function () {
    $page = thermalBrowserAdmin();

    $cashier = User::factory()->create()->assignRole('cashier');
    $sale = Sale::factory()->create(['user_id' => $cashier->id]);
    $sale->items()->create([
        'name' => 'Kabel HDMI',
        'quantity' => 2,
        'price' => 25000,
        'subtotal' => 50000,
    ]);

    $page->navigate('/sales/'.$sale->id)
        ->wait(2)
        ->assertSee('Cetak Thermal')
        ->click('Cetak Thermal')
        ->wait(1)
        ->assertSee('Lebar kertas')
        ->assertSee('58 mm')
        ->assertSee('80 mm')
        ->assertSee('Bluetooth')
        ->assertSee('Kabel (Serial)');
});

it('remembers the selected paper width from the service detail page', function () {
    $page = thermalBrowserAdmin();

    $customer = Customer::factory()->create();
    $service = ServiceOrder::factory()->create(['customer_id' => $customer->id]);

    $page->navigate('/services/'.$service->id)
        ->wait(2)
        ->assertSee('Cetak Thermal')
        ->click('Cetak Thermal')
        ->wait(1)
        ->assertSee('Lebar kertas')
        ->click('58 mm')
        ->wait(1);

    // Struk servis tetap memuat nomor servis untuk pelacakan.
    expect(Str::startsWith($service->invoice_number, 'SRV-'))->toBeTrue();

    expect($page->script("localStorage.getItem('sercomp.thermal.paper-width')"))
        ->toBe('58');
});
