import 'package:google_mobile_ads/google_mobile_ads.dart';
import 'package:mohish/core/data/ad_placement_service.dart';
import 'package:mohish/core/models/ad_placement_response.dart';

/// Pre-loads a full-screen interstitial ad whose unit ID is resolved from the
/// backend placement engine at runtime.
///
/// A client-side frequency cap (3 minutes) prevents over-showing.
///
/// Platform logic (handled by the backend, not here):
///   iOS devices score campaigns by ios_cpm_estimate (typically higher than
///   android_cpm_estimate), so iOS users automatically get higher-value ads.
class InterstitialAdService {
  static const String _fallbackAdUnitId = String.fromEnvironment(
    'ADMOB_INTERSTITIAL_UNIT_ID',
    defaultValue: 'ca-app-pub-3940256099942544/1033173712', // Android test ID
  );

  static const _minIntervalBetweenShows = Duration(minutes: 3);

  InterstitialAd? _ad;
  DateTime? _lastShownAt;
  String _resolvedAdUnitId = _fallbackAdUnitId;

  // ── Public API ─────────────────────────────────────────────────────────────

  /// Call once after [MobileAds.instance.initialize()].
  /// Optionally pass [placementService] + [placementKey] to resolve the best
  /// network/unit for the current platform.
  Future<void> initialize({
    AdPlacementService? placementService,
    String placementKey = 'splash_interstitial',
  }) async {
    if (placementService != null) {
      await _resolvePlacement(placementService, placementKey);
    }
    _loadAd();
  }

  /// Shows the interstitial if one is ready and the frequency cap allows.
  /// Automatically pre-loads the next ad after dismissal.
  void showIfReady() {
    if (!_canShow) return;

    _ad!.fullScreenContentCallback = FullScreenContentCallback(
      onAdDismissedFullScreenContent: (ad) {
        ad.dispose();
        _ad = null;
        _loadAd();
      },
      onAdFailedToShowFullScreenContent: (ad, _) {
        ad.dispose();
        _ad = null;
        _loadAd();
      },
    );

    _lastShownAt = DateTime.now();
    _ad!.show();
    _ad = null;
  }

  void dispose() {
    _ad?.dispose();
  }

  // ── Private ────────────────────────────────────────────────────────────────

  bool get _canShow {
    if (_ad == null) return false;
    if (_lastShownAt == null) return true;
    return DateTime.now().difference(_lastShownAt!) >= _minIntervalBetweenShows;
  }

  Future<void> _resolvePlacement(
    AdPlacementService service,
    String placementKey,
  ) async {
    try {
      final AdPlacementResponse? response =
          await service.getPlacement(placementKey);
      if (response != null && !response.blocked && response.adUnitId != null) {
        _resolvedAdUnitId = response.adUnitId!;
      }
    } catch (_) {
      // Keep fallback
    }
  }

  void _loadAd() {
    InterstitialAd.load(
      adUnitId: _resolvedAdUnitId,
      request: const AdRequest(),
      adLoadCallback: InterstitialAdLoadCallback(
        onAdLoaded: (ad) => _ad = ad,
        onAdFailedToLoad: (_) {
          _ad = null;
          Future.delayed(const Duration(minutes: 2), _loadAd);
        },
      ),
    );
  }
}
