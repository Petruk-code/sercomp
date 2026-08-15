<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\Supplier;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Resolve the report date range from the request.
     *
     * @return array{CarbonInterface, CarbonInterface}
     */
    private function dateRange(Request $request): array
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->string('date_from'))->startOfDay()
            : null;

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->string('date_to'))->endOfDay()
            : null;

        return [
            $dateFrom ?? now()->startOfMonth(),
            $dateTo ?? now()->endOfDay(),
        ];
    }

    /**
     * Sales report.
     */
    public function sales(Request $request): Response
    {
        [$dateFrom, $dateTo] = $this->dateRange($request);

        $sales = Sale::query()
            ->with(['customer:id,name'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $summary = Sale::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('COUNT(*) as total_transactions')
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(CASE WHEN type = "retail" THEN total ELSE 0 END), 0) as retail_revenue')
            ->selectRaw('COALESCE(SUM(CASE WHEN type = "service" THEN total ELSE 0 END), 0) as service_revenue')
            ->first();

        return Inertia::render('reports/sales', [
            'sales' => $sales,
            'summary' => $summary,
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
        ]);
    }

    /**
     * Service report.
     */
    public function services(Request $request): Response
    {
        [$dateFrom, $dateTo] = $this->dateRange($request);

        $orders = ServiceOrder::query()
            ->with(['customer:id,name', 'technician:id,name', 'sale:id,payment_status,total,paid_amount'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $summary = ServiceOrder::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('COALESCE(SUM(CASE WHEN status NOT IN ("cancelled") THEN total_cost ELSE 0 END), 0) as billed_cost')
            ->selectRaw('COALESCE(SUM(CASE WHEN status = "taken" THEN total_cost ELSE 0 END), 0) as completed_revenue')
            ->first();

        $statusCounts = ServiceOrder::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('reports/services', [
            'orders' => $orders,
            'summary' => $summary,
            'statusCounts' => $statusCounts,
            'statusLabels' => ServiceOrder::STATUS_LABELS,
            'paymentStatusLabels' => Sale::PAYMENT_STATUS_LABELS,
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
        ]);
    }

    /**
     * Stock report.
     */
    public function stock(Request $request): Response
    {
        $products = Product::query()
            ->with('category:id,name')
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->low_stock, fn ($query) => $query->whereColumn('stock', '<=', 'min_stock')->where('min_stock', '>', 0))
            ->orderBy('stock')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total_products' => Product::query()->count(),
            'total_stock' => Product::query()->sum('stock'),
            'low_stock_products' => Product::query()->whereColumn('stock', '<=', 'min_stock')->where('min_stock', '>', 0)->count(),
            'stock_value' => Product::query()->selectRaw('COALESCE(SUM(stock * purchase_price), 0) as total')->value('total'),
        ];

        return Inertia::render('reports/stock', [
            'products' => $products,
            'summary' => $summary,
            'filters' => $request->only(['search', 'low_stock']),
        ]);
    }

    /**
     * Purchase report (PO per periode & supplier).
     */
    public function purchases(Request $request): Response
    {
        [$dateFrom, $dateTo] = $this->dateRange($request);

        $supplierId = $request->filled('supplier_id')
            ? (int) $request->integer('supplier_id')
            : null;

        $paymentStatus = null;
        if ($request->filled('payment_status')) {
            $candidate = (string) $request->string('payment_status');

            if (in_array($candidate, Purchase::PAYMENT_STATUSES, true)) {
                $paymentStatus = $candidate;
            }
        }

        $purchases = Purchase::query()
            ->with('supplier:id,name')
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->when($supplierId !== null, fn ($query) => $query->where('supplier_id', $supplierId))
            ->when($paymentStatus !== null, fn ($query) => $query->where('payment_status', $paymentStatus))
            ->orderByDesc('purchase_date')
            ->paginate(20)
            ->withQueryString();

        $summary = Purchase::query()
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->when($supplierId !== null, fn ($query) => $query->where('supplier_id', $supplierId))
            ->when($paymentStatus !== null, fn ($query) => $query->where('payment_status', $paymentStatus))
            ->selectRaw('COUNT(*) as total_purchases')
            ->selectRaw('COALESCE(SUM(total), 0) as total_value')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as total_paid')
            ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as outstanding')
            ->first();

        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('reports/purchases', [
            'purchases' => $purchases,
            'summary' => $summary,
            'suppliers' => $suppliers,
            'paymentStatusLabels' => Purchase::PAYMENT_STATUS_LABELS,
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'supplier_id' => $supplierId,
                'payment_status' => $paymentStatus,
            ],
        ]);
    }

    /**
     * Mutasi hutang per supplier (aging: belum jatuh tempo, 0-30, 31-60, >60 hari).
     */
    public function debts(Request $request): Response
    {
        $today = now()->startOfDay();

        $supplierId = $request->filled('supplier_id')
            ? (int) $request->integer('supplier_id')
            : null;

        $openPurchases = Purchase::query()
            ->where('payment_status', '!=', 'paid')
            ->with('supplier:id,name')
            ->when($supplierId !== null, fn ($query) => $query->where('supplier_id', $supplierId))
            ->get(['id', 'supplier_id', 'total', 'paid_amount', 'due_date', 'purchase_date']);

        $rows = $openPurchases
            ->groupBy('supplier_id')
            ->map(function ($group) use ($today): array {
                $supplier = $group->first()?->supplier;

                $buckets = [
                    'current' => 0.0,
                    'overdue_0_30' => 0.0,
                    'overdue_31_60' => 0.0,
                    'overdue_60' => 0.0,
                ];

                foreach ($group as $purchase) {
                    $balance = $purchase->balanceDue();

                    if ($balance <= 0) {
                        continue;
                    }

                    $dueDate = $purchase->due_date?->startOfDay();

                    if ($dueDate === null || $dueDate->gte($today)) {
                        $buckets['current'] += $balance;

                        continue;
                    }

                    $daysOverdue = (int) $dueDate->diffInDays($today);

                    if ($daysOverdue <= 30) {
                        $buckets['overdue_0_30'] += $balance;
                    } elseif ($daysOverdue <= 60) {
                        $buckets['overdue_31_60'] += $balance;
                    } else {
                        $buckets['overdue_60'] += $balance;
                    }
                }

                return [
                    'id' => $supplier?->id,
                    'name' => $supplier->name ?? 'Supplier tidak dikenal',
                    'open_count' => $group->count(),
                    ...$buckets,
                    'outstanding' => round(array_sum($buckets), 2),
                ];
            })
            ->sortByDesc('outstanding')
            ->values();

        $summary = [
            'outstanding' => round((float) $rows->sum('outstanding'), 2),
            'overdue' => round((float) $rows->sum(
                fn (array $row): float => $row['overdue_0_30'] + $row['overdue_31_60'] + $row['overdue_60'],
            ), 2),
            'suppliers' => $rows->count(),
        ];

        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('reports/debts', [
            'rows' => $rows,
            'summary' => $summary,
            'suppliers' => $suppliers,
            'filters' => [
                'supplier_id' => $supplierId,
            ],
        ]);
    }
}
