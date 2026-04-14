<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Illuminate\Support\Carbon;
use Filament\Widgets\ChartWidget;

class UserBehaviorChart extends ChartWidget
{
    protected ?string $heading = 'Profit Trend (Last 30 Days)';

    protected function getData(): array
    {
        $service = app(AnalyticsService::class);
        $rows = $service->profitTrend(Carbon::today()->subDays(29), Carbon::today());

        return [
            'datasets' => [
                [
                    'label' => 'Net Profit',
                    'data' => array_map(static fn (array $row): float => (float) $row['net_profit'], $rows),
                ],
                [
                    'label' => 'Gross Revenue',
                    'data' => array_map(static fn (array $row): float => (float) $row['gross_revenue'], $rows),
                ],
            ],
            'labels' => array_map(static fn (array $row): string => $row['date'], $rows),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
