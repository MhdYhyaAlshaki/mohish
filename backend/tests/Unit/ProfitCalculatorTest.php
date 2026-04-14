<?php

namespace Tests\Unit;

use App\Models\PayoutTier;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ProfitCalculator;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_profit_calculation_uses_settings_and_tier_multiplier(): void
    {
        $tier = PayoutTier::query()->create([
            'name' => 'vip',
            'label' => 'VIP',
            'payout_multiplier' => 1.10,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'payout_tier_id' => $tier->id,
        ]);

        SystemSetting::query()->create(['key' => 'revenue_per_1000_ads', 'group' => 'profit', 'value' => '4', 'value_type' => 'float']);
        SystemSetting::query()->create(['key' => 'point_cash_value', 'group' => 'profit', 'value' => '0.001', 'value_type' => 'float']);
        SystemSetting::query()->create(['key' => 'base_user_payout_points_per_ad', 'group' => 'profit', 'value' => '10', 'value_type' => 'float']);

        $calculator = new ProfitCalculator(new SettingsService());
        $result = $calculator->metricsForUser($user, completedAds: 100, rewardedPoints: 1100, referralCostPoints: 100);

        $this->assertSame(0.4, $result['gross_revenue']);
        $this->assertSame(1.1, $result['user_payout_cost']);
        $this->assertSame(0.1, $result['referral_cost']);
        $this->assertSame(-0.8, $result['net_profit']);
    }
}
