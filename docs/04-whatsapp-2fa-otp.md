# WhatsApp 2FA & OTP

Panduan fitur **autentikasi dua faktor via WhatsApp** dan sistem **kode OTP**-nya.

## Ringkasan

- Setelah diaktifkan, setiap login staf membutuhkan **kode OTP 6 digit** yang dikirim ke nomor WhatsApp pengguna (`users.phone`) melalui gateway **Fonnte**.
- Berjalan **melengkapi** 2FA TOTP (Google Authenticator) yang sudah ada. Jika pengguna mengaktifkan WhatsApp 2FA **dan** TOTP, kode WhatsApp yang dipakai (dicek lebih dulu di `RedirectIfTwoFactorAuthenticatable`).
- Kode disimpan **hash (bcrypt)**, tidak pernah disimpan/kirim plaintext selain di dalam isi pesan WhatsApp itu sendiri.
- Kode pending **dihapus** setelah berhasil diverifikasi, setelah 3 kali salah, atau saat kedaluwarsa.

## Prasyarat

1. Kolom **`users.phone`** terisi (nomor WhatsApp format bebas, contoh `0812...` / `62812...`). Diisi oleh admin lewat manajemen pengguna.
2. **`FONNTE_API_KEY`** terisi di `.env` dan device Fonnte dalam keadaan `connect` (cek: `curl -X POST https://api.fonnte.com/device -H "Authorization: <FONNTE_API_KEY>"`).
3. **Pengiriman nyata hanya terjadi saat `APP_ENV=production`**. Di lokal/testing kode hanya ditulis ke log — lihat bagian *Development / testing*.

## Cara mengaktifkan

1. Login → **Pengaturan** → **Keamanan** (`/settings/security`).
2. Pastikan nomor WhatsApp sudah terisi pada akun. Jika kosong, tombol enable disembunyikan dan POST enable membalas toast error (harus atur nomor dulu).
3. Klik tombol **"Enable WhatsApp 2FA"**.
4. Selesai — mulai saat itu setiap login menuntut kode OTP WhatsApp.

## Cara menonaktifkan

1. Login (butuh kode OTP) → **Pengaturan** → **Keamanan**.
2. Klik tombol **"Disable WhatsApp 2FA"**.
3. Semua kode OTP pending untuk akun itu otomatis dihapus (`WhatsAppOtpService::forget`).

### Lockout — tidak bisa login (nomor kena pembatasan / kode tak pernah sampai)

2FA hanya flag di database, jadi bisa dimatikan dari server tanpa perlu kode:

```bash
php artisan tinker --execute '
App\Models\User::where("email", "admin@sercomp.test")->update(["whatsapp_2fa_enabled" => false]);
App\Models\WhatsappOtp::whereIn("user_id", App\Models\User::pluck("id"))->delete();
'
```

(atau langsung lewat MySQL: `UPDATE users SET whatsapp_2fa_enabled = 0 WHERE id = ...` lalu hapus baris `whatsapp_otps` miliknya).

## Alur OTP — `App\Support\WhatsAppOtpService`

| Method | Fungsi |
| --- | --- |
| `send(User $user)` | Generate kode acak 6 digit (100000–999999), hapus kode pending lama, simpan hash + `expires_at`, kirim pesan berisi `*kode*` via Fonnte. |
| `verify(User $user, string $code)` | Ambil kode pending terbaru. Tolak bila tidak ada / kedaluwarsa / sudah `maxAttempts` kali gagal. Salah → `attempts` bertambah. Benar → kode dihapus, `true`. |
| `forget(User $user)` | Hapus semua kode pending (dipakai saat disable/reset). |

Parameter konfigurasi (`config/services.php` → `fonnte`):

| Env | Default | Keterangan |
| --- | --- | --- |
| `FONNTE_OTP_EXPIRY_MINUTES` | `5` | Masa berlaku kode (menit). |
| `FONNTE_OTP_ATTEMPTS` | `3` | Batas percobaan salah sebelum kode dianulir. |
| `FONNTE_OTP_FORCE_SEND` | `false` | Paksa kirim nyata walau bukan produksi (untuk debugging, tidak disarankan). |

### Alur login

`App\Actions\Fortify\RedirectIfTwoFactorAuthenticatable` (didaftarkan via `Fortify::redirectUserForTwoFactorAuthenticationUsing`) — jika `users.whatsapp_2fa_enabled = true` **dan** `users.phone` terisi, pengguna diarahkan ke challenge WhatsApp **sebelum** logika TOTP Fortify. Session memakai `login.id` + `login.remember` (sama seperti TOTP).

### Route

| Method | URL | Middleware | Nama |
| --- | --- | --- | --- |
| GET | `/two-factor/whatsapp` | `guest` | `two-factor.whatsapp` |
| POST | `/two-factor/whatsapp/verify` | `guest` + `throttle:two-factor` (5/menit per `login.id`) | `two-factor.whatsapp.verify` |
| POST | `/two-factor/whatsapp/resend` | `guest` + `throttle:two-factor` | `two-factor.whatsapp.resend` |
| POST | `/settings/security/whatsapp-2fa/enable` | `auth,verified` + `throttle:6,1` | `security.whatsapp-2fa.enable` |
| POST | `/settings/security/whatsapp-2fa/disable` | `auth,verified` + `throttle:6,1` | `security.whatsapp-2fa.disable` |

## Tabel database

- **`users.whatsapp_2fa_enabled`** — boolean, default `false`.
- **`whatsapp_otps`** — `id`, `user_id` (FK cascade), `code_hash`, `expires_at`, `attempts`, `used_at` (tidak dipakai; baris dihapus saat selesai), `created_at`, `updated_at`.

## Development / testing

- **Di lokal/testing**: OTP **tidak dikirim** ke WhatsApp. Kode ditulis ke `storage/logs/laravel.log` (baris `WhatsApp OTP (not sent — development only)`) dan saat tombol *Resend* muncul di toast sebagai **"Dev preview: XXXXXX"**. Cara pantau: `tail -f storage/logs/laravel.log`.
- **Test otomatis**: `tests/Feature/Auth/WhatsAppTwoFactorTest.php` (13 test). Memakai `Http::fake` + mengaktifkan `otp_force_send` agar jalur HTTP ke Fonnte benar-benar teruji **tanpa** kirim pesan nyata.

## Kenapa nomor WhatsApp bisa kena pembatasan

Pengiriman OTP berulang dalam waktu singkat (login berulang + tombol Resend) bisa memicu pembatasan anti-spam dari sisi WhatsApp/Fonnte — bukan bug aplikasi. Proteksi yang sudah diterapkan: **non-produksi tidak pernah mengirim** (hanya log), jadi pengujian aman. Untuk membuka pembatasan akun: **Pengaturan WhatsApp → Bantuan → Hubungi kami**.
