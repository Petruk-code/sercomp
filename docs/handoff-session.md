# Handoff Sesi — Status & Instruksi Lanjutan

> Dokumen ini adalah **prompt/briefing siap-pakai** untuk sesi pengembangan berikutnya. Baca dari awal sebelum mulai kerja, lalu eksekusi langkah di bagian "Siap dikerjakan berikutnya".

## 1. Posisi proyek

SerComp — aplikasi manajemen servis komputer + kasir/POS (Laravel 13, Inertia + React, Tailwind v4, shadcn/ui, MySQL). Fitur inti **selesai semua**: servis, tracking publik, POS, stok, supplier/pembelian/hutang, laporan, RBAC, PWA, 2FA, kartu lacak PDF.

Verifikasi terkini (sesi 2026-08-15): **197 test Pest lulus (196 passed + 1 skipped PwaTest)**, PHPStan 0 error, Pint rapi, ESLint & Prettier bersih, `tsc --noEmit` bersih, `npm run build` sukses. **Proyek bukan git repo** — tidak ada safety net version control, hati-hati dengan perubahan destruktif.

## 2. Yang dikerjakan sesi terakhir (2026-08-15)

1. **Autentikasi dua faktor (2FA) — TOTP + recovery codes**
   - `config/fortify.php`: `Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true])` + limiter `'two-factor'` (5/menit per `login.id`).
   - Migration `2026_08_15_015440_add_two_factor_columns_to_users_table` (`two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`); `User` memakai trait `TwoFactorAuthenticatable`.
   - `FortifyServiceProvider`: `twoFactorChallengeView` → `auth/two-factor-challenge`, `confirmPasswordView` → `auth/confirm-password`, `RateLimiter::for('two-factor')`.
   - `SecurityController::edit` props: `canManageTwoFactor` + (`twoFactorEnabled`, `requiresConfirmation` hanya saat fitur aktif — `SecurityTest` menguji ketiadaan keduanya).
   - Frontend: `hooks/use-two-factor-auth.ts` (`useHttp` → qrCode/secretKey/recoveryCodes), `two-factor-setup-modal.tsx`, `two-factor-recovery-codes.tsx`, `manage-two-factor.tsx`, `ui/input-otp.tsx`, halaman `auth/two-factor-challenge.tsx` & `auth/confirm-password.tsx`.
   - `UserFactory::withTwoFactor()`: secret base32 valid `JBSWY3DPEHPK3PXP`, `two_factor_confirmed_at = now()`, 8 recovery codes, di-`encrypt()`.
   - Test: `tests/Feature/Auth/TwoFactorAuthenticationTest.php` (9 test: enable, wajib confirm password, confirm valid/invalid, login TOTP/recovery, disable, render challenge).
2. **Kartu lacak servis unduhan (PDF)**
   - Route publik `GET /tracking/card?token=…` (`throttle:pdf-downloads`, 30/menit/IP) → `TrackingController::card()`. Hanya via `tracking_token`; selain itu `404`. Token tidak pernah ke klien — halaman memakai prop `cardUrl`.
   - View `pdf/tracking_card.blade.php`: kartu kredit 86×54mm. **Penting**: `setPaper([0, 0, 242.65, 153.07])` tanpa argumen orientation — dompdf menukar w/h untuk `'landscape'` dan menghasilkan halaman tegak (2 halaman). Layout table-only (dompdf tanpa flexbox), QR sebagai base64 data URI.
   - Tombol unduh di `tracking/show.tsx` & `services/show.tsx`.
   - Test: `tests/Feature/TrackingCardTest.php` (5 test: unduh valid + header filename, 404 token salah/kosong, isi kartu, rate limit per IP).
   - Wayfinder regenerated: `php artisan wayfinder:generate --with-form` (route `tracking.card`).
3. **Notifikasi di aplikasi (in-app notification center)**
   - Backend baru: `NotificationsController` (`index`, `markAsRead`, `markAllAsRead`) + route `auth` `notifications.index/read/read-all`; `Support/NotificationFormatter` (shape seragam: `id, title, message, action_url, read_at, created_at`).
   - `ServiceReadyForPickupAlert` (database saja) dikirim **seketika** dari `ServiceOrderObserver::updated` saat status berubah jadi `done` (ke admin & owner). `LowStockAlert` & `ServiceQueueAlert` diberi `action_url`; stok/antrian tetap dikirim terjadwal `app:send-alerts` (1×/jam).
   - Shared props di `HandleInertiaRequests`: `notificationSummary.unread_count` + `notificationSummary.recent` (5 terbaru) → bel + badge di `AppHeader` (`notifications-popover.tsx`); halaman `/notifications` (`pages/notifications/index.tsx`) dengan paginasi & tandai dibaca.
   - **Wayfinder**: route notifikasi pakai `.url`/`.url(args)` langsung pada fungsi (bukan `.()`), contoh `notificationsReadAll.url()` & `notificationsRead.url({ notification: id })` — ikuti pola `pos.tsx` (`SaleController.store.url()`).
   - Test: `tests/Feature/NotificationsTest.php` (7 test: notif admin/owner saat `done`, bukan untuk status lain, tersimpan in-app, index render, mark read, 403 notif milik orang lain, mark all).
4. **Notifikasi WhatsApp via Fonnte — DIBAHAS, lalu DIHAPUS (anti-spam)**
   - Awalnya dibuat: `FonnteChannel` (`app/Notifications/Channels`) + contract `SendsWhatsapp` (`toWhatsapp()`); via() ketiga alert (LowStockAlert, ServiceQueueAlert, ServiceReadyForPickupAlert) menambahkan `FonnteChannel::class` sehingga stok menipis/antrian/siap-ambil otomatis terkirim ke staff via WhatsApp.
   - **Keputusan anti-spam**: kiriman otomatis berulang memicu pembatasan nomor WhatsApp. Seluruh channel itu **dihapus** — `FonnteChannel`, contract `SendsWhatsapp`, `toWhatsapp()`, dan `tests/Feature/WhatsAppNotificationTest.php` sudah tidak ada. Alert terjadwal/observer kini murni database (in-app).
   - Yang **tetap dipakai**: `App\Support\WhatsApp::send()` (manual, user-initiated), `users.phone`, dan manual "Kirim WhatsApp" (lihat poin 5). WhatsApp hanya boleh dikirim saat ada aksi eksplisit pengguna — tidak pernah dari schedule/observer.
   - Regresi terjaga: `tests/Feature/SendAlertsTest.php` ("scheduled alerts never hit the whatsapp gateway to avoid spam") dan `CustomerWhatsappNotificationTest.php` ("marking a service done does not automatically send whatsapp to the customer") memakai `Http::assertNothingSent()`.
5. **Notifikasi WhatsApp ke pelanggan (link tracking) — MANUAL saja**
   - Auto-kirim saat servis berubah ke `done` **dihapus** (sebelumnya `ServiceOrderObserver` otomatis kirim link tracking ke `customers.phone`; sekarang tidak — anti-spam, lihat poin 4). Observer tidak menyentuh WhatsApp sama sekali.
   - Helper di `Support/WhatsApp`: `trackingUrl(ServiceOrder $order)` & `trackingMessageFor(ServiceOrder $order)`; controller `show()` memakai helper ini (prop `whatsappUrl` dihapus dari `services/show.tsx`).
   - Tombol "Kirim WhatsApp" di detail servis `POST services/{service}/whatsapp` (`ServiceOrderController::sendWhatsapp`, admin/owner/cashier) → kirim langsung via API, bukan link wa.me. Toast sukses/gagal; gagal saat no. pelanggan kosong atau gateway belum dikonfigurasi.
   - Test: `tests/Feature/CustomerWhatsappNotificationTest.php` (4 test: kirim manual + target ternormalisasi + header, skip tanpa phone, **tidak auto-kirim saat `done`**, 403 untuk teknisi).
6. **Sesi sebelumnya (2026-08-14)**: tracking token unik per servis, branding design token indigo/violet, redesign landing & tracking, PDF nota/tanda terima ber-brand (tanda terima 1 halaman A5), checklist tracking live di `docs/03-operasional.md`.
7. **WhatsApp 2FA + OTP (sesi ini)**
   - Opsi 2FA kedua di samping TOTP: kode OTP 6 digit dikirim ke `users.phone` via Fonnte saat login.
   - Migrasi `2026_08_15_045250_add_whatsapp_2fa_enabled_to_users_table` + `2026_08_15_045251_create_whatsapp_otps_table` (`code_hash`, `expires_at`, `attempts`, `used_at` — `used_at` tidak dipakai, baris dihapus saat selesai).
   - `App\Support\WhatsAppOtpService` (send/verify/forget; hash bcrypt; expiry 5 menit `FONNTE_OTP_EXPIRY_MINUTES`; maks 3 salah `FONNTE_OTP_ATTEMPTS`). **Trap**: nama kelas PHP case-insensitive → jangan buat kelas `WhatsAppOtp` (bentrok dengan model `WhatsappOtp`); layanan wajib bernama `WhatsAppOtpService`.
   - `App\Actions\Fortify\RedirectIfTwoFactorAuthenticatable` (override Fortify, didaftarkan `Fortify::redirectUserForTwoFactorAuthenticationUsing`) — challenge WhatsApp dicek **sebelum** TOTP; butuh `whatsapp_2fa_enabled=true` + `users.phone` terisi.
   - `Auth\WhatsAppTwoFactorController` (create/verify/resend, `throttle:two-factor`) + halaman `auth/whatsapp-otp-challenge.tsx` (InputOTP 6 digit + nomor termask + resend). Enable/disable: `Settings\WhatsappTwoFactorController` (`security.whatsapp-2fa.enable/disable`, butuh `users.phone`), komponen `manage-whatsapp-two-factor.tsx` di `settings/security`.
   - **Anti-spam**: di luar produksi OTP tidak dikirim ke Fonnte, hanya di-log (`WhatsApp OTP (not sent — development only)`) + toast "Dev preview" saat resend. Paksa kirim: `FONNTE_OTP_FORCE_SEND=true`. Alasan: pengujian berulang memicu pembatasan akun WhatsApp.
   - **Recovery lockout**: kalau user tak bisa login (nomor kena pembatasan) → tinker: `User::where('email',...)->update(['whatsapp_2fa_enabled'=>false])` + hapus `whatsapp_otps` miliknya.
   - Test: `tests/Feature/Auth/WhatsAppTwoFactorTest.php` (13 test, `Http::fake` + `otp_force_send=true`). Panduan lengkap: `docs/04-whatsapp-2fa-otp.md`.
8. **Notifikasi murni in-app (tanpa email/WhatsApp) + lonceng & popup**
   - Ketiga alert (`LowStockAlert`, `ServiceQueueAlert`, `ServiceReadyForPickupAlert`) kini `via() = ['database']` saja — **channel email dihapus** (bukan hanya WhatsApp), sesuai keputusan "hanya di dalam aplikasi". `toMail()` dan import `MailMessage` dibuang.
   - Pesan alert diperkaya agar berguna di popover/toast: stok menipis memuat **nama produk**, antrian memuat **nomor invoice**.
   - **Bug yang diperbaiki**: lonceng (`NotificationsPopover`) selama ini hanya dirender di `AppHeader` (dipakai `AppHeaderLayout` yang tidak dipakai halaman mana pun) — jadi bel tidak pernah terlihat di layout aktual (`AppSidebarLayout`). Sekarang bel dipasang di `AppSidebarHeader` (header layout aktif, kanan atas).
    - **Popup toast notifikasi baru**: komponen baru `resources/js/components/notification-alerts.tsx`, dipasang di `AppShell`. Semula memakai `usePoll(30_000, { only: ['notificationSummary'] })` (Inertia v3); **mekanisme ini sudah digantikan** oleh `NotificationsProvider` + endpoint JSON (`notifications.summary`) pada sesi rekomendasi 1.2 — lihat **item 10**. Diffing tetap sama: tiap snapshot membandingkan ID dengan baseline; ID baru → toast sonner (klik "Lihat" → mark-as-read + `action_url`). Baseline saat data pertama tiba via ref `initialized` terpisah (jangan pakai `seenIds.size === 0` — menelan toast pertama saat daftar kosong).
   - Test: `SendAlertsTest` (+2: alert database-only tanpa mail, pesan memuat nama produk), `NotificationsTest` (+1: pickup alert tanpa mail).
9. **Bug fix (review sesi):**
   - **Tabrakan prop `notifications`**: prop page `/notifications` (daftar paginated, `NotificationsController@index`) menimpa shared prop bernama sama → di halaman itu badge lonceng, toast alert, dan tombol "Tandai semua dibaca" mati. Solusi: shared prop di-rename ke `notificationSummary` (`HandleInertiaRequests`), dipakai `NotificationsPopover`, `NotificationAlerts` (`usePoll` juga ikut `only: ['notificationSummary']`), dan `notifications/index.tsx`.
   - **Toast pertama "tertelan"**: guard `seenIds.size === 0` di `NotificationAlerts` ikut menelan notifikasi pertama yang tiba saat daftar masih kosong (di-seed ke baseline, tidak di-toast). Diganti flag `initialized` ref terpisah.
   - **POS kuantitas tak ter-clamp atas**: tombol +/- di `pos.tsx` bisa menaikkan qty melebihi stok (`updateQuantity` hanya clamp bawah). Kini `Math.min(Math.max(qty,1), product.stock)`.
    - Yang **bukan bug** (diverifikasi): `$service->customer->name ?? ...` aman karena null-coalescing; error lama log (kolom `whatsapp_2fa_enabled`) hanya sisa sebelum migrasi.
10. **Rekomendasi 1.2 — sistem notifikasi dikencangkan (sesi ini, arsitektur SAAT INI)**
    - **Shared prop `notificationSummary` DIHAPUS** dari `HandleInertiaRequests` — page load tidak lagi query tabel notifikasi. Gantinya endpoint JSON ringan `GET /notifications/summary` (`NotificationsController@summary`, route `notifications.summary`) → `{unread_count, recent}`.
    - **State terpusat client**: `NotificationsProvider` (`resources/js/hooks/use-notifications.tsx`, di-mount di `AppShell`) polling 30 dtk + saat tab fokus via `useHttp` (Inertia v3, mengembalikan Promise JSON; otomatis kirim `Accept: application/json` + `X-XSRF-TOKEN`). Hook `useNotifications()` → `{summary, unreadCount, markRead, markAllRead, remove, refresh}`.
    - **Mark-as-read saat klik**: klik item popover / tombol "Lihat" toast → `markRead(id)` (optimistic: `read_at` + `unread_count` di-update dulu, lalu POST `notifications.read`; response summary = source of truth). Membuka popover TIDAK menandai dibaca — notifikasi lama tetap tampil, badge menyusut saat benar-benar dibaca.
    - **Hapus notifikasi**: `DELETE notifications/{notification}` (`NotificationsController@destroy`, ownership 403). Tombol hapus (Trash2) di setiap item popover (hover) dan baris halaman `/notifications`.
    - **Auto-cleanup**: command baru `notifications:prune` (`--days=90`, hapus notifikasi lebih tua) terjadwal daily di `routes/console.php`; diuji di `ScheduleTest` + `PruneNotificationsTest`.
    - **Aksi JSON vs Inertia**: endpoint balas JSON bila `$request->expectsJson()`, selain itu `back()` (tetap kompatibel). Halaman `/notifications` pakai provider untuk unreadCount + `router.reload({ only: ['notifications'] })` untuk refresh daftar paginated setelah aksi.
    - `NotificationAlerts` kini diffing dari provider (bukan `usePoll`); `usePoll` dan shared prop sudah tidak dipakai sama sekali.
    - Test: `NotificationsTest` (+6: summary JSON + auth, read JSON, destroy 200 & 403), `PruneNotificationsTest` (2), `ScheduleTest` (+`notifications:prune`). Total **197 test**. Wayfinder regenerated `--with-form` (route `notifications.summary` + `notifications.destroy`).

## 3. Siap dikerjakan berikutnya (urut saran)

### A. Deploy ke domain produksi (runutan terakhir)
Masih tahap pengembangan; berikut **urutan eksekusi final** saat siap live (detail checklist di `docs/03-operasional.md` & `docs/01-status-roadmap.md`):

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

### B. Lainnya dari roadmap
Audit log aksi sensitif (prioritas tertinggi berikutnya), import/export CSV/Excel, E2E Playwright, PWA offline, thermal print, realtime, multi-cabang. Peta lengkap + estimasi: [`docs/05-rekomendasi-pengembangan.md`](05-rekomendasi-pengembangan.md) (rekomendasi 1.2 sudah SELESAI).

## 4. Konvensi wajib (baca sebelum menulis kode)

- Baca `.ai/rules/index.md`, lalu rule yang glob-nya menyentuh path kerja.
- Gunakan `php artisan make:*`; Pest untuk test; setiap perubahan WAJIB ada test dan diverifikasi.
- Frontend: Inertia v3, Wayfinder (`@/actions`, `@/routes`) — jangan hardcode URL; shadcn/ui; warna lewat token CSS.
- Backend: PHP 8.4 + constructor property promotion, type hints, Eloquent attributes.
- Setelah ubah PHP: `vendor/bin/pint --format agent`. Setelah ubah TS/TSX: `npm run format` (Prettier) & `npm run lint:check`.
- Cek versi paket sebelum pakai API baru: `composer show --direct`.

## 5. Verifikasi kualitas

```bash
php artisan test --compact            # seluruh test (sesi terakhir: 197 total = 196 lulus + 1 skip)
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
vendor/bin/pint --format agent        # jalankan file yang diubah (tanpa --dirty, bukan git repo)
npm run types:check
npm run lint:check
npx prettier --check resources/       # atau `npm run format` untuk auto-fix
npm run build
```

> Catatan: PHPStan/Pest hanya benar-benar memuat bootstrap saat dijalankan sebagai satu project, bukan per-file — jalankan penuh seperti di atas.

## 6. Data & akun

- DB dev MySQL `sercomp`. Reset data dummy: `php artisan migrate:fresh --seed`.
- Akun demo: `admin@sercomp.test` / `password`.
- `APP_URL` di `.env` menentukan host QR/WhatsApp/PDF tracking.
