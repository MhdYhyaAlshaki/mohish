<?php

namespace App\Http\Controllers\Api;

use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ConfigController
 * ────────────────
 * GET /api/v1/config
 *
 * Returns everything the mobile app needs before it can show any ad:
 *   • sdk_init       – AdMob app IDs (so even the SDK initialisation can be
 *                      server-driven rather than baked into AndroidManifest/Info.plist)
 *   • feature_flags  – per-surface on/off switches (app_open, interstitial, rewarded)
 *   • ads_enabled    – global kill-switch; when false the app must not load any ad
 *   • fallback_placements – last-resort ad unit IDs used when the decision engine
 *                           returns no campaign (replaces the old --dart-define constants)
 *
 * This endpoint is intentionally public (no token.auth) so it can be called
 * before the user has logged in (app cold-start).
 */
class ConfigController extends BaseApiController
{
    public function __construct(private readonly SettingsService $settings) {}

    public function index(Request $request): JsonResponse
    {
        $platform = $request->query('platform', 'android'); // 'android' | 'ios'

        return $this->success([
            'ads_enabled' => $this->settings->getBool('ads_enabled', true),

            'sdk_init' => [
                'admob_app_id_android' => $this->settings->getString(
                    'admob_app_id_android',
                    'ca-app-pub-3940256099942544~3347511713' // AdMob test app ID
                ),
                'admob_app_id_ios' => $this->settings->getString(
                    'admob_app_id_ios',
                    'ca-app-pub-3940256099942544~1458002511' // AdMob test app ID
                ),
            ],

            'feature_flags' => [
                'rewarded_ads'      => $this->settings->getBool('feature_flag_rewarded_ads', true),
                'interstitial_ads'  => $this->settings->getBool('feature_flag_interstitial_ads', true),
                'app_open_ads'      => $this->settings->getBool('feature_flag_app_open_ads', true),
            ],

            // Per-placement fallback unit IDs – used when the decision engine
            // finds no eligible campaign for a placement.
            // Keys mirror AdPlacement.fallback_config but are exposed here so the
            // Flutter app never needs a single hardcoded unit ID.
            'fallback_placements' => [
                'home_rewarded' => [
                    'network'          => $this->settings->getString('fallback_home_rewarded_network', 'admob'),
                    'android_ad_unit_id' => $this->settings->getString(
                        'fallback_home_rewarded_android',
                        'ca-app-pub-3940256099942544/5224354917' // AdMob test rewarded
                    ),
                    'ios_ad_unit_id' => $this->settings->getString(
                        'fallback_home_rewarded_ios',
                        'ca-app-pub-3940256099942544/1712485313' // AdMob test rewarded iOS
                    ),
                ],
                'splash_interstitial' => [
                    'network'          => $this->settings->getString('fallback_splash_interstitial_network', 'admob'),
                    'android_ad_unit_id' => $this->settings->getString(
                        'fallback_splash_interstitial_android',
                        'ca-app-pub-3940256099942544/1033173712' // AdMob test interstitial
                    ),
                    'ios_ad_unit_id' => $this->settings->getString(
                        'fallback_splash_interstitial_ios',
                        'ca-app-pub-3940256099942544/4411468910' // AdMob test interstitial iOS
                    ),
                ],
                'app_open' => [
                    'network'          => $this->settings->getString('fallback_app_open_network', 'admob'),
                    'android_ad_unit_id' => $this->settings->getString(
                        'fallback_app_open_android',
                        'ca-app-pub-3940256099942544/9257395921' // AdMob test app open
                    ),
                    'ios_ad_unit_id' => $this->settings->getString(
                        'fallback_app_open_ios',
                        'ca-app-pub-3940256099942544/5575463023' // AdMob test app open iOS
                    ),
                ],
            ],
        ]);
    }
}
