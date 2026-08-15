---
paths:
  - 'app/Http/Controllers/**'
  - app/Http/Controllers/DashboardController.php
  - app/Http/Controllers/TrackingController.php
---

# Controllers

## Request::date() has no default-value parameter
`$request->date('key', $default)` does NOT accept a default value — the second argument is a parse FORMAT (e.g. 'Y-m-d') and the method returns null when the key is absent or invalid. Use `$request->filled('key') ? Carbon::parse($request->string('key'))->... : now()->...` instead. This bit ReportController (crashed with toDateString() on null).

## Trend slots must use date-truncated keys
In trendData(), the grouping key must truncate the stored created_at string: substr($date,0,7) for monthly, substr($date,0,10) for daily. Using the raw datetime (Y-m-d H:i:s) never matches the slot keys and silently yields 0 everywhere (this caused a real bug fixed 2026-08-12). App timezone is UTC so raw DB strings align with now() slots.

## Tracking links use the unique tracking_token, not the invoice number
Every ServiceOrder gets a random `tracking_token` (UUID) auto-generated in `ServiceOrderObserver::creating`. QR codes and the WhatsApp tracking link must point to `route('tracking', ['token' => $order->tracking_token])`. `TrackingController` resolves by `token` first and falls back to `no` (invoice number) only for legacy/manual search. Never link tracking by invoice number alone, and never expose tokens in URLs other than query params.

## Tracking card PDF: token-only query param, public + throttled
TrackingController::card() is a PUBLIC download route (tracking.card, GET /tracking/card?token=...) that streams a credit-card-size PDF (QR + tracking token) with throttle:pdf-downloads (30/min per IP for guests). It resolves ONLY by tracking_token (404 otherwise) — never by invoice number. Use setPaper([0, 0, 242.65, 153.07]) with NO orientation arg: dompdf's getPaperSize() SWAPS width/height for 'landscape', producing a tall page that overflows to a second page. Always pass the QR image src as a base64 data URI (dompdf can't load remote URLs) and keep blade layout table-based (no flexbox).

## Capture audit old_values before update() — getOriginal() is stale after save
Eloquent syncs `original` to the current attributes after save()/update(), so `$model->getOriginal('field')` called AFTER the update returns the NEW value, not the old one. When logging an audit diff (old vs new), snapshot the old state into locals BEFORE the DB::transaction/update (e.g. `$oldStatus = $model->payment_status;`), then pass those locals to AuditLogger::record. This hit SaleController::completePayment, PurchaseController::pay, and PurchaseController::update.
