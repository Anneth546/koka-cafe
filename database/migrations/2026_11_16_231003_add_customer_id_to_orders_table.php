<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('phone', 24)->unique();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['customer_id', 'created_at']);
        });

        DB::table('orders')
            ->select('customer_phone')
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '<>', '')
            ->distinct()
            ->orderBy('customer_phone')
            ->chunk(100, function ($orders): void {
                foreach ($orders as $order) {
                    $latestOrder = DB::table('orders')
                        ->where('customer_phone', $order->customer_phone)
                        ->orderByDesc('id')
                        ->first(['customer_name']);
                    $customerId = DB::table('customers')->insertGetId([
                        'name' => filled($latestOrder?->customer_name) ? $latestOrder->customer_name : 'Pelanggan',
                        'phone' => $order->customer_phone,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('orders')
                        ->where('customer_phone', $order->customer_phone)
                        ->update(['customer_id' => $customerId]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'created_at']);
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::dropIfExists('customers');
    }
};
