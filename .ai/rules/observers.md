---
paths:
  - 'app/Observers/**'
---

# Observers

## Warranty fields are observer-managed on ServiceOrder
ServiceOrderObserver sets completed_at when status becomes 'done' and recomputes warranty_end_date = completed_at + warranty_days (also recomputes via saved hook when warranty_days changes on a completed order). Do not write warranty_end_date/completed_at manually — call recomputeWarrantyEndDate() or rely on the observer.

## ServiceOrder status must default to 'pending' on create
ServiceOrder::create() is called without a status (StoreServiceOrderRequest has no status field), so the DB column had a NULL status and ServiceOrderObserver::created crashed inserting a status history with a null status (Integrity constraint 1048). ServiceOrderObserver::creating() now defaults blank status to 'pending', and record() skips when status is blank. Never rely on a caller to pass status.

## No auto WhatsApp — tracking link is manual only (anti-spam)
ServiceOrderObserver::updated() does NOT send WhatsApp to the customer anymore (removed to avoid WhatsApp restrictions from automated sends). The tracking link is delivered only on explicit user action: POST services/{service}/whatsapp (ServiceOrderController::sendWhatsapp, role admin/owner/cashier) → WhatsApp::send + trackingMessageFor. Customer phone lives on the customers table, NOT on service_orders. If a live send fails, check device status first: curl -X POST https://api.fonnte.com/device -H "Authorization: <key>" → device_status must be 'connect'.
