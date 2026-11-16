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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 24)->unique();
            $table->string('public_token', 64)->unique();
            $table->uuid('idempotency_key')->unique();
            $table->foreignId('dining_table_id')->constrained()->restrictOnDelete();
            $table->string('customer_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('pending_payment');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
