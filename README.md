# SerComp — Sistem Manajemen Servis & Kasir

Aplikasi untuk mengelola bisnis servis komputer/laptop/HP dan kasir/penjualan produk retail. Dibangun dengan **Laravel 13**, **React 19 (Inertia.js v3)**, **TypeScript**, dan **Tailwind CSS v4**.

## Feature Stack

### Akses & Akun
- **Autentikasi** (Laravel Fortify): login, reset password, verifikasi email, 2FA (TOTP via authenticator **dan** WhatsApp OTP via Fonnte), pengaturan profil, tema light/dark.
- **RBAC** via `spatie/laravel-permission`: peran `admin`, `owner`, `cashier` (kasir), `technician` (teknisi).
- **Dashboard per peran**: admin/owner/kasir (ringkasan penjualan, servis aktif, stok menipis, beban kerja teknisi, aktivitas terbaru); teknisi (servis masuk & beban kerja pribadi).

### Master Data
- Kategori produk & jasa, produk (stok, barcode), katalog jasa servis (biaya & estimasi waktu).
- Pelanggan (profil + riwayat servis) dan supplier.

### Manajemen Supplier & Pembelian
- Pencatatan pembelian (PO) dari supplier — nomor `PO-YYYYMMDD-0001`, stok bertambah otomatis.
- Tempo pembayaran / jatuh tempo, pembayaran bertahap (cicilan), daftar hutang berjalan & status jatuh tempo.
- Retur ke supplier — hasil ganti barang / uang kembali / potong hutang, stok berkurang otomatis.

### Modul Servis
- Alur status *Pending → Diagnosa → Proses → Selesai → Diambil*, penugasan teknisi, sparepart terpakai (stok berkurang otomatis).
- Riwayat status otomatis + manajemen garansi (durasi, masa berlaku, tindak lanjut).
- Tanda terima dengan **QR code** → halaman publik `/tracking` untuk pelacakan status.

### Kasir / POS
- Penjualan produk retail, checkout servis (jasa + sparepart), metode pembayaran beragam.
- Cetak nota/struk & barcode (dompdf); layout dua-panel yang dioptimalkan untuk tablet.

### Laporan, Notifikasi & Teknis
- Laporan penjualan, servis, stok untuk admin/owner.
- Notifikasi otomatis stok menipis & antrian servis (e-mail + database, command terjadwal).
- Backup database otomatis (SQLite/MySQL/PostgreSQL, retensi otomatis).
- PWA (installable di HP/tablet), rate limiting halaman publik & unduhan PDF.

## Tech Stack

| Lapisan   | Teknologi                                                              |
| --------- | ---------------------------------------------------------------------- |
| Backend   | Laravel 13 (PHP 8.4+), Laravel Fortify, Wayfinder                       |
| Frontend  | React 19, Inertia.js v3, TypeScript                                    |
| Styling   | Tailwind CSS v4, shadcn/ui, lucide-react, sonner                       |
| PDF/QR    | barryvdh/laravel-dompdf, endroid/qr-code, picqer/php-barcode-generator |
| Database  | SQLite (default), bisa diganti MySQL/PostgreSQL via `.env`             |
| Testing   | Pest, PHPStan (larastan), Pint, ESLint, Prettier, `tsc --noEmit`       |

## Instalasi

### Prasyarat
- PHP 8.3+ (disarankan 8.4) dengan ekstensi yang dibutuhkan Laravel
- Composer, Node.js 20+ & npm
- SQLite (bawaan, tanpa konfigurasi tambahan)

### Cara cepat
```bash
composer run setup
```

Menjalankan: `composer install`, membuat `.env`, `php artisan key:generate`, migrasi database, `npm install`, dan `npm run build`.

### Cara manual
```bash
# 1. Dependensi PHP
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Migrasi database (SQLite dibuat otomatis)
touch database/database.sqlite
php artisan migrate

# 4. Dependensi & build frontend
npm install
npm run build
```

### Seeder & akun demo
```bash
php artisan db:seed
```

Seeder membuat peran `admin`, `owner`, `cashier`, `technician` beserta data contoh. Akun admin default:

```
Email    : admin@sercomp.test
Password : password
```

## Menjalankan Aplikasi

```bash
composer run dev
```

Perintah ini menjalankan server Laravel (`php artisan dev`) dan Vite dev server bersamaan. Buka `http://localhost:8000`.

Alternatif terpisah:

```bash
php artisan serve   # terminal 1
npm run dev         # terminal 2
```

## Dokumentasi

Catatan update & pengembangan proyek tersimpan rapi di [`docs/`](docs/README.md): status & roadmap, arsitektur & konvensi, operasional (PWA, scheduler, persiapan produksi), panduan WhatsApp 2FA & OTP, serta handoff per modul.
