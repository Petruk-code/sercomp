# Arsitektur & Konvensi Pengembangan

## Struktur Proyek

```
app/
├── Console/Commands/          # BackupDatabase, SendAlerts (tugas terjadwal)
├── Http/Controllers/          # Dashboard, Service, Sales, Master, Supplier, Reports, Users
├── Models/                    # User, ServiceOrder, Sale, Product, Customer, Purchase, dll.
├── Notifications/             # LowStockAlert, ServiceQueueAlert, ServiceReadyForPickupAlert (in-app, database saja)
├── Observers/                 # ServiceOrderObserver (rekam riwayat status)
├── Providers/                 # AppServiceProvider, FortifyServiceProvider
├── Support/                   # InvoiceNumber (nomor PO/RTN/INV/SRV), CsvStream/CsvImporter (import-export CSV), WhatsApp, WhatsAppOtpService, AuditLogger/StockManager
└── ...
database/
├── factories/               # Factory model untuk testing
├── migrations/              # Skema database
└── seeders/                 # RoleSeeder, DummyDataSeeder, DatabaseSeeder
resources/js/
├── pages/                   # Halaman Inertia (welcome, dashboard, services, sales, tracking, supplier/*)
├── components/              # Komponen React (ui/*, nav, status-badge)
├── layouts/                 # Layout aplikasi & autentikasi
├── actions/                 # Hasil generate Wayfinder (controller types) — jangan diedit manual
└── routes/                  # Hasil generate Wayfinder (route types) — jangan diedit manual
resources/views/pdf/         # Template PDF (tanda terima servis, struk, barcode)
routes/
├── web.php                  # Route utama
├── settings.php             # Route pengaturan akun
└── console.php
```

## Konvensi Wajib

Rule lengkap & load-bearing ada di `.ai/rules/` (baca `index.md` dulu, lalu file rule sesuai path). Ringkasannya:

- **Wayfinder**: type untuk route/controller di `resources/js/routes` & `resources/js/actions` di-generate otomatis oleh plugin Vite. Jangan diedit manual. Saat generate manual selalu `php artisan wayfinder:generate --with-form` (tanpa `--with-form` akan merusak `.form()`).
- **Model**: pola `#[Fillable(...)]` (bukan `$fillable`), docblock tanggal pakai `Carbon\CarbonImmutable`, const label dalam model (lihat `Sale`, `Purchase`). Baca `.ai/rules/models.md`.
- **Controller**: Form Request, `Inertia::render`, flash toast `Inertia::flash('toast', [...])` (lihat `ProductController`), named routes, redirect ke index.
- **Mutasi stok**: `Product::increment/decrement` (dipakai di Sale, ServiceOrder, Purchase, dan seeder).
- **Riwayat status servis**: direkam otomatis oleh `ServiceOrderObserver`. Jangan menulis langsung ke tabel `service_order_status_histories`. Status baru yang tidak diisi otomatis menjadi `pending`.
- **Frontend**: pakai library/komponen yang sudah ada (shadcn/ui) sebelum membuat komponen baru. Semua panggilan route/controller memakai Wayfinder (`@/actions/...`, `@/routes/...`).
- **Tema/brand**: identitas visual SerComp (Indigo/Violet) hanya lewat design token di `resources/css/app.css` (oklch). Komponen/pages harus memakai token (`bg-primary`, `bg-sidebar-primary`, dll), bukan warna hardcode, agar dark mode & rebranding tetap mudah.
- **Role**: transaksi supplier & pembayaran → `admin,owner` saja; pelanggan boleh dikelola kasir; servis oleh semua peran; laporan hanya admin/owner (lihat `routes/web.php`).
- **Nomor dokumen**: `InvoiceNumber::next()` di `app/Support/InvoiceNumber.php` (`INV`, `SRV`, `PO-YYYYMMDD-0001`, `RTN-YYYYMMDD-0001`).
- **CSV import/export**: selalu lewat `app/Support/CsvStream.php` (export streaming, BOM UTF-8, anti formula-injection) & `CsvImporter.php` (import streaming). Header CSV dinormalisasi ke snake_case. Rute export/import harus dideklarasikan **sebelum** `Route::resource` saudaranya. Aturan: `.ai/rules/support-support.md`.

## Verifikasi Kualitas

```bash
composer run lint:check      # Pint (PHP) — cek format
composer run types:check     # PHPStan — static analysis PHP (memori 1G)
npm run lint:check           # ESLint — JavaScript/TypeScript
npm run types:check          # tsc — TypeScript type check
npm run format:check         # Prettier — format frontend
php artisan test --compact   # Pest — unit & feature test
```

Sekaligus: `composer run ci:check`.

Setelah mengubah PHP: `vendor/bin/pint --format agent` (atau `vendor/bin/pint --dirty` — projek kini git repo, hanya file berubah).

## Catatan Teknis

- **PHPStan**: jalankan dengan `--memory-limit=1G` (default 128M crash saat analisis paralel).
- **Test pembelian**: `tests/Feature/PurchaseFlowTest.php` (10 tes) + `tests/Feature/PurchaseReturnFlowTest.php` (12 tes) — lihat helper di file masing-masing.
- **Seeder**: jangan memakai `WithoutModelEvents` di `DatabaseSeeder` (lihat `.ai/rules/seeders.md`).
