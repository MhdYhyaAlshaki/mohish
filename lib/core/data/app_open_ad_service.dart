import 'package:flutter/widgets.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';

/// Loads and shows an App Open ad whenever the app returns to the foreground.
/// Register it with [WidgetsBinding] once by calling [initialize] from main().
class AppOpenAdService with WidgetsBindingObserver {
  // Pass your real unit ID via --dart-define=ADMOB_APP_OPEN_UNIT_ID=ca-app-pub-xxx
  // Falls back to the AdMob test unit ID while in development.
  static const String _adUnitId = String.fromEnvironment(
    'ADMOB_APP_OPEN_UNIT_ID',
    defaultValue: 'ca-app-pub-3940256099942544/9257395921', // Android test ID
  );

  AppOpenAd? _appOpenAd;
  bool _isShowingAd = false;
  DateTime? _loadedAt;

  // App Open ads expire 4 hours after load.
  static const _maxAdAge = Duration(hours: 4);

  /// Call once after [MobileAds.instance.initialize()].
  void initialize() {
    WidgetsBinding.instance.addObserver(this);
    _loadAd();
  }

  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _appOpenAd?.dispose();
  }

  bool get _isAdReady {
    if (_appOpenAd == null) return false;
    if (_loadedAt == null) return false;
    return DateTime.now().difference(_loadedAt!) < _maxAdAge;
  }

  void _loadAd() {
    AppOpenAd.load(
      adUnitId: _adUnitId,
      request: const AdRequest(),
      adLoadCallback: AppOpenAdLoadCallback(
        onAdLoaded: (ad) {
          _appOpenAd = ad;
          _loadedAt = DateTime.now();
        },
        onAdFailedToLoad: (_) {
          _appOpenAd = null;
          // Retry after a short back-off so we don't spam the network.
          Future.delayed(const Duration(minutes: 2), _loadAd);
        },
      ),
    );
  }

  void _showAdIfReady() {
    if (_isShowingAd || !_isAdReady) return;

    _appOpenAd!.fullScreenContentCallback = FullScreenContentCallback(
      onAdShowedFullScreenContent: (_) => _isShowingAd = true,
      onAdDismissedFullScreenContent: (ad) {
        _isShowingAd = false;
        ad.dispose();
        _appOpenAd = null;
        _loadAd(); // pre-load the next one immediately
      },
      onAdFailedToShowFullScreenContent: (ad, _) {
        _isShowingAd = false;
        ad.dispose();
        _appOpenAd = null;
        _loadAd();
      },
    );
    _appOpenAd!.show();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _showAdIfReady();
    }
  }
}
