import 'dart:async';

import 'package:mohish/core/ads/ad_adapter_factory.dart';
import 'package:mohish/core/ads/ad_network_adapter.dart';
import 'package:mohish/core/data/ad_placement_service.dart';
import 'package:mohish/core/data/remote_config_service.dart';

/// RewardedAdService
/// ──────────────────
/// Manages rewarded-ad loading and display.
///
/// Ad unit ID resolution order:
///   1. GET /api/v1/ad?placement=home_rewarded  (decision engine — best campaign)
///   2. GET /api/v1/config fallback_placements  (RemoteConfigService)
///   3. No ad served (returns false)
///
/// No unit IDs or network names are hardcoded in the app.
/// The correct network adapter is created via [AdAdapterFactory].
class RewardedAdService {
  AdNetworkAdapter? _adapter;
  int? _impressionId;
  String _resolvedNetwork = 'admob';
  String _resolvedUnitId = '';

  // ── Public API ─────────────────────────────────────────────────────────────

  /// Warm the cache.  Call once after RemoteConfigService.load().
  Future<void> preload(
    AdPlacementService placementService,
    RemoteConfigService remoteConfig,
  ) async {
    // Gate: global kill-switch or feature flag
    if (!remoteConfig.adsEnabled ||
        !remoteConfig.isFeatureEnabled('rewarded_ads')) {
      return;
    }

    await _resolveAndLoad(placementService, remoteConfig);
  }

  /// Show the rewarded ad.
  /// Returns `true` if the user completed the ad and earned the reward.
  Future<bool> showRewardedAd() async {
    final adapter = _adapter;
    if (adapter == null || !adapter.isLoaded) return false;
    return adapter.show();
  }

  /// Impression ID from the last placement resolution — used by AdsRepository
  /// to attach it to the /ad/complete call.
  int? get lastImpressionId => _impressionId;

  // ── Private ────────────────────────────────────────────────────────────────

  Future<void> _resolveAndLoad(
    AdPlacementService placementService,
    RemoteConfigService remoteConfig,
  ) async {
    // Step 1: try the decision engine
    try {
      final placement = await placementService.getPlacement('home_rewarded');
      if (placement != null && !placement.blocked && placement.adUnitId != null) {
        _resolvedNetwork = placement.network;
        _resolvedUnitId  = placement.adUnitId!;
        _impressionId    = placement.impressionId;
      }
    } catch (_) {
      // Fall through to step 2
    }

    // Step 2: remote config fallback (no hardcoded unit IDs)
    if (_resolvedUnitId.isEmpty) {
      final fallbackId = remoteConfig.fallbackUnitId('home_rewarded');
      final fallbackNet = remoteConfig.fallbackNetwork('home_rewarded');
      if (fallbackId != null && fallbackId.isNotEmpty) {
        _resolvedUnitId  = fallbackId;
        _resolvedNetwork = fallbackNet;
      }
    }

    if (_resolvedUnitId.isEmpty) return; // nothing to load

    // Step 3: load via the correct network adapter
    _adapter?.dispose();
    _adapter = AdAdapterFactory.forNetwork(_resolvedNetwork);
    try {
      await _adapter!.load(_resolvedUnitId, 'rewarded');
    } catch (_) {
      _adapter = null;
    }
  }
}
