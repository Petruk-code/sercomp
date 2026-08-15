# Status Proyek & Roadmap

> Dokumen hidup — perbarui setiap kali satu tahap selesai. Terakhir diperbarui: **2026-08-15**.

## Status saat ini

Semua fitur inti **selesai** dan seluruh verifikasi kualitas **hijau**: Pest (215: 214 lulus + 1 skipped PwaTest), PHPStan 0 error, Pint, ESLint, Prettier, `tsc --noEmit`, dan `npm run build`. Arah pengembangan berikutnya di [`docs/05-rekomendasi-pengembangan.md`](05-rekomendasi-pengembangan.md); briefing kerja di [`docs/handoff-session.md`](handoff-session.md).

Terakhir dikerjakan (**2026-08-15**): **rekomendasi 1.4 — import/export data master & laporan (CSV)**. Streaming CSV native (`app/Support/CsvStream.php` export + `app/Support/CsvImporter.php` import) — BOM UTF-8, anti formula-injection, header dinormalisasi ke snake_case. Export produk/pelanggan/supplier (`{resource}/export-csv`) + laporan penjualan/servis/hutang, import master via `ImportCsvDialog` (`{resource}/import-csv`). Retur pembelian & PO sengaja belum di-export (belum punya halaman laporan). `ImportExportTest` (9 test); aturan di `.ai/rules/support-support.md`.

## Riwayat sesi (2026-08-15)

| Sesi | Yang dikerjakan |
| --- | --- |
| 1.4 | Import/export CSV master + laporan (detail di atas). |
| 1.3 | **Audit log aksi sensitif** — tabel + model `AuditLog`, helper `AuditLogger`/`StockManager` (event di sale/purchase/retur/servis/stok/2FA), halaman `/audit-logs` (filter + diff). `AuditLogTest` (9 test). Aturan: `.ai/rules/controllers.md` & `.ai/rules/support.md`. |
| 1.2 | **Sistem notifikasi dikencangkan** — endpoint JSON `GET /notifications/summary` menggantikan shared prop (`notificationSummary` dihapus dari `HandleInertiaRequests`); `NotificationsProvider` polling 30 dtk + saat tab fokus (tanpa reload halaman); mark-as-read saat klik item popover/toast; hapus per item (`DELETE notifications/{notification}`, ownership 403); `notifications:prune` (default >90 hari) terjadwal daily. |
| Bug-fix | Review: prop `notifications` → `notificationSummary` (tabrakan di halaman `/notifications`), toast pertama "tertelan" (guard `seenIds.size === 0` → ref `initialized`), qty POS di-clamp atas `Math.min(Math.max(qty,1), product.stock)`. |
| Notifikasi | Alert murni **in-app** (database saja; email & WhatsApp otomatis dihapus — anti-spam); lonceng + badge di `AppSidebarHeader`; popup toast sonner (`NotificationAlerts`); alert servis siap diambil seketika saat status `done`; pesan diperkaya (nama produk / nomor invoice). |
| WhatsApp 2FA | OTP 6 digit via Fonnte ke `users.phone`; challenge login sebelum TOTP; hash bcrypt + kedaluwarsa 5 menit + maks 3 percobaan; kirim nyata hanya saat `APP_ENV=production`; recovery lockout via tinker. Panduan: `docs/04-whatsapp-2fa-otp.md`. |
| 2FA TOTP | Google Authenticator + recovery codes, challenge login, confirm-password, rate limit. |
| Kartu lacak PDF | `GET /tracking/card?token=…` (30/menit/IP), kartu kredit 86×54mm, QR memakai token unik. |
| Notif WhatsApp pelanggan | **Manual saja** — tombol "Kirim WhatsApp" di detail servis (admin/owner/casher); auto-kirim saat `done` dihapus (anti-spam). |
| 2026-08-14 | Branding & hardening — token tracking unik per servis, tema Indigo/Violet (design token), redesign landing & tracking, PDF nota/tanda terima ber-brand. |

## Roadmap (Fase)

| Fase | Isi | Status |
| --- | --- | --- |
| Fase 1 | Fondasi & manajemen akses (database, autentikasi, RBAC spatie: admin/kasir/teknisi/pemilik) | ✅ Selesai |
| Fase 2 | Master data (kategori, produk+stok+barcode, katalog jasa, pelanggan, supplier) | ✅ Selesai |
| Fase 3 | Modul inti servis (alur status, kelengkapan barang masuk, penugasan teknisi, sparepart) | ✅ Selesai |
| Fase 4 | Kasir/POS & transaksi gabungan (jual retail, checkout servis, cetak nota dompdf) | ✅ Selesai |
| Fase 5 | Laporan & cetak barcode | ✅ Selesai |
| Fase 6 | Manajemen supplier & pembelian (PO, tempo/cicilan, hutang, retur) | ✅ Selesai |
| Fase 7 | Laporan pembelian & mutasi hutang per supplier (aging) | ✅ Selesai |

## Fitur yang sudah selesai

- [x] Fondasi aplikasi, autentikasi & pengaturan akun (Fortify)
- [x] RBAC (admin, owner, kasir, teknisi)
- [x] Master data: kategori, produk, stok, pelanggan, supplier, katalog jasa
- [x] Modul servis: alur status, penugasan teknisi, sparepart, cetak tanda terima
- [x] Riwayat status servis + halaman tracking publik + QR di nota
- [x] Dashboard teknisi (servis masuk & beban kerja personal)
- [x] Dashboard admin: grafik tren penjualan/servis, panel notifikasi stok & antrian servis
- [x] Kasir/POS, checkout servis, cetak struk & barcode produk
- [x] Laporan penjualan, servis, stok, pembelian & hutang (aging per supplier)
- [x] Notifikasi di aplikasi: bel + badge + popover, daftar `/notifications`, popup toast (sonner); alert stok/antrian (jam-jaman) + servis siap diambil (seketika) — semua `via() = ['database']`
- [x] ~~Notifikasi WhatsApp/e-mail otomatis untuk stok & antrian~~ — **DIHAPUS/DIBATALKAN** (anti-spam WhatsApp): alert murni in-app
- [x] Backup database otomatis (scheduled command)
- [x] Manajemen garansi servis & tindak lanjut pasca-servis
- [x] Kirim link tracking ke pelanggan via WhatsApp: **manual** lewat tombol di detail servis (admin/owner/casher) via API Fonnte — auto-kirim saat `done` dihapus (anti-spam)
- [x] PWA, tampilan kasir ramah tablet, rate limiting aksi sensitif
- [x] Manajemen supplier & pembelian: CRUD pembelian, pembayaran tempo/cicilan, daftar hutang jatuh tempo, retur ke supplier (ganti/refund/potong hutang)
- [x] Autentikasi dua faktor: TOTP (Google Authenticator + recovery codes) **dan** WhatsApp OTP via Fonnte (hash + kedaluwarsa + batas percobaan; kirim nyata hanya di produksi)
- [x] Kartu lacak servis unduhan (PDF): QR + token tracking ukuran kartu kredit, dari halaman tracking publik & halaman servis
- [x] Audit log aksi sensitif: pembayaran, retur, ubah status servis, ubah stok, login 2FA — halaman `/audit-logs` (filter + diff)
- [x] Import/export CSV: master (produk/pelanggan/supplier) + laporan (penjualan/servis/hutang); streaming native, BOM UTF-8, anti formula-injection

## Todo pengembang (belum dikerjakan)

Prioritas tinggi:

- [x] ~~Audit log untuk aksi sensitif~~ — **SELESAI (2026-08-15)**, rekomendasi 1.3.
- [x] ~~Import/export data master & laporan (Excel/CSV)~~ — **SELESAI (2026-08-15)**, rekomendasi 1.4 (CSV; Excel/XLSX belum — CSV cukup untuk skala ini).
- [ ] Browser/E2E testing (Playwright) sebagai pelengkap test Pest
- [ ] Dukungan multi-cabang / multi-toko (tenant)

Ide berikutnya (belum diprioritaskan):

- [ ] Deploy ke hosting HTTPS + domain (runutan eksekusi final di bagian "Persiapan Produksi" di bawah)
- [ ] Push notification web untuk notifikasi servis/stok di HP yang ter-install
- [ ] Mode offline penuh / antrean sinkronisasi untuk kasir (PWA tingkat lanjut)
- [ ] Cetak struk langsung dari tablet ke printer thermal/Bluetooth

## Persiapan Produksi — Runutan Terakhir

Masih tahap pengembangan; berikut **urutan eksekusi final** saat siap live (detail & troubleshooting di [`03-operasional.md`](03-operasional.md)):

1. Siapkan hosting + domain + SSL (Laravel Cloud / Forge / VPS + nginx).
2. Set `.env` produksi: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` baru, kredensial DB non-root, `MAIL_*`.
3. Set `APP_URL=https://<domain>` — penentu host QR/WhatsApp/PDF tracking.
4. Tambah trusted proxies di `bootstrap/app.php` (`$middleware->trustProxies(at: '*')`) — kritikal agar IP pengunjung benar & rate limit tracking (20/menit/IP) bekerja di belakang proxy.
5. Build aset: `npm ci && npm run build` (menghasilkan `public/build` + PWA `manifest.webmanifest`/`sw.js`).
6. Migrasi data: `php artisan migrate --force` (jangan `migrate:fresh` untuk data produksi) + `php artisan storage:link` bila perlu.
7. Optimasi: `php artisan optimize` (config/route/view cache).
8. Pasang cron scheduler: `* * * * * php /path/proyek/artisan schedule:run`.
9. Verifikasi pasca-deploy: scan QR dari nota, buka `/tracking?token=…` dari HP & PC jaringan berbeda, percobaan ke-21 per IP → `429` (rate limit bekerja), login 2FA, unduh kartu lacak PDF.
10. Cetak ulang tanda terima & kirim ulang link WhatsApp lama (QR lama masih menunjuk `localhost`) — opsional.

## Catatan pengembang

- Projek adalah **git repo** — `main` berisi baseline commit; kerja berjalan di branch fitur (mis. `feature/import-export`). Komit per fitur yang kohesif; hati-hati dengan perubahan destruktif.
- DB dev memakai MySQL (`sercomp`). Reset data dummy: `php artisan migrate:fresh --seed`.
- Akun demo: `admin@sercomp.test` / `password`.
- Konvensi & pola wajib dijelaskan di [`02-arsitektur-konvensi.md`](02-arsitektur-konvensi.md).
