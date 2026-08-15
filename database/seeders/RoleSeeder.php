<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's roles.
     */
    public function run(): void
    {
        foreach ([
            'admin' => 'Administrator',
            'owner' => 'Pemilik Toko',
            'cashier' => 'Kasir',
            'technician' => 'Teknisi',
        ] as $role => $label) {
            Role::query()->updateOrCreate(
                ['name' => $role, 'guard_name' => 'web'],
                ['label' => $label],
            );
        }
    }
}
