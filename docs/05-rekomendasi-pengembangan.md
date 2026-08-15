# Rekomendasi Pengembangan SerComp

> Status: **fitur inti selesai**, verifikasi kualitas hijau (Pest 189 / 188 lulus, PHPStan 0 error, Pint, ESLint, Prettier, `tsc`, `npm run build`). Dokumen ini = peta prioritas pengembangan berikutnya, dari yang paling mendesak ke yang opsional.

## 0. Prasyarat teknis yang disarankan sebelum semua itu

- **Masuk ke version control.** Proyek **bukan git repo** — tidak ada safety net. Mulai `git init` + commit baseline + `.gitignore` (`vendor/`, `node_modules/`, `.env`, `storage/logs/*`, `public/build/`) sebelum menyentuh fitur besar. Ini pencegahan paling berdampak.
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
Sudah lama ada di roadmap. Catat siapa mengubah apa pada: pembayaran, retur, ubah status servis, ubah stok, login 2FA. Minimal: tabel `audit_logs` + middleware/event `Observer` di model finansial. Pintu masuk wajib sebelum "percaya" pada produksi.

### 1.4 Import/export data master & laporan (Excel/CSV)
Produk, pelanggan, supplier, laporan penjualan/servis/hutang. Pakai `maatwebsite/excel` atau streaming CSV native (hindari beban memori di data besar).

## 2. Prioritas menengah

### 2.1 E2E / Browser testing (Playwright)
Lapisan test saat ini 100% backend (Pest). Alur lintas halaman yang kritis (POS → checkout → nota, servis → status → tracking → WhatsApp) belum diuji di browser. Tambah smoke test semua route autentikasi (cek error JS + 5xx).

### 2.2 PWA offline / antrean sinkronisasi POS
PWA sudah bisa di-install. Berikutnya: simpan keranjang/transaksi offline lalu sinkron saat online (mode kasir yang putus jaringan), karena koneksi toko sering tidak stabil. Ini modul paling menantang — lakukan setelah 1.x selesai.

### 2.3 Cetak struk langsung dari tablet ke printer thermal/Bluetooth
Integrasi Web Bluetooth/Web Serial atau protokol ESC/POS. Butuh riset kompatibilitas perangkat; jadikan iterasi terpisah.

## 3. Prioritas rendah / ide lanjutan

- **Realtime dengan WebSocket (Laravel Reverb)** menggantikan polling 30 dtk — hanya jika notifikasi realtime menjadi kebutuhan utama (multiuser toko).
- **Multi-cabang / multi-toko (tenant)**: skema multi-tenant sederhana (`store_id`) vs paket (stancl/tenancy). Dampak luas ke hampir semua query — mulai dari laporan & POS dulu.
- **Push notification web** untuk servis siap diambil / stok menipis di HP staf (Web Push API / Laravel Notifications + VAPID). Perlu consent & izin khusus; cocok dilanjutkan dari fitur notifikasi in-app.
- **Ringkasan laporan otomatis (mingguan/bulanan)** via email — nilai tambah untuk pemilik, berpasangan dengan audit log.

## 4. Catatan teknis yang memengaruhi keputusan

- **Queue worker tidak aktif** (`QUEUE_CONNECTION=database` tanpa worker). Jangan tambahkan `ShouldQueue` tanpa menghidupkan worker/supervisor di produksi — notifikasi/email akan antri selamanya.
- **WhatsApp anti-spam**: kiriman otomatis (jadwal/observer) **dilarang** (pembatasan nomor). Fitur WhatsApp berikutnya harus dipicu aksi eksplisit pengguna (lihat `.ai/rules/notifications.md`).
- **Konvensi wajib** sebelum mulai modul baru: baca `.ai/rules/index.md` dan file rule yang match path; `make:*`, Pest, Wayfinder, Pint, prettier/eslint/tsc.
- Proyek memakai **Inertia v3 + React 19 + Tailwind v4 + shadcn/ui** — pakai API versi yang sesuai, jangan asumsi versi.

## 5. Rekomendasi urutan kerja

1. Git init + baseline commit (0.5–1 hari).
2. Deploy produksi per checklist (1–2 hari + verifikasi).
3. Audit log aksi sensitif (1–2 hari).
4. ~~Penguatan notifikasi: endpoint polling ringan + mark-read saat klik + cleanup~~ — **SELESAI (2026-08-15)**.
5. Import/export CSV/Excel (1–2 hari).
6. E2E Playwright smoke (1–2 hari).
7. Baru setelah itu: PWA offline, thermal print, realtime, multi-cabang (estimasi per-modul saat mulai).
