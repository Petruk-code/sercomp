<?php

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }
});

function actingAsReportAdmin(): User
{
    $admin = User::factory()->create()->assignRole('admin');

    test()->actingAs($admin);

    return $admin;
}

test('guests are redirected away from purchase and debt reports', function () {
    $this->get(route('reports.purchases'))->assertRedirect();
    $this->get(route('reports.debts'))->assertRedirect();
});

test('cashiers cannot access purchase and debt reports', function () {
    $cashier = User::factory()->create()->assignRole('cashier');

    $this->actingAs($cashier)->get(route('reports.purchases'))->assertForbidden();
    $this->actingAs($cashier)->get(route('reports.debts'))->assertForbidden();
});

test('purchase report lists purchases within the period with summary', function () {
    actingAsReportAdmin();
    $supplier = Supplier::factory()->create();

    Purchase::factory()->create([
        'supplier_id' => $supplier->id,
        'purchase_date' => now()->toDateString(),
        'total' => 100_000,
        'paid_amount' => 40_000,
        'payment_status' => 'partial',
    ]);

    $this->get(route('reports.purchases'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/purchases')
            ->has('purchases.data', 1)
            ->where('purchases.data.0.supplier.name', $supplier->name)
            ->where('purchases.data.0.total', '100000.00')
            ->where('purchases.data.0.paid_amount', '40000.00')
            ->where('summary.total_purchases', 1));
});

test('purchase report respects supplier and payment status filters', function () {
    actingAsReportAdmin();
    $supplierA = Supplier::factory()->create(['name' => 'Supplier A']);
    $supplierB = Supplier::factory()->create(['name' => 'Supplier B']);

    $unpaidA = Purchase::factory()->create([
        'supplier_id' => $supplierA->id,
        'purchase_date' => now()->toDateString(),
        'payment_status' => 'unpaid',
    ]);
    Purchase::factory()->create([
        'supplier_id' => $supplierB->id,
        'purchase_date' => now()->toDateString(),
        'payment_status' => 'unpaid',
    ]);

    $this->get(route('reports.purchases', ['supplier_id' => $supplierA->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/purchases')
            ->has('purchases.data', 1)
            ->where('purchases.data.0.id', $unpaidA->id));

    $this->get(route('reports.purchases', ['supplier_id' => $supplierA->id, 'payment_status' => 'paid']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/purchases')
            ->has('purchases.data', 0));
});

test('debt report buckets aging per supplier and excludes paid purchases', function () {
    actingAsReportAdmin();

    $supplierA = Supplier::factory()->create(['name' => 'Supplier A']);
    $supplierB = Supplier::factory()->create(['name' => 'Supplier B']);

    Purchase::factory()->create([
        'supplier_id' => $supplierA->id,
        'total' => 100_000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'due_date' => now()->subDays(10)->toDateString(),
    ]);
    Purchase::factory()->create([
        'supplier_id' => $supplierA->id,
        'total' => 200_000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'due_date' => now()->subDays(40)->toDateString(),
    ]);
    Purchase::factory()->create([
        'supplier_id' => $supplierB->id,
        'total' => 50_000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'due_date' => now()->subDays(90)->toDateString(),
    ]);
    Purchase::factory()->create([
        'supplier_id' => $supplierB->id,
        'total' => 30_000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'due_date' => now()->addDays(5)->toDateString(),
    ]);
    Purchase::factory()->paid()->create([
        'supplier_id' => $supplierB->id,
        'total' => 500_000,
        'due_date' => now()->subDays(20)->toDateString(),
    ]);

    $this->get(route('reports.debts'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/debts')
            ->has('rows', 2)
            ->where('rows.0.id', $supplierA->id)
            ->where('rows.0.current', 0)
            ->where('rows.0.overdue_0_30', 100_000)
            ->where('rows.0.overdue_31_60', 200_000)
            ->where('rows.0.overdue_60', 0)
            ->where('rows.0.outstanding', 300_000)
            ->where('rows.1.id', $supplierB->id)
            ->where('rows.1.current', 30_000)
            ->where('rows.1.overdue_0_30', 0)
            ->where('rows.1.overdue_31_60', 0)
            ->where('rows.1.overdue_60', 50_000)
            ->where('rows.1.outstanding', 80_000)
            ->where('summary.outstanding', 380_000)
            ->where('summary.overdue', 350_000)
            ->where('summary.suppliers', 2));
});

test('debt report can be filtered by a single supplier', function () {
    actingAsReportAdmin();

    $supplierA = Supplier::factory()->create(['name' => 'Supplier A']);
    $supplierB = Supplier::factory()->create(['name' => 'Supplier B']);

    Purchase::factory()->create([
        'supplier_id' => $supplierA->id,
        'total' => 100_000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'due_date' => now()->subDays(10)->toDateString(),
    ]);
    Purchase::factory()->create([
        'supplier_id' => $supplierB->id,
        'total' => 200_000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'due_date' => now()->subDays(10)->toDateString(),
    ]);

    $this->get(route('reports.debts', ['supplier_id' => $supplierB->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/debts')
            ->has('rows', 1)
            ->where('rows.0.id', $supplierB->id)
            ->where('rows.0.overdue_0_30', 200_000)
            ->where('summary.outstanding', 200_000)
            ->where('summary.suppliers', 1));
});
