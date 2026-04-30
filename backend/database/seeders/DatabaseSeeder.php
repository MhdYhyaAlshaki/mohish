<?php

namespace Database\Seeders;

use App\Models\PayoutTier;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tiers = [
            ['name' => 'new', 'label' => 'New User', 'payout_multiplier' => 1.00],
            ['name' => 'regular', 'label' => 'Regular User', 'payout_multiplier' => 1.00],
            ['name' => 'vip', 'label' => 'VIP User', 'payout_multiplier' => 1.10],
            ['name' => 'risky', 'label' => 'Risky User', 'payout_multiplier' => 0.70],
        ];

        foreach ($tiers as $tier) {
            PayoutTier::query()->updateOrCreate(['name' => $tier['name']], $tier);
        }

        $settings = [
            'revenue_per_1000_ads' => '3.0',
            'base_user_payout_points_per_ad' => (string) config('reward.points_per_ad'),
            'point_cash_value' => '0.0001',
            'referral_percent' => (string) config('reward.referral_percent'),
            'daily_cap' => (string) config('reward.daily_cap'),
            'cooldown_seconds' => (string) config('reward.cooldown_seconds'),
            'session_expiry_minutes' => (string) config('reward.session_expiry_minutes'),
            'daily_claim_points' => (string) config('reward.daily_claim_points'),
            'min_withdraw_points' => (string) config('reward.min_withdraw_points'),
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['group' => 'profit', 'value' => $value, 'value_type' => 'float'],
            );
        }

        // ── Ads / remote-config settings ──────────────────────────────────────
        $adsSettings = [
            // Global kill-switch
            'ads_enabled'                          => ['value' => '1',          'group' => 'ads', 'value_type' => 'bool'],

            // Feature flags per surface
            'feature_flag_rewarded_ads'            => ['value' => '1',          'group' => 'ads', 'value_type' => 'bool'],
            'feature_flag_interstitial_ads'        => ['value' => '1',          'group' => 'ads', 'value_type' => 'bool'],
            'feature_flag_app_open_ads'            => ['value' => '1',          'group' => 'ads', 'value_type' => 'bool'],

            // SDK init (AdMob test app IDs – replace with real ones in production)
            'admob_app_id_android'                 => ['value' => 'ca-app-pub-3940256099942544~3347511713', 'group' => 'ads', 'value_type' => 'string'],
            'admob_app_id_ios'                     => ['value' => 'ca-app-pub-3940256099942544~1458002511', 'group' => 'ads', 'value_type' => 'string'],

            // Fallback ad unit IDs – used when decision engine has no campaign
            'fallback_home_rewarded_network'       => ['value' => 'admob',       'group' => 'ads', 'value_type' => 'string'],
            'fallback_home_rewarded_android'       => ['value' => 'ca-app-pub-3940256099942544/5224354917', 'group' => 'ads', 'value_type' => 'string'],
            'fallback_home_rewarded_ios'           => ['value' => 'ca-app-pub-3940256099942544/1712485313', 'group' => 'ads', 'value_type' => 'string'],

            'fallback_splash_interstitial_network' => ['value' => 'admob',       'group' => 'ads', 'value_type' => 'string'],
            'fallback_splash_interstitial_android' => ['value' => 'ca-app-pub-3940256099942544/1033173712', 'group' => 'ads', 'value_type' => 'string'],
            'fallback_splash_interstitial_ios'     => ['value' => 'ca-app-pub-3940256099942544/4411468910', 'group' => 'ads', 'value_type' => 'string'],

            'fallback_app_open_network'            => ['value' => 'admob',       'group' => 'ads', 'value_type' => 'string'],
            'fallback_app_open_android'            => ['value' => 'ca-app-pub-3940256099942544/9257395921', 'group' => 'ads', 'value_type' => 'string'],
            'fallback_app_open_ios'                => ['value' => 'ca-app-pub-3940256099942544/5575463023', 'group' => 'ads', 'value_type' => 'string'],
        ];

        foreach ($adsSettings as $key => $meta) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['group' => $meta['group'], 'value' => $meta['value'], 'value_type' => $meta['value_type']],
            );
        }

        $regularTier = PayoutTier::query()->where('name', 'regular')->first();

        User::query()->updateOrCreate(
            ['email' => 'admin@mohish.local'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin12345'),
                'points' => 0,
                'api_token' => Str::random(60),
                'referral_code' => 'ADMIN001',
                'admin_role' => 'admin',
                'is_flagged' => false,
                'payout_tier_id' => $regularTier?->id,
            ],
        );
    }
}
