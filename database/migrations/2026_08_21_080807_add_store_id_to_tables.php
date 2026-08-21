<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel induk yang diberi store_id (skema multi-cabang sederhana).
     * Tabel anak (sale_items, service_order_items, purchase_items, dst.)
     * mengikuti store dari induknya.
     *
     * @var list<string>
     */
    private array $tables = [
        'products',
        'customers',
        'suppliers',
        'service_catalogs',
        'sales',
        'service_orders',
        'purchases',
    ];

    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Cabang default untuk seluruh data yang sudah ada.
        $defaultStoreId = DB::table('stores')->insertGetId([
            'name' => 'Cabang Utama',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('store_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->nullOnDelete();
            });

            DB::table($table)->whereNull('store_id')->update(['store_id' => $defaultStoreId]);
        }

        // Null = bebas pilih cabang (owner/admin); terisi = terikat satu cabang.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('store_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });

        foreach (array_reverse($this->tables) as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('store_id');
            });
        }

        Schema::dropIfExists('stores');
    }
};
