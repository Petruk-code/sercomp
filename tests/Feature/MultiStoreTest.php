<?php

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Support\StoreContext;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'owner', 'cashier', 'technician'] as $role) {
        Role::query()->updateOrCreate(
            ['name' => $role, 'guard_name' => 'web'],
            ['label' => ucfirst($role)],
        );
    }
});

test('user terikat hanya melihat produk cabangnya sendiri', function () {
    $storeA = Store::factory()->create(['name' => 'Cabang A']);
    $storeB = Store::factory()->create(['name' => 'Cabang B']);

    $admin = User::factory()->create(['store_id' => $storeA->id])->assignRole('admin');

    Product::factory()->count(3)->create(['store_id' => $storeA->id]);
    Product::factory()->count(2)->create(['store_id' => $storeB->id]);

    $this->actingAs($admin)
        ->get(route('master.products.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/products/index')
            ->has('products.data', 3));
});

test('produk yang dibuat user terikat otomatis terisi store_id cabangnya', function () {
    $store = Store::factory()->create();
    $admin = User::factory()->create(['store_id' => $store->id])->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('master.products.store'), [
            'name' => 'Kabel SATA',
            'sku' => 'KBLSATA-001',
            'category_id' => null,
            'supplier_id' => null,
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'stock' => 5,
            'min_stock' => 1,
            'unit' => 'pcs',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('products', [
        'sku' => 'KBLSATA-001',
        'store_id' => $store->id,
    ]);
});

test('owner bebas melihat produk semua cabang sebelum memilih', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    $owner = User::factory()->create()->assignRole('owner');

    Product::factory()->count(2)->create(['store_id' => $storeA->id]);
    Product::factory()->count(3)->create(['store_id' => $storeB->id]);

    $this->actingAs($owner)
        ->get(route('master.products.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/products/index')
            ->has('products.data', 5));
});

test('owner dapat pindah cabang aktif dan konteks tersimpan di sesi', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create(['is_active' => true]);

    Product::factory()->count(2)->create(['store_id' => $storeA->id]);
    Product::factory()->count(3)->create(['store_id' => $storeB->id]);

    $owner = User::factory()->create()->assignRole('owner');

    $this->actingAs($owner)
        ->put(route('stores.active'), ['store_id' => $storeB->id])
        ->assertRedirect();

    expect(session('active_store_id'))->toBe($storeB->id);

    StoreContext::flush();

    $this->get(route('master.products.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 3));

    expect(StoreContext::id())->toBe($storeB->id);
});

test('kasir terikat tidak boleh pindah cabang aktif', function () {
    $store = Store::factory()->create();
    $other = Store::factory()->create();

    $cashier = User::factory()->create(['store_id' => $store->id])->assignRole('cashier');

    $this->actingAs($cashier)
        ->put(route('stores.active'), ['store_id' => $other->id])
        ->assertForbidden();

    expect(session('active_store_id'))->toBeNull();
});

test('pindah ke cabang nonaktif ditolak validasi', function () {
    $inactive = Store::factory()->create(['is_active' => false]);

    $owner = User::factory()->create()->assignRole('owner');

    $this->actingAs($owner)
        ->put(route('stores.active'), ['store_id' => $inactive->id])
        ->assertSessionHasErrors('store_id');
});

test('halaman users mengirim daftar cabang aktif untuk form', function () {
    // +1 store default "Cabang Utama" dari migrasi.
    Store::factory()->create(['name' => 'Cabang Dago']);
    Store::factory()->create(['name' => 'Cabang Tutup', 'is_active' => false]);

    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('users.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/form')
            ->has('stores', 2)
            ->where('stores.0.name', 'Cabang Dago'));
});

test('hanya admin dan owner yang dapat mengelola cabang', function () {
    $cashier = User::factory()->create()->assignRole('cashier');

    $this->actingAs($cashier)->get(route('stores.index'))->assertForbidden();
    $this->actingAs($cashier)->post(route('stores.store'), ['name' => 'X'])->assertForbidden();
});

test('admin dapat membuat dan memperbarui cabang', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('stores.store'), [
            'name' => 'Cabang Cihampelas',
            'address' => 'Jl. Cihampelas No. 1',
            'phone' => '081200000001',
            'is_active' => '1',
        ])
        ->assertRedirect(route('stores.index'));

    $store = Store::query()->where('name', 'Cabang Cihampelas')->firstOrFail();

    expect($store->is_active)->toBeTrue()
        ->and($store->phone)->toBe('081200000001');

    $this->actingAs($admin)
        ->put(route('stores.update', $store), [
            'name' => 'Cabang Cihampelas',
            'is_active' => '0',
        ])
        ->assertRedirect(route('stores.index'));

    expect($store->refresh()->is_active)->toBeFalse();
});
