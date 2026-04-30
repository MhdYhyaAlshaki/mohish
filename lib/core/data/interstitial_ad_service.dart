import 'package:mohish/core/ads/ad_adapter_factory.dart';
import 'package:mohish/core/ads/ad_network_adapter.dart';
import 'package:mohish/core/data/ad_placement_service.dart';
import 'package:mohish/core/data/remote_config_service.dart';

/// InterstitialAdService
/// ──────────────────────
/// Pre-loads a full-screen interstitial ad whose unit ID and network are
/// resolved from the backend at runtime.
///
/// A client-side frequency cap (3 minutes) prevents over-showing.
/// All serving decisions (network, unit ID, frequency) are backend-controlled.
class InterstitialAdService {
  static const _minIntervalBetweenShows = Duration(minutes: 3);

  AdNetworkAdapter? _adapter;
  DateTime? _lastShownAt;
  String _resolvedNetwork = 'admob';
  String _resolvedUnitId  = '';

  // ── Public API ─────────────────────────────────────────────────────────────

  /// Call once after RemoteConfigService.load().
  Future<void> initialize({
    required AdPlacementService placementService,
    required RemoteConfigService remoteConfig,
    String placementKey = 'splash_interstitial',
  }) async {
    // Gate: global kill-switch or feature flag
    if (!remoteConfig.adsEnabled ||
        !remoteConfig.isFeatureEnabled('interstitial_ads')) {
      return;
    }

    await _resolveAndLoad(placementService, remoteConfig, placementKey);
  }

  /// Shows the interstitial if one is ready and the frequency cap allows.
  /// Automatically pre-loads the next ad after dismissal.
  Future<void> showIfReady({
    AdPlacementService? placementService,
    RemoteConfigService? remoteConfig,
  }) async {
    if (!_canShow) return;
    _lastShownAt = DateTime.now();
    await _adapter!.show();
    _adapter = null;

    // Pre-load next ad if services are available
    if (placementService != null && remoteConfig != null) {
      _resolveAndLoad(
        placementService,
        remoteConfig,
        'splash_interstitial',
      ).ignore();
    }
  }

  void dispose() {
    _adapter?.dispose();
    _adapter = null;
  }

  // ── Private ────────────────────────────────────────────────────────────────

  bool get _canShow {
    if (_adapter == null || !_adapter!.isLoaded) return false;
    if (_lastShownAt == null) return true;
    return DateTime.now().difference(_lastShownAt!) >= _minIntervalBetweenShows;
  }

  Future<void> _resolveAndLoad(
    AdPlacementService placementService,
    RemoteConfigService remoteConfig,
    String placementKey,
  ) async {
    // Step 1: decision engine
    try {
      final placement = await placementService.getPlacement(placementKey);
      if (placement != null && !placement.blocked && placement.adUnitId != null) {
        _resolvedNetwork = placement.network;
        _resolvedUnitId  = placement.adUnitId!;
      }
    } catch (_) { /* fall through */ }

    // Step 2: remote config fallback
    if (_resolvedUnitId.isEmpty) {
      final fallbackId  = remoteConfig.fallbackUnitId(placementKey);
      final fallbackNet = remoteConfig.fallbackNetwork(placementKey);
      if (fallbackId != null && fallbackId.isNotEmpty) {
        _resolvedUnitId  = fallbackId;
        _resolvedNetwork = fallbackNet;
      }
    }

    if (_resolvedUnitId.isEmpty) return;

    _adapter?.dispose();
    _adapter = AdAdapterFactory.forNetwork(_resolvedNetwork);
    try {
      await _adapter!.load(_resolvedUnitId, 'interstitial');
    } catch (_) {
      _adapter = null;
    }
  }
}
