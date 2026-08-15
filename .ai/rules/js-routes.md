---
paths:
  - 'resources/js/routes/**'
---

# Js Routes

## Always regenerate Wayfinder with --with-form
After adding/changing named routes, run `php artisan wayfinder:generate --with-form`. The `--with-form` flag is REQUIRED because vite.config.js sets `formVariants: true`; without it every generated file under resources/js/routes loses its `.form()` method and `tsc` fails. Running plain `wayfinder:generate` first and then re-running with the flag also leaves stale artifacts — just use the flag once.
