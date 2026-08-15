<?php

use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }

    config()->set('services.fonnte.api_key', 'test-key');
});

test('sending the whatsapp tracking message hits the customer phone', function () {
    Http::fake(['api.fonnte.com/send' => Http::response(['status' => true])]);

    $cashier = User::factory()->create()->assignRole('cashier');
    $customer = Customer::factory()->create(['name' => 'Budi', 'phone' => '081234567890']);
    $service = ServiceOrder::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($cashier)
        ->post(route('services.whatsapp', $service))
        ->assertRedirect();

    Http::assertSent(function ($request) use ($service) {
        return $request->url() === 'https://api.fonnte.com/send'
            && $request['target'] === '6281234567890'
            && str_contains($request['message'], $service->invoice_number)
            && str_contains($request['message'], $service->tracking_token)
            && $request->hasHeader('Authorization');
    });
});

test('sending the whatsapp tracking message is skipped when the customer has no phone', function () {
    Http::fake();

    $cashier = User::factory()->create()->assignRole('cashier');
    $customer = Customer::factory()->create(['phone' => null]);
    $service = ServiceOrder::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($cashier)
        ->post(route('services.whatsapp', $service))
        ->assertRedirect();

    Http::assertNothingSent();
});

test('marking a service done does not automatically send whatsapp to the customer', function () {
    Http::fake();

    $cashier = User::factory()->create()->assignRole('cashier');
    $customer = Customer::factory()->create(['name' => 'Budi', 'phone' => '081234567890']);
    $service = ServiceOrder::factory()->create(['customer_id' => $customer->id, 'status' => 'in_progress']);

    $this->actingAs($cashier)
        ->patch(route('services.status', $service), ['status' => 'done'])
        ->assertRedirect();

    Http::assertNothingSent();
});

test('sending the whatsapp tracking message requires a management role', function () {
    $technician = User::factory()->create()->assignRole('technician');
    $customer = Customer::factory()->create(['phone' => '081234567890']);
    $service = ServiceOrder::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($technician)
        ->post(route('services.whatsapp', $service))
        ->assertForbidden();
});
