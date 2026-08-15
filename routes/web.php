<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\WhatsAppTwoFactorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\ServiceCatalogController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\Service\ServiceOrderController;
use App\Http\Controllers\Supplier\PurchaseController;
use App\Http\Controllers\Supplier\PurchaseReturnController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\Users\RoleController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('tracking', TrackingController::class)->middleware('throttle:tracking-lookups')->name('tracking');

Route::get('tracking/card', [TrackingController::class, 'card'])->middleware('throttle:pdf-downloads')->name('tracking.card');

Route::get('build/sw.js', function (): Response {
    $path = public_path('build/sw.js');

    if (! file_exists($path)) {
        abort(404);
    }

    $contents = file_get_contents($path);

    if ($contents === false) {
        abort(404);
    }

    return response($contents, 200, [
        'Content-Type' => 'application/javascript',
        'Service-Worker-Allowed' => '/',
        'Cache-Control' => 'no-cache, must-revalidate',
    ]);
})->name('pwa.sw');

Route::middleware(['guest'])->group(function () {
    Route::get('two-factor/whatsapp', [WhatsAppTwoFactorController::class, 'create'])->name('two-factor.whatsapp');
    Route::post('two-factor/whatsapp/verify', [WhatsAppTwoFactorController::class, 'store'])
        ->middleware('throttle:two-factor')
        ->name('two-factor.whatsapp.verify');
    Route::post('two-factor/whatsapp/resend', [WhatsAppTwoFactorController::class, 'resend'])
        ->middleware('throttle:two-factor')
        ->name('two-factor.whatsapp.resend');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Notifikasi
    Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::get('notifications/summary', [NotificationsController::class, 'summary'])->name('notifications.summary');
    Route::post('notifications/read-all', [NotificationsController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationsController::class, 'markAsRead'])->name('notifications.read');
    Route::delete('notifications/{notification}', [NotificationsController::class, 'destroy'])->name('notifications.destroy');

    // Master data — kategori produk & jasa
    Route::middleware('role:admin,owner')->group(function () {
        Route::resource('categories', CategoryController::class)->parameters(['categories' => 'category'])->names('master.categories');

        Route::get('products/barcode', [ProductController::class, 'barcode'])->name('master.products.barcode');
        Route::resource('products', ProductController::class)->parameters(['products' => 'product'])->names('master.products');

        Route::resource('catalog/services', ServiceCatalogController::class)->parameters(['services' => 'service'])->names('master.services');

        Route::resource('suppliers', SupplierController::class)->parameters(['suppliers' => 'supplier'])->names('master.suppliers');

        // Pembelian dari supplier (PO + tempo/cicilan)
        Route::post('supplier/purchases/{purchase}/payments', [PurchaseController::class, 'pay'])->name('supplier.purchases.pay');
        Route::resource('supplier/purchases', PurchaseController::class)->parameters(['purchases' => 'purchase'])->names('supplier.purchases');

        // Retur ke supplier
        Route::get('supplier/returns', [PurchaseReturnController::class, 'index'])->name('supplier.returns.index');
        Route::get('supplier/purchases/{purchase}/returns/create', [PurchaseReturnController::class, 'create'])->name('supplier.returns.create');
        Route::post('supplier/purchases/{purchase}/returns', [PurchaseReturnController::class, 'store'])->name('supplier.returns.store');
        Route::get('supplier/returns/{purchaseReturn}', [PurchaseReturnController::class, 'show'])->name('supplier.returns.show');
        Route::delete('supplier/returns/{purchaseReturn}', [PurchaseReturnController::class, 'destroy'])->name('supplier.returns.destroy');

        Route::resource('roles', RoleController::class)->parameters(['roles' => 'role'])->names('roles')->except('show');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::put('users/{user}/role', [UserController::class, 'updateRole'])->name('users.update-role');
    });

    // Master data — pelanggan (kasir boleh mengelola)
    Route::middleware('role:admin,owner,cashier')->group(function () {
        Route::resource('customers', CustomerController::class)->parameters(['customers' => 'customer'])->names('master.customers');
    });

    // Modul servis
    Route::middleware('role:admin,owner,cashier,technician')->group(function () {
        Route::post('services/{service}/items', [ServiceOrderController::class, 'addItem'])->name('services.items.store');
        Route::delete('services/{service}/items/{item}', [ServiceOrderController::class, 'removeItem'])->name('services.items.destroy');
        Route::patch('services/{service}/status', [ServiceOrderController::class, 'updateStatus'])->name('services.status');
        Route::post('services/{service}/whatsapp', [ServiceOrderController::class, 'sendWhatsapp'])->name('services.whatsapp');
        Route::get('services/{service}/print', [ServiceOrderController::class, 'print'])->middleware('throttle:pdf-downloads')->name('services.print');
        Route::resource('services', ServiceOrderController::class)->parameters(['services' => 'service'])->names('services');
    });

    // Kasir / POS & transaksi
    Route::middleware('role:admin,owner,cashier')->group(function () {
        Route::get('pos', [SaleController::class, 'create'])->name('sales.create');
        Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
        Route::post('services/{service}/checkout', [SaleController::class, 'checkout'])->name('sales.checkout');
        Route::post('sales/{sale}/payment', [SaleController::class, 'completePayment'])->name('sales.payment');
        Route::get('sales/{sale}/print', [SaleController::class, 'print'])->middleware('throttle:pdf-downloads')->name('sales.print');
        Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
    });

    // Laporan
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('reports/services', [ReportController::class, 'services'])->name('reports.services');
        Route::get('reports/stock', [ReportController::class, 'stock'])->name('reports.stock');
        Route::get('reports/purchases', [ReportController::class, 'purchases'])->name('reports.purchases');
        Route::get('reports/debts', [ReportController::class, 'debts'])->name('reports.debts');
    });

    // Audit log aksi sensitif — khusus admin/owner
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});

require __DIR__.'/settings.php';
