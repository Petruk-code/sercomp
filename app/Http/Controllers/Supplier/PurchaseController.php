<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Support\AuditLogger;
use App\Support\InvoiceNumber;
use App\Support\StockManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the purchases.
     */
    public function index(Request $request): Response
    {
        $purchases = Purchase::query()
            ->with(['supplier:id,name', 'receiver:id,name'])
            ->when($request->search, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('purchase_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', "%{$search}%"));
            }))
            ->when($request->payment_status, fn ($query, $status) => $query->where('payment_status', $status))
            ->when($request->overdue, fn ($query) => $query->where('payment_status', '!=', 'paid')->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString()))
            ->when($request->date_from, fn ($query, $date) => $query->whereDate('purchase_date', '>=', $date))
            ->when($request->date_to, fn ($query, $date) => $query->whereDate('purchase_date', '<=', $date))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('supplier/purchases/index', [
            'purchases' => $purchases,
            'filters' => $request->only(['search', 'payment_status', 'overdue', 'date_from', 'date_to']),
            'paymentStatusLabels' => Purchase::PAYMENT_STATUS_LABELS,
        ]);
    }

    /**
     * Show the form for creating a new purchase.
     */
    public function create(): Response
    {
        return Inertia::render('supplier/purchases/form', [
            'purchase' => null,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->with('category:id,name')->orderBy('name')->get(['id', 'name', 'unit', 'purchase_price', 'stock']),
        ]);
    }

    /**
     * Store a newly created purchase.
     */
    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        $purchase = DB::transaction(function () use ($request): Purchase {
            $rows = $this->rowsFromItems($request->input('items', []));

            $subtotal = collect($rows)->sum('subtotal');
            $discount = (float) $request->input('discount', 0);
            $total = max($subtotal - $discount, 0);

            $purchase = Purchase::query()->create([
                'purchase_number' => InvoiceNumber::next('PO'),
                'supplier_id' => $request->input('supplier_id'),
                'user_id' => $request->user()->id,
                'purchase_date' => $request->input('purchase_date'),
                'due_date' => $request->input('due_date'),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => 0,
                'payment_status' => 'unpaid',
                'notes' => $request->input('notes'),
            ]);

            $purchase->items()->createMany($rows);

            return $purchase;
        });

        AuditLogger::record(
            'purchase.created',
            __('Pembelian :number dibuat (:status, Rp :total).', [
                'number' => $purchase->purchase_number,
                'status' => Purchase::PAYMENT_STATUS_LABELS[$purchase->payment_status] ?? $purchase->payment_status,
                'total' => number_format((float) $purchase->total, 0, ',', '.'),
            ]),
            $purchase,
            [],
            [
                'total' => $purchase->total,
                'payment_status' => $purchase->payment_status,
                'paid_amount' => $purchase->paid_amount,
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pembelian '.$purchase->purchase_number.' berhasil dicatat.')]);

        return redirect()->route('supplier.purchases.show', $purchase);
    }

    /**
     * Display the purchase.
     */
    public function show(Purchase $purchase): Response
    {
        return Inertia::render('supplier/purchases/show', [
            'purchase' => $purchase->load(['supplier', 'receiver:id,name', 'items.product:id,name,unit', 'payments']),
            'paymentMethodLabels' => Purchase::PAYMENT_METHOD_LABELS,
            'paymentStatusLabels' => Purchase::PAYMENT_STATUS_LABELS,
        ]);
    }

    /**
     * Show the form for editing the purchase.
     */
    public function edit(Purchase $purchase): Response
    {
        return Inertia::render('supplier/purchases/form', [
            'purchase' => $purchase->load('items'),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->with('category:id,name')->orderBy('name')->get(['id', 'name', 'unit', 'purchase_price', 'stock']),
        ]);
    }

    /**
     * Update the purchase, re-adjusting stock for the changed items.
     */
    public function update(UpdatePurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        if ($purchase->returns()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Pembelian dengan retur tidak dapat diubah.')]);

            return back();
        }

        $oldTotal = (float) $purchase->total;

        $purchase = DB::transaction(function () use ($request, $purchase): Purchase {
            $locked = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            foreach ($locked->items as $item) {
                if ($item->product_id !== null) {
                    StockManager::decrement($item->product, $item->quantity, __('Edit pembelian'));
                }
            }

            $locked->items()->delete();

            $rows = $this->rowsFromItems($request->input('items', []));

            $subtotal = collect($rows)->sum('subtotal');
            $discount = (float) $request->input('discount', 0);
            $total = max($subtotal - $discount, 0);
            $paid = min((float) $locked->paid_amount, $total);
            $status = $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

            $locked->update([
                'supplier_id' => $request->input('supplier_id'),
                'purchase_date' => $request->input('purchase_date'),
                'due_date' => $request->input('due_date'),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paid,
                'payment_status' => $status,
                'notes' => $request->input('notes'),
            ]);

            $locked->items()->createMany($rows);

            return $locked;
        });

        AuditLogger::record(
            'purchase.updated',
            __('Pembelian :number diperbarui.', ['number' => $purchase->purchase_number]),
            $purchase,
            ['total' => $oldTotal],
            ['total' => $purchase->total],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pembelian '.$purchase->purchase_number.' berhasil diperbarui.')]);

        return redirect()->route('supplier.purchases.show', $purchase);
    }

    /**
     * Remove the purchase and reverse the stock it added.
     */
    public function destroy(Purchase $purchase): RedirectResponse
    {
        if ($purchase->returns()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Pembelian dengan retur tidak dapat dihapus.')]);

            return back();
        }

        DB::transaction(function () use ($purchase): void {
            foreach ($purchase->items as $item) {
                if ($item->product_id !== null) {
                    StockManager::decrement($item->product, $item->quantity, __('Hapus pembelian'));
                }
            }

            $purchase->delete();
        });

        AuditLogger::record(
            'purchase.deleted',
            __('Pembelian :number dihapus.', ['number' => $purchase->purchase_number]),
            $purchase,
            ['total' => $purchase->total],
            [],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pembelian '.$purchase->purchase_number.' berhasil dihapus.')]);

        return redirect()->route('supplier.purchases.index');
    }

    /**
     * Record an installment / tempo payment against the purchase.
     */
    public function pay(Request $request, Purchase $purchase): RedirectResponse
    {
        abort_if($purchase->payment_status === 'paid', 422, __('Pembelian ini sudah lunas.'));

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(Purchase::PAYMENT_METHODS)],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $amount = (float) $validated['amount'];
        $total = (float) $purchase->total;
        $remaining = $purchase->balanceDue();
        $oldStatus = $purchase->payment_status;
        $oldPaid = (float) $purchase->paid_amount;

        if ($amount > $remaining) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Jumlah pembayaran melebihi sisa hutang.')]);

            return back();
        }

        $purchase = DB::transaction(function () use ($purchase, $amount, $validated, $total): Purchase {
            $locked = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            $locked->payments()->create([
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'paid_at' => $validated['paid_at'] ?? now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $paid = (float) $locked->paid_amount + $amount;
            $status = $paid >= $total ? 'paid' : 'partial';

            $locked->update([
                'paid_amount' => $paid,
                'payment_status' => $status,
            ]);

            return $locked;
        });

        AuditLogger::record(
            'purchase.payment',
            __('Pembayaran :number: +Rp :amount (:status).', [
                'number' => $purchase->purchase_number,
                'amount' => number_format($amount, 0, ',', '.'),
                'status' => Purchase::PAYMENT_STATUS_LABELS[$purchase->payment_status] ?? $purchase->payment_status,
            ]),
            $purchase,
            ['payment_status' => $oldStatus, 'paid_amount' => $oldPaid],
            ['payment_status' => $purchase->payment_status, 'paid_amount' => $purchase->paid_amount],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $purchase->payment_status === 'paid'
                ? __('Pembelian '.$purchase->purchase_number.' lunas.')
                : __('Pembayaran dicatat. Sisa Rp '.number_format($purchase->balanceDue(), 0, ',', '.').'.'),
        ]);

        return back();
    }

    /**
     * Build purchase-item rows from validated request input, increasing stock.
     *
     * @param  array<int, array{product_id?: int|string|null, name?: string|null, quantity?: int|string, purchase_price?: int|string|float}>  $items
     * @return array<int, array{product_id: int|null, name: string, quantity: int, purchase_price: float, subtotal: float}>
     */
    protected function rowsFromItems(array $items): array
    {
        $rows = [];

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $product = $productId === null
                ? null
                : Product::query()->whereKey($productId)->first();

            $quantity = (int) ($item['quantity'] ?? 0);
            $price = (float) ($item['purchase_price'] ?? 0);

            if ($product) {
                StockManager::increment($product, $quantity, __('Pembelian supplier'));
            }

            $rows[] = [
                'product_id' => $product?->id,
                'name' => $product->name ?? (string) ($item['name'] ?? ''),
                'quantity' => $quantity,
                'purchase_price' => $price,
                'subtotal' => $price * $quantity,
            ];
        }

        return $rows;
    }
}
