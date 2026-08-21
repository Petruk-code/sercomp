---
paths:
  - '**/*'
  - composer.json
---

# General

## Regenerate Wayfinder actions with --with-form
`php artisan wayfinder:generate` without flags strips the `.form()` variants from generated action files, breaking `Form action={...form()}` calls. Always run `php artisan wayfinder:generate --with-form` so controllers keep `.url`, `.delete`, `.post`, `.form`, etc.

## PHPStan needs --memory-limit on CLI
PHPStan 2.x removed the memoryLimit config key from phpstan.neon; setting it there fails with "Unexpected item 'parameters › memoryLimit'". The types:check script must pass --memory-limit=1G (default 128M crashes during parallel analysis).

## Production deployment prerequisites (not yet done)
As of 2026-08-12 the app is feature-complete and all checks green, but NOT yet production-deployed. Before going live: set APP_URL to the final HTTPS domain (QR/tracking URLs depend on it), harden .env (APP_ENV=production, APP_DEBUG=false, new APP_KEY, non-root DB credentials — currently root/password), configure MAIL_*, and install the scheduler cron (`* * * * * php /path/artisan schedule:run`). Optional before trusting it in a real shop: audit log for financial actions and Playwright E2E tests.

## Reverb ops: port 8081 (NOT 8080), two port vars, restart composer dev
Reverb server MUST stay on port 8081: local Apache (httpd) occupies 8080 on this machine. Two DIFFERENT env vars control ports and BOTH are 8081 in .env: REVERB_SERVER_PORT = port the reverb server LISTENS on (config/reverb.php servers.reverb.port), REVERB_PORT = client/app options used by Echo config baked into JS. After changing any VITE_REVERB_* value run `npm run build` (values are compile-time). The dev TUI (`composer dev` / php artisan dev) auto-starts reverb because laravel/reverb registers itself — but only after a RESTART of composer dev; an already-running multiplex will NOT pick it up. If reverb is down the app still works: notifications fall back to 30s polling by design.
