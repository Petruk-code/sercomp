<?php

use App\Models\Product;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'owner'] as $role) {
        Role::query()->updateOrCreate(['name' => $role], ['guard_name' => 'web']);
    }
});

function actingAsAdmin(): User
{
    $admin = User::factory()->create()->assignRole('admin');

    test()->actingAs($admin);

    return $admin;
}

test('admin can download a barcode PDF for the selected products', function () {
    actingAsAdmin();
    $first = Product::factory()->create();
    $second = Product::factory()->create();

    $this->get(route('master.products.barcode', ['ids' => "{$first->id},{$second->id}"]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertSee('%PDF-', escape: false);
});

test('the barcode endpoint generates a PDF for every product when no ids are given', function () {
    actingAsAdmin();
    Product::factory()->count(3)->create();

    $this->get(route('master.products.barcode'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertSee('%PDF-', escape: false);
});

test('guests are redirected to login when downloading barcodes', function () {
    $this->get(route('master.products.barcode', ['ids' => '1']))
        ->assertRedirect(route('login'));
});
