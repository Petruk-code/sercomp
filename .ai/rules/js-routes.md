---
paths:
  - 'resources/js/routes/**'
---

# Js Routes

## Always regenerate Wayfinder with --with-form
After adding/changing named routes, run `php artisan wayfinder:generate --with-form`. The `--with-form` flag is REQUIRED because vite.config.js sets `formVariants: true`; without it every generated file under resources/js/routes loses its `.form()` method and `tsc` fails. Running plain `wayfinder:generate` first and then re-running with the flag also leaves stale artifacts — just use the flag once.

## Wayfinder output gitignored — clone baru wajib generate dulu
resources/js/{routes,actions,wayfinder} (hasil wayfinder:generate) sengaja di-gitignore. Setelah clone baru: npm install lalu npm run build (atau php artisan wayfinder:generate --with-form) HARUS dijalankan sebelum tsc/npm run types:check, kalau tidak file @/routes/* tidak ada dan tsc gagal. Jangan pernah commit direktori ini.
