import 'package:google_mobile_ads/google_mobile_ads.dart';

/// Pre-loads a full-screen interstitial ad and exposes [showIfReady].
/// A client-side frequency cap (3 minutes) prevents over-showing.
class InterstitialAdService {
  // Pass your real unit ID via --dart-define=ADMOB_INTERSTITIAL_UNIT_ID=ca-app-pub-xxx
  static const String _adUnitId = String.fromEnvironment(
    'ADMOB_INTERSTITIAL_UNIT_ID',
    defaultValue: 'ca-app-pub-3940256099942544/1033173712', // Android test ID
  );

  static const _minIntervalBetweenShows = Duration(minutes: 3);

  InterstitialAd? _ad;
  DateTime? _lastShownAt;

  InterstitialAdService() {
    _loadAd();
  }

  bool get _canShow {
    if (_ad == null) return false;
    if (_lastShownAt == null) return true;
    return DateTime.now().difference(_lastShownAt!) >= _minIntervalBetweenShows;
  }

  void _loadAd() {
    InterstitialAd.load(
      adUnitId: _adUnitId,
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

  /// Shows the interstitial if one is ready and the frequency cap allows it.
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
}
