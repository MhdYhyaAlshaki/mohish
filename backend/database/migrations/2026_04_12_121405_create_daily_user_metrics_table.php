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
        Schema::create('daily_user_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('metric_date');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tier_name', 24)->default('regular');
            $table->unsignedInteger('started_ads')->default(0);
            $table->unsignedInteger('completed_ads')->default(0);
            $table->unsignedBigInteger('rewarded_points')->default(0);
            $table->unsignedBigInteger('referral_cost_points')->default(0);
            $table->decimal('gross_revenue', 12, 4)->default(0);
            $table->decimal('user_payout_cost', 12, 4)->default(0);
            $table->decimal('referral_cost', 12, 4)->default(0);
            $table->decimal('net_profit', 12, 4)->default(0);
            $table->decimal('completion_rate', 6, 4)->default(0);
            $table->unsignedTinyInteger('risk_score_avg')->default(0);
            $table->boolean('vpn_events')->default(false);
            $table->timestamps();

            $table->unique(['metric_date', 'user_id']);
            $table->index(['metric_date', 'net_profit']);
            $table->index(['metric_date', 'tier_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_user_metrics');
    }
};
