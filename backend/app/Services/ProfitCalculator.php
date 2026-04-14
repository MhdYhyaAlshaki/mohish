<?php

namespace App\Services;

use App\Models\User;

class ProfitCalculator
{
    public function __construct(private readonly SettingsService $settingsService)
    {
    }

    public function metricsForUser(User $user, int $completedAds, int $rewardedPoints, int $referralCostPoints): array
    {
        $revenuePerThousand = $this->settingsService->getFloat('revenue_per_1000_ads', 3.0);
        $pointCashValue = $this->settingsService->getFloat('point_cash_value', 0.0001);
        $basePayoutPoints = $this->settingsService->getFloat('base_user_payout_points_per_ad', 10.0);

        $multiplier = (float) ($user->payoutTier?->payout_multiplier ?? 1.0);
        $effectivePayoutPoints = $basePayoutPoints * $multiplier;

        $grossRevenue = ($completedAds / 1000) * $revenuePerThousand;
        $userPayoutCost = (($rewardedPoints > 0 ? $rewardedPoints : ($completedAds * $effectivePayoutPoints))) * $pointCashValue;
        $referralCost = $referralCostPoints * $pointCashValue;
        $netProfit = $grossRevenue - $userPayoutCost - $referralCost;

        return [
            'gross_revenue' => round($grossRevenue, 4),
            'user_payout_cost' => round($userPayoutCost, 4),
            'referral_cost' => round($referralCost, 4),
            'net_profit' => round($netProfit, 4),
        ];
    }
}
