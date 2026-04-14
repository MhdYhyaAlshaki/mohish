<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ProfitOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $service = app(AnalyticsService::class);
        $overview = $service->overview(Carbon::today()->subDays(29), Carbon::today());

        return [
            Stat::make('Gross Revenue', (string) number_format($overview['gross_revenue'], 4)),
            Stat::make('User Payout Cost', (string) number_format($overview['user_payout_cost'], 4)),
            Stat::make('Net Profit', (string) number_format($overview['net_profit'], 4)),
            Stat::make('Active Users', (string) $overview['active_users']),
            Stat::make('Completion Rate', (string) number_format($overview['completion_rate'] * 100, 2).' %'),
            Stat::make('Pending Withdrawals', (string) $overview['pending_withdrawals']),
        ];
    }
}
