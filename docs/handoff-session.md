# Handoff Sesi — Briefing Kerja Lanjutan

> Dokumen ini adalah **briefing siap-pakai** untuk sesi pengembangan berikutnya. Baca dari awal sebelum mulai kerja, lalu eksekusi bagian "Siap dikerjakan berikutnya". Peta prioritas & estimasi jangka panjang: [`docs/05-rekomendasi-pengembangan.md`](05-rekomendasi-pengembangan.md).

## 1. Posisi proyek

SerComp — aplikasi manajemen servis komputer + kasir/POS (Laravel 13, Inertia v3 + React 19, Tailwind v4, shadcn/ui, MySQL). Fitur inti **selesai semua**: servis, tracking publik, POS, stok, supplier/pembelian/hutang, laporan, RBAC, PWA, 2FA (TOTP + WhatsApp OTP), kartu lacak PDF, audit log, import/export CSV.

**Git**: repo aktif. `feature/import-export` sudah **di-merge ke `main`** (fast-forward) dan branch fitur bisa dihapus. Kerja berikutnya mulai dari `main`.

Verifikasi terkini (2026-08-21): **230 test Pest (229 lulus + 1 skipped PwaTest — termasuk 11 test browser Playwright)**, PHPStan 0 error, Pint rapi. ESLint & Prettier bersih, `tsc --noEmit` bersih, `npm run build` sukses.

## 2. Ringkasan yang sudah selesai

| Rekomendasi | Status | Inti |
| --- | --- | --- |
| 2.4 Realtime notifikasi (Reverb) | ✅ SELESAI (2026-08-21) | Laravel Reverb + Echo (`@laravel/echo-react`); listener sinkron `PushDatabaseNotificationToRealtime` push event `NotificationPushed` (ShouldBroadcastNow, tanpa queue worker) ke channel privat `App.Models.User.{id}` untuk tiap notifikasi database; `NotificationsProvider` realtime-first — polling 30 dtk hanya fallback saat socket putus/tak terkonfigurasi. Test: `RealtimeNotificationTest` (3). Port server **8081** (`REVERB_SERVER_PORT`, 8080 dipakai Apache lokal). |
| 2.3 Cetak struk thermal/Bluetooth | ✅ SELESAI (2026-08-21) | Encoder ESC/POS murni TS (`lib/escpos.ts`: align/bold/size/columns/cut, lebar 58mm=32 / 80mm=48 kolom); builder struk nota & tanda terima (`lib/thermal-receipt.ts`); transport Web Bluetooth (service 18F0) & Web Serial 9600 8N1 dengan auto-reconnect perangkat terizin (`lib/thermal-printer.ts`); dialog pilih lebar kertas + preferensi localStorage; tombol "Cetak Thermal" di `sales/show` & `services/show`. Fallback PDF A5 tetap ada. Test: `ThermalPrintTest` (2 browser). |
| 2.2 PWA offline / antrean sinkronisasi POS | ✅ SELESAI (2026-08-21) | Idempotensi `sales.client_reference` unik (+ header `X-Offline-Sync` → JSON); antrean & draf keranjang localStorage (`lib/offline-sales.ts`); checkout offline-aware (`onNetworkError` → enqueue, auto-sync saat mount/event `online`); dialog antrean retry/buang; badge Offline. Test: `PosOfflineSyncTest` (4) + `PosOfflineQueueTest` (3 browser). Trap infrastruktur: tiap `visit()` = context baru → login lanjut dari page instance sama (`.ai/rules/browser.md`). |
| 2.1 E2E/browser testing (Playwright) | ✅ SELESAI (2026-08-21) | `pestphp/pest-plugin-browser` + Playwright Chromium; suite `tests/Browser` (8 test / 100 assertion): smoke 31 route autentikasi lewat login form asli (`assertNoSmoke`), alur POS → checkout → nota, servis → status → tracking publik (+ not-found). Helper `loginBrowserAdmin()` di `tests/Pest.php`. Trap `public/hot` basi → `.ai/rules/browser.md`. |
| 1.4 Import/export CSV | ✅ SELESAI | Streaming CSV native: `app/Support/CsvStream.php` (export, BOM UTF-8, anti formula-injection) + `CsvImporter.php` (import streaming). Export master (produk/pelanggan/supplier) + laporan (penjualan/servis/hutang); import master via `ImportCsvDialog`. `ImportExportTest` (9 test). Aturan: `.ai/rules/support-support.md`. |
| 1.3 Audit log aksi sensitif | ✅ SELESAI | Tabel + model `AuditLog`, helper `AuditLogger`/`StockManager`, event di sale/purchase/retur/servis/stok/2FA, halaman `/audit-logs` (filter + diff). `AuditLogTest` (9 test). |

Sesi-sesi sebelumnya hari ini (detail teknis di `docs/01-status-roadmap.md'):

- **Notifikasi dikencangkan (1.2)**: endpoint JSON `GET /notifications/summary` menggantikan shared prop (`notificationSummary` dihapus dari `HandleInertiaRequests`); `NotificationsProvider` polling 30 dtk + saat tab fokus; mark-as-read saat klik; hapus per item (`DELETE notifications/{notification}`); `notifications:prune` (default >90 hari) terjadwal daily.
- **Notifikasi murni in-app**: ketiga alert (`LowStockAlert`, `ServiceQueueAlert`, `ServiceReadyForPickupAlert`) `via() = ['database']` saja (email & WhatsApp otomatis dihapus — anti-spam); lonceng di `AppSidebarHeader`; popup toast sonner untuk notifikasi baru.
- **WhatsApp 2FA + OTP**: kode OTP 6 digit via Fonnte ke `users.phone`; challenge login sebelum TOTP; hash bcrypt + kedaluwarsa 5 menit + maks 3 percobaan; pengiriman nyata **hanya** saat `APP_ENV=production` (lokal hanya log). Panduan: `docs/04-whatsapp-2fa-otp.md`.
- **2FA TOTP**: Google Authenticator + recovery codes, challenge login, confirm-password, rate limit.
- **Kartu lacak servis (PDF)**: `GET /tracking/card?token=…` (30/menit/IP), kartu kredit 86×54mm, QR memakai token.
- **Notifikasi WhatsApp ke pelanggan — MANUAL saja**: tombol "Kirim WhatsApp" di detail servis (admin/owner/casher); auto-kirim saat `done` dihapus (anti-spam). **Diaudit ulang 2026-08-21 setelah laporan pembatasan nomor: TIDAK ada jalur auto-kirim tersisa** — `WhatsApp::send` (Fonnte) hanya dipanggil dari tombol manual & OTP 2FA login; ketiga alert otomatis murni `['database']`; perilaku dikunci test `CustomerWhatsappNotificationTest` (termasuk "marking done does NOT auto-send"). Jangan pernah menambah channel WA pada notifikasi/jadwal/observer.
- **Bug fix review**: prop `notifications` → `notificationSummary` (tabrakan di halaman `/notifications`), toast pertama "tertelan" (guard `seenIds.size === 0` → ref `initialized`), qty POS di-clamp `Math.min(Math.max(qty,1), product.stock)`.

## 3. Checklist status & siap dikerjakan berikutnya (urut saran)

### Checklist rekomendasi

| # | Rekomendasi | Status |
| --- | --- | --- |
| 1.2 | Kencangkan sistem notifikasi | ✅ Selesai |
| 1.3 | Audit log aksi sensitif | ✅ Selesai |
| 1.4 | Import/export CSV master & laporan | ✅ Selesai |
| 2.1 | E2E / browser testing (Playwright) | ✅ Selesai |
| 2.2 | PWA offline / antrean sinkronisasi POS | ✅ Selesai |
| 2.3 | Cetak struk thermal/Bluetooth | ✅ Selesai |
| 2.4 | Realtime notifikasi (Laravel Reverb) | ✅ Selesai |
| — | Push web, multi-cabang, ringkasan otomatis | 🔜 **Berikutnya** |
| 1.1 | **Deploy ke produksi** | ⏳ **PALING AKHIR** — hanya setelah semua modul selesai & verifikasi bebas error |

### A. PWA offline / antrean sinkronisasi POS (2.2) — ✅ SELESAI
Implementasi yang sudah jalan:
- **Backend idempotensi**: kolom `sales.client_reference` (nullable unique) + pre-check & catch `UniqueConstraintViolationException` di `SaleController@store`; header `X-Offline-Sync` mengubah respons redirect menjadi JSON (`{status, invoice_number, redirect_url}`) agar sync via fetch bisa membedakan sukses/gagal. Test: `tests/Feature/PosOfflineSyncTest.php` (4 test).
- **Frontend antrean**: `resources/js/lib/offline-sales.ts` (antrean + draf keranjang di localStorage, `syncPendingSales()` via fetch berurutan), `resources/js/components/pos-offline-dialog.tsx` (dialog antrean: status pending/failed, retry, buang), integrasi `pos.tsx` (persist draf keranjang, checkout offline-aware dengan `client_reference` per attempt, `onNetworkError` → enqueue, auto-sync saat mount + event `online`, badge Offline + tombol Antrean).
- **Test browser**: `tests/Browser/PosOfflineQueueTest.php` (auto-sync sukses, gagal stok → status failed, restore draf keranjang).
- Catatan infrastruktur test: setiap `visit()` = context browser baru; login harus dilanjutkan dari page instance yang sama (lihat `.ai/rules/browser.md`).

### B. Cetak struk thermal/Bluetooth (2.3) — ✅ SELESAI
Implementasi yang sudah jalan (frontend murni, tanpa backend baru):
- **Encoder ESC/POS**: `resources/js/lib/escpos.ts` — `EscposBuilder` (init/align/bold/size/text/feed/cut/columns/separator/raw), lebar kertas 58mm = 32 kolom, 80mm = 48 kolom; teks non-Latin-1 → `?`; payload dipotong chunk 180 byte.
- **Builder struk**: `resources/js/lib/thermal-receipt.ts` — `buildSaleReceipt()` (nota penjualan: item qty×harga, subtotal/diskon/total bold besar, bayar/kembalian) & `buildServiceReceipt()` (tanda terima servis: unit/keluhan/kondisi/estimasi biaya + URL lacak `/tracking?no=…`), word-wrap otomatis selebar kertas.
- **Transport**: `resources/js/lib/thermal-printer.ts` — Web Bluetooth (service ESC/POS BLE `18F0`, characteristic write/writeWithoutResponse, delay antar-chunk 20ms) & Web Serial (9600 8N1); keduanya coba pakai perangkat/port yang sudah diberi izin (`getDevices()`/`getPorts()`) sebelum minta pairing baru; preferensi lebar & transport terakhir di localStorage (`sercomp.thermal.*`).
- **UI**: `resources/js/components/thermal-print-dialog.tsx` (pilih 58/80mm via ToggleGroup, tombol Bluetooth/Kabel dengan status disabled bila tak didukung + pesan error); tombol "Cetak Thermal" di `sales/show.tsx` & `services/show.tsx`; fallback PDF A5 Dompdf tetap tersedia.
- **Test**: `tests/Browser/ThermalPrintTest.php` — dialog terbuka dari kedua halaman + lebar kertas tersimpan ke localStorage. Catatan: headless Chrome punya `navigator.bluetooth`/`serial`, jadi jangan assert teks "tidak mendukung".
- Verifikasi hardware nyata (pairing printer fisik) belum dilakukan — butuh printer thermal ESC/POS asli; protokol mengikuti standar industri (service 18F0).

### B2. Realtime notifikasi via Laravel Reverb (2.4) — ✅ SELESAI
Implementasi yang sudah jalan:
- **Server**: `laravel/reverb` terpasang (`install:broadcasting` manual karena TTY); `bootstrap/app.php` + `routes/channels.php`; channel `App.Models.User.{id}` dijaga `UserNotificationsChannel` (hanya pemilik). **Port server 8081** via `REVERB_SERVER_PORT=8081` di `.env` — 8080 dipakai Apache lokal (trap: `servers.reverb.port` di config/reverb.php terpisah dari `REVERB_PORT` apps).
- **Backend push**: listener sinkron `PushDatabaseNotificationToRealtime` (event `NotificationSent`) → broadcast `NotificationPushed` (**ShouldBroadcastNow** — sengaja tanpa queue worker, sesuai aturan notifikasi) ke channel user, event name `.notification.pushed`, payload = `toArray()`. Hanya notifikasi yang implement kontrak baru `App\Contracts\HasDatabasePayload` (sudah diimplement ketiga alert) dan channel `database`.
- **Frontend**: `@laravel/echo-react` + `pusher-js`; `configureEcho({broadcaster:'reverb'})` di `app.tsx` (dijaga `VITE_REVERB_APP_KEY` ada); `NotificationsProvider` subscribe channel privat → refresh saat `.notification.pushed`; polling 30 dtk hanya fallback saat socket putus/belum tersambung/tak terkonfigurasi. CSRF auth channel dari `<meta name="csrf-token">` di `app.blade.php`.
- **Test**: `RealtimeNotificationTest` (3): push terkirim utk notifikasi database (channel+payload), tidak push utk channel lain, otorisasi channel. Smoke server: handshake WS `101 Switching Protocols` di 8081.
- **Catatan operasional**: restart `composer dev` agar TUI dev menjalankan `reverb:start` otomatis (Reverb mendaftar sendiri ke `php artisan dev`); nilai `VITE_REVERB_*` ter-bake saat build → jalankan `npm run build` setelah ubah env; produksi nanti butuh supervisor utk `reverb:start` + WebSocket di belakang proxy (TLS).

### C. Deploy ke domain produksi — PALING AKHIR
Dikerjakan **hanya setelah seluruh roadmap (2.2, 2.3, dst.) selesai dan seluruh verifikasi hijau tanpa error**. Urutan eksekusi final saat saatnya tiba (checklist detail: `docs/03-operasional.md` & `docs/01-status-roadmap.md`):

1. Siapkan hosting + domain + SSL (Laravel Cloud / Forge / VPS + nginx).
2. `.env` produksi: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` baru, kredensial DB non-root, `MAIL_*`.
3. `APP_URL=https://<domain>` — penentu host QR/WhatsApp/PDF tracking.
4. Trusted proxies di `bootstrap/app.php` (`$middleware->trustProxies(at: '*')`) — kritikal agar rate limit tracking bekerja di belakang proxy.
5. Build aset: `npm ci && npm run build`.
6. Migrasi data: `php artisan migrate --force` + `storage:link` bila perlu.
7. Optimasi: `php artisan optimize`.
8. Cron scheduler: `* * * * * php /path/proyek/artisan schedule:run`.
9. Verifikasi pasca-deploy: scan QR, `/tracking?token=…` dari HP & PC beda jaringan, percobaan ke-21 → `429`, login 2FA, unduh kartu lacak PDF.
10. Cetak ulang tanda terima & kirim ulang link WhatsApp lama (QR lama masih `localhost`) — opsional.

## 4. Konvensi wajib (baca sebelum menulis kode)

- Baca `.ai/rules/index.md`, lalu rule yang glob-nya menyentuh path kerja.
- Gunakan `php artisan make:*`; Pest untuk test; setiap perubahan WAJIB ada test dan diverifikasi.
- Frontend: Inertia v3, Wayfinder (`@/actions`, `@/routes`) — jangan hardcode URL; shadcn/ui; warna lewat token CSS (`resources/css/app.css`).
- Backend: PHP 8.4 + constructor property promotion, type hints, Eloquent attributes (`#[Fillable]`).
- Setelah ubah PHP: `vendor/bin/pint --format agent` (atau `vendor/bin/pint --dirty`). Setelah ubah TS/TSX: `npm run format` & `npm run lint:check`.
- Cek versi paket sebelum pakai API baru: `composer show --direct` / `package.json`.

## 5. Verifikasi kualitas

```bash
php artisan test --compact            # seluruh test (sesi terakhir: 235 total = 234 lulus + 1 skip)
./vendor/bin/pest tests/Browser       # hanya test browser Playwright
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
vendor/bin/pint --dirty --format agent
npm run types:check
npm run lint:check
npx prettier --check resources/       # atau `npm run format` untuk auto-fix
npm run build
```

> Catatan: PHPStan/Pest hanya benar-benar memuat bootstrap saat dijalankan sebagai satu project — jalankan penuh seperti di atas, bukan per-file.

## 6. Data & akun

- DB dev MySQL `sercomp`. Reset data dummy: `php artisan migrate:fresh --seed`.
- Akun demo: `admin@sercomp.test` / `password`.
- `APP_URL` di `.env` menentukan host QR/WhatsApp/PDF tracking.
