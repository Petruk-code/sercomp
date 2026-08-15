# Operasional

## Menjalankan Aplikasi

```bash
composer run dev
```

Menjalankan server Laravel (`php artisan dev`) + Vite dev server. Buka `http://localhost:8000`.

### Halaman publik

| Halaman                 | URL                       | Keterangan                             |
| ----------------------- | ------------------------- | -------------------------------------- |
| Landing page            | `/`                       | Pengenalan aplikasi & fitur            |
| Lacak status servis     | `/tracking?token=...`     | Pelanggan cek status servis (token unik per servis, dipakai oleh QR & WhatsApp) |
| Login staf              | `/login`                  | Akses internal (tidak dipajang publik); butuh kode OTP WhatsApp bila 2FA aktif |

> **Penting**: pastikan `APP_URL` di `.env` sudah diisi domain publik yang benar (mis. `https://sercomp.test`), karena QR code pada nota servis memakai nilai tersebut.

### Checklist tracking saat live di domain produksi

Halaman `/tracking` dan semua tautan lacak (QR, WhatsApp, PDF) memakai **URL absolut yang dibentuk dari `APP_URL`**. Saat pindah dari `localhost` ke domain asli:

1. **Set `APP_URL` ke `https://<domain>` final** di `.env` produksi, lalu `php artisan config:cache`. Ini satu-satunya yang menentukan host pada QR/WhatsApp/PDF — tokennya **tidak perlu diubah**.
2. **Cetak ulang / kirim ulang media lama**: QR yang sudah tercetak atau link WhatsApp yang terkirim saat masih `localhost`/tunnel tidak akan ter-scan ke domain baru (host-nya masih `localhost:8000`). Setelah deploy, cetak ulang tanda terima servis dan kirim ulang link WhatsApp agar QR mengarah ke domain final.
3. **Konfigurasi trusted proxies (kritikal untuk rate limit)** — halaman `tracking` di-throttle **20 permintaan/menit per IP** (`RateLimiter::for('tracking-lookups')` di `AppServiceProvider`, kunci `$request->ip()`). Di belakang reverse proxy (nginx, Cloudflare, load balancer, Laravel Cloud), tanpa `trustProxies` semua pengunjung akan terbaca sebagai IP proxy yang sama → berbagi satu bucket dan halaman publik bisa cepat kena `429 Too Many Requests`. Tambahkan di `bootstrap/app.php`:
   ```php
   ->withMiddleware(function (Middleware $middleware): void {
       // ganti dengan daftar IP proxy/host yang dipakai hosting (mis. Cloudflare, nginx)
       $middleware->trustProxies(at: '*'); // untuk Laravel Cloud/Forge umumnya '*'
   })
   ```
   (Pada Laravel Cloud/Forge hal ini biasanya sudah ditangani otomatis — verifikasi tetap disarankan.)
4. **HTTPS** — pastikan `APP_URL` memakai `https` dan redirect http→https. Dengan trusted proxies aktif, scheme `https` juga ikut benar pada URL yang dibentuk Laravel.
5. **Verifikasi pasca-deploy**: dari HP & PC (beda jaringan) buka `/tracking?token=<salah satu token servis>`, scan QR dari nota, dan pastikan respons masih normal (percobaan ke-21 dari IP sama akan mendapat `429` — itu tanda rate limit bekerja benar).

## Akses Mobile & PWA

Aplikasi sudah ber-PWA: manifest, service worker (cache aset), ikon, dan tombol *Install*. Tampilan kasir/POS dioptimalkan untuk tablet (dua-panel: produk di kiri, keranjang + pembayaran di kanan).

### Menjalankan agar bisa diakses dari HP

```bash
php artisan serve --host=0.0.0.0 --port=8000
ipconfig getifaddr en0        # contoh hasil: 192.168.1.5 (macOS)
```

Hubungkan HP ke **Wi-Fi yang sama**, lalu buka `http://192.168.1.5:8000`. Cara ini cukup untuk memakai aplikasi dari HP/tablet, tapi **belum** bisa *meng-install* PWA.

### Meng-install PWA di HP

PWA hanya bisa di-install lewat **HTTPS** (kecuali `localhost`). Gunakan tunnel HTTPS:

```bash
ngrok http 8000
# atau
cloudflared tunnel --url http://localhost:8000
```

- **Android (Chrome)**: prompt "Install app", atau menu ⋮ → *Add to Home screen*.
- **iOS (Safari)**: Share → *Add to Home Screen* (fullscreen karena ada `apple-mobile-web-app-capable`).
- **Laptop sendiri**: `localhost` sudah secure context → install langsung dari address bar.

> **Catatan PWA** (jangan dihapus):
> - Tag `<link rel="manifest">` beserta meta `theme-color`, `apple-mobile-web-app-capable`, dan `mobile-web-app-capable` ditambahkan **manual** di `resources/views/app.blade.php`. vite-plugin-pwa tidak meng-inject otomatis di proyek Laravel karena tidak ada file `index.html`.
> - File `manifest.webmanifest` & `sw.js` hanya di-generate saat `npm run build` (mode dev tidak memuat PWA).
> - Saat akses lewat tunnel/domain, set `APP_URL` di `.env` ke URL HTTPS tersebut (memengaruhi QR code & tautan absolut).

## Scheduler & Tugas Terjadwal

Command tersedia:

```bash
php artisan app:backup-database   # backup database ke storage/app/backups (simpan 14 terakhir)
php artisan app:send-alerts       # notifikasi stok menipis & antrian servis ke admin/owner
```

Untuk pengembangan: `php artisan schedule:work` (jalan terus di foreground).

Di produksi pasang cron `* * * * * php /path/proyek/artisan schedule:run`. Jadwal bawaan:

- `app:backup-database` — setiap hari pukul 02:00.
- `app:send-alerts` — setiap jam (antrian servis yang idle > 24 jam).

Backup memakai driver aktif: SQLite (salin file), MySQL (`mysqldump`), PostgreSQL (`pg_dump`). Notifikasi alert (stok menipis, antrian servis, servis siap diambil) murni **in-app** — tersimpan di tabel `notifications` dan dilihat lewat lonceng di header + halaman `/notifications`; **tidak dikirim lewat e-mail atau WhatsApp**. Konfigurasi `MAIL_*` hanya untuk e-mail autentikasi (verifikasi email, reset password).

## Persiapan Produksi (belum dilakukan)

Aplikasi fitur-lengkap dan semua cek hijau, tetapi **belum di-deploy ke produksi**. Sebelum live:

1. Set `APP_URL` ke domain HTTPS final (QR/tracking bergantung padanya).
2. Harden `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` baru, kredensial DB non-root (saat ini `root`/password).
3. Konfigurasi `MAIL_*` (e-mail autentikasi: verifikasi email, reset password) dan `FONNTE_API_KEY` (WhatsApp: kiriman **manual** link tracking ke pelanggan **dan** OTP 2FA).
4. Pasang cron scheduler (di atas).
5. Opsional sebelum dipercaya di toko sungguhan: audit log untuk aksi finansial & E2E Playwright.

> **Penting**: OTP 2FA WhatsApp **hanya terkirim asli saat `APP_ENV=production`**. Di lokal/testing kode hanya di-log — jadi tanpa `.env` produksi yang benar, staf yang mengaktifkan 2FA WhatsApp tidak akan menerima kode.

## Data & Akun

- Reset data dummy: `php artisan migrate:fresh --seed`.
- Akun demo: `admin@sercomp.test` / `password`.

## Autentikasi 2FA & OTP WhatsApp (Fonnte)

Ringkasan operasional; panduan lengkap (aktif/nonaktifkan, alur OTP, recovery lockout) di [`04-whatsapp-2fa-otp.md`](04-whatsapp-2fa-otp.md).

- **Aktifkan/nonaktifkan**: `Pengaturan → Keamanan` (`/settings/security`). Syarat aktif: nomor `users.phone` terisi.
- **Cek device Fonnte** (dipakai untuk kiriman manual link tracking & OTP 2FA): `curl -X POST https://api.fonnte.com/device -H "Authorization: <FONNTE_API_KEY>"` → `device_status` harus `connect`. Jika `disconnect`, pesan tidak terkirim (kegagalan di-log).
- **Anti-spam**: WhatsApp **tidak pernah dikirim otomatis** oleh sistem — alert terjadwal/observer murni **in-app** (database saja, tampil di lonceng + popup toast; kiriman otomatis berulang memicu pembatasan akun WhatsApp). Hanya aksi eksplisit pengguna yang mengirim: tombol "Kirim WhatsApp" (link tracking) & pengiriman OTP 2FA. OTP pun hanya terkirim asli saat `APP_ENV=production`.
- **Recovery lockout** (nomor kena batasan / kode tak kunjung sampai): matikan flag dari server tanpa login lewat tinker/MySQL (perintah di `04-whatsapp-2fa-otp.md`).
