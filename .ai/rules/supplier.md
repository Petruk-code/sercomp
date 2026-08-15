---
paths:
  - 'app/Http/Controllers/Supplier/**'
---

# Supplier

## Purchase stock mutation & payment flow conventions
PurchaseController lives in App\Http\Controllers\Supplier\PurchaseController. Stock changes via Product::increment inside a DB::transaction on store; update() and destroy() must first reverse old item stock (decrement per old item) then re-apply. Payment via supplier.purchases.pay: validate amount <= balanceDue(), create PurchasePayment, then recompute paid_amount + payment_status. rowsFromItems() normalizes validated item values with (int)/(float)/?? casts — PHPStan requires a typed input shape with optional offsets.

## Return processing & debt-deduction conventions
PurchaseReturnController (Supplier namespace) records returns against a purchase. Store: DB::transaction, cap each line's quantity at purchase_item.quantity minus already-returned (alreadyReturnedQuantities sums prior returns), decrement Product stock per product line, RTN number via InvoiceNumber::next('RTN'). result_type 'debt_deduction' reduces the purchase total (NOT paid_amount) and clamps paid_amount + recomputes payment_status so balanceDue drops. ValidationException::withMessages is thrown inside the transaction for business errors (item not on purchase / over quantity). Purchases that have returns cannot be edited or deleted (PurchaseController guards); debt_deduction returns cannot be deleted (others restore stock).
