<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds two nullable timestamp columns to ad_impressions:
 *
 *   confirmed_at – set by POST /api/v1/ad/impression when the Flutter app
 *                  confirms the ad actually rendered on screen.
 *
 *   rewarded_at  – set by POST /api/v1/ad/reward when the user earns a reward
 *                  (analytics only; the point credit is in ads_log/RewardService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_impressions', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('ip_address');
            $table->timestamp('rewarded_at')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('ad_impressions', function (Blueprint $table) {
            $table->dropColumn(['confirmed_at', 'rewarded_at']);
        });
    }
};
