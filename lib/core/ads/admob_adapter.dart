import 'dart:async';

import 'package:google_mobile_ads/google_mobile_ads.dart';
import 'package:mohish/core/ads/ad_network_adapter.dart';

/// AdMob implementation of [AdNetworkAdapter].
///
/// Wraps google_mobile_ads for rewarded, interstitial, and app-open ad types.
/// Callers only use the [AdNetworkAdapter] interface — they never import
/// google_mobile_ads directly.
class AdmobAdapter implements AdNetworkAdapter {
  RewardedAd? _rewardedAd;
  InterstitialAd? _interstitialAd;
  AppOpenAd? _appOpenAd;

  String _adType = 'rewarded';

  @override
  bool get isLoaded {
    return switch (_adType) {
      'rewarded'     => _rewardedAd != null,
      'interstitial' => _interstitialAd != null,
      'app_open'     => _appOpenAd != null,
      _              => false,
    };
  }

  @override
  Future<void> load(String adUnitId, String adType) async {
    _adType = adType;
    final completer = Completer<void>();

    switch (adType) {
      case 'rewarded':
        await RewardedAd.load(
          adUnitId: adUnitId,
          request: const AdRequest(),
          rewardedAdLoadCallback: RewardedAdLoadCallback(
            onAdLoaded: (ad) {
              _rewardedAd = ad;
              completer.complete();
            },
            onAdFailedToLoad: (err) => completer.completeError(err),
          ),
        );

      case 'interstitial':
        await InterstitialAd.load(
          adUnitId: adUnitId,
          request: const AdRequest(),
          adLoadCallback: InterstitialAdLoadCallback(
            onAdLoaded: (ad) {
              _interstitialAd = ad;
              completer.complete();
            },
            onAdFailedToLoad: (err) => completer.completeError(err),
          ),
        );

      case 'app_open':
        await AppOpenAd.load(
          adUnitId: adUnitId,
          request: const AdRequest(),
          adLoadCallback: AppOpenAdLoadCallback(
            onAdLoaded: (ad) {
              _appOpenAd = ad;
              completer.complete();
            },
            onAdFailedToLoad: (err) => completer.completeError(err),
          ),
        );

      default:
        completer.completeError(
          UnsupportedError('AdmobAdapter: unknown adType "$adType"'),
        );
    }

    return completer.future;
  }

  @override
  Future<bool> show() async {
    return switch (_adType) {
      'rewarded'     => _showRewarded(),
      'interstitial' => _showInterstitial(),
      'app_open'     => _showAppOpen(),
      _              => Future.value(false),
    };
  }

  @override
  void dispose() {
    _rewardedAd?.dispose();
    _rewardedAd = null;
    _interstitialAd?.dispose();
    _interstitialAd = null;
    _appOpenAd?.dispose();
    _appOpenAd = null;
  }

  // ── Private show helpers ───────────────────────────────────────────────────

  Future<bool> _showRewarded() async {
    final ad = _rewardedAd;
    if (ad == null) return false;
    _rewardedAd = null;

    final completer = Completer<bool>();

    ad.fullScreenContentCallback = FullScreenContentCallback(
      onAdDismissedFullScreenContent: (closedAd) {
        closedAd.dispose();
        if (!completer.isCompleted) completer.complete(false);
      },
      onAdFailedToShowFullScreenContent: (failedAd, error) {
        failedAd.dispose();
        if (!completer.isCompleted) completer.complete(false);
      },
    );

    ad.show(
      onUserEarnedReward: (rewardAd, reward) {
        if (!completer.isCompleted) completer.complete(true);
      },
    );

    return completer.future;
  }

  Future<bool> _showInterstitial() async {
    final ad = _interstitialAd;
    if (ad == null) return false;
    _interstitialAd = null;

    final completer = Completer<bool>();

    ad.fullScreenContentCallback = FullScreenContentCallback(
      onAdDismissedFullScreenContent: (ad) {
        ad.dispose();
        if (!completer.isCompleted) completer.complete(true);
      },
      onAdFailedToShowFullScreenContent: (ad, _) {
        ad.dispose();
        if (!completer.isCompleted) completer.complete(false);
      },
    );

    ad.show();
    return completer.future;
  }

  Future<bool> _showAppOpen() async {
    final ad = _appOpenAd;
    if (ad == null) return false;
    _appOpenAd = null;

    final completer = Completer<bool>();

    ad.fullScreenContentCallback = FullScreenContentCallback(
      onAdDismissedFullScreenContent: (ad) {
        ad.dispose();
        if (!completer.isCompleted) completer.complete(true);
      },
      onAdFailedToShowFullScreenContent: (ad, _) {
        ad.dispose();
        if (!completer.isCompleted) completer.complete(false);
      },
    );

    ad.show();
    return completer.future;
  }
}
