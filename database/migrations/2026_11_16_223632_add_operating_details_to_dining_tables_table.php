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
        Schema::table('dining_tables', function (Blueprint $table) {
            $table->unsignedTinyInteger('capacity')->default(2);
            $table->string('area', 50)->nullable();
            $table->string('status', 20)->default('available');
            $table->index(['status', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dining_tables', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_active']);
            $table->dropColumn(['capacity', 'area', 'status']);
        });
    }
};
