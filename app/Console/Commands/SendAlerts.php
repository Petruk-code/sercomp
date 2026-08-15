<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Notifications\LowStockAlert;
use App\Notifications\ServiceQueueAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendAlerts extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:send-alerts
        {--idle-hours=24 : Only alert on services untouched for at least this many hours}';

    /**
     * @var string
     */
    protected $description = 'Notify admins/owners about low stock and queued service orders';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $recipients = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'owner']))
            ->get();

        if ($recipients->isEmpty()) {
            $this->components->warn('No admin or owner recipients found.');

            return self::SUCCESS;
        }

        $lowStockProducts = Product::query()
            ->whereColumn('stock', '<=', 'min_stock')
            ->where('min_stock', '>', 0)
            ->orderBy('name')
            ->get();

        $queuedServices = ServiceOrder::query()
            ->whereIn('status', ['pending', 'diagnosed'])
            ->where('created_at', '<=', now()->subHours((int) $this->option('idle-hours')))
            ->with('customer:id,name')
            ->get();

        if ($lowStockProducts->isNotEmpty()) {
            Notification::send($recipients, new LowStockAlert($lowStockProducts));
            $this->components->info("Low stock alert sent to {$recipients->count()} recipients for {$lowStockProducts->count()} products.");
        }

        if ($queuedServices->isNotEmpty()) {
            Notification::send($recipients, new ServiceQueueAlert($queuedServices));
            $this->components->info("Service queue alert sent to {$recipients->count()} recipients for {$queuedServices->count()} services.");
        }

        if ($lowStockProducts->isEmpty() && $queuedServices->isEmpty()) {
            $this->components->info('No alerts to send.');
        }

        return self::SUCCESS;
    }
}
