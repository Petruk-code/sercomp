<?php

use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderStatusHistory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

test('tracking page renders the search form without a query', function () {
    $this->get(route('tracking'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tracking/show')
            ->where('query', '')
            ->where('order', null)
            ->where('timeline', []));
});

test('tracking page shows the order status and timeline for a valid invoice number', function () {
    $customer = Customer::factory()->create();
    $order = ServiceOrder::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'pending',
    ]);

    $order->update(['status' => 'in_progress']);
    $order->update(['status' => 'done']);

    $this->get(route('tracking', ['no' => $order->invoice_number]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tracking/show')
            ->where('query', $order->invoice_number)
            ->where('order.invoice_number', $order->invoice_number)
            ->where('order.status', 'done')
            ->where('order.complaint', $order->complaint)
            ->has('timeline', 3)
            ->has('timeline.0', fn (Assert $entry) => $entry->where('status', 'pending')->etc())
            ->has('timeline.2', fn (Assert $entry) => $entry->where('status', 'done')->etc()));
});

test('tracking page lookup is case-insensitive', function () {
    $order = ServiceOrder::factory()->create(['status' => 'pending']);

    $this->get(route('tracking', ['no' => strtolower($order->invoice_number)]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('order.invoice_number', $order->invoice_number));
});

test('tracking page shows a not-found state for an unknown invoice number', function () {
    $this->get(route('tracking', ['no' => 'SRV-00000000-9999']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tracking/show')
            ->where('query', 'SRV-00000000-9999')
            ->where('cardUrl', null)
            ->where('order', null));
});

test('tracking page shows the order when looked up by tracking token', function () {
    $order = ServiceOrder::factory()->create(['status' => 'done']);

    $this->get(route('tracking', ['token' => $order->tracking_token]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('order.invoice_number', $order->invoice_number)
            ->where('order.status', 'done')
            ->where('cardUrl', route('tracking.card', ['token' => $order->tracking_token])));
});

test('tracking page shows a not-found state for an unknown tracking token', function () {
    $this->get(route('tracking', ['token' => '00000000-0000-0000-0000-000000000000']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('order', null));
});

test('creating a service order auto-generates a unique tracking token', function () {
    $first = ServiceOrder::factory()->create(['status' => 'pending']);
    $second = ServiceOrder::factory()->create(['status' => 'pending']);

    expect($first->tracking_token)->not->toBeNull()
        ->and($second->tracking_token)->not->toBeNull()
        ->and($first->tracking_token)->not->toBe($second->tracking_token);
});

test('creating a service order records the initial status in history', function () {
    $order = ServiceOrder::factory()->create(['status' => 'pending']);

    expect($order->statusHistories)->toHaveCount(1);

    $history = $order->statusHistories->first();

    expect($history->status)->toBe('pending')
        ->and($history->service_order_id)->toBe($order->id);
});

test('changing the status records a new history entry', function () {
    $order = ServiceOrder::factory()->create(['status' => 'pending']);

    $order->update(['status' => 'in_progress']);

    expect(ServiceOrderStatusHistory::query()->count())->toBe(2);
    expect($order->statusHistories->last()->status)->toBe('in_progress');
});

test('updating other fields does not create history entries', function () {
    $order = ServiceOrder::factory()->create(['status' => 'pending']);

    $order->update(['complaint' => 'Kipas rusak']);

    expect($order->statusHistories)->toHaveCount(1);
});

test('creating a service order without an explicit status defaults to pending', function () {
    $order = ServiceOrder::query()->create([
        'invoice_number' => 'SRV-DEFAULT-0001',
        'customer_id' => null,
        'user_id' => User::factory()->create()->id,
        'item_name' => 'Laptop ASUS',
        'complaint' => 'Tidak menyala',
    ]);

    expect($order->status)->toBe('pending')
        ->and($order->statusHistories)->toHaveCount(1)
        ->and($order->statusHistories->first()->status)->toBe('pending');
});

test('the service order store endpoint creates an order without requiring a status', function () {
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }

    $cashier = User::factory()->create()->assignRole('cashier');

    $this->actingAs($cashier)
        ->post(route('services.store'), [
            'item_name' => 'Laptop ASUS',
            'complaint' => 'Tidak menyala',
        ])
        ->assertRedirect();

    $order = ServiceOrder::query()->where('item_name', 'Laptop ASUS')->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe('pending')
        ->and($order->statusHistories)->toHaveCount(1);
});
