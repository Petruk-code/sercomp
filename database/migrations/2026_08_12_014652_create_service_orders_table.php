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
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained(); // penerima
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('item_name');
            $table->string('serial_number')->nullable();
            $table->text('complaint');
            $table->text('physical_condition')->nullable();
            $table->enum('status', ['pending', 'diagnosed', 'in_progress', 'done', 'taken', 'cancelled'])->default('pending');
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('estimated_completion_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};
