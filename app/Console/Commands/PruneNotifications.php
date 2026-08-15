<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneNotifications extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notifications:prune
        {--days=90 : Delete notifications older than this many days}';

    /**
     * @var string
     */
    protected $description = 'Delete notifications older than the given number of days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        $deleted = DB::table('notifications')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->components->info("Deleted {$deleted} notification(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
