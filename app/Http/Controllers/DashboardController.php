<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the dashboard with summary stats.
     */
    public function __invoke(Request $request): Response
    {
        if ($request->user()->hasRole('technician')) {
            return $this->technicianDashboard($request);
        }

        return $this->operationsDashboard();
    }

    /**
     * Dashboard for staff (admin/owner/cashier).
     */
    protected function operationsDashboard(): Response
    {
        $today = now()->startOfDay();

        $activeStatuses = ['pending', 'diagnosed', 'in_progress'];

        return Inertia::render('dashboard', [
            'stats' => [
                'sales_today' => Sale::query()->where('created_at', '>=', $today)->sum('total'),
                'sales_today_count' => Sale::query()->where('created_at', '>=', $today)->count(),
                'services_active' => ServiceOrder::query()->whereIn('status', $activeStatuses)->count(),
                'services_today' => ServiceOrder::query()->where('created_at', '>=', $today)->count(),
                'services_done_pending_pickup' => ServiceOrder::query()->where('status', 'done')->count(),
                'low_stock_products' => Product::query()->whereColumn('stock', '<=', 'min_stock')->where('min_stock', '>', 0)->count(),
                'total_products' => Product::query()->count(),
                'total_customers' => Customer::query()->count(),
                'debt_outstanding' => (float) Purchase::query()->where('payment_status', '!=', 'paid')->sum(DB::raw('total - paid_amount')),
                'debt_overdue' => Purchase::query()->where('payment_status', '!=', 'paid')->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString())->count(),
            ],
            'recentServiceOrders' => ServiceOrder::query()
                ->with(['customer:id,name', 'technician:id,name'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentSales' => Sale::query()
                ->with('customer:id,name')
                ->latest()
                ->limit(5)
                ->get(),
            'technicianLoads' => User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'technician'))
                ->withCount(['serviceOrders' => fn ($query) => $query->whereIn('status', $activeStatuses)])
                ->orderByDesc('service_orders_count')
                ->limit(5)
                ->get(['id', 'name']),
            'lowStockProducts' => Product::query()
                ->whereColumn('stock', '<=', 'min_stock')
                ->where('min_stock', '>', 0)
                ->orderByRaw('stock * 1.0 / min_stock asc')
                ->orderBy('name')
                ->limit(5)
                ->get(['id', 'name', 'unit', 'stock', 'min_stock']),
            'serviceQueue' => ServiceOrder::query()
                ->whereIn('status', ['pending', 'diagnosed'])
                ->with('customer:id,name')
                ->oldest()
                ->limit(5)
                ->get(['id', 'invoice_number', 'item_name', 'status', 'created_at']),
            'trends' => [
                '7d' => $this->trendData(7),
                '30d' => $this->trendData(30),
                '12m' => $this->trendData(12, monthly: true),
            ],
        ]);
    }

    /**
     * Daily (or monthly) sales and service-order trend for the given period.
     *
     * Returns an array of ['date' => 'Y-m-d'|'Y-m', 'value' => int] points with
     * every slot in the period filled, defaulting to zero when there is no data.
     *
     * @return array{sales: array<int, array{date: string, value: int}>, services: array<int, array{date: string, value: int}>}
     */
    protected function trendData(int $amount, bool $monthly = false): array
    {
        $start = $monthly
            ? now()->subMonths($amount - 1)->startOfMonth()
            : now()->subDays($amount - 1)->startOfDay();

        $key = fn (string $date): string => $monthly ? substr($date, 0, 7) : substr($date, 0, 10);

        $salesByKey = Sale::query()
            ->where('created_at', '>=', $start)
            ->pluck('total', 'created_at')
            ->groupBy(fn (mixed $total, mixed $createdAt) => $key((string) $createdAt))
            ->map(fn ($totals) => (int) $totals->sum());

        $servicesByKey = ServiceOrder::query()
            ->where('created_at', '>=', $start)
            ->pluck('id', 'created_at')
            ->groupBy(fn (mixed $id, mixed $createdAt) => $key((string) $createdAt))
            ->map(fn ($rows) => $rows->count());

        $slots = $monthly
            ? collect(range($amount - 1, 0))->map(fn (int $i): string => now()->subMonths($i)->format('Y-m'))
            : collect(range($amount - 1, 0))->map(fn (int $i): string => now()->subDays($i)->toDateString());

        return [
            'sales' => $slots->map(fn (string $slot): array => ['date' => $slot, 'value' => $salesByKey[$slot] ?? 0])->values()->all(),
            'services' => $slots->map(fn (string $slot): array => ['date' => $slot, 'value' => $servicesByKey[$slot] ?? 0])->values()->all(),
        ];
    }

    /**
     * Dashboard for technicians — incoming services and their workload.
     */
    protected function technicianDashboard(Request $request): Response
    {
        $user = $request->user();
        $activeStatuses = ['pending', 'diagnosed', 'in_progress'];

        $mine = fn ($query) => $query->where('technician_id', $user->id);
        $incoming = fn ($query) => $query->whereNull('technician_id')->whereIn('status', ['pending', 'diagnosed']);

        return Inertia::render('dashboard/technician', [
            'stats' => [
                'incoming' => ServiceOrder::query()->where($incoming)->count(),
                'my_active' => ServiceOrder::query()->where($mine)->whereIn('status', $activeStatuses)->count(),
                'in_progress' => ServiceOrder::query()->where($mine)->where('status', 'in_progress')->count(),
                'done_pending_pickup' => ServiceOrder::query()->where($mine)->where('status', 'done')->count(),
            ],
            'incomingServices' => ServiceOrder::query()
                ->where($incoming)
                ->with('customer:id,name,phone')
                ->latest()
                ->limit(5)
                ->get(),
            'myServices' => ServiceOrder::query()
                ->where($mine)
                ->with(['customer:id,name,phone', 'sale:id,payment_status'])
                ->latest()
                ->limit(5)
                ->get(),
            'statusLabels' => ServiceOrder::STATUS_LABELS,
        ]);
    }
}
