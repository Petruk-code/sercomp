<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->unsignedInteger('warranty_days')->nullable()->after('notes');
            $table->date('warranty_end_date')->nullable()->after('warranty_days');
            $table->text('warranty_notes')->nullable()->after('warranty_end_date');
            $table->timestamp('completed_at')->nullable()->after('warranty_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['warranty_days', 'warranty_end_date', 'warranty_notes', 'completed_at']);
        });
    }
};
