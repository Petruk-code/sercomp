---
paths:
  - 'tests/Browser/**'
---

# Browser

## Browser test: public/hot, API pressAndWaitFor, & satu context per visit()
Browser tests pakai pestphp/pest-plugin-browser (Playwright). Trap: file public/hot (ada saat dev server jalan ATAU basi) mengarahkan aset ke Vite dev server dan membuat test auth gagal dengan gejala aneh: URL tetap /pos tapi konten halaman login, data-page hilang. Solusi saat dev server berjalan: sembunyikan sementara `mv public/hot /tmp` sebelum php artisan test lalu kembalikan setelah selesai — jangan matikan proses dev user. pressAndWaitFor(button, seconds) = argumen kedua TIMEOUT DETIK, bukan teks; untuk menunggu teks toast pakai press() lalu assertSee() (auto-wait).

PENTING arsitektur plugin v5: SETIAP panggilan visit() membuat context browser BARU tanpa cookie sesi (SESSION_DRIVER=array per proses). Login lewat form asli hanya valid di page instance yang sama — helper loginBrowserAdmin() mengembalikan AwaitableWebpage yang sudah login; lanjutkan alur dari situ dan pindah halaman dengan $page->navigate(), JANGAN panggil visit() lagi setelah login. actingAs tidak berlaku untuk session browser.
