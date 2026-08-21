# Rekomendasi Pengembangan SerComp

> Status: **fitur inti selesai**, verifikasi kualitas hijau (Pest 223 / 222 lulus + 1 skip — termasuk 8 test browser Playwright, PHPStan 0 error, Pint, ESLint, Prettier, `tsc`, `npm run build`). Dokumen ini = peta prioritas pengembangan berikutnya, dari yang paling mendesak ke yang opsional.

## 0. Prasyarat teknis yang disarankan sebelum semua itu

- ~~**Masuk ke version control.** Proyek **bukan git repo** — tidak ada safety net. Mulai `git init` + commit baseline + `.gitignore` (`vendor/`, `node_modules/`, `.env`, `storage/logs/*`, `public/build/`) sebelum menyentuh fitur besar. Ini pencegahan paling berdampak.~~ — **SELESAI (2026-08-15)**: `git init` + baseline commit; `.gitignore` diperkuat (`database/*.sqlite*`, `storage/app/backups`, `public/sw.js`, `public/workbox-*.js`, `tsconfig.tsbuildinfo`). Wayfinder actions/routes (`resources/js/{actions,routes,wayfinder}`) sengaja di-ignore — regenerasi via `php artisan wayfinder:generate --with-form`.
- **Jangan migrasi `migrate:fresh` di data riil** — kalau sudah ada data produksi, pakai `php artisan migrate --force`.

## 1. Prioritas tinggi — jangka pendek

### 1.1 Deploy ke produksi (runutan final)
Belum pernah live. Urutan eksekusi lengkap ada di `docs/03-operasional.md` & `docs/01-status-roadmap.md`:
- Hosting + domain + SSL (Laravel Cloud / Forge / VPS + nginx), `.env` produksi aman (`APP_DEBUG=false`, `APP_KEY` baru, DB non-root).
- **`APP_URL` = domain final** — menentukan host QR / link tracking / PDF.
- Trusted proxies (`trustProxies(at: '*')`) agar rate limit tracking (20/menit/IP) benar di balik proxy.
- Cron scheduler (`schedule:run`), `optimize`, build aset, verifikasi pasca-deploy.

### 1.2 Kencangkan sistem notifikasi (fondasi sudah ada) — SELESAI (2026-08-15)
- ~~**Mark as read saat klik** toast/popover item~~ — klik item popover / "Lihat" toast kini langsung menandai dibaca (optimistic) lalu navigasi ke `action_url`.
- ~~**Hapus notifikasi + auto-cleanup > 90 hari**~~ — tombol hapus per item (popover + halaman `/notifications`, `DELETE notifications/{notification}`) + command `notifications:prune` (`--days=90`) terjadwal daily.
- ~~**Endpoint polling ringan**~~ — `GET /notifications/summary` (JSON `{unread_count, recent}`) diganti `usePoll` yang reload halaman; shared prop `notificationSummary` **dihapus** dari `HandleInertiaRequests` sehingga page load tidak lagi query tabel notifikasi. `NotificationsProvider` (AppShell) polling 30 dtk + saat tab fokus.
- ~~**Pisahkan "dibaca di popover" vs "dibaca saat navigasi"**~~ — membuka popover TIDAK menandai dibaca; hanya klik item / "Tandai semua" yang mengecilkan badge, notifikasi lama tetap tampil di daftar.

### 1.3 Audit log aksi sensitif
Sudah lama ada di roadmap. Catat siapa mengubah apa pada: pembayaran, retur, ubah status servis, ubah stok, login 2FA. Minimal: tabel `audit_logs` + middleware/event `Observer` di model finansial. Pintu masuk wajib sebelum "percaya" pada produksi. — **SELESAI (2026-08-15)**: tabel + model `AuditLog`, helper `AuditLogger`/`StockManager`, event di sale/purchase/retur/servis/stok/2FA, halaman `/audit-logs` (filter + diff), `AuditLogTest` (9 test). Aturan terkait di `.ai/rules/controllers.md` & `.ai/rules/support.md`.

### 1.4 Import/export data master & laporan (Excel/CSV)
Produk, pelanggan, supplier, laporan penjualan/servis/hutang. Pakai `maatwebsite/excel` atau streaming CSV native (hindari beban memori di data besar). — **SELESAI (2026-08-15)**: streaming CSV native (`app/Support/CsvStream.php` export + `app/Support/CsvImporter.php` import) — hindari beban memori, BOM UTF-8, anti formula-injection, header dinormalisasi ke snake_case. Export master produk/pelanggan/supplier (`{resource}/export-csv`), laporan penjualan/servis/hutang, dan import master produk/pelanggan/supplier (`{resource}/import-csv`) dari tombol toolbar di halaman index masing-masing. Komponen `ImportCsvDialog`, `ImportExportTest` (9 test). Retur pembelian & PO sengaja belum di-export (belum punya halaman laporan); daftar lengkap di `.ai/rules/support-support.md`.

## 2. Prioritas menengah

### 2.1 E2E / Browser testing (Playwright)
Lapisan test saat ini 100% backend (Pest). Alur lintas halaman yang kritis (POS → checkout → nota, servis → status → tracking → WhatsApp) belum diuji di browser. Tambah smoke test semua route autentikasi (cek error JS + 5xx). — **SELESAI (2026-08-21)**: `pestphp/pest-plugin-browser` + Playwright Chromium; suite `tests/Browser` (8 test / 100 assertion): smoke 31 route autentikasi lewat login form asli (`assertNoSmoke` — bebas error JS & console), alur POS → checkout → nota (invoice, Lunas, stok berkurang), dan servis → status → tracking publik (+ state tidak ditemukan). Helper `loginBrowserAdmin()` di `tests/Pest.php`. Trap `public/hot` basi tercatat di `.ai/rules/browser.md`.

### 2.2 PWA offline / antrean sinkronisasi POS
PWA sudah bisa di-install. Berikutnya: simpan keranjang/transaksi offline lalu sinkron saat online (mode kasir yang putus jaringan), karena koneksi toko sering tidak stabil. — **SELESAI (2026-08-21)**: idempotensi server via `sales.client_reference` unik (+ header `X-Offline-Sync` → respons JSON untuk sync fetch); antrean & draf keranjang di localStorage (`resources/js/lib/offline-sales.ts`); checkout POS offline-aware (`onNetworkError` → enqueue, auto-sync saat mount/event `online`); dialog antrean dengan status pending/failed + retry/buang; badge Offline di halaman POS. Test: `PosOfflineSyncTest` (4 feature) + `PosOfflineQueueTest` (3 browser).

### 2.3 Cetak struk langsung dari tablet ke printer thermal/Bluetooth
Integrasi Web Bluetooth/Web Serial atau protokol ESC/POS. Butuh riset kompatibilitas perangkat; jadikan iterasi terpisah. — **SELESAI (2026-08-21)**: encoder ESC/POS murni TS (`lib/escpos.ts`, 58mm=32 / 80mm=48 kolom); builder nota & tanda terima (`lib/thermal-receipt.ts`); transport Web Bluetooth (service 18F0) + Web Serial 9600 8N1 dengan auto-reconnect perangkat terizin & preferensi localStorage (`lib/thermal-printer.ts`); dialog pilih lebar kertas; tombol "Cetak Thermal" di detail penjualan & servis; fallback PDF tetap ada. Test: `ThermalPrintTest` (2 browser). Verifikasi dengan printer fisik menyusul.

## 3. Prioritas rendah / ide lanjutan

- ~~**Realtime dengan WebSocket (Laravel Reverb)** menggantikan polling 30 dtk~~ — **SELESAI (2026-08-21)**: push instan via Reverb (port 8081), polling 30 dtk hanya fallback; detail di handoff B2 & `.ai/rules/hooks.md`.
- ~~**Multi-cabang / multi-toko (tenant)**~~ — **SELESAI (2026-08-21)**: skema sederhana `store_id` satu database (Opsi A); konteks cabang `StoreContext`, trait `BelongsToStore`, pindah cabang + CRUD cabang; panduan di `docs/06-multi-cabang-email-bulanan.md`.
- **Push notification web** untuk servis siap diambil / stok menipis di HP staf (Web Push API / Laravel Notifications + VAPID). Perlu consent & izin khusus; cocok dilanjutkan dari fitur notifikasi in-app — realtime in-app sudah jalan via Reverb.
- ~~**Ringkasan laporan otomatis (mingguan/bulanan)** via email~~ — **SELESAI (2026-08-21)**: bulanan ke owner tanggal 1 jam 07:00 (`app:send-monthly-report`); panduan di `docs/06-multi-cabang-email-bulanan.md`.

## 4. Catatan teknis yang memengaruhi keputusan

- **Queue worker tidak aktif** (`QUEUE_CONNECTION=database` tanpa worker). Jangan tambahkan `ShouldQueue` tanpa menghidupkan worker/supervisor di produksi — notifikasi/email akan antri selamanya.
- **WhatsApp anti-spam**: kiriman otomatis (jadwal/observer) **dilarang** (pembatasan nomor). Fitur WhatsApp berikutnya harus dipicu aksi eksplisit pengguna (lihat `.ai/rules/notifications.md`).
- **Konvensi wajib** sebelum mulai modul baru: baca `.ai/rules/index.md` dan file rule yang match path; `make:*`, Pest, Wayfinder, Pint, prettier/eslint/tsc.
- Proyek memakai **Inertia v3 + React 19 + Tailwind v4 + shadcn/ui** — pakai API versi yang sesuai, jangan asumsi versi.

## 5. Rekomendasi urutan kerja

1. ~~Git init + baseline commit (0.5–1 hari).~~ — **SELESAI (2026-08-15)**.
2. ~~Audit log aksi sensitif (1–2 hari).~~ — **SELESAI (2026-08-15)**.
3. ~~Penguatan notifikasi: endpoint polling ringan + mark-read saat klik + cleanup~~ — **SELESAI (2026-08-15)**.
4. ~~Import/export CSV/Excel (1–2 hari).~~ — **SELESAI (2026-08-15)**: streaming CSV native untuk master (produk/pelanggan/supplier) + laporan (penjualan/servis/hutang); import master via dialog.
5. ~~E2E Playwright smoke (1–2 hari).~~ — **SELESAI (2026-08-21)**: suite `tests/Browser` (8 test) — smoke 31 route autentikasi + alur POS→nota & servis→tracking.
6. ~~PWA offline / antrean sinkronisasi POS (2.2)~~ — **SELESAI (2026-08-21)**: idempotensi `client_reference` + antrean localStorage + auto-sync + dialog antrean.
7. ~~Cetak struk thermal (2.3)~~ — **SELESAI (2026-08-21)**: ESC/POS via Web Bluetooth/Web Serial dari browser; fallback PDF tetap ada.
8. ~~Realtime Reverb (2.4), multi-cabang (2.5), email bulanan (2.6)~~ — **SELESAI (2026-08-21)**: detail di `docs/06-multi-cabang-email-bulanan.md` & handoff B–B4.
9. **Deploy produksi — PALING AKHIR**: hanya setelah semua modul di atas selesai & verifikasi bebas error (checklist di `docs/03-operasional.md`).
