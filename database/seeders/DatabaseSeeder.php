<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model events are intentionally NOT disabled: DummyDataSeeder relies on
     * ServiceOrderObserver to build the status-history timeline while it walks
     * each order through the status flow.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(DummyDataSeeder::class);
    }
}
