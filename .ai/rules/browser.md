---
paths:
  - 'tests/Browser/**'
---

# Browser

## Browser test: hapus public/hot basi & API pressAndWaitFor
Browser tests pakai pestphp/pest-plugin-browser (Playwright). Trap: file public/hot basi (dev server mati) membuat semua halaman timeout + console log "[vite] server connection lost" yang otomatis gagalkan assertNoSmoke. Solusi: rm public/hot agar fallback ke public/build, atau jalankan npm run dev. pressAndWaitFor(button, seconds) = argumen kedua TIMEOUT DETIK, bukan teks; untuk menunggu teks toast pakai press() lalu assertSee() (auto-wait). Login di browser test harus lewat form asli (helper loginBrowserAdmin di tests/Pest.php), actingAs tidak berlaku untuk session browser.
