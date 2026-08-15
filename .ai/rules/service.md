---
paths:
  - 'app/Http/Controllers/Service/**'
---

# Service

## Service receipt embeds a QR tracking code
ServiceOrderController::print() renders pdf/service_receipt with a QR (endroid/qr-code v6 Builder + PngWriter, base64 data URI) pointing to route('tracking', ['no' => invoice]). Keep tracking as a public GET route; the blade view must always receive $trackingUrl and $qrDataUri.

## Checkout freezes service order: no part changes, no cancel, atomic SRV number
Once a service order has a sale (sales.service_order_id UNIQUE), addItem/removeItem are rejected and updateStatus('cancelled') is rejected — parts stock and the paid/partial sale must stay consistent (do not revert parts after checkout). 'taken' is also uncancellable. Service invoice numbers come from InvoiceNumber::next('SRV') (atomic sequence table) — never count()+1, which races under concurrency and reuses numbers after deletes.
