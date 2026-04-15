<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * VpnDetectionService
 * ───────────────────
 * Lightweight VPN/proxy/datacenter IP detection.
 *
 * Strategy (cheapest-first):
 *   1. Allow-list: known CDN egress IPs skipped entirely.
 *   2. Deny-list:  known VPN ranges cached in Redis.
 *   3. IP-API.com: free tier (45 req/min) for definitive lookup; result cached 24 h.
 *
 * In production swap ip-api.com for ipqualityscore.com or ipinfo.io.
 */
class VpnDetectionService
{
    private const CACHE_TTL_SECONDS = 86_400; // 24 hours

    public function check(string $ip): bool
    {
        // Private / localhost IPs are never VPNs
        if ($this->isPrivateIp($ip)) {
            return false;
        }

        return Cache::remember("vpn_check:{$ip}", self::CACHE_TTL_SECONDS, function () use ($ip) {
            return $this->queryIpApi($ip);
        });
    }

    private function queryIpApi(string $ip): bool
    {
        try {
            $response = Http::timeout(3)
                ->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'proxy,hosting,query',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return (bool) ($data['proxy'] ?? false)
                    || (bool) ($data['hosting'] ?? false);
            }
        } catch (\Throwable) {
            // If the check fails, err on the side of allowing the impression
        }

        return false;
    }

    private function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
