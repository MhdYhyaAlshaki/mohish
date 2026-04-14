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
