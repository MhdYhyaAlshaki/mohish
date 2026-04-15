<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdClick;
use App\Models\AdImpression;
use App\Services\AdsDecisionEngine;
use App\Services\VpnDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdPlacementController extends Controller
{
    public function __construct(
        private readonly AdsDecisionEngine  $engine,
        private readonly VpnDetectionService $vpnDetection,
    ) {}

    // ── GET /api/v1/ad ─────────────────────────────────────────────────────────
    // Query params: placement (required), platform (required), country (optional)
    //
    // The engine scores every eligible campaign by:
    //   (campaign.priority × 1000) + (network_cpm_for_platform × 500)
    //
    // So iOS campaigns automatically bubble to the top because their CPM
    // estimates are higher than Android.
    public function resolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'placement' => 'required|string|max:100',
            'platform'  => 'required|in:android,ios,web',
            'country'   => 'nullable|string|size:2',
        ]);

        $isVpn = $this->vpnDetection->check($request->ip());

        if ($isVpn && ! config('ads.allow_vpn_impressions', false)) {
            return response()->json([
                'blocked' => true,
                'reason'  => 'VPN detected',
            ], 403);
        }

        $result = $this->engine->resolve(
            placementKey: $validated['placement'],
            platform:     $validated['platform'],
            countryCode:  $validated['country'] ?? null,
            user:         $request->user(),
            isVpn:        $isVpn,
        );

        if (! $result) {
            return response()->json([
                'blocked'    => false,
                'ad_unit_id' => null,
                'reason'     => 'No active campaign for this placement',
            ], 404);
        }

        return response()->json($result);
    }

    // ── POST /api/v1/ad/placement/click ────────────────────────────────────────
    public function recordClick(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'impression_id' => 'required|integer|exists:ad_impressions,id',
        ]);

        AdClick::firstOrCreate(
            ['ad_impression_id' => $validated['impression_id']],
            ['ip_address'       => $request->ip()],
        );

        return response()->json(['ok' => true]);
    }

    // ── GET /api/v1/ad/stats (admin / analytics) ───────────────────────────────
    // Returns aggregated impression + click stats per platform for the last N days.
    public function platformStats(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 7);

        $stats = AdImpression::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                platform,
                COUNT(*)                           AS impressions,
                SUM(estimated_revenue)             AS estimated_revenue,
                AVG(estimated_revenue) * 1000      AS avg_cpm,
                COUNT(CASE WHEN is_vpn THEN 1 END) AS vpn_impressions
            ')
            ->groupBy('platform')
            ->orderByDesc('estimated_revenue')
            ->get();

        // Add click-through rates
        $stats->transform(function ($row) use ($days) {
            $clicks = AdClick::whereHas('impression', fn ($q) => $q
                ->where('platform', $row->platform)
                ->where('created_at', '>=', now()->subDays($days))
            )->count();

            $row->clicks = $clicks;
            $row->ctr    = $row->impressions > 0
                ? round($clicks / $row->impressions * 100, 2)
                : 0;

            return $row;
        });

        return response()->json([
            'days'  => $days,
            'stats' => $stats,
        ]);
    }
}
