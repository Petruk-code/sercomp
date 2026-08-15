<?php

use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Notifications\LowStockAlert;
use App\Notifications\ServiceQueueAlert;
use App\Notifications\ServiceReadyForPickupAlert;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

test('send alerts notifies admins about low stock and queued services', function () {
    Notification::fake();

    Role::query()->updateOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
    Role::query()->updateOrCreate(['name' => 'owner'], ['guard_name' => 'web']);

    $admin = User::factory()->create()->assignRole('admin');
    $owner = User::factory()->create()->assignRole('owner');
    User::factory()->create();

    Product::factory()->create(['stock' => 2, 'min_stock' => 5]);
    Product::factory()->create(['stock' => 50, 'min_stock' => 5]);

    ServiceOrder::factory()->create(['status' => 'pending', 'created_at' => now()->subHours(48)]);
    ServiceOrder::factory()->create(['status' => 'pending', 'created_at' => now()]);

    $this->artisan('app:send-alerts')->assertSuccessful();

    Notification::assertSentTo($admin, LowStockAlert::class, fn ($notification) => $notification->products->count() === 1);
    Notification::assertSentTo($owner, LowStockAlert::class);
    Notification::assertSentTo($admin, ServiceQueueAlert::class, fn ($notification) => $notification->services->count() === 1);
});

test('send alerts stores database notifications for in-app visibility', function () {
    Role::query()->updateOrCreate(['name' => 'admin'], ['guard_name' => 'web']);

    $admin = User::factory()->create()->assignRole('admin');

    Product::factory()->create(['stock' => 1, 'min_stock' => 5]);

    $this->artisan('app:send-alerts')->assertSuccessful();

    expect(DB::table('notifications')->count())->toBe(1)
        ->and(DB::table('notifications')->value('type'))->toBe(LowStockAlert::class);
});

test('send alerts does nothing when there are no alerts', function () {
    Notification::fake();

    Role::query()->updateOrCreate(['name' => 'admin'], ['guard_name' => 'web']);

    User::factory()->create()->assignRole('admin');
    Product::factory()->create(['stock' => 50, 'min_stock' => 5]);

    $this->artisan('app:send-alerts')->assertSuccessful();

    Notification::assertNothingSent();
});

test('scheduled alerts never hit the whatsapp gateway to avoid spam', function () {
    Http::fake();
    Notification::fake();

    config()->set('services.fonnte.api_key', 'test-key');

    Role::query()->updateOrCreate(['name' => 'admin'], ['guard_name' => 'web']);

    User::factory()->create(['phone' => '081234567890'])->assignRole('admin');
    Product::factory()->create(['stock' => 1, 'min_stock' => 5]);

    $this->artisan('app:send-alerts')->assertSuccessful();

    Http::assertNothingSent();
});

test('alert notifications are in-app only and never sent as mail', function () {
    Mail::fake();
    Notification::fake();

    Role::query()->updateOrCreate(['name' => 'admin'], ['guard_name' => 'web']);

    User::factory()->create()->assignRole('admin');
    Product::factory()->create(['stock' => 1, 'min_stock' => 5]);
    ServiceOrder::factory()->create(['status' => 'pending', 'created_at' => now()->subHours(48)]);

    $this->artisan('app:send-alerts')->assertSuccessful();

    Mail::assertNothingSent();

    expect((new LowStockAlert(collect()))->via(new User))->toBe(['database'])
        ->and((new ServiceQueueAlert(collect()))->via(new User))->toBe(['database'])
        ->and((new ServiceReadyForPickupAlert(ServiceOrder::factory()->create()))->via(new User))->toBe(['database']);
});

test('low stock alert message lists the affected product names', function () {
    Product::factory()->create(['name' => 'Keyboard', 'stock' => 1, 'min_stock' => 5]);

    $lowStockProducts = Product::query()->whereColumn('stock', '<=', 'min_stock')->get();

    $alert = new LowStockAlert($lowStockProducts);

    expect($alert->toArray(new User)['message'])->toContain('Keyboard');
});
