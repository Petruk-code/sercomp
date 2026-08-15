# Dokumentasi Proyek SerComp

Folder ini berisi **catatan status & pengembangan proyek**, disusun mengikuti proses kerja: *status saat ini → arsitektur & konvensi → operasional → briefing kerja*.

| Dokumen | Isi |
| --- | --- |
| [`01-status-roadmap.md`](01-status-roadmap.md) | Status proyek saat ini, riwayat sesi, fitur selesai, todo, dan persiapan produksi |
| [`02-arsitektur-konvensi.md`](02-arsitektur-konvensi.md) | Struktur proyek, pola yang dipakai, dan konvensi wajib saat mengembangkan |
| [`03-operasional.md`](03-operasional.md) | PWA & akses HP, scheduler, backup database, dan persiapan produksi |
| [`04-whatsapp-2fa-otp.md`](04-whatsapp-2fa-otp.md) | WhatsApp 2FA & OTP: cara aktifkan/nonaktifkan, alur kode OTP, konfigurasi, recovery lockout |
| [`05-rekomendasi-pengembangan.md`](05-rekomendasi-pengembangan.md) | **Peta prioritas pengembangan**: prekondisi teknis, jangka pendek/menengah/lanjutan, urutan kerja |
| [`handoff-session.md`](handoff-session.md) | **Briefing kerja untuk sesi berikutnya**: posisi proyek, ringkasan yang sudah selesai, dan langkah lanjutan (acuan: `05-rekomendasi-pengembangan.md`) |

## Cara pakai folder ini

1. **Sebelum mulai kerja**: baca `handoff-session.md` dulu agar tahu posisi proyek & langkah lanjutan, lalu buka rule terkait di `.ai/rules/` sesuai path yang akan disentuh.
2. **Saat mengerjakan satu modul besar**: catat keputusan & aturan durable lewat `record-rule` (tersimpan di `.ai/rules/`), bukan di dokumen — supaya otomatis terbaca agent berikutnya.
3. **Setelah satu tahap selesai**: perbarui `01-status-roadmap.md` (pindahkan todo ke "Fitur yang sudah selesai", coret yang selesai) dan `05-rekomendasi-pengembangan.md` (tandai rekomendasi SELESAI).

> Ringkasan feature stack & cara instalasi ada di [`../README.md`](../README.md).
