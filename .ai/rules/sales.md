---
paths:
  - 'app/Http/Controllers/Sales/**'
---

# Sales

## Service payment flow: checkout confirms final labor fee, payment settles via sales.payment
Checkout (sales.checkout) accepts optional labor_cost to confirm the FINAL jasa fee at payment time, then creates the service Sale; it stays 'done' until the Sale is fully paid, and paid => service status becomes 'taken' (also in SaleController::completePayment via POST sales/{sale}/payment). ServiceOrderController::updateStatus blocks 'taken' unless the sale is 'paid'. Do not bill estimated_cost — it is informational (shown on tracking); billed total = labor_cost (final) + sparepart items.
