import 'dart:async';

import 'package:google_mobile_ads/google_mobile_ads.dart';

/// Manages rewarded-ad loading and display.
///
/// Pre-loads the next ad immediately after the current one is dismissed so
/// the "Watch & Earn" button is never blocked by a cold-load delay.
class RewardedAdService {
  // Pass your real unit ID via --dart-define=ADMOB_REWARDED_UNIT_ID=ca-app-pub-xxx
  static const String _adUnitId = String.fromEnvironment(
    'ADMOB_REWARDED_UNIT_ID',
    defaultValue: 'ca-app-pub-3940256099942544/5224354917', // Android test ID
  );

  RewardedAd? _cachedAd;

  /// Call once during app start to warm up the first ad.
  void preload() {
    _loadAd();
  }

  void _loadAd() {
    RewardedAd.load(
      adUnitId: _adUnitId,
      request: const AdRequest(),
      rewardedAdLoadCallback: RewardedAdLoadCallback(
        onAdLoaded: (ad) => _cachedAd = ad,
        onAdFailedToLoad: (_) {
          _cachedAd = null;
          // Retry after a short back-off.
          Future.delayed(const Duration(minutes: 1), _loadAd);
        },
      ),
    );
  }

  Future<bool> showRewardedAd() async {
    final completer = Completer<bool>();

    Future<void> showAd(RewardedAd ad) async {
      ad.fullScreenContentCallback = FullScreenContentCallback(
        onAdDismissedFullScreenContent: (ad) {
          ad.dispose();
          _loadAd(); // pre-load the next ad immediately
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
          // Reward is acknowledged server-side via /ad/complete.
          if (!completer.isCompleted) completer.complete(true);
        },
      );
    }

    if (_cachedAd != null) {
      final ad = _cachedAd!;
      _cachedAd = null;
      await showAd(ad);
    } else {
      // Fallback: load on-demand if pre-cached ad is not ready.
      RewardedAd.load(
        adUnitId: _adUnitId,
        request: const AdRequest(),
        rewardedAdLoadCallback: RewardedAdLoadCallback(
          onAdLoaded: (ad) => showAd(ad),
          onAdFailedToLoad: (error) {
            print('Failed to load rewarded ad on demand: $error');
            if (!completer.isCompleted) completer.complete(false);
          },
        ),
      );
    }

    return completer.future;
  }
}
