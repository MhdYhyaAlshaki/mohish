import 'dart:async';

import 'package:google_mobile_ads/google_mobile_ads.dart';
import 'package:mohish/core/data/ad_placement_service.dart';
import 'package:mohish/core/models/ad_placement_response.dart';

/// Manages rewarded-ad loading and display.
///
/// The ad unit ID is now resolved from the backend placement engine:
///   AdPlacementService.getPlacement('home_rewarded')
///     → network: admob, ad_unit_id: ca-app-pub-xxx/yyy
///
/// iOS devices receive higher-CPM unit IDs automatically because the backend
/// scores campaigns by platform CPM (iOS ≈ 1.5–3× Android).
///
/// Lifecycle:
///   preload(service)  → called once at app start; fetches placement + caches ad
///   showRewardedAd()  → shows the cached ad; returns true if user earned reward
///   close()           → dispose (called by AdsCubit)
class RewardedAdService {
  static const String _fallbackAdUnitId = String.fromEnvironment(
    'ADMOB_REWARDED_UNIT_ID',
    defaultValue: 'ca-app-pub-3940256099942544/5224354917', // Android test
  );

  RewardedAd? _cachedAd;
  String _resolvedAdUnitId = _fallbackAdUnitId;
  int? _impressionId;

  // ── Public API ─────────────────────────────────────────────────────────────

  /// Warm up the cache.  Pass the [AdPlacementService] so we can resolve the
  /// best ad unit ID for this device's platform before the user ever taps
  /// "Watch & Earn".
  Future<void> preload([AdPlacementService? placementService]) async {
    if (placementService != null) {
      await _resolvePlacement(placementService);
    }
    _loadAd();
  }

  /// Show the rewarded ad.
  /// Returns `true` if the user completed the ad and earned the reward.
  Future<bool> showRewardedAd() async {
    final completer = Completer<bool>();

    Future<void> _show(RewardedAd ad) async {
      ad.fullScreenContentCallback = FullScreenContentCallback(
        onAdDismissedFullScreenContent: (ad) {
          ad.dispose();
          _loadAd(); // pre-load immediately for next watch
          if (!completer.isCompleted) completer.complete(false);
        },
        onAdFailedToShowFullScreenContent: (ad, _) {
          ad.dispose();
          _loadAd();
          if (!completer.isCompleted) completer.complete(false);
        },
      );
      ad.show(
        onUserEarnedReward: (_, __) {
          if (!completer.isCompleted) completer.complete(true);
        },
      );
    }

    if (_cachedAd != null) {
      final ad = _cachedAd!;
      _cachedAd = null;
      await _show(ad);
    } else {
      // On-demand fallback – should rarely happen if preload() was called.
      RewardedAd.load(
        adUnitId: _resolvedAdUnitId,
        request: const AdRequest(),
        rewardedAdLoadCallback: RewardedAdLoadCallback(
          onAdLoaded: (ad) => _show(ad),
          onAdFailedToLoad: (_) {
            if (!completer.isCompleted) completer.complete(false);
          },
        ),
      );
    }

    return completer.future;
  }

  /// Returns the impression ID from the last placement resolution (used by
  /// AdsRepository to attach it to the /ad/complete call).
  int? get lastImpressionId => _impressionId;

  // ── Private ────────────────────────────────────────────────────────────────

  Future<void> _resolvePlacement(AdPlacementService service) async {
    try {
      final AdPlacementResponse? response =
          await service.getPlacement('home_rewarded');
      if (response != null && !response.blocked && response.adUnitId != null) {
        _resolvedAdUnitId = response.adUnitId!;
        _impressionId = response.impressionId;
      }
    } catch (_) {
      // Keep the fallback ID
    }
  }

  void _loadAd() {
    RewardedAd.load(
      adUnitId: _resolvedAdUnitId,
      request: const AdRequest(),
      rewardedAdLoadCallback: RewardedAdLoadCallback(
        onAdLoaded: (ad) => _cachedAd = ad,
        onAdFailedToLoad: (_) {
          _cachedAd = null;
          Future.delayed(const Duration(minutes: 1), _loadAd);
        },
      ),
    );
  }
}
