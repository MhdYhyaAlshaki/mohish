<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analyticsService)
    {
    }

    public function overview(Request $request): JsonResponse
    {
        [$start, $end] = $this->resolveRange($request);

        return response()->json([
            'range' => [$start->toDateString(), $end->toDateString()],
            'overview' => $this->analyticsService->overview($start, $end),
            'fraud_buckets' => $this->analyticsService->fraudBuckets($start, $end),
        ]);
    }

    public function topUsers(Request $request): JsonResponse
    {
        [$start, $end] = $this->resolveRange($request);
        $limit = max(1, min((int) $request->integer('limit', 20), 100));

        return response()->json([
            'range' => [$start->toDateString(), $end->toDateString()],
            'items' => $this->analyticsService->topUsers($start, $end, $limit),
        ]);
    }

    public function profitTrend(Request $request): JsonResponse
    {
        [$start, $end] = $this->resolveRange($request);

        return response()->json([
            'range' => [$start->toDateString(), $end->toDateString()],
            'items' => $this->analyticsService->profitTrend($start, $end),
        ]);
    }

    private function resolveRange(Request $request): array
    {
        $end = $request->filled('end_date')
            ? Carbon::parse((string) $request->string('end_date'))->endOfDay()
            : Carbon::today()->endOfDay();

        $start = $request->filled('start_date')
            ? Carbon::parse((string) $request->string('start_date'))->startOfDay()
            : $end->copy()->subDays(29)->startOfDay();

        return [$start, $end];
    }
}
