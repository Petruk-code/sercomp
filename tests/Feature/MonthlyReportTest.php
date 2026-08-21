<?php

use App\Mail\MonthlyReportMail;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\Store;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'owner'] as $role) {
        Role::query()->updateOrCreate(
            ['name' => $role, 'guard_name' => 'web'],
            ['label' => ucfirst($role)],
        );
    }
});

test('mengirim laporan bulanan ke owner dengan data yang benar', function () {
    Mail::fake();

    $storeA = Store::factory()->create(['name' => 'Cabang A']);
    $storeB = Store::factory()->create(['name' => 'Cabang B']);

    User::factory()->create(['email' => 'owner@sercomp.test'])->assignRole('owner');

    Sale::factory()->count(3)->create([
        'store_id' => $storeA->id,
        'total' => 100_000,
        'created_at' => '2026-07-05 10:00:00',
    ]);
    Sale::factory()->create([
        'store_id' => $storeB->id,
        'total' => 250_000,
        'created_at' => '2026-07-20 14:00:00',
    ]);
    // Di luar periode — tidak boleh ikut.
    Sale::factory()->create([
        'store_id' => $storeA->id,
        'total' => 999_999,
        'created_at' => '2026-08-02 09:00:00',
    ]);

    ServiceOrder::factory()->count(2)->create([
        'status' => 'in_progress',
        'created_at' => '2026-07-08 09:00:00',
        'completed_at' => null,
    ]);
    ServiceOrder::factory()->create([
        'status' => 'done',
        'created_at' => '2026-07-03 09:00:00',
        'completed_at' => '2026-07-06 15:00:00',
    ]);

    Purchase::factory()->create([
        'due_date' => '2026-07-10',
        'total' => 500_000,
        'paid_amount' => 200_000,
        'payment_status' => 'partial',
    ]);
    // Lunas — tidak masuk hutang jatuh tempo.
    Purchase::factory()->create([
        'due_date' => '2026-07-01',
        'total' => 400_000,
        'paid_amount' => 400_000,
        'payment_status' => 'paid',
    ]);
    // Belum jatuh tempo.
    Purchase::factory()->create([
        'due_date' => '2027-01-10',
        'total' => 300_000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
    ]);

    Product::factory()->create([
        'store_id' => $storeA->id,
        'stock' => 2,
        'min_stock' => 5,
    ]);
    Product::factory()->create([
        'store_id' => $storeA->id,
        'stock' => 10,
        'min_stock' => 5,
    ]);

    Artisan::call('app:send-monthly-report', ['--month' => '2026-07']);

    $periodLabel = CarbonImmutable::createFromFormat('Y-m', '2026-07')->startOfMonth()->translatedFormat('F Y');

    Mail::assertSent(MonthlyReportMail::class, 1);

    Mail::assertSent(MonthlyReportMail::class, function (MonthlyReportMail $mail) use ($periodLabel) {
        return $mail->hasTo('owner@sercomp.test')
            && $mail->periodLabel === $periodLabel
            && $mail->salesCount === 4
            && abs($mail->salesTotal - 550_000) < 0.01
            && count($mail->salesPerStore) === 2
            && $mail->servicesIn === 3
            && $mail->servicesDone === 1
            && count($mail->overdueDebts) === 1
            && abs($mail->overdueDebts[0]['outstanding'] - 300_000) < 0.01
            && abs($mail->overdueDebtsTotal - 300_000) < 0.01
            && $mail->lowStockCount === 1
            && $mail->lowStockProducts[0]['stock'] === 2
            && $mail->render() !== '';
    });
});

test('tidak mengirim apa pun bila tidak ada owner', function () {
    Mail::fake();

    User::factory()->create()->assignRole('admin');

    Artisan::call('app:send-monthly-report', ['--month' => '2026-07']);

    Mail::assertNothingSent();
});
