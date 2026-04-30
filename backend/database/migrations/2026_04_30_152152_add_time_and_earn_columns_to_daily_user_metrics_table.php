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
        Schema::table('daily_user_metrics', function (Blueprint $table) {
            $table->unsignedInteger('time_spent_seconds')->default(0)->after('completed_ads');
            $table->decimal('avg_gross_per_ad', 12, 6)->default(0)->after('gross_revenue');
            $table->decimal('avg_net_per_ad', 12, 6)->default(0)->after('net_profit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_user_metrics', function (Blueprint $table) {
            $table->dropColumn([
                'time_spent_seconds',
                'avg_gross_per_ad',
                'avg_net_per_ad',
            ]);
        });
    }
};
