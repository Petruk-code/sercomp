<?php

use App\Models\ServiceOrder;
use App\Models\User;
use App\Notifications\ServiceReadyForPickupAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::query()->updateOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
});

test('pruning deletes notifications older than 90 days and keeps recent ones', function () {
    $cashier = User::factory()->create()->assignRole('cashier');
    $service = ServiceOrder::factory()->create(['status' => 'in_progress']);

    $old = $cashier->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => ServiceReadyForPickupAlert::class,
        'data' => ['title' => 'Lama', 'message' => 'Pesan', 'action_url' => null],
    ]);
    $old->forceFill(['created_at' => now()->subDays(120)])->save();

    $recent = $cashier->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => ServiceReadyForPickupAlert::class,
        'data' => ['title' => 'Baru', 'message' => 'Pesan', 'action_url' => null],
    ]);

    $this->artisan('notifications:prune')->assertSuccessful();

    expect(DB::table('notifications')->where('id', $old->id)->count())->toBe(0)
        ->and(DB::table('notifications')->where('id', $recent->id)->count())->toBe(1);
});

test('pruning respects a custom retention window', function () {
    $cashier = User::factory()->create()->assignRole('cashier');

    $young = $cashier->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => ServiceReadyForPickupAlert::class,
        'data' => ['title' => 'Baru', 'message' => 'Pesan', 'action_url' => null],
    ]);
    $young->forceFill(['created_at' => now()->subDays(10)])->save();

    $old = $cashier->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => ServiceReadyForPickupAlert::class,
        'data' => ['title' => 'Lama', 'message' => 'Pesan', 'action_url' => null],
    ]);
    $old->forceFill(['created_at' => now()->subDays(40)])->save();

    $this->artisan('notifications:prune', ['--days' => 30])->assertSuccessful();

    expect(DB::table('notifications')->where('id', $young->id)->count())->toBe(1)
        ->and(DB::table('notifications')->where('id', $old->id)->count())->toBe(0);
});
