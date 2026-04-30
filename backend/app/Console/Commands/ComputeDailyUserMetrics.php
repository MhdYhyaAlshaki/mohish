<?php

namespace App\Console\Commands;

use App\Models\AdsLog;
use App\Models\DailyUserMetric;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ProfitCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:compute-daily-user-metrics {--date=}')]
#[Description('Compute and upsert per-user daily profitability and behavior metrics')]
class ComputeDailyUserMetrics extends Command
{
    public function __construct(private readonly ProfitCalculator $profitCalculator)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dateArg = $this->option('date');
        $date = $dateArg
            ? CarbonImmutable::parse((string) $dateArg)->startOfDay()
            : CarbonImmutable::yesterday()->startOfDay();

        $users = User::query()->with('payoutTier')->get();

        foreach ($users as $user) {
            $startedAds = AdsLog::query()
                ->where('user_id', $user->id)
                ->whereDate('started_at', $date->toDateString())
                ->count();

            $completedAds = AdsLog::query()
                ->where('user_id', $user->id)
                ->where('completed', true)
                ->whereDate('completed_at', $date->toDateString())
                ->count();

            $rewardedPoints = (int) Transaction::query()
                ->where('user_id', $user->id)
                ->where('type', 'ad')
                ->whereDate('created_at', $date->toDateString())
                ->sum('points');

            $referralCostPoints = (int) Transaction::query()
                ->where('user_id', $user->id)
                ->where('type', 'referral')
                ->whereDate('created_at', $date->toDateString())
                ->sum('points');

            $riskAvg = (int) round(AdsLog::query()
                ->where('user_id', $user->id)
                ->whereDate('created_at', $date->toDateString())
                ->avg('risk_score') ?? 0);

            $vpnEvents = AdsLog::query()
                ->where('user_id', $user->id)
                ->whereDate('created_at', $date->toDateString())
                ->where('vpn_flag', true)
                ->exists();

            $completionRate = $startedAds > 0 ? round($completedAds / $startedAds, 4) : 0.0;
            $tierName = $user->payoutTier?->name ?? 'regular';
            $timeSpentSeconds = (int) round(AdsLog::query()
                ->where('user_id', $user->id)
                ->where('completed', true)
                ->whereDate('completed_at', $date->toDateString())
                ->selectRaw('SUM(strftime("%s", completed_at) - strftime("%s", started_at)) as total_seconds')
                ->value('total_seconds') ?? 0);

            $profit = $this->profitCalculator->metricsForUser(
                $user,
                $completedAds,
                $rewardedPoints,
                $referralCostPoints,
            );
            $avgGrossPerAd = $completedAds > 0 ? round($profit['gross_revenue'] / $completedAds, 6) : 0.0;
            $avgNetPerAd = $completedAds > 0 ? round($profit['net_profit'] / $completedAds, 6) : 0.0;

            DailyUserMetric::query()->updateOrCreate(
                [
                    'metric_date' => $date->toDateString(),
                    'user_id' => $user->id,
                ],
                [
                    'tier_name' => $tierName,
                    'started_ads' => $startedAds,
                    'completed_ads' => $completedAds,
                    'time_spent_seconds' => max(0, $timeSpentSeconds),
                    'rewarded_points' => $rewardedPoints,
                    'referral_cost_points' => $referralCostPoints,
                    'gross_revenue' => $profit['gross_revenue'],
                    'avg_gross_per_ad' => $avgGrossPerAd,
                    'user_payout_cost' => $profit['user_payout_cost'],
                    'referral_cost' => $profit['referral_cost'],
                    'net_profit' => $profit['net_profit'],
                    'avg_net_per_ad' => $avgNetPerAd,
                    'completion_rate' => $completionRate,
                    'risk_score_avg' => min(100, $riskAvg),
                    'vpn_events' => $vpnEvents,
                ],
            );
        }

        $this->info("Daily metrics computed for {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
