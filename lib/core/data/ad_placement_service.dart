import 'dart:io';

import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/core/models/ad_placement_response.dart';

/// AdPlacementService
/// ──────────────────
/// Calls GET /api/v1/ad on the backend to retrieve the best ad for a given
/// placement key and the current device platform.
///
/// The backend's AdsDecisionEngine scores campaigns by:
///   (campaign.priority × 1000) + (network_cpm_for_platform × 500)
///
/// Because iOS CPM estimates are typically 1.5–3× higher than Android, iOS
/// devices automatically receive ads from the highest-paying network with no
/// extra logic needed on the Flutter side.
///
/// Results are cached for [_cacheTtl] so a single session makes at most one
/// API call per placement, while still refreshing on the next app launch.
class AdPlacementService {
  AdPlacementService({required ApiClient apiClient})
      : _apiClient = apiClient;

  final ApiClient _apiClient;

  static const _cacheTtl = Duration(minutes: 30);
  final Map<String, AdPlacementResponse> _cache = {};
  final Map<String, DateTime> _cacheTimestamps = {};

  // ── Platform detection ─────────────────────────────────────────────────────

  /// Returns the platform string expected by the backend.
  static String get currentPlatform {
    if (Platform.isIOS) return 'ios';
    if (Platform.isAndroid) return 'android';
    return 'web';
  }

  // ── Public API ─────────────────────────────────────────────────────────────

  /// Fetch (or return cached) placement config for [placementKey].
  ///
  /// Returns null if the backend returns 404 (no active campaign) or on error.
  /// The caller should fall back to its own hardcoded test unit IDs in that case.
  Future<AdPlacementResponse?> getPlacement(
    String placementKey, {
    String? countryCode,
    bool forceRefresh = false,
  }) async {
    final cacheKey = '$placementKey-$currentPlatform';

    if (!forceRefresh) {
      final cached = _getCached(cacheKey);
      if (cached != null) return cached;
    }

    try {
      final queryParams = StringBuffer('/v1/ad?')
        ..write('placement=${Uri.encodeQueryComponent(placementKey)}')
        ..write('&platform=$currentPlatform');

      if (countryCode != null) {
        queryParams.write('&country=${Uri.encodeQueryComponent(countryCode)}');
      }

      final data = await _apiClient.get(queryParams.toString());
      final response = AdPlacementResponse.fromJson(data);

      // Don't cache a blocked response — re-check on next request
      if (!response.blocked) {
        _cache[cacheKey] = response;
        _cacheTimestamps[cacheKey] = DateTime.now();
      }

      return response;
    } on ApiException {
      return null;
    }
  }

  /// Record a user click against the given impression ID.
  Future<void> recordClick(int impressionId) async {
    try {
      await _apiClient.post('/v1/ad/click', {'impression_id': impressionId});
    } catch (_) {
      // Fire-and-forget; swallow errors silently
    }
  }

  /// Clear the local placement cache (e.g. on logout or foreground resume).
  void clearCache() {
    _cache.clear();
    _cacheTimestamps.clear();
  }

  // ── Private ────────────────────────────────────────────────────────────────

  AdPlacementResponse? _getCached(String key) {
    final cached = _cache[key];
    final ts = _cacheTimestamps[key];
    if (cached == null || ts == null) return null;
    if (DateTime.now().difference(ts) > _cacheTtl) return null;
    return cached;
  }
}
