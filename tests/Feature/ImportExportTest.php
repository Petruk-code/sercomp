<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }
});

function actingAsImportAdmin(): User
{
    $admin = User::factory()->create()->assignRole('admin');

    test()->actingAs($admin);

    return $admin;
}

function importCsvUpload(string $filename, string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent($filename, $content);
}

function toastFlash(): ?array
{
    return session('inertia.flash_data');
}

test('admin can export products as a CSV download', function () {
    actingAsImportAdmin();

    $product = Product::factory()->create();
    Product::factory()->create(['name' => '=1+1']);

    $response = $this->get(route('master.products.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();

    expect($content)
        ->toContain('SKU')
        ->toContain($product->name)
        ->toContain($product->sku)
        ->toContain("'=1+1");
});

test('admin can export customers and suppliers as CSV', function () {
    actingAsImportAdmin();

    $customer = Customer::factory()->create();
    $supplier = Supplier::factory()->create();

    expect($this->get(route('master.customers.export'))->streamedContent())
        ->toContain($customer->name)
        ->toContain($customer->phone);

    expect($this->get(route('master.suppliers.export'))->streamedContent())
        ->toContain($supplier->name)
        ->toContain($supplier->contact_person);
});

test('cashier can export customers but not products', function () {
    $cashier = User::factory()->create()->assignRole('cashier');

    $this->actingAs($cashier)
        ->get(route('master.customers.export'))
        ->assertOk();

    $this->get(route('master.products.export'))->assertForbidden();
    $this->post(route('master.products.import'), [
        'file' => importCsvUpload('produk.csv', "Nama,SKU\nBaru,P001"),
    ])->assertForbidden();
});

test('admin can import products, creating new and updating existing', function () {
    actingAsImportAdmin();

    $this->post(route('master.products.import'), [
        'file' => importCsvUpload('produk.csv', "Nama,SKU,Harga Beli,Harga Jual,Stok\nProduk Import,P001,\"10.000\",\"15.000\",5"),
    ])->assertRedirect();

    $product = Product::query()->where('sku', 'P001')->first();

    expect($product)->not->toBeNull()
        ->and((float) $product->purchase_price)->toBe(10_000.0)
        ->and((float) $product->selling_price)->toBe(15_000.0)
        ->and($product->stock)->toBe(5);

    $this->post(route('master.products.import'), [
        'file' => importCsvUpload('produk.csv', "Nama,SKU,Harga Jual\nProduk Import, P001, 20000"),
    ])->assertRedirect();

    expect((float) $product->fresh()->selling_price)->toBe(20_000.0)
        ->and(toastFlash()['toast']['message'])->toContain('1 diperbarui');
});

test('product import creates a missing category by name', function () {
    actingAsImportAdmin();

    $this->post(route('master.products.import'), [
        'file' => importCsvUpload('produk.csv', "Nama,SKU,Kategori,Harga Jual\nProduk Elektronik,P002,Elektronik,25000"),
    ])->assertRedirect();

    $product = Product::query()->where('sku', 'P002')->first();

    expect($product?->category?->name)->toBe('Elektronik')
        ->and($product->category->type)->toBe('product');
});

test('product import skips invalid rows and reports the counts', function () {
    actingAsImportAdmin();

    $this->post(route('master.products.import'), [
        'file' => importCsvUpload('produk.csv', "Nama,SKU,Harga Jual\n,B001,1000\nProduk Valid,B002,2000"),
    ])->assertRedirect();

    expect(Product::query()->where('sku', 'B002')->exists())->toBeTrue()
        ->and(Product::query()->where('sku', 'B001')->exists())->toBeFalse();

    $message = toastFlash()['toast']['message'];

    expect($message)
        ->toContain('1 dibuat')
        ->toContain('1 gagal')
        ->toContain('Nama produk wajib diisi');
});

test('admin can import customers and suppliers', function () {
    actingAsImportAdmin();

    $this->post(route('master.customers.import'), [
        'file' => importCsvUpload('pelanggan.csv', "Nama,Telepon,Email\nBudi Santoso,081234567890,budi@example.com"),
    ])->assertRedirect();

    $customer = Customer::query()->where('phone', '081234567890')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->name)->toBe('Budi Santoso');

    $this->post(route('master.suppliers.import'), [
        'file' => importCsvUpload('supplier.csv', "Nama,Kontak Person,Telepon\nCV Maju Jaya,Pak Joko,0811223344"),
    ])->assertRedirect();

    $supplier = Supplier::query()->where('name', 'CV Maju Jaya')->first();

    expect($supplier)->not->toBeNull()
        ->and($supplier->contact_person)->toBe('Pak Joko');
});

test('report exports stream the expected CSV data', function () {
    actingAsImportAdmin();

    $sale = Sale::factory()->create();
    $service = ServiceOrder::factory()->create();
    $supplier = Supplier::factory()->create();
    Purchase::factory()->create([
        'supplier_id' => $supplier->id,
        'total' => 100_000,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'due_date' => now()->addDays(5)->toDateString(),
    ]);

    $range = [
        'date_from' => now()->startOfMonth()->toDateString(),
        'date_to' => now()->toDateString(),
    ];

    expect($this->get(route('reports.sales.export', $range))->streamedContent())
        ->toContain($sale->invoice_number)
        ->toContain($sale->customer->name);

    expect($this->get(route('reports.services.export', $range))->streamedContent())
        ->toContain($service->invoice_number)
        ->toContain($service->customer->name);

    expect($this->get(route('reports.debts.export'))->streamedContent())
        ->toContain($supplier->name)
        ->toContain('100000');
});

test('non-admin roles cannot access report exports', function () {
    $cashier = User::factory()->create()->assignRole('cashier');

    $this->actingAs($cashier)
        ->get(route('reports.sales.export'))
        ->assertForbidden();
});
