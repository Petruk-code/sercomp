<?php

namespace App\Http\Controllers\Sales;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\ServiceAlreadyCheckedOutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Support\AuditLogger;
use App\Support\InvoiceNumber;
use App\Support\StockManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    /**
     * Display a listing of the sales.
     */
    public function index(Request $request): Response
    {
        $sales = Sale::query()
            ->with(['customer:id,name', 'cashier:id,name', 'serviceOrder:id,invoice_number,item_name'])
            ->when($request->search, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%"));
            }))
            ->when($request->type, fn ($query, $type) => $query->where('type', $type))
            ->when($request->payment_status, fn ($query, $status) => $query->where('payment_status', $status))
            ->when($request->date_from, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('sales/index', [
            'sales' => $sales,
            'filters' => $request->only(['search', 'type', 'payment_status', 'date_from', 'date_to']),
            'paymentStatusLabels' => Sale::PAYMENT_STATUS_LABELS,
        ]);
    }

    /**
     * Show the point of sale page.
     */
    public function create(): Response
    {
        return Inertia::render('sales/pos', [
            'products' => Product::query()->with('category:id,name')->orderBy('name')->get(),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'phone']),
            'paymentMethods' => Sale::PAYMENT_METHODS,
            'paymentMethodLabels' => Sale::PAYMENT_METHOD_LABELS,
        ]);
    }

    /**
     * Store a new retail sale.
     */
    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $items = $request->input('items', []);

        if (empty($items)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Keranjang masih kosong.')]);

            return back();
        }

        try {
            $sale = DB::transaction(function () use ($request, $items): Sale {
                $rows = [];

                foreach ($items as $item) {
                    $productId = $item['product_id'] ?? null;
                    $product = $productId === null
                        ? null
                        : Product::query()->lockForUpdate()->whereKey($productId)->first();

                    if ($product) {
                        if ($product->stock < $item['quantity']) {
                            throw new InsufficientStockException($product->name);
                        }

                        StockManager::decrement($product, $item['quantity'], __('Penjualan retail'));
                    }

                    $rows[] = [
                        'product_id' => $product?->id,
                        'name' => $product->name ?? $item['name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $item['price'] * $item['quantity'],
                    ];
                }

                $subtotal = collect($rows)->sum('subtotal');
                $discount = (float) $request->input('discount', 0);
                $total = max($subtotal - $discount, 0);
                $paid = (float) $request->input('paid_amount', $total);

                $sale = Sale::query()->create([
                    'invoice_number' => InvoiceNumber::next('INV'),
                    'type' => 'retail',
                    'customer_id' => $request->input('customer_id'),
                    'user_id' => $request->user()->id,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total' => $total,
                    'payment_method' => $request->input('payment_method'),
                    'payment_status' => $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                    'paid_amount' => $paid,
                    'notes' => $request->input('notes'),
                ]);

                $sale->items()->createMany($rows);

                return $sale;
            });
        } catch (InsufficientStockException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Stok :name tidak mencukupi.', ['name' => $exception->productName]),
            ]);

            return back();
        }

        AuditLogger::record(
            'sale.created',
            __('Penjualan :invoice dibuat (:status).', [
                'invoice' => $sale->invoice_number,
                'status' => Sale::PAYMENT_STATUS_LABELS[$sale->payment_status] ?? $sale->payment_status,
            ]),
            $sale,
            [],
            [
                'total' => $sale->total,
                'payment_status' => $sale->payment_status,
                'paid_amount' => $sale->paid_amount,
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaksi '.$sale->invoice_number.' berhasil.')]);

        return redirect()->route('sales.show', $sale);
    }

    /**
     * Checkout a completed service order (combines labor + spareparts).
     */
    public function checkout(StoreSaleRequest $request, ServiceOrder $service): RedirectResponse
    {
        try {
            $sale = DB::transaction(function () use ($request, $service): Sale {
                $locked = ServiceOrder::query()->lockForUpdate()->findOrFail($service->id);

                if ($locked->sale()->exists()) {
                    throw new ServiceAlreadyCheckedOutException;
                }

                if ($request->filled('labor_cost')) {
                    $locked->update(['labor_cost' => (float) $request->input('labor_cost')]);
                    $locked->recalculateTotal();
                    $locked->refresh();
                }

                $rows = [];

                if ((float) $locked->labor_cost > 0) {
                    $rows[] = [
                        'product_id' => null,
                        'name' => 'Jasa servis: '.$locked->item_name,
                        'quantity' => 1,
                        'price' => $locked->labor_cost,
                        'subtotal' => $locked->labor_cost,
                    ];
                }

                foreach ($locked->items as $item) {
                    $rows[] = [
                        'product_id' => $item->product_id,
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                    ];
                }

                $subtotal = collect($rows)->sum('subtotal');
                $discount = (float) $request->input('discount', 0);
                $total = max($subtotal - $discount, 0);
                $paid = (float) $request->input('paid_amount', $total);

                $sale = Sale::query()->create([
                    'invoice_number' => InvoiceNumber::next('INV'),
                    'type' => 'service',
                    'service_order_id' => $locked->id,
                    'customer_id' => $locked->customer_id,
                    'user_id' => $request->user()->id,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total' => $total,
                    'payment_method' => $request->input('payment_method'),
                    'payment_status' => $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                    'paid_amount' => $paid,
                    'notes' => $request->input('notes'),
                ]);

                $sale->items()->createMany($rows);

                if ($sale->payment_status === 'paid') {
                    $locked->update(['status' => 'taken']);
                }

                return $sale;
            });
        } catch (ServiceAlreadyCheckedOutException) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Servis ini sudah memiliki transaksi.')]);

            return back();
        }

        AuditLogger::record(
            'sale.created',
            __('Penjualan servis :invoice dibuat (:status).', [
                'invoice' => $sale->invoice_number,
                'status' => Sale::PAYMENT_STATUS_LABELS[$sale->payment_status] ?? $sale->payment_status,
            ]),
            $sale,
            [],
            [
                'total' => $sale->total,
                'payment_status' => $sale->payment_status,
                'paid_amount' => $sale->paid_amount,
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Nota '.$sale->invoice_number.' berhasil dibuat.')]);

        return redirect()->route('sales.show', $sale);
    }

    /**
     * Accept an additional payment to settle a partially paid sale.
     */
    public function completePayment(Request $request, Sale $sale): RedirectResponse
    {
        abort_if($sale->payment_status === 'paid', 422, __('Transaksi ini sudah lunas.'));

        $request->validate([
            'paid_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $amount = (float) $request->input('paid_amount');
        $total = (float) $sale->total;
        $remaining = max($total - (float) $sale->paid_amount, 0);
        $oldStatus = $sale->payment_status;
        $oldPaid = (float) $sale->paid_amount;

        if ($amount <= 0) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Jumlah pembayaran harus lebih dari 0.')]);

            return back();
        }

        $sale = DB::transaction(function () use ($sale, $amount, $total, $remaining): Sale {
            $paid = (float) $sale->paid_amount + min($amount, $remaining);
            $status = $paid >= $total ? 'paid' : 'partial';

            $sale->update([
                'paid_amount' => $paid,
                'payment_status' => $status,
            ]);

            if ($status === 'paid' && $sale->service_order_id !== null) {
                $sale->serviceOrder->update(['status' => 'taken']);
            }

            return $sale;
        });

        AuditLogger::record(
            'sale.payment',
            __('Pembayaran :invoice: +Rp :amount (:status).', [
                'invoice' => $sale->invoice_number,
                'amount' => number_format($amount, 0, ',', '.'),
                'status' => Sale::PAYMENT_STATUS_LABELS[$sale->payment_status] ?? $sale->payment_status,
            ]),
            $sale,
            ['payment_status' => $oldStatus, 'paid_amount' => $oldPaid],
            ['payment_status' => $sale->payment_status, 'paid_amount' => $sale->paid_amount],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $sale->payment_status === 'paid'
                ? __('Pembayaran lunas. Servis ditandai sebagai diambil.')
                : __('Pembayaran dicatat. Sisa Rp '.number_format(max((float) $sale->total - (float) $sale->paid_amount, 0), 0, ',', '.').'.'),
        ]);

        return back();
    }

    /**
     * Display the sale.
     */
    public function show(Sale $sale): Response
    {
        return Inertia::render('sales/show', [
            'sale' => $sale->load(['customer', 'cashier:id,name', 'serviceOrder:id,invoice_number,item_name', 'items']),
            'paymentMethodLabels' => Sale::PAYMENT_METHOD_LABELS,
            'paymentStatusLabels' => Sale::PAYMENT_STATUS_LABELS,
        ]);
    }

    /**
     * Stream a printable receipt PDF for the sale.
     */
    public function print(Sale $sale): HttpResponse
    {
        $sale->load(['customer', 'cashier:id,name', 'items']);

        $pdf = Pdf::loadView('pdf.receipt', [
            'sale' => $sale,
            'paymentMethodLabels' => Sale::PAYMENT_METHOD_LABELS,
            'paymentStatusLabels' => Sale::PAYMENT_STATUS_LABELS,
        ])->setPaper('a5');

        return $pdf->stream('nota-'.$sale->invoice_number.'.pdf');
    }
}
