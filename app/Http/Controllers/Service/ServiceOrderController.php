<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceStatusRequest;
use App\Http\Requests\StoreServiceItemRequest;
use App\Http\Requests\StoreServiceOrderRequest;
use App\Http\Requests\UpdateServiceOrderRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\User;
use App\Support\InvoiceNumber;
use App\Support\StockManager;
use App\Support\WhatsApp;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ServiceOrderController extends Controller
{
    /**
     * Display a listing of the service orders.
     */
    public function index(Request $request): Response
    {
        $orders = ServiceOrder::query()
            ->with(['customer:id,name,phone', 'technician:id,name', 'sale:id,payment_status'])
            ->when($request->user()->hasRole('technician'), fn ($query) => $query->where(fn ($query) => $query
                ->where('technician_id', $request->user()->id)
                ->orWhereNull('technician_id')))
            ->when($request->search, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('item_name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%"));
            }))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->warranty, fn ($query, $warranty) => match ($warranty) {
                'active' => $query
                    ->whereNotNull('warranty_days')
                    ->where(fn ($query) => $query->whereNull('warranty_end_date')->orWhereDate('warranty_end_date', '>=', now()->toDateString())),
                'expired' => $query
                    ->whereNotNull('warranty_days')
                    ->whereDate('warranty_end_date', '<', now()->toDateString()),
                'none' => $query->where(fn ($query) => $query->whereNull('warranty_days')->orWhere('warranty_days', 0)),
                default => $query,
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('services/index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'warranty']),
            'statuses' => ServiceOrder::STATUSES,
            'statusLabels' => ServiceOrder::STATUS_LABELS,
        ]);
    }

    /**
     * Show the form for creating a new service order.
     */
    public function create(Request $request): Response
    {
        $this->authorizeManagement($request);

        return Inertia::render('services/form', [
            'order' => null,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'phone']),
            'technicians' => User::query()->role('technician')->orderBy('name')->get(['id', 'name']),
            'statuses' => ServiceOrder::STATUSES,
            'statusLabels' => ServiceOrder::STATUS_LABELS,
        ]);
    }

    /**
     * Store a newly created service order.
     */
    public function store(StoreServiceOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['invoice_number'] = InvoiceNumber::next('SRV');

        $order = ServiceOrder::query()->create($data);
        $order->recalculateTotal();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Servis '.$order->invoice_number.' berhasil dibuat.')]);

        return redirect()->route('services.show', $order);
    }

    /**
     * Display the service order.
     */
    public function show(ServiceOrder $service): Response
    {
        $service->load(['customer', 'receiver:id,name', 'technician:id,name', 'items.product:id,name,unit', 'sale']);

        return Inertia::render('services/show', [
            'order' => $service,
            'cardUrl' => WhatsApp::trackingUrl($service),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'phone']),
            'technicians' => User::query()->role('technician')->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('stock', '>', 0)->orderBy('name')->get(['id', 'name', 'selling_price', 'stock', 'unit']),
            'statuses' => ServiceOrder::STATUSES,
            'statusLabels' => ServiceOrder::STATUS_LABELS,
            'paymentStatusLabels' => Sale::PAYMENT_STATUS_LABELS,
        ]);
    }

    /**
     * Send the customer their tracking link on WhatsApp through Fonnte.
     */
    public function sendWhatsapp(Request $request, ServiceOrder $service): RedirectResponse
    {
        $this->authorizeManagement($request);

        $phone = $service->customer?->phone;

        if (blank($phone)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Nomor WhatsApp pelanggan belum tersedia.')]);

            return back();
        }

        if (! WhatsApp::isConfigured()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Gateway WhatsApp belum dikonfigurasi.')]);

            return back();
        }

        $payload = WhatsApp::send($phone, WhatsApp::trackingMessageFor($service));

        if (($payload['status'] ?? false) === true) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Pesan WhatsApp terkirim ke '.$service->customer->name.'.')]);
        } else {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Pesan WhatsApp gagal dikirim.')]);
        }

        return back();
    }

    /**
     * Stream a printable service receipt PDF (tanda terima servis).
     */
    public function print(ServiceOrder $service): HttpResponse
    {
        $service->load(['customer', 'receiver:id,name', 'technician:id,name', 'items']);

        $trackingUrl = route('tracking', ['token' => $service->tracking_token]);

        $qr = (new Builder(
            writer: new PngWriter,
            data: $trackingUrl,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 220,
            margin: 10,
        ))->build();

        $pdf = Pdf::loadView('pdf.service_receipt', [
            'order' => $service,
            'statusLabels' => ServiceOrder::STATUS_LABELS,
            'trackingUrl' => $trackingUrl,
            'qrDataUri' => $qr->getDataUri(),
        ])->setPaper('a5');

        return $pdf->stream('tanda-terima-'.$service->invoice_number.'.pdf');
    }

    /**
     * Show the form for editing the service order.
     */
    public function edit(Request $request, ServiceOrder $service): Response
    {
        $this->authorizeManagement($request);

        return Inertia::render('services/form', [
            'order' => $service,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'phone']),
            'technicians' => User::query()->role('technician')->orderBy('name')->get(['id', 'name']),
            'statuses' => ServiceOrder::STATUSES,
            'statusLabels' => ServiceOrder::STATUS_LABELS,
        ]);
    }

    /**
     * Update the service order.
     */
    public function update(UpdateServiceOrderRequest $request, ServiceOrder $service): RedirectResponse
    {
        $service->update($request->validated());
        $service->recalculateTotal();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Data servis berhasil diperbarui.')]);

        return redirect()->route('services.show', $service);
    }

    /**
     * Remove the service order.
     */
    public function destroy(Request $request, ServiceOrder $service): RedirectResponse
    {
        $this->authorizeManagement($request);

        $service->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Servis berhasil dihapus.')]);

        return back();
    }

    /**
     * Add a sparepart item to the service order.
     */
    public function addItem(StoreServiceItemRequest $request, ServiceOrder $service): RedirectResponse
    {
        if ($service->sale()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Servis sudah di-checkout, sparepart tidak dapat diubah lagi.')]);

            return back();
        }

        return DB::transaction(function () use ($request, $service): RedirectResponse {
            $name = $request->string('name')->toString();
            $product = null;

            if ($request->filled('product_id')) {
                $product = Product::query()->lockForUpdate()->findOrFail($request->integer('product_id'));
                $name = $product->name;

                if ($product->stock < $request->integer('quantity')) {
                    Inertia::flash('toast', ['type' => 'error', 'message' => __('Stok '.$product->name.' tidak mencukupi.')]);

                    return back();
                }
            }

            $price = (float) $request->input('price');
            $quantity = $request->integer('quantity');

            $service->items()->create([
                'product_id' => $product?->id,
                'name' => $name,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $price * $quantity,
            ]);

            if ($product) {
                StockManager::decrement($product, $quantity, __('Tambah sparepart servis'));
            }

            $service->recalculateTotal();
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Sparepart berhasil ditambahkan.')]);

            return back();
        });
    }

    /**
     * Remove a sparepart item from the service order.
     */
    public function removeItem(ServiceOrder $service, ServiceOrderItem $item): RedirectResponse
    {
        if ($service->sale()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Servis sudah di-checkout, sparepart tidak dapat diubah lagi.')]);

            return back();
        }

        return DB::transaction(function () use ($service, $item): RedirectResponse {
            abort_unless($item->service_order_id === $service->id, 404);

            if ($item->product_id) {
                StockManager::increment($item->product, $item->quantity, __('Hapus sparepart servis'));
            }

            $item->delete();
            $service->recalculateTotal();

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Sparepart berhasil dihapus.')]);

            return back();
        });
    }

    /**
     * Update the service order status.
     */
    public function updateStatus(ServiceStatusRequest $request, ServiceOrder $service): RedirectResponse
    {
        return DB::transaction(function () use ($request, $service): RedirectResponse {
            $newStatus = $request->input('status');
            $current = $service->status;

            if ($current === $newStatus) {
                return back();
            }

            if ($newStatus === 'cancelled') {
                if ($service->sale()->exists()) {
                    Inertia::flash('toast', ['type' => 'error', 'message' => __('Servis sudah memiliki transaksi, tidak dapat dibatalkan.')]);

                    return back();
                }

                if ($current === 'taken') {
                    Inertia::flash('toast', ['type' => 'error', 'message' => __('Servis yang sudah diambil tidak dapat dibatalkan.')]);

                    return back();
                }

                foreach ($service->items()->whereNotNull('product_id')->get() as $item) {
                    StockManager::increment($item->product, $item->quantity, __('Batalkan servis'));
                }

                $service->items()->delete();
            }

            if ($newStatus === 'taken' && $service->total_cost > 0) {
                $sale = $service->sale()->first();

                if (! $sale) {
                    Inertia::flash('toast', ['type' => 'error', 'message' => __('Servis harus di-checkout terlebih dahulu sebelum diambil.')]);

                    return back();
                }

                if ($sale->payment_status !== 'paid') {
                    Inertia::flash('toast', ['type' => 'error', 'message' => __('Servis harus lunas sebelum diambil.')]);

                    return back();
                }
            }

            $service->update(['status' => $newStatus]);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Status servis menjadi '.ServiceOrder::STATUS_LABELS[$newStatus].'.')]);

            return back();
        });
    }

    /**
     * Only staff that receive service orders may manage their data.
     */
    protected function authorizeManagement(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'owner', 'cashier']), 403);
    }
}
