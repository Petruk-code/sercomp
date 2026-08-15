# Status Proyek & Roadmap

> Dokumen hidup — perbarui setiap kali satu tahap selesai. Terakhir diperbarui: **2026-08-15**.

Sesi lanjutan (**2026-08-15**): **rekomendasi 1.2 — sistem notifikasi dikencangkan** — bell & popup kini murni client-driven: shared prop `notificationSummary` **dihapus** dari `HandleInertiaRequests` (page load tidak lagi query tabel notifikasi); endpoint ringan baru `GET /notifications/summary` (JSON `{unread_count, recent}`) dipakai `NotificationsProvider` (`use-notifications.tsx`, di `AppShell`) yang polling 30 dtk + saat tab fokus, menggantikan `usePoll` yang reload halaman. Aksi JSON (read/read-all/destroy) mengembalikan summary terbaru → klik item popover / "Lihat" toast kini **langsung menandai dibaca** (optimistic) lalu navigasi; **hapus notifikasi** per item (`DELETE notifications/{notification}`, popover + halaman `/notifications`) + **auto-cleanup** command baru `notifications:prune` (default >90 hari) terjadwal daily; membuka popover **tidak** menandai dibaca (notifikasi lama tetap tampil, badge menyusut saat benar-benar dibaca). Test baru: summary JSON, read JSON, destroy (termasuk 403 milik orang lain), prune (default + custom window); total **Pest 197 (196 lulus + 1 skip)**.

Sesi lanjutan (**2026-08-15**): **bug-fix hasil review** — 3 bug nyata diperbaiki: (1) **tabrakan prop `notifications`** — prop page `/notifications` (daftar paginated) menimpa shared prop bernama sama, membuat badge lonceng + toast + "Tandai semua" mati di halaman itu; shared prop di-rename ke **`notificationSummary`** di `HandleInertiaRequests` (dan 3 konsumen: `NotificationsPopover`, `NotificationAlerts`, `notifications/index.tsx`); (2) **toast pertama "tertelan"** — guard `seenIds.size === 0` di `NotificationAlerts` ikut me-seed notifikasi pertama yang tiba saat daftar kosong; diganti ref `initialized` terpisah sehingga notifikasi pertama benar-benar muncul; (3) **qty POS tak ter-clamp atas** — tombol +/- di `pos.tsx` kini `Math.min(Math.max(qty,1), product.stock)`. Yang diverifikasi **bukan bug**: null-coalescing `$service->customer->name ?? …` aman (BUKAN case break; `customer_id` nullable + `on_delete set null`). Rekomendasi pengembangan lengkap: [`docs/05-rekomendasi-pengembangan.md`](05-rekomendasi-pengembangan.md).

Sesi lanjutan (**2026-08-15**): **notifikasi murni in-app (tanpa email/WhatsApp)** — ketiga alert (stok menipis, antrian servis, servis siap diambil) kini `via()` = `['database']` saja: channel **mail dihapus** (bukan hanya WhatsApp). Lonceng **kini terpasang di header layout aktif** (`AppSidebarHeader`; sebelumnya hanya ada di `AppHeader` yang tidak dipakai halaman mana pun). Popup toast notifikasi baru via sonner muncul otomatis: komponen `NotificationAlerts` di `AppShell` melakukan polling `usePoll(30 dtk, only: ['notificationSummary'])` lalu memunculkan toast untuk notifikasi yang belum pernah terlihat (baseline saat mount, jadi unread lama tidak spam saat login). Pesan alert diperkaya: stok menipis memuat nama produk, antrian memuat nomor invoice. Test: `SendAlertsTest` (alert database-only tanpa mail, pesan memuat nama produk) + `NotificationsTest` (pickup alert tanpa mail).

Sesi lanjutan (**2026-08-15**): **WhatsApp 2FA + OTP** selesai — opsi 2FA kedua di samping TOTP: kode OTP 6 digit dikirim ke nomor WhatsApp staf (`users.phone`) via Fonnte. Migrasi `users.whatsapp_2fa_enabled` + tabel `whatsapp_otps` (kode di-hash, kedaluwarsa 5 menit, maks 3 percobaan). Login pengguna yang mengaktifkannya diarahkan ke halaman challenge `/two-factor/whatsapp` (`RedirectIfTwoFactorAuthenticatable` override Fortify); enable/disable lewat `Pengaturan → Keamanan`. **Pengiriman nyata hanya saat `APP_ENV=production`** — di lokal kode hanya di-log (cegah pembatasan akun WhatsApp saat pengujian). 13 test baru (`tests/Feature/Auth/WhatsAppTwoFactorTest.php`). Panduan lengkap: [`docs/04-whatsapp-2fa-otp.md`](04-whatsapp-2fa-otp.md).

Sesi lanjutan (**2026-08-15**): **autentikasi dua faktor (2FA)** selesai — `Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true])` aktif di `config/fortify.php`; UI setup (QR via OTP Input, recovery codes, regenerate) di `settings/security`; halaman challenge `auth/two-factor-challenge` saat login; confirm-password sebelum mengelola 2FA; rate limit login 2FA; 9 test baru (`tests/Feature/Auth/TwoFactorAuthenticationTest.php`).

Sesi lanjutan (**2026-08-15**): **kartu lacak servis unduhan (PDF)** selesai — kartu ukuran kartu kredit (86×54mm, landscape) berisi QR + token tracking + status, bisa diunduh dari halaman tracking publik (`/tracking/card?token=…`, rate limit 30/menit/IP) maupun dari halaman detail servis; teks QR memakai token, bukan nomor servis.

Sesi lanjutan (**2026-08-15**): **notifikasi di aplikasi** selesai — bel notifikasi di header (badge jumlah belum dibaca + popover 5 terbaru) dan halaman daftar `/notifications` (paginasi + tandai dibaca/semua). Stok menipis & antrian servis dikirim terjadwal (`app:send-alerts`, 1×/jam) dan **servis siap diambil** dikirim seketika saat status berubah jadi `done` (notifikasi baru `ServiceReadyForPickupAlert` via observer). Semua notifikasi kini menyertakan `action_url` untuk navigasi cepat. Test: `tests/Feature/NotificationsTest.php` (7 test). Perbaikan: notifikasi **tidak di-queue** (`QUEUE_CONNECTION=database` tanpa worker membuat notifikasi tak pernah masuk tabel — sinkron lebih andal untuk skala ini) dan link "Lihat semua" stok di dashboard diperbaiki ke route benar (`/products`, bukan `/master/products`).

Sesi lanjutan (**2026-08-15**): notifikasi WhatsApp via Fonnte sempat dibuat (channel `FonnteChannel` + contract `SendsWhatsapp` + `users.phone`) namun **dihapus karena anti-spam** — kiriman otomatis berulang memicu pembatasan nomor WhatsApp. Alert (stok menipis, antrian servis, servis siap diambil) kembali murni in-app (database saja); WhatsApp hanya untuk aksi manual pengguna: tombol "Kirim WhatsApp" (link tracking) & OTP 2FA login. Kolom `phone` (migration `2026_08_15_035830`) tetap dipakai untuk kiriman manual & OTP. Regresi dijaga `Http::assertNothingSent()` di `SendAlertsTest` + `CustomerWhatsappNotificationTest`.

Sesi lanjutan (**2026-08-15**): **notifikasi WhatsApp ke pelanggan (link tracking)** — **manual saja**. Auto-kirim saat servis berubah `done` **dihapus** (anti-spam, lihat sesi di atas); link tracking hanya dikirim lewat tombol "Kirim WhatsApp" di detail servis (`POST services/{service}/whatsapp`, admin/owner/cashier) via API Fonnte ke `customers.phone`, menggantikan link wa.me share. Helper `WhatsApp::trackingUrl()` + `WhatsApp::trackingMessageFor()`. Catatan: **device Fonnte (087843581528) sempat disconnect** — kirim uji ditolak ("request invalid on disconnected device"); kode mendeteksi & mencatat kegagalan, cukup sambungkan ulang device di dashboard Fonnte. Test: `tests/Feature/CustomerWhatsappNotificationTest.php` (4 test, termasuk regresi "tidak auto-kirim saat `done`").

## Status saat ini

Semua fitur inti **selesai** dan seluruh verifikasi kualitas **hijau**: Pest, PHPStan, Pint, ESLint, Prettier, `tsc`, dan `npm run build`.

Terakhir dikerjakan (**2026-08-15**): **rekomendasi 1.2 — sistem notifikasi dikencangkan** (endpoint JSON `notifications/summary` menggantikan shared prop + `usePoll`, mark-as-read saat klik, hapus notifikasi, `notifications:prune` daily); semua hijau (Pest 197/196 lulus + 1 skipped, PHPStan 0, Pint, ESLint, Prettier, `tsc`, build). Fitur inti lengkap; arah pengembangan berikutnya di [`docs/05-rekomendasi-pengembangan.md`](05-rekomendasi-pengembangan.md).

Sesi lanjutan (**2026-08-14**): **branding & hardening** — tracking kini memakai token unik per servis (bukan nomor servis) untuk QR/WhatsApp; tema Indigo/Violet via design token di `app.css`; landing page & halaman tracking di-redesign; PDF nota & tanda terima diberi aksen brand (tanda terima kini 1 halaman A5); checklist deploy tracking di `docs/03-operasional.md`. Briefing lanjutan: [`handoff-session.md`](handoff-session.md).

Perbaikan bug terakhir (2026-08-12):

- [x] **Fix kritis**: membuat servis tanpa status sebelumnya memicu `Integrity constraint violation: status cannot be null`. Kini status baru otomatis di-set `pending` (`ServiceOrderObserver`).
- [x] **Fix hydration SSR**: tombol tema di halaman landing tidak lagi memicu *React hydration mismatch*.
- [x] **Code cleanup**: hapus method mati `generateInvoiceNumber()` di `SaleController` (error PHPStan).

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
- [x] Laporan penjualan, servis, stok
- [x] ~~Notifikasi e-mail untuk stok menipis & antrian servis (butuh konfigurasi `MAIL_*`)~~ — **DIHAPUS**: alert kini murni in-app
- [x] Notifikasi di aplikasi: bel + badge + popover di header layout aktif (`AppSidebarHeader`), daftar `/notifications` dengan tandai dibaca; **popup toast** (sonner) otomatis untuk notifikasi baru via polling `usePoll` 30 dtk; alert stok/antrian (jam-jaman) + alert servis siap diambil (seketika saat status `done`) — semua `via() = ['database']`
- [x] ~~Notifikasi WhatsApp via Fonnte: stok menipis, antrian servis, dan servis siap diambil terkirim ke nomor staff (`users.phone`)~~ — **DIBATALKAN** (anti-spam WhatsApp): alert murni in-app (database saja); WhatsApp hanya untuk kiriman manual & OTP 2FA
- [x] Backup database otomatis (scheduled command)
- [x] Manajemen garansi servis & tindak lanjut pasca-servis
- [x] Kirim link tracking ke pelanggan via WhatsApp: **manual** lewat tombol di detail servis (admin/owner/cashier), via API Fonnte ke `customers.phone` — auto-kirim saat `done` dihapus (anti-spam)
- [x] PWA, tampilan kasir ramah tablet, rate limiting aksi sensitif
- [x] Manajemen supplier & pembelian: CRUD pembelian, pembayaran tempo/cicilan, daftar hutang jatuh tempo, retur ke supplier (ganti/refund/potong hutang)
- [x] Laporan pembelian per periode/supplier + mutasi hutang per supplier (aging: belum jatuh tempo, 0–30, 31–60, >60 hari)
- [x] Autentikasi dua faktor (TOTP via Google Authenticator + recovery codes), challenge saat login, konfirmasi password sebelum mengelola 2FA
- [x] WhatsApp 2FA: kode OTP 6 digit via Fonnte ke `users.phone`, challenge login, enable/disable di `Pengaturan → Keamanan`, kode di-hash + kedaluwarsa + batas percobaan; pengiriman nyata hanya di produksi
- [x] Kartu lacak servis unduhan (PDF): QR + token tracking ukuran kartu kredit, dari halaman tracking publik & halaman servis

## Todo pengembang (belum dikerjakan)

Prioritas tinggi:

- [x] ~~Notifikasi WhatsApp untuk stok menipis & antrian servis (via Fonnte, terkirim ke staff yang punya nomor di `users.phone`)~~ — **DIBATALKAN** (anti-spam), lihat catatan di atas
- [ ] Audit log untuk aksi sensitif
- [ ] Import/export data master & laporan (Excel/CSV)
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

- Projek **bukan git repo** — tidak ada safety net version control. Hati-hati dengan perubahan destruktif.
- DB dev memakai MySQL (`sercomp`). Reset data dummy: `php artisan migrate:fresh --seed`.
- Akun demo: `admin@sercomp.test` / `password`.
- Konvensi & pola wajib dijelaskan di [`02-arsitektur-konvensi.md`](02-arsitektur-konvensi.md).
