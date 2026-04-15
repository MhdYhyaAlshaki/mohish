<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── ad_networks ────────────────────────────────────────────────────────
        // Stores one row per provider (AdMob, Meta, Unity, AppLovin, …).
        // android_cpm_estimate / ios_cpm_estimate drive the profitability score
        // inside AdsDecisionEngine – iOS is typically 1.5-2× higher than Android.
        Schema::create('ad_networks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();           // admob | meta | unity | applovin | direct
            $table->text('description')->nullable();
            $table->json('credentials')->nullable();    // encrypted; never exposed to API
            $table->integer('base_priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->decimal('android_cpm_estimate', 8, 4)->default(0.50);
            $table->decimal('ios_cpm_estimate', 8, 4)->default(1.20);
            $table->timestamps();
        });

        // ── ad_types ───────────────────────────────────────────────────────────
        Schema::create('ad_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();           // banner | interstitial | rewarded | native | app_open
            $table->timestamps();
        });

        // ── ad_placements ──────────────────────────────────────────────────────
        // Each placement has a unique *key* that the Flutter SDK references.
        // fallback_config holds a default ad_unit_id if no campaign matches.
        Schema::create('ad_placements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();            // home_banner | home_rewarded | splash_interstitial
            $table->foreignId('ad_type_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->json('fallback_config')->nullable();
            $table->timestamps();
        });

        // ── campaigns ──────────────────────────────────────────────────────────
        // A campaign ties a network + placement together with targeting rules.
        // ad_config stores ad_unit_ids keyed by platform:
        //   { "android_ad_unit_id": "ca-app-pub-xxx", "ios_ad_unit_id": "ca-app-pub-yyy" }
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('ad_network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_placement_id')->constrained()->cascadeOnDelete();
            $table->decimal('budget', 12, 2)->nullable();
            $table->decimal('spent', 12, 6)->default(0);
            $table->integer('priority')->default(0);
            $table->json('target_platforms')->nullable();    // ["android","ios"] or null = all
            $table->json('target_countries')->nullable();    // ["AE","US"] or null = all
            $table->json('ad_config')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── ad_impressions ─────────────────────────────────────────────────────
        // One row per ad served.  Revenue is estimated at serve time.
        Schema::create('ad_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('placement_key', 100);
            $table->string('platform', 20)->default('unknown');
            $table->string('country_code', 5)->nullable();
            $table->boolean('is_vpn')->default(false);
            $table->decimal('estimated_revenue', 10, 6)->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'created_at']);
            $table->index('placement_key');
            $table->index('platform');
        });

        // ── ad_clicks ──────────────────────────────────────────────────────────
        Schema::create('ad_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_impression_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique('ad_impression_id');           // one click per impression
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_clicks');
        Schema::dropIfExists('ad_impressions');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('ad_placements');
        Schema::dropIfExists('ad_types');
        Schema::dropIfExists('ad_networks');
    }
};
