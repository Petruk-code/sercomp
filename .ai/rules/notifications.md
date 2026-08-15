---
paths:
  - 'app/Notifications/**'
---

# Notifications

## Bell UI is client-driven: JSON summary endpoint + NotificationsProvider, NO shared prop, NO usePoll
The bell badge, popover, and toast alerts are driven by a client-side store — `NotificationsProvider` (resources/js/hooks/use-notifications.tsx, mounted in AppShell) — that polls `GET notifications.summary` every 30s and on window focus, and exposes `markRead(id)` / `markAllRead()` / `remove(id)` which call the JSON endpoints and apply optimistic updates. **There is NO `notificationSummary` shared prop** — it was removed so page loads don't query the notifications table (that was the point of 1.2; before the refactor the /notifications page prop `notifications` also shadowed the old shared prop of the same name). `NotificationsPopover` and `NotificationAlerts` read from the provider; `notifications/index.tsx` reads `unreadCount` from the provider and refetches its paginated list via `router.reload({ only: ['notifications'] })` after actions. Endpoints return JSON when `$request->expectsJson()` (useHttp sends `Accept: application/json`; XHR client auto-sends `X-XSRF-TOKEN`) and `back()` otherwise: `summary` returns `{unread_count, recent}`, the read/read-all/destroy actions return the fresh summary so the client syncs from the response. All endpoints are auth-gated (`auth,verified` middleware) and ownership-checked (`notifiable_id`).

## Alerts are IN-APP only — via() is ['database'], no mail, no WhatsApp
Automatic alerts (LowStockAlert, ServiceQueueAlert, ServiceReadyForPickupAlert) are delivered through the database channel ONLY — their `via()` returns `['database']` (mail channel was removed by decision, WhatsApp channel removed for anti-spam). The bell must live in the actual used header (resources/js/components/app-sidebar-header.tsx, AppHeaderLayout is unused). New-notification popups: `NotificationAlerts` (mounted in AppShell) diffs notification IDs across provider snapshots and fires a sonner toast for any ID not seen on the previous snapshot — baseline is taken when the first summary arrives (use a dedicated `initialized` ref, NOT `seenIds.size === 0`, or the first notification that arrives while the list was empty is swallowed instead of toasted) so existing unread items never spam on login. Mark-as-read happens on item click / toast "Lihat" / "Tandai semua", never on merely opening the popover — opening the popover does NOT mark anything read, so old notifications stay visible while the badge shrinks. Keep `toArray()` messages descriptive (list product names / invoice numbers) because the popover and toasts render only title + message.

## Notifications must NOT be queued (no ShouldQueue) — no queue worker in this app
Do not make notification classes implement ShouldQueue. QUEUE_CONNECTION=database in dev with no worker running means queued notifications never reach the notifications table, so the bell/UI stays empty (surfaced as a bug: dashboard showed low stock but bell had nothing). Alert notifications deliver via the database channel synchronously — volume is tiny (2 recipients, a few types), latency is negligible.

## Notifications are pruned, not kept forever
The `notifications:prune` command (app/Console/Commands/PruneNotifications.php, default `--days=90`) deletes notifications older than the retention window and is scheduled `daily` in routes/console.php (asserted in ScheduleTest). UI also lets users delete individual notifications (`DELETE notifications.destroy`).

## WhatsApp sends are MANUAL-ONLY — alerts must NOT use the Fonnte channel
Anti-spam decision (WhatsApp restricts numbers that receive repeated automated messages): automatic alerts never send WhatsApp. The FonnteChannel + SendsWhatsapp contract were removed entirely. The only WhatsApp traffic allowed is user-initiated: the manual "Kirim WhatsApp" button (ServiceOrderController::sendWhatsapp → WhatsApp::send) and the WhatsApp 2FA login OTP. If a new WhatsApp feature is needed it must be triggered by an explicit user action, never scheduled/observer-driven.
