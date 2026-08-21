<?php

namespace App\Console\Commands;

use App\Mail\MonthlyReportMail;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\Store;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMonthlyReport extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:send-monthly-report
        {--month= : Periode laporan dalam format Y-m (default: bulan lalu)}';

    /**
     * @var string
     */
    protected $description = 'Kirim ringkasan bulanan (penjualan, servis, hutang jatuh tempo, stok menipis) ke owner';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $recipients = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'owner'))
            ->whereNotNull('email')
            ->get();

        if ($recipients->isEmpty()) {
            $this->components->warn('No owner recipients found.');

            return self::SUCCESS;
        }

        $period = $this->resolvePeriod();

        $salesRows = Sale::query()
            ->whereBetween('created_at', [$period->startOfMonth(), $period->endOfMonth()])
            ->toBase()
            ->selectRaw('store_id, count(*) as sales_count, coalesce(sum(total), 0) as sales_total')
            ->groupBy('store_id')
            ->get();

        $storeNames = Store::query()
            ->whereIn('id', $salesRows->pluck('store_id'))
            ->pluck('name', 'id');

        $salesPerStore = $salesRows
            ->map(fn (object $row): array => [
                'store' => (string) $storeNames->get($row->store_id, '-'),
                'count' => (int) $row->sales_count,
                'total' => (float) $row->sales_total,
            ])
            ->sortByDesc('total')
            ->values();

        $servicesIn = ServiceOrder::query()
            ->whereBetween('created_at', [$period->startOfMonth(), $period->endOfMonth()])
            ->count();

        $servicesDone = ServiceOrder::query()
            ->whereBetween('completed_at', [$period->startOfMonth(), $period->endOfMonth()])
            ->count();

        $overdueDebts = Purchase::query()
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $period->endOfMonth()->toDateString())
            ->whereColumn('paid_amount', '<', 'total')
            ->with('supplier:id,name')
            ->orderBy('due_date')
            ->get()
            ->map(fn (Purchase $purchase): array => [
                'supplier' => $purchase->supplier_id === null ? '-' : (string) $purchase->supplier->name,
                'due_date' => $purchase->due_date->isoFormat('D MMM Y'),
                'outstanding' => (float) $purchase->total - (float) $purchase->paid_amount,
            ])
            ->values();

        $lowStockProducts = Product::query()
            ->whereColumn('stock', '<=', 'min_stock')
            ->where('min_stock', '>', 0)
            ->with('store:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product): array => [
                'name' => (string) $product->name,
                'store' => $product->store_id === null ? '-' : (string) $product->store->name,
                'stock' => (int) $product->stock,
                'min_stock' => (int) $product->min_stock,
            ]);

        Mail::to($recipients->pluck('email')->all())->send(new MonthlyReportMail(
            periodLabel: $period->translatedFormat('F Y'),
            salesTotal: (float) $salesPerStore->sum('total'),
            salesCount: (int) $salesPerStore->sum('count'),
            salesPerStore: $salesPerStore->all(),
            servicesIn: $servicesIn,
            servicesDone: $servicesDone,
            overdueDebts: $overdueDebts->all(),
            overdueDebtsTotal: (float) $overdueDebts->sum('outstanding'),
            lowStockProducts: $lowStockProducts->take(15)->values()->all(),
            lowStockCount: $lowStockProducts->count(),
        ));

        $this->components->info("Monthly report for {$period->translatedFormat('F Y')} sent to {$recipients->count()} owners.");

        return self::SUCCESS;
    }

    /**
     * Resolve periode laporan; default bulan kalender sebelumnya.
     */
    private function resolvePeriod(): CarbonImmutable
    {
        $option = $this->option('month');

        if (is_string($option) && preg_match('/^\d{4}-\d{2}$/', $option)) {
            return CarbonImmutable::createFromFormat('Y-m', $option)->startOfMonth();
        }

        return CarbonImmutable::now()->subMonthNoOverflow()->startOfMonth();
    }
}
