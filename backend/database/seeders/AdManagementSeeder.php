<?php

namespace Database\Seeders;

use App\Models\AdNetwork;
use App\Models\AdPlacement;
use App\Models\AdType;
use Illuminate\Database\Seeder;

/**
 * AdManagementSeeder
 * ──────────────────
 * Seeds the four core tables with sensible defaults so the app works
 * out-of-the-box with AdMob test unit IDs.
 *
 * CPM estimates are approximate industry averages (2024).
 * Adjust them in the admin dashboard to match your actual network earnings.
 *
 * Platform profitability note:
 *   iOS CPM is set ~1.5-2× higher than Android for all networks.
 *   AdsDecisionEngine uses these figures to score campaigns, so iOS devices
 *   automatically receive ads from the most profitable network for their platform.
 */
class AdManagementSeeder extends Seeder
{
    public function run(): void
    {
        // ── Ad Types ───────────────────────────────────────────────────────────
        $types = collect([
            ['name' => 'Banner',        'slug' => 'banner'],
            ['name' => 'Interstitial',  'slug' => 'interstitial'],
            ['name' => 'Rewarded',      'slug' => 'rewarded'],
            ['name' => 'Native',        'slug' => 'native'],
            ['name' => 'App Open',      'slug' => 'app_open'],
        ])->keyBy('slug');

        foreach ($types as $data) {
            AdType::firstOrCreate(['slug' => $data['slug']], $data);
        }

        $typeIds = AdType::pluck('id', 'slug');

        // ── Ad Networks ────────────────────────────────────────────────────────
        // android_cpm_estimate / ios_cpm_estimate in USD per 1 000 impressions.
        $networks = [
            [
                'name'                  => 'Google AdMob',
                'slug'                  => 'admob',
                'description'           => 'Google\'s mobile ad network. Largest reach, strong iOS eCPM.',
                'base_priority'         => 100,
                'is_active'             => true,
                'android_cpm_estimate'  => 0.80,
                'ios_cpm_estimate'      => 1.60,
            ],
            [
                'name'                  => 'Meta Audience Network',
                'slug'                  => 'meta',
                'description'           => 'Facebook / Instagram ads. Strong for social-interest targeting.',
                'base_priority'         => 90,
                'is_active'             => false,   // enable once keys are added
                'android_cpm_estimate'  => 0.70,
                'ios_cpm_estimate'      => 1.40,
            ],
            [
                'name'                  => 'Unity Ads',
                'slug'                  => 'unity',
                'description'           => 'Best for gaming apps. High CPM for rewarded video.',
                'base_priority'         => 80,
                'is_active'             => false,
                'android_cpm_estimate'  => 1.00,
                'ios_cpm_estimate'      => 2.20,
            ],
            [
                'name'                  => 'AppLovin MAX',
                'slug'                  => 'applovin',
                'description'           => 'Mediation + in-app bidding across many demand sources.',
                'base_priority'         => 85,
                'is_active'             => false,
                'android_cpm_estimate'  => 0.90,
                'ios_cpm_estimate'      => 1.80,
            ],
            [
                'name'                  => 'Direct Campaign',
                'slug'                  => 'direct',
                'description'           => 'Self-serve / house ads managed directly.',
                'base_priority'         => 50,
                'is_active'             => true,
                'android_cpm_estimate'  => 0.00,
                'ios_cpm_estimate'      => 0.00,
            ],
        ];

        foreach ($networks as $data) {
            AdNetwork::firstOrCreate(['slug' => $data['slug']], $data);
        }

        $admobId = AdNetwork::where('slug', 'admob')->value('id');

        // ── Ad Placements ──────────────────────────────────────────────────────
        // These keys are referenced by the Flutter SDK.
        // fallback_config holds AdMob test unit IDs so development works with
        // no campaigns in the database.
        $placements = [
            [
                'name'           => 'Home Banner',
                'key'            => 'home_banner',
                'ad_type_id'     => $typeIds['banner'],
                'is_active'      => true,
                'fallback_config' => [
                    'network'             => 'admob',
                    'android_ad_unit_id'  => 'ca-app-pub-3940256099942544/9214589741',
                    'ios_ad_unit_id'      => 'ca-app-pub-3940256099942544/2934735716',
                    'refresh_after'       => 30,
                ],
            ],
            [
                'name'           => 'Home Rewarded',
                'key'            => 'home_rewarded',
                'ad_type_id'     => $typeIds['rewarded'],
                'is_active'      => true,
                'fallback_config' => [
                    'network'             => 'admob',
                    'android_ad_unit_id'  => 'ca-app-pub-3940256099942544/5224354917',
                    'ios_ad_unit_id'      => 'ca-app-pub-3940256099942544/1712485313',
                    'refresh_after'       => 0,
                ],
            ],
            [
                'name'           => 'Splash Interstitial',
                'key'            => 'splash_interstitial',
                'ad_type_id'     => $typeIds['interstitial'],
                'is_active'      => true,
                'fallback_config' => [
                    'network'             => 'admob',
                    'android_ad_unit_id'  => 'ca-app-pub-3940256099942544/1033173712',
                    'ios_ad_unit_id'      => 'ca-app-pub-3940256099942544/4411468910',
                    'refresh_after'       => 0,
                ],
            ],
            [
                'name'           => 'Wallet Banner',
                'key'            => 'wallet_banner',
                'ad_type_id'     => $typeIds['banner'],
                'is_active'      => true,
                'fallback_config' => [
                    'network'             => 'admob',
                    'android_ad_unit_id'  => 'ca-app-pub-3940256099942544/9214589741',
                    'ios_ad_unit_id'      => 'ca-app-pub-3940256099942544/2934735716',
                    'refresh_after'       => 30,
                ],
            ],
            [
                'name'           => 'Referral Interstitial',
                'key'            => 'referral_interstitial',
                'ad_type_id'     => $typeIds['interstitial'],
                'is_active'      => true,
                'fallback_config' => [
                    'network'             => 'admob',
                    'android_ad_unit_id'  => 'ca-app-pub-3940256099942544/1033173712',
                    'ios_ad_unit_id'      => 'ca-app-pub-3940256099942544/4411468910',
                    'refresh_after'       => 0,
                ],
            ],
            [
                'name'           => 'Dashboard Banner',
                'key'            => 'dashboard_banner',
                'ad_type_id'     => $typeIds['banner'],
                'is_active'      => true,
                'fallback_config' => [
                    'network'             => 'admob',
                    'android_ad_unit_id'  => 'ca-app-pub-3940256099942544/9214589741',
                    'ios_ad_unit_id'      => 'ca-app-pub-3940256099942544/2934735716',
                    'refresh_after'       => 30,
                ],
            ],
        ];

        foreach ($placements as $data) {
            AdPlacement::firstOrCreate(['key' => $data['key']], $data);
        }
    }
}
