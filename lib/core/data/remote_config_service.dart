import 'dart:convert';

import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/core/models/remote_config.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// RemoteConfigService
/// ────────────────────
/// Fetches GET /api/v1/config at app startup and makes it available
/// synchronously for the lifetime of the session.
///
/// Strategy:
///   1. Try to fetch fresh config from the backend.
///   2. On success: cache it to SharedPreferences and serve it.
///   3. On failure: fall back to the last known-good cached config.
///   4. If no cache exists either: use [RemoteConfig.fallback] (all defaults).
///
/// This guarantees the app always has a valid config, even offline.
class RemoteConfigService {
  RemoteConfigService({required ApiClient apiClient})
      : _apiClient = apiClient;

  final ApiClient _apiClient;

  static const _cacheKey = 'remote_config_v1';

  RemoteConfig _config = RemoteConfig.fallback;

  // ── Public API ─────────────────────────────────────────────────────────────

  /// Call once in main() before runApp().
  Future<void> load({String platform = 'android'}) async {
    try {
      final data = await _apiClient.get('/v1/config?platform=$platform');
      _config = RemoteConfig.fromJson(data);
      await _persist(data);
    } catch (_) {
      // Network failure — try cached copy
      _config = await _loadCached() ?? RemoteConfig.fallback;
    }
  }

  // ── Public getters (synchronous after load()) ──────────────────────────────

  /// Global on/off for all ads. Check this before loading any ad.
  bool get adsEnabled => _config.adsEnabled;

  /// Feature flag for a specific surface.
  /// [flag] values: 'rewarded_ads' | 'interstitial_ads' | 'app_open_ads'
  bool isFeatureEnabled(String flag) {
    return switch (flag) {
      'rewarded_ads'     => _config.featureFlags.rewardedAds,
      'interstitial_ads' => _config.featureFlags.interstitialAds,
      'app_open_ads'     => _config.featureFlags.appOpenAds,
      _                  => true, // unknown flag → don't block
    };
  }

  /// SDK init data (AdMob app IDs etc.).
  RemoteConfig get config => _config;

  /// Fallback ad unit ID for [placementKey] — null if not configured.
  ///
  /// Returns the correct platform-specific unit ID automatically.
  String? fallbackUnitId(String placementKey) =>
      _config.fallbackPlacements[placementKey]?.currentPlatformUnitId;

  /// Fallback network slug for [placementKey] — defaults to 'admob'.
  String fallbackNetwork(String placementKey) =>
      _config.fallbackPlacements[placementKey]?.network ?? 'admob';

  // ── Private ────────────────────────────────────────────────────────────────

  Future<void> _persist(Map<String, dynamic> data) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_cacheKey, jsonEncode(data));
    } catch (_) {
      // Persistence is best-effort
    }
  }

  Future<RemoteConfig?> _loadCached() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final raw = prefs.getString(_cacheKey);
      if (raw == null) return null;
      final json = jsonDecode(raw) as Map<String, dynamic>;
      return RemoteConfig.fromJson(json);
    } catch (_) {
      return null;
    }
  }
}
