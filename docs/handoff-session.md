# Handoff Sesi — Briefing Kerja Lanjutan

> Dokumen ini adalah **briefing siap-pakai** untuk sesi pengembangan berikutnya. Baca dari awal sebelum mulai kerja, lalu eksekusi bagian "Siap dikerjakan berikutnya". Peta prioritas & estimasi jangka panjang: [`docs/05-rekomendasi-pengembangan.md`](05-rekomendasi-pengembangan.md).

## 1. Posisi proyek

SerComp — aplikasi manajemen servis komputer + kasir/POS (Laravel 13, Inertia v3 + React 19, Tailwind v4, shadcn/ui, MySQL). Fitur inti **selesai semua**: servis, tracking publik, POS, stok, supplier/pembelian/hutang, laporan, RBAC, PWA, 2FA (TOTP + WhatsApp OTP), kartu lacak PDF, audit log, import/export CSV.

**Git**: repo aktif. `feature/import-export` sudah **di-merge ke `main`** (fast-forward) dan branch fitur bisa dihapus. Kerja berikutnya mulai dari `main`.

Verifikasi terkini (2026-08-21): **223 test Pest (222 lulus + 1 skipped PwaTest — termasuk 8 test browser Playwright)**, PHPStan 0 error, Pint rapi. ESLint & Prettier bersih, `tsc --noEmit` bersih, `npm run build` sukses.

## 2. Ringkasan yang sudah selesai

| Rekomendasi | Status | Inti |
| --- | --- | --- |
| 2.1 E2E/browser testing (Playwright) | ✅ SELESAI (2026-08-21) | `pestphp/pest-plugin-browser` + Playwright Chromium; suite `tests/Browser` (8 test / 100 assertion): smoke 31 route autentikasi lewat login form asli (`assertNoSmoke`), alur POS → checkout → nota, servis → status → tracking publik (+ not-found). Helper `loginBrowserAdmin()` di `tests/Pest.php`. Trap `public/hot` basi → `.ai/rules/browser.md`. |
| 1.4 Import/export CSV | ✅ SELESAI | Streaming CSV native: `app/Support/CsvStream.php` (export, BOM UTF-8, anti formula-injection) + `CsvImporter.php` (import streaming). Export master (produk/pelanggan/supplier) + laporan (penjualan/servis/hutang); import master via `ImportCsvDialog`. `ImportExportTest` (9 test). Aturan: `.ai/rules/support-support.md`. |
| 1.3 Audit log aksi sensitif | ✅ SELESAI | Tabel + model `AuditLog`, helper `AuditLogger`/`StockManager`, event di sale/purchase/retur/servis/stok/2FA, halaman `/audit-logs` (filter + diff). `AuditLogTest` (9 test). |

Sesi-sesi sebelumnya hari ini (detail teknis di `docs/01-status-roadmap.md'):

- **Notifikasi dikencangkan (1.2)**: endpoint JSON `GET /notifications/summary` menggantikan shared prop (`notificationSummary` dihapus dari `HandleInertiaRequests`); `NotificationsProvider` polling 30 dtk + saat tab fokus; mark-as-read saat klik; hapus per item (`DELETE notifications/{notification}`); `notifications:prune` (default >90 hari) terjadwal daily.
- **Notifikasi murni in-app**: ketiga alert (`LowStockAlert`, `ServiceQueueAlert`, `ServiceReadyForPickupAlert`) `via() = ['database']` saja (email & WhatsApp otomatis dihapus — anti-spam); lonceng di `AppSidebarHeader`; popup toast sonner untuk notifikasi baru.
- **WhatsApp 2FA + OTP**: kode OTP 6 digit via Fonnte ke `users.phone`; challenge login sebelum TOTP; hash bcrypt + kedaluwarsa 5 menit + maks 3 percobaan; pengiriman nyata **hanya** saat `APP_ENV=production` (lokal hanya log). Panduan: `docs/04-whatsapp-2fa-otp.md`.
- **2FA TOTP**: Google Authenticator + recovery codes, challenge login, confirm-password, rate limit.
- **Kartu lacak servis (PDF)**: `GET /tracking/card?token=…` (30/menit/IP), kartu kredit 86×54mm, QR memakai token.
- **Notifikasi WhatsApp ke pelanggan — MANUAL saja**: tombol "Kirim WhatsApp" di detail servis (admin/owner/casher); auto-kirim saat `done` dihapus (anti-spam).
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
| 2.3 | Cetak struk thermal/Bluetooth | 🔜 **Berikutnya** |
| — | Realtime (Reverb), push web, multi-cabang, ringkasan otomatis | ⬜ Ide lanjutan |
| 1.1 | **Deploy ke produksi** | ⏳ **PALING AKHIR** — hanya setelah semua modul selesai & verifikasi bebas error |

### A. PWA offline / antrean sinkronisasi POS (2.2) — ✅ SELESAI
Implementasi yang sudah jalan:
- **Backend idempotensi**: kolom `sales.client_reference` (nullable unique) + pre-check & catch `UniqueConstraintViolationException` di `SaleController@store`; header `X-Offline-Sync` mengubah respons redirect menjadi JSON (`{status, invoice_number, redirect_url}`) agar sync via fetch bisa membedakan sukses/gagal. Test: `tests/Feature/PosOfflineSyncTest.php` (4 test).
- **Frontend antrean**: `resources/js/lib/offline-sales.ts` (antrean + draf keranjang di localStorage, `syncPendingSales()` via fetch berurutan), `resources/js/components/pos-offline-dialog.tsx` (dialog antrean: status pending/failed, retry, buang), integrasi `pos.tsx` (persist draf keranjang, checkout offline-aware dengan `client_reference` per attempt, `onNetworkError` → enqueue, auto-sync saat mount + event `online`, badge Offline + tombol Antrean).
- **Test browser**: `tests/Browser/PosOfflineQueueTest.php` (auto-sync sukses, gagal stok → status failed, restore draf keranjang).
- Catatan infrastruktur test: setiap `visit()` = context browser baru; login harus dilanjutkan dari page instance yang sama (lihat `.ai/rules/browser.md`).

### B. Setelah 2.2: cetak struk thermal (2.3) — BERIKUTNYA → realtime (Reverb), push notification web, multi-cabang, ringkasan laporan otomatis
Konvensi wajib sebelum mulai: baca `.ai/rules/index.md` + rule yang match path.

> Catatan test browser: jalankan `./vendor/bin/pest tests/Browser` — butuh aset build (`npm run build`) atau dev server hidup; jika semua halaman timeout + log "[vite] server connection lost", hapus `public/hot` yang basi (lihat `.ai/rules/browser.md`).

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
php artisan test --compact            # seluruh test (sesi terakhir: 223 total = 222 lulus + 1 skip)
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
