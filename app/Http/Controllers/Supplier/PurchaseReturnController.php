<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseReturnRequest;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Support\AuditLogger;
use App\Support\InvoiceNumber;
use App\Support\StockManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseReturnController extends Controller
{
    /**
     * Display a listing of the returns.
     */
    public function index(Request $request): Response
    {
        $returns = PurchaseReturn::query()
            ->with(['purchase:id,purchase_number,supplier_id', 'purchase.supplier:id,name', 'creator:id,name'])
            ->when($request->search, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('return_number', 'like', "%{$search}%")
                    ->orWhereHas('purchase.supplier', fn ($query) => $query->where('name', 'like', "%{$search}%"));
            }))
            ->when($request->result_type, fn ($query, $type) => $query->where('result_type', $type))
            ->when($request->date_from, fn ($query, $date) => $query->whereDate('return_date', '>=', $date))
            ->when($request->date_to, fn ($query, $date) => $query->whereDate('return_date', '<=', $date))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('supplier/returns/index', [
            'returns' => $returns,
            'filters' => $request->only(['search', 'result_type', 'date_from', 'date_to']),
            'resultTypeLabels' => PurchaseReturn::RESULT_TYPE_LABELS,
        ]);
    }

    /**
     * Show the form for creating a new return against a purchase.
     */
    public function create(Purchase $purchase): Response
    {
        $purchase->load(['supplier', 'items.product:id,name,unit']);

        return Inertia::render('supplier/returns/form', [
            'purchase' => $purchase,
            'alreadyReturned' => $this->alreadyReturnedQuantities($purchase),
            'resultTypeLabels' => PurchaseReturn::RESULT_TYPE_LABELS,
        ]);
    }

    /**
     * Store a newly created return, reducing stock (and the purchase total for
     * a potong-hutang return).
     */
    public function store(StorePurchaseReturnRequest $request, Purchase $purchase): RedirectResponse
    {
        $return = DB::transaction(function () use ($request, $purchase): PurchaseReturn {
            $locked = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            $rows = $this->rowsFromItems($locked, $request->input('items', []));
            $total = (float) collect($rows)->sum('subtotal');

            $return = PurchaseReturn::query()->create([
                'return_number' => InvoiceNumber::next('RTN'),
                'purchase_id' => $locked->id,
                'user_id' => $request->user()->id,
                'return_date' => $request->input('return_date'),
                'result_type' => $request->input('result_type'),
                'reason' => $request->input('reason'),
                'total' => $total,
                'notes' => $request->input('notes'),
            ]);

            $return->items()->createMany($rows);

            if ($request->input('result_type') === 'debt_deduction') {
                $this->applyDebtDeduction($locked, $total);
            }

            return $return;
        });

        AuditLogger::record(
            'purchase_return.created',
            __('Retur :number dibuat (:result).', [
                'number' => $return->return_number,
                'result' => PurchaseReturn::RESULT_TYPE_LABELS[$return->result_type] ?? $return->result_type,
            ]),
            $return,
            [],
            [
                'result_type' => $return->result_type,
                'total' => $return->total,
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Retur '.$return->return_number.' berhasil dicatat.')]);

        return redirect()->route('supplier.returns.show', $return);
    }

    /**
     * Display the return.
     */
    public function show(PurchaseReturn $purchaseReturn): Response
    {
        return Inertia::render('supplier/returns/show', [
            'purchaseReturn' => $purchaseReturn->load([
                'purchase.supplier',
                'purchase.receiver:id,name',
                'items.product:id,name,unit',
                'creator:id,name',
            ]),
            'resultTypeLabels' => PurchaseReturn::RESULT_TYPE_LABELS,
        ]);
    }

    /**
     * Remove a non-debt-deduction return and restore the stock it removed.
     */
    public function destroy(PurchaseReturn $purchaseReturn): RedirectResponse
    {
        if ($purchaseReturn->result_type === 'debt_deduction') {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Retur potong hutang tidak dapat dihapus.')]);

            return back();
        }

        DB::transaction(function () use ($purchaseReturn): void {
            foreach ($purchaseReturn->items as $item) {
                if ($item->product_id !== null) {
                    StockManager::increment($item->product, $item->quantity, __('Hapus retur'));
                }
            }

            $purchaseReturn->delete();
        });

        AuditLogger::record(
            'purchase_return.deleted',
            __('Retur :number dihapus.', ['number' => $purchaseReturn->return_number]),
            $purchaseReturn,
            ['total' => $purchaseReturn->total],
            [],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Retur '.$purchaseReturn->return_number.' berhasil dihapus.')]);

        return redirect()->route('supplier.returns.index');
    }

    /**
     * Recompute a purchase's totals after a potong-hutang return, keeping the
     * payment status consistent with paid_amount vs total.
     */
    private function applyDebtDeduction(Purchase $purchase, float $deduction): void
    {
        $newTotal = max((float) $purchase->total - $deduction, 0);
        $paid = min((float) $purchase->paid_amount, $newTotal);
        $status = $paid >= $newTotal ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

        $purchase->update([
            'total' => $newTotal,
            'paid_amount' => $paid,
            'payment_status' => $status,
        ]);
    }

    /**
     * Validate return lines against the purchase and reduce stock.
     *
     * @param  array<int, array{purchase_item_id?: int|string, quantity?: int|string, price?: int|string|float}>  $items
     * @return array<int, array{purchase_item_id: int, product_id: int|null, name: string, quantity: int, price: float, subtotal: float}>
     *
     * @throws ValidationException
     */
    protected function rowsFromItems(Purchase $purchase, array $items): array
    {
        $purchaseItems = $purchase->items()->get()->keyBy('id');
        $alreadyReturned = $this->alreadyReturnedQuantities($purchase);
        $rows = [];

        foreach ($items as $index => $item) {
            $purchaseItem = $purchaseItems->get((int) ($item['purchase_item_id'] ?? 0));

            if ($purchaseItem === null) {
                throw ValidationException::withMessages([
                    "items.{$index}.purchase_item_id" => __('Item tidak termasuk pembelian ini.'),
                ]);
            }

            $quantity = (int) ($item['quantity'] ?? 0);
            $available = $purchaseItem->quantity - (int) ($alreadyReturned[$purchaseItem->id] ?? 0);

            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => __('Jumlah retur melebihi sisa item pembelian (tersisa :remaining).', ['remaining' => $available]),
                ]);
            }

            $price = (float) ($item['price'] ?? 0);

            if ($purchaseItem->product_id !== null) {
                StockManager::decrement($purchaseItem->product, $quantity, __('Retur pembelian'));
            }

            $rows[] = [
                'purchase_item_id' => $purchaseItem->id,
                'product_id' => $purchaseItem->product_id,
                'name' => $purchaseItem->name,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $price * $quantity,
            ];
        }

        return $rows;
    }

    /**
     * Quantities already returned per purchase item (used to cap further
     * returns so an item cannot be over-returned).
     *
     * @return array<int, int>
     */
    protected function alreadyReturnedQuantities(Purchase $purchase): array
    {
        $alreadyReturned = [];

        foreach (PurchaseReturnItem::query()
            ->whereHas('purchaseReturn', fn ($query) => $query->where('purchase_id', $purchase->id))
            ->whereNotNull('purchase_item_id')
            ->get() as $item) {
            $id = $item->purchase_item_id;

            if ($id === null) {
                continue;
            }

            $alreadyReturned[$id] = ($alreadyReturned[$id] ?? 0) + (int) $item->quantity;
        }

        return $alreadyReturned;
    }
}
