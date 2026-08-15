# Dokumentasi Proyek SerComp

Folder ini adalah tempat **catatan update & pengembangan proyek**, disusun mengikuti proses kerja: *status saat ini → arsitektur & konvensi → operasional → handoff per modul*.

| Dokumen | Isi |
| --- | --- |
| [`01-status-roadmap.md`](01-status-roadmap.md) | Status proyek saat ini, apa yang sudah selesai, dan rencana kerja berikutnya |
| [`02-arsitektur-konvensi.md`](02-arsitektur-konvensi.md) | Struktur proyek, pola yang dipakai, dan konvensi wajib saat mengembangkan |
| [`03-operasional.md`](03-operasional.md) | PWA & akses HP, scheduler, backup database, dan persiapan produksi |
| [`04-whatsapp-2fa-otp.md`](04-whatsapp-2fa-otp.md) | WhatsApp 2FA & OTP: cara aktifkan/nonaktifkan, alur kode OTP, konfigurasi, dan recovery lockout |
| [`05-rekomendasi-pengembangan.md`](05-rekomendasi-pengembangan.md) | Peta prioritas pengembangan: prekondisi teknis, jangka pendek/menengah/lanjutan, dan urutan kerja |
| [`handoff-session.md`](handoff-session.md) | **Prompt/briefing untuk sesi berikutnya**: status terkini, apa yang dikerjakan, dan langkah lanjutan (2FA, kartu lacak unduhan, deploy) |
| `handoff-supplier-management.md` | Handoff modul supplier & pembelian (status + instruksi kelanjutan) |

## Cara pakai folder ini

1. **Sebelum mulai kerja**: baca `01-status-roadmap.md` dulu agar tahu posisi proyek, lalu buka rule terkait di `.ai/rules/` sesuai path yang akan disentuh.
2. **Saat mengerjakan satu modul besar**: buat dokumen *handoff* di folder ini (contoh: `handoff-supplier-management.md`) agar konteks tidak hilang saat pekerjaan dilanjutkan agent/session lain.
3. **Setelah satu tahap selesai**: perbarui `01-status-roadmap.md` (coret todo yang selesai, catat apa yang dikerjakan).

> Ringkasan feature stack & cara instalasi ada di [`../README.md`](../README.md).
