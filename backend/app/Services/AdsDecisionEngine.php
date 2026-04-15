<?php

namespace App\Services;

use App\Models\AdClick;
use App\Models\AdImpression;
use App\Models\AdPlacement;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * AdsDecisionEngine
 * ─────────────────
 * Receives a placement key + request context and returns the single best
 * campaign to serve.
 *
 * Scoring formula (higher = better):
 *   score = (campaign.priority × 1 000) + (network_cpm_for_platform × 500)
 *
 * Because iOS CPM estimates are typically 1.5–3× higher than Android, this
 * naturally floats iOS-targeted campaigns to the top when the device is iOS,
 * while still allowing the admin to override via manual priority weights.
 *
 * Eligibility filters applied before scoring:
 *   1. Placement must be active.
 *   2. Campaign must be active and within its date window.
 *   3. Campaign must be within its budget (if set).
 *   4. Campaign's target_platforms must include the request platform.
 *   5. Campaign's target_countries must include the request country (if set).
 *   6. The campaign's ad_network must be active.
 *   7. VPN traffic is blocked unless the campaign explicitly opts in.
 */
class AdsDecisionEngine
{
    /**
     * Resolve the best ad for the given context.
     *
     * @return array|null  Structured ad response, or null if nothing is eligible.
     */
    public function resolve(
        string  $placementKey,
        string  $platform,
        ?string $countryCode,
        ?User   $user,
        bool    $isVpn = false,
    ): ?array {
        $placement = AdPlacement::with(['adType', 'campaigns.adNetwork'])
            ->where('key', $placementKey)
            ->where('is_active', true)
            ->first();

        if (! $placement) {
            return null;
        }

        $eligible = $this->filterEligible($placement->campaigns, $platform, $countryCode, $isVpn);

        if ($eligible->isEmpty()) {
            return $this->buildFallbackResponse($placement, $platform);
        }

        $best = $eligible
            ->map(fn (Campaign $c) => [
                'campaign' => $c,
                'score'    => $this->score($c, $platform),
            ])
            ->sortByDesc('score')
            ->first();

        return $this->buildResponse($best['campaign'], $placement, $platform, $countryCode, $user, $isVpn);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function filterEligible(
        Collection $campaigns,
        string     $platform,
        ?string    $countryCode,
        bool       $isVpn,
    ): Collection {
        return $campaigns
            ->filter(fn (Campaign $c) => $c->isActiveNow())
            ->filter(fn (Campaign $c) => $c->isWithinBudget())
            ->filter(fn (Campaign $c) => $c->supportsPlatform($platform))
            ->filter(fn (Campaign $c) => $c->supportsCountry($countryCode))
            ->filter(fn (Campaign $c) => $c->adNetwork->is_active)
            // VPN: block unless the campaign explicitly opts in via ad_config.allow_vpn
            ->filter(fn (Campaign $c) => ! $isVpn || (bool) ($c->ad_config['allow_vpn'] ?? false));
    }

    /**
     * Profitability-aware score.
     *
     * The CPM multiplier rewards networks with higher estimated earnings for the
     * current platform.  An AdMob iOS CPM of $1.50 outscores an Android CPM of
     * $0.50 by 500 extra points, which is significant vs the priority scale.
     */
    private function score(Campaign $campaign, string $platform): float
    {
        $cpm = $campaign->adNetwork->getCpmForPlatform($platform);

        return ($campaign->priority * 1_000) + ($cpm * 500);
    }

    private function buildResponse(
        Campaign    $campaign,
        AdPlacement $placement,
        string      $platform,
        ?string     $countryCode,
        ?User       $user,
        bool        $isVpn,
    ): array {
        $adUnitId          = $campaign->getAdUnitId($platform);
        $cpm               = $campaign->adNetwork->getCpmForPlatform($platform);
        $estimatedRevenue  = $cpm / 1_000; // revenue per single impression

        $impression = AdImpression::create([
            'campaign_id'      => $campaign->id,
            'user_id'          => $user?->id,
            'placement_key'    => $placement->key,
            'platform'         => $platform,
            'country_code'     => $countryCode,
            'is_vpn'           => $isVpn,
            'estimated_revenue' => $estimatedRevenue,
            'ip_address'       => request()->ip(),
        ]);

        // Increment campaign spend in-place (single UPDATE, no race conditions)
        $campaign->increment('spent', $estimatedRevenue);

        $config = $campaign->ad_config ?? [];

        return [
            'impression_id' => $impression->id,
            'type'          => $placement->adType->slug,
            'network'       => $campaign->adNetwork->slug,
            'ad_unit_id'    => $adUnitId,
            'placement_key' => $placement->key,
            'refresh_after' => (int) ($config['refresh_after'] ?? 30),
            'click_url'     => $config['click_url'] ?? null,
            'platform'      => $platform,
            'blocked'       => false,
        ];
    }

    /**
     * If no campaign matched, return a fallback ad_unit_id configured on the
     * placement itself (e.g. the app's default test ID).
     */
    private function buildFallbackResponse(AdPlacement $placement, string $platform): ?array
    {
        $fallback = $placement->fallback_config;
        if (empty($fallback)) {
            return null;
        }

        $adUnitId = $fallback[$platform . '_ad_unit_id']
            ?? $fallback['ad_unit_id']
            ?? null;

        return [
            'impression_id' => null,
            'type'          => $placement->adType->slug,
            'network'       => $fallback['network'] ?? 'admob',
            'ad_unit_id'    => $adUnitId,
            'placement_key' => $placement->key,
            'refresh_after' => (int) ($fallback['refresh_after'] ?? 30),
            'click_url'     => null,
            'platform'      => $platform,
            'blocked'       => false,
        ];
    }
}
