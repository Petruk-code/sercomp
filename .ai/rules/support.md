---
paths:
  - app/Support/InvoiceNumber.php
  - 'app/Support/**'
---

# Support

## InvoiceNumber prefix map includes table + number column
InvoiceNumber::currentMax() maps a prefix to BOTH table and document-column: SRV→service_orders.invoice_number, PO→purchases.purchase_number, RTN→purchase_returns.return_number (future), default INV→sales.invoice_number. When adding new prefixes keep the match() in currentMax updated — the number column is not always 'invoice_number'. PO sequence starts today because InvoiceNumber::next() always uses now(); seeded purchases may be backdated.

## WhatsappOtp model name forces WhatsAppOtpService class name
The OTP model is App\Models\WhatsappOtp (lowercase 'app'). PHP class names are case-INSENSITIVE, so a class named App\Support\WhatsAppOtp would collide with the model (fatal "Cannot redeclare class ... as local import"). The OTP helper must stay named WhatsAppOtpService (app/Support/WhatsAppOtpService.php).

## StockManager suppresses Product events; audit keys live in AuditLog::ACTION_LABELS
StockManager::increment/decrement wraps the change in Product::withoutEvents() so ProductObserver::updated does NOT also record product.stock_changed (double audit rows). ProductObserver still logs manual stock edits made outside StockManager. Every sensitive-action audit key (sale.payment, purchase_return.deleted, whatsapp_2fa.enabled, etc.) must be added to AuditLog::ACTION_LABELS or the UI shows the raw key.

## Runtime 500 "table doesn't exist" = jalankan migrate dulu
AuditLogger menulis ke tabel audit_logs dari banyak controller/observer. Test suite lolos walau migrasi lokal pending karena RefreshDatabase hanya berlaku di DB testing. Jika muncul SQLSTATE[42S02] saat akses route di lokal, cek `php artisan migrate:status` dan jalankan `php artisan migrate` sebelum debugging kode.
