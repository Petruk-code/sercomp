<?php

use App\Models\ServiceOrder;
use App\Models\User;
use App\Notifications\ServiceReadyForPickupAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'owner', 'cashier'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }
});

test('completing a service order notifies admin and owner', function () {
    Notification::fake();

    $admin = User::factory()->create()->assignRole('admin');
    $owner = User::factory()->create()->assignRole('owner');

    $service = ServiceOrder::factory()->create(['status' => 'in_progress']);

    $service->update(['status' => 'done']);

    Notification::assertSentTo($admin, ServiceReadyForPickupAlert::class, fn ($notification) => $notification->service->is($service));
    Notification::assertSentTo($owner, ServiceReadyForPickupAlert::class);
});

test('status changes other than done do not send pickup alerts', function () {
    Notification::fake();

    User::factory()->create()->assignRole('admin');

    $service = ServiceOrder::factory()->create(['status' => 'pending']);

    $service->update(['status' => 'diagnosed']);

    Notification::assertNothingSent();
});

test('completing a service order stores an in-app notification', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $service = ServiceOrder::factory()->create(['status' => 'in_progress']);

    $service->update(['status' => 'done']);

    expect(DB::table('notifications')->where('notifiable_id', $admin->id)->count())->toBe(1)
        ->and(DB::table('notifications')->where('notifiable_id', $admin->id)->value('type'))->toBe(ServiceReadyForPickupAlert::class);
});

test('the pickup alert is in-app only and never sent as mail', function () {
    Mail::fake();

    $admin = User::factory()->create()->assignRole('admin');

    $service = ServiceOrder::factory()->create(['status' => 'in_progress']);

    $service->update(['status' => 'done']);

    Mail::assertNothingSent();

    expect(DB::table('notifications')->where('notifiable_id', $admin->id)->count())->toBe(1);
});

test('the notifications page lists the authenticated users notifications', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $service = ServiceOrder::factory()->create(['status' => 'in_progress']);

    $cashier->notify(new ServiceReadyForPickupAlert($service));

    $this->actingAs($cashier)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('notifications/index')
            ->has('notifications.data', 1));
});

test('a user can mark a notification as read', function () {
    $cashier = User::factory()->create()->assignRole('cashier');

    $notification = $cashier->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => ServiceReadyForPickupAlert::class,
        'data' => ['title' => 'Servis siap diambil', 'message' => 'Pesan', 'action_url' => null],
    ]);

    $this->actingAs($cashier)
        ->post(route('notifications.read', $notification))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('a user cannot mark another users notification as read', function () {
    $owner = User::factory()->create()->assignRole('owner');
    $cashier = User::factory()->create()->assignRole('cashier');

    $notification = $owner->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => ServiceReadyForPickupAlert::class,
        'data' => ['title' => 'Servis siap diambil', 'message' => 'Pesan', 'action_url' => null],
    ]);

    $this->actingAs($cashier)
        ->post(route('notifications.read', $notification))
        ->assertForbidden();
});

test('a user can mark all notifications as read', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $service = ServiceOrder::factory()->create(['status' => 'in_progress']);

    $cashier->notify(new ServiceReadyForPickupAlert($service));
    $cashier->notify(new ServiceReadyForPickupAlert($service));

    $this->actingAs($cashier)
        ->post(route('notifications.read-all'))
        ->assertRedirect();

    expect(DB::table('notifications')
        ->where('notifiable_id', $cashier->id)
        ->whereNull('read_at')
        ->count())->toBe(0);
});

test('the summary endpoint returns the unread count and recent notifications as JSON', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $service = ServiceOrder::factory()->create(['status' => 'in_progress']);

    $cashier->notify(new ServiceReadyForPickupAlert($service));
    $cashier->notify(new ServiceReadyForPickupAlert($service));

    $this->actingAs($cashier)
        ->getJson(route('notifications.summary'))
        ->assertOk()
        ->assertJsonStructure(['unread_count', 'recent'])
        ->assertJsonCount(2, 'recent')
        ->assertJsonPath('unread_count', 2);
});

test('the summary endpoint requires authentication', function () {
    $this->getJson(route('notifications.summary'))->assertUnauthorized();
});

test('marking a notification read via JSON returns the fresh summary', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $notification = $cashier->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => ServiceReadyForPickupAlert::class,
        'data' => ['title' => 'Servis siap diambil', 'message' => 'Pesan', 'action_url' => null],
    ]);

    $this->actingAs($cashier)
        ->postJson(route('notifications.read', $notification))
        ->assertOk()
        ->assertJsonPath('unread_count', 0);
});

test('marking all notifications read via JSON returns the fresh summary', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $service = ServiceOrder::factory()->create(['status' => 'in_progress']);

    $cashier->notify(new ServiceReadyForPickupAlert($service));
    $cashier->notify(new ServiceReadyForPickupAlert($service));

    $this->actingAs($cashier)
        ->postJson(route('notifications.read-all'))
        ->assertOk()
        ->assertJsonPath('unread_count', 0);
});

test('a user can delete their own notification', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $notification = $cashier->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => ServiceReadyForPickupAlert::class,
        'data' => ['title' => 'Servis siap diambil', 'message' => 'Pesan', 'action_url' => null],
    ]);

    $this->actingAs($cashier)
        ->deleteJson(route('notifications.destroy', $notification))
        ->assertOk()
        ->assertJsonPath('unread_count', 0);

    expect(DB::table('notifications')->where('id', $notification->id)->count())->toBe(0);
});

test('a user cannot delete another users notification', function () {
    $owner = User::factory()->create()->assignRole('owner');
    $cashier = User::factory()->create()->assignRole('cashier');

    $notification = $owner->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => ServiceReadyForPickupAlert::class,
        'data' => ['title' => 'Servis siap diambil', 'message' => 'Pesan', 'action_url' => null],
    ]);

    $this->actingAs($cashier)
        ->deleteJson(route('notifications.destroy', $notification))
        ->assertForbidden();

    expect(DB::table('notifications')->where('id', $notification->id)->count())->toBe(1);
});
