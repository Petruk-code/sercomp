# Multi-Cabang & Email Ringkasan Bulanan

> Panduan lengkap (penggunaan + teknis) untuk rekomendasi 2.5 dan 2.6.
> Terakhir diperbarui: **2026-08-21**. Status: ✅ selesai & teruji.

---

## Bagian 1 — Multi-Cabang

### 1.1 Konsep

Satu bisnis, beberapa cabang, **satu database** dengan kolom pemisah `store_id`
(skema sederhana — bukan multi-tenant penuh). Prinsipnya:

| Aturan | Penjelasan |
| --- | --- |
| Data per cabang | Produk, pelanggan, supplier, katalog jasa, penjualan, servis, pembelian — semuanya milik satu cabang. Cabang A tidak melihat stok/penjualan Cabang B. |
| Kasir & teknisi terikat | User dengan role kasir/teknisi **wajib** punya `store_id`; mereka otomatis bekerja di cabang itu dan tidak bisa pindah. |
| Owner/admin bebas | User tanpa `store_id` (umumnya owner/admin) bisa **pindah-pindah cabang** dari header aplikasi; sebelum memilih, mereka melihat data semua cabang. |
| Kategori global | Kategori produk/jasa dipakai bersama semua cabang. |
| Nomor invoice global | Penomoran invoice tetap berurutan global (tidak per cabang). |

### 1.2 Cara pakai (panduan pengguna)

**Menambah cabang** (admin/owner):

1. Sidebar → **Master Data → Cabang** (`/stores`).
2. Klik **Tambah Cabang**, isi nama/alamat/telepon, simpan.
3. Cabang nonaktif tidak bisa dipilih sebagai cabang kerja; untuk "menutup"
   cabang, ubah statusnya menjadi **Nonaktif** lewat tombol Edit (tidak ada
   hard delete agar riwayat transaksi aman).

**Pindah cabang kerja** (owner/admin yang tidak terikat cabang):

1. Di header aplikasi (sebelah breadcrumb) ada dropdown berlogo gedung 🏢
   menampilkan cabang aktif — muncul hanya jika ada ≥ 2 cabang aktif.
2. Pilih cabang → halaman dimuat ulang dengan data cabang tersebut.
3. Pilihan tersimpan di session; login berikutnya tetap di cabang terakhir.

**Mengikat user ke cabang** (admin/owner):

1. **Master Data → Pengguna** → tambah/edit pengguna.
2. Field **Cabang (opsional)**: pilih cabang untuk kasir/teknisi, atau
   kosongkan untuk owner/admin yang boleh pindah-pindah.

### 1.3 Cara kerja (referensi teknis)

```
StoreContext::id()  — resolusi cabang aktif per request:
  1. auth()->user()->store_id            (user terikat → selalu cabang itu)
  2. session('active_store_id')          (user bebas yang sudah memilih;
                                          divalidasi: store harus ada & aktif)
  3. null                                (console, publik, belum memilih)
     → global scope TIDAK aktif = lihat semua data
```

- **Trait** `App\Concerns\BelongsToStore` (dipasang di `Product`, `Customer`,
  `Supplier`, `ServiceCatalog`, `Sale`, `ServiceOrder`, `Purchase`):
  - global scope `store` → setiap query otomatis difilter ke cabang aktif;
  - hook `creating` → `store_id` terisi otomatis dari konteks saat create.
- **Memo konteks disimpan di container** (`app()->instance`), bukan properti
  static — wajib begitu agar aman antar-test Pest dan long-running worker
  (Octane). Lihat `.ai/rules/support.md`.
- **Tabel anak** (`sale_items`, `service_order_items`, dst.) tidak punya
  `store_id` — mengikuti induknya. Retur pembelian di-scope lewat relasi
  `purchase`.
- **Endpoint pindah cabang**: `PUT stores/active` (`StoreController@switchActive`)
  — ditolak 403 bila user terikat cabang; validasi store harus ada & aktif.
- **Props Inertia**: `stores` (daftar cabang aktif, hanya untuk user bebas) dan
  `activeStoreId` dibagikan oleh `HandleInertiaRequests`.
- **Rute**: CRUD `stores.*` + `stores.active` di group `role:admin,owner`.

### 1.4 Migrasi & data lama

`2026_08_21_080807_add_store_id_to_tables.php`:

1. Membuat tabel `stores` (name, address, phone, is_active).
2. Menyisipkan store default **"Cabang Utama"**.
3. Menambah kolom `store_id` (nullable, FK) ke 7 tabel + `users`.
4. Meng-backfill **semua baris lama** ke "Cabang Utama" — data existing aman
   dan otomatis jadi milik cabang pertama.

### 1.5 Test

`tests/Feature/MultiStoreTest.php` (9 test): isolasi produk antar cabang,
auto-fill `store_id`, owner bebas sebelum memilih, pindah cabang tersimpan di
sesi, user terikat ditolak pindah (403), cabang nonaktif ditolak validasi,
daftar cabang di form user, otorisasi CRUD cabang, create/update cabang.

> Trap: rute `master.products.*` khusus admin/owner — untuk menguji scoping
> sebagai kasir gunakan rute lain (mis. customers).

---

## Bagian 2 — Email Ringkasan Bulanan

### 2.1 Apa yang dikirim

Email ke semua user ber-role **owner** (bukan admin) setiap **tanggal 1 jam
07:00** berisi rekap bulan kalender sebelumnya:

| Bagian | Isi |
| --- | --- |
| Penjualan | Total & jumlah transaksi **per cabang** + grand total |
| Servis | Jumlah servis **masuk** (dibuat) dan **selesai** (`completed_at`) dalam periode |
| Hutang jatuh tempo | Supplier, tanggal jatuh tempo, sisa tagihan (`total - paid_amount`) — hanya yang `due_date ≤ akhir periode` dan belum lunas |
| Stok menipis | Produk dengan `stock ≤ min_stock` (min_stock > 0), maks 15 item + total hitungan |

Laporan mencakup **semua cabang** (command berjalan tanpa konteks cabang →
tanpa scoping).

### 2.2 Operasional

```bash
# Kirim manual untuk bulan tertentu:
php artisan app:send-monthly-report --month=2026-07

# Default (bulan kalender sebelumnya):
php artisan app:send-monthly-report
```

Jadwal terdaftar di `routes/console.php`:

```php
Schedule::command('app:send-monthly-report')->monthlyOn(1, '07:00');
```

Syarat agar email benar-benar terkirim di produksi: konfigurasi `MAIL_*` di
`.env` (SMTP/hosting) dan cron scheduler aktif
(`* * * * * php artisan schedule:run`). Bila belum ada user ber-role owner,
command hanya memberi peringatan tanpa error.

### 2.3 Struktur kode

| File | Peran |
| --- | --- |
| `app/Console/Commands/SendMonthlyReport.php` | Kumpul data (aggregate SQL per cabang), kirim via `Mail::to(...)->send()` |
| `app/Mail/MonthlyReportMail.php` | Mailable markdown; subject `Laporan Bulanan {app} — {periode}` |
| `resources/views/mail/monthly-report.blade.php` | Template email (komponen `mail::table` bawaan Laravel) |

### 2.4 Test

`tests/Feature/MonthlyReportTest.php` (2 test): data laporan benar (periode
tepat, hutang lunas/belum jatuh tempo dikecualikan, stok aman tak ikut,
view ter-render tanpa error blade) dan tidak mengirim apa pun bila belum ada
owner. Jadwal dicek `ScheduleTest`.

> Trap: locale app adalah `en` — jangan hardcode nama bulan Indonesia
> ("Juli") di asersi; bandingkan dengan `translatedFormat('F Y')` dari Carbon.

---

## Bagian 3 — Verifikasi menyeluruh (saat fitur selesai)

```bash
php artisan test --compact                              # 246 test: 245 lulus + 1 skip
vendor/bin/phpstan analyse --no-progress --memory-limit=1G   # 0 error
vendor/bin/pint --dirty --format agent                  # bersih
npx tsc --noEmit && npm run lint:check                  # bersih
npm run build                                           # sukses
```

## Bagian 4 — Ide lanjutan (belum dikerjakan)

- Filter laporan/email **per cabang** (opsi `--store=`).
- Ringkasan **mingguan** bila nanti dibutuhkan (tinggal ganti frekuensi schedule).
- Web push notification saat app tertutup (realtime in-app sudah jalan via Reverb).
