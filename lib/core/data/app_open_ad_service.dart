import 'package:flutter/widgets.dart';
import 'package:mohish/core/ads/ad_adapter_factory.dart';
import 'package:mohish/core/ads/ad_network_adapter.dart';
import 'package:mohish/core/data/ad_placement_service.dart';
import 'package:mohish/core/data/remote_config_service.dart';

/// AppOpenAdService
/// ─────────────────
/// Loads and shows an App Open ad whenever the app returns to the foreground.
///
/// Ad unit ID resolution order:
///   1. GET /api/v1/ad?placement=app_open  (decision engine)
///   2. GET /api/v1/config fallback_placements (RemoteConfigService)
///   3. No ad served (silently skipped)
///
/// No unit IDs or network names are hardcoded.
/// Register once with [WidgetsBinding] by calling [initialize] from main().
class AppOpenAdService with WidgetsBindingObserver {
  AppOpenAdService({
    required AdPlacementService placementService,
    required RemoteConfigService remoteConfig,
  })  : _placementService = placementService,
        _remoteConfig = remoteConfig;

  final AdPlacementService _placementService;
  final RemoteConfigService _remoteConfig;

  AdNetworkAdapter? _adapter;
  bool _isShowingAd = false;
  DateTime? _loadedAt;

  // App Open ads expire 4 hours after load.
  static const _maxAdAge = Duration(hours: 4);

  bool get _isAdReady {
    if (_adapter == null || !_adapter!.isLoaded) return false;
    if (_loadedAt == null) return false;
    return DateTime.now().difference(_loadedAt!) < _maxAdAge;
  }

  // ── Public API ─────────────────────────────────────────────────────────────

  /// Call once after RemoteConfigService.load().
  Future<void> initialize() async {
    // Gate: global kill-switch or feature flag
    if (!_remoteConfig.adsEnabled ||
        !_remoteConfig.isFeatureEnabled('app_open_ads')) {
      return;
    }

    WidgetsBinding.instance.addObserver(this);
    await _loadAd();
  }

  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _adapter?.dispose();
    _adapter = null;
  }

  // ── Private ────────────────────────────────────────────────────────────────

  Future<void> _loadAd() async {
    String? unitId;
    String network = 'admob';

    // Step 1: decision engine
    try {
      final placement =
          await _placementService.getPlacement('app_open');
      if (placement != null && !placement.blocked && placement.adUnitId != null) {
        unitId  = placement.adUnitId;
        network = placement.network;
      }
    } catch (_) { /* fall through */ }

    // Step 2: remote config fallback
    if (unitId == null || unitId.isEmpty) {
      unitId  = _remoteConfig.fallbackUnitId('app_open');
      network = _remoteConfig.fallbackNetwork('app_open');
    }

    if (unitId == null || unitId.isEmpty) return;

    _adapter?.dispose();
    _adapter = AdAdapterFactory.forNetwork(network);
    try {
      await _adapter!.load(unitId, 'app_open');
      _loadedAt = DateTime.now();
    } catch (_) {
      _adapter = null;
      // Retry after a short back-off
      Future.delayed(const Duration(minutes: 2), _loadAd);
    }
  }

  void _showAdIfReady() {
    if (_isShowingAd || !_isAdReady) return;
    _isShowingAd = true;
    _adapter!.show().then((_) {
      _isShowingAd = false;
      _adapter = null;
      _loadAd(); // pre-load next
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _showAdIfReady();
    }
  }
}
