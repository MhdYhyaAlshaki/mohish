<?php

namespace App\Filament\Pages;

use App\Services\AnalyticsService;
use App\Services\UserScoreCalculator;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class Analytics extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static string|\UnitEnum|null $navigationGroup = 'Insights';
    protected static ?string $title = 'Analytics';

    public string $startDate;
    public string $endDate;
    public array $overview = [];
    public array $trend = [];
    public array $fraudBuckets = [];
    public array $topUsers = [];

    protected string $view = 'filament.pages.analytics';

    public function mount(AnalyticsService $analyticsService, UserScoreCalculator $scoreCalculator): void
    {
        $this->endDate = Carbon::today()->toDateString();
        $this->startDate = Carbon::today()->subDays(29)->toDateString();

        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        $this->overview = $analyticsService->overview($start, $end);
        $this->trend = $analyticsService->profitTrend($start, $end);
        $this->fraudBuckets = $analyticsService->fraudBuckets($start, $end);
        $this->topUsers = $analyticsService
            ->topUsers($start, $end, 10)
            ->map(static function ($row) use ($scoreCalculator): array {
                return [
                    'user_id' => $row->user_id,
                    'name' => $row->user?->name ?? 'N/A',
                    'email' => $row->user?->email ?? '',
                    'net_profit' => round((float) $row->net_profit, 4),
                    'completed_ads' => (int) $row->completed_ads,
                    'completion_rate' => round((float) $row->completion_rate, 4),
                    'risk_score_avg' => (int) round((float) $row->risk_score_avg),
                    'score' => $scoreCalculator->score(
                        (float) $row->net_profit,
                        (float) $row->completion_rate,
                        (int) round((float) $row->risk_score_avg),
                    ),
                ];
            })
            ->values()
            ->all();
    }
}
