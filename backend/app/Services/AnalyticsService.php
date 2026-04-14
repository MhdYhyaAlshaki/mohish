<?php

namespace App\Services;

use App\Models\AdsLog;
use App\Models\DailyUserMetric;
use App\Models\Withdrawal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AnalyticsService
{
    public function overview(Carbon $start, Carbon $end): array
    {
        $rows = DailyUserMetric::query()
            ->whereBetween('metric_date', [$start->toDateString(), $end->toDateString()]);

        $grossRevenue = (float) $rows->sum('gross_revenue');
        $userPayoutCost = (float) $rows->sum('user_payout_cost');
        $referralCost = (float) $rows->sum('referral_cost');
        $netProfit = (float) $rows->sum('net_profit');
        $activeUsers = (int) $rows->distinct('user_id')->count('user_id');

        $startedAds = (int) $rows->sum('started_ads');
        $completedAds = (int) $rows->sum('completed_ads');
        $completionRate = $startedAds > 0 ? round($completedAds / $startedAds, 4) : 0.0;

        $pendingWithdrawals = (int) Withdrawal::query()->where('status', 'pending')->count();
        $avgRisk = (float) $rows->avg('risk_score_avg');

        $dau = (int) DailyUserMetric::query()
            ->where('metric_date', $end->toDateString())
            ->distinct('user_id')
            ->count('user_id');

        $wau = (int) DailyUserMetric::query()
            ->whereBetween('metric_date', [$end->copy()->subDays(6)->toDateString(), $end->toDateString()])
            ->distinct('user_id')
            ->count('user_id');

        return [
            'gross_revenue' => round($grossRevenue, 4),
            'user_payout_cost' => round($userPayoutCost, 4),
            'referral_cost' => round($referralCost, 4),
            'net_profit' => round($netProfit, 4),
            'active_users' => $activeUsers,
            'dau' => $dau,
            'wau' => $wau,
            'started_ads' => $startedAds,
            'completed_ads' => $completedAds,
            'completion_rate' => $completionRate,
            'pending_withdrawals' => $pendingWithdrawals,
            'risk_score_avg' => round($avgRisk, 2),
        ];
    }

    public function topUsers(Carbon $start, Carbon $end, int $limit = 20): Collection
    {
        return DailyUserMetric::query()
            ->with(['user:id,name,email,admin_role,is_flagged'])
            ->selectRaw('
                user_id,
                SUM(net_profit) as net_profit,
                SUM(completed_ads) as completed_ads,
                AVG(completion_rate) as completion_rate,
                AVG(risk_score_avg) as risk_score_avg
            ')
            ->whereBetween('metric_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('user_id')
            ->orderByDesc('net_profit')
            ->orderByDesc('completion_rate')
            ->orderBy('risk_score_avg')
            ->limit($limit)
            ->get();
    }

    public function profitTrend(Carbon $start, Carbon $end): array
    {
        $rows = DailyUserMetric::query()
            ->selectRaw('metric_date, SUM(gross_revenue) as gross_revenue, SUM(user_payout_cost) as user_payout_cost, SUM(referral_cost) as referral_cost, SUM(net_profit) as net_profit')
            ->whereBetween('metric_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('metric_date')
            ->orderBy('metric_date')
            ->get();

        return $rows->map(static fn (DailyUserMetric $row): array => [
            'date' => $row->metric_date->toDateString(),
            'gross_revenue' => round((float) $row->gross_revenue, 4),
            'user_payout_cost' => round((float) $row->user_payout_cost, 4),
            'referral_cost' => round((float) $row->referral_cost, 4),
            'net_profit' => round((float) $row->net_profit, 4),
        ])->values()->all();
    }

    public function fraudBuckets(Carbon $start, Carbon $end): array
    {
        $rows = AdsLog::query()
            ->selectRaw('
                CASE
                    WHEN risk_score < 25 THEN "low"
                    WHEN risk_score < 60 THEN "medium"
                    ELSE "high"
                END as bucket,
                COUNT(*) as total
            ')
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        return [
            'low' => (int) ($rows['low'] ?? 0),
            'medium' => (int) ($rows['medium'] ?? 0),
            'high' => (int) ($rows['high'] ?? 0),
        ];
    }
}
