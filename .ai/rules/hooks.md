---
paths:
  - 'resources/js/hooks/**'
---

# Hooks

## Notification bell is realtime-first: Echo push + disconnect-only polling
NotificationsProvider no longer polls on a fixed interval. When `echoIsConfigured()` (VITE_REVERB_APP_KEY set, configureEcho called in app.tsx) it subscribes to private channel `App.Models.User.${userId}` listening '.notification.pushed' → refresh(); a 30s fallback poll runs ONLY while the socket is disconnected (echo().connector.onConnectionChange). If Echo is not configured the provider degrades to plain 30s polling. CSRF for /broadcasting/auth comes from <meta name="csrf-token"> in app.blade.php — do not remove it. Reverb listens on REVERB_SERVER_PORT=8081 in .env because local Apache occupies 8080; VITE_REVERB_* values are baked at build time, so run npm run build after changing them.
