/// AdNetworkAdapter
/// ─────────────────
/// Abstract interface every network-specific adapter must implement.
///
/// The Flutter app never calls AdMob / Meta / Unity APIs directly.
/// It always goes through this interface, so switching networks is a
/// backend-config change, not a code change.
///
/// Lifecycle expected by callers:
///   1. await load(adUnitId, adType)
///   2. await show()          → returns true if user earned reward (rewarded only)
///   3. dispose()             → called when the owning service is torn down
abstract class AdNetworkAdapter {
  /// Pre-fetch / warm the ad.
  ///
  /// [adUnitId] – the unit ID resolved from the backend placement engine.
  /// [adType]   – "rewarded" | "interstitial" | "app_open" | "banner"
  Future<void> load(String adUnitId, String adType);

  /// Display the pre-loaded ad.
  ///
  /// For rewarded ads: returns `true` if the user watched long enough to earn
  /// the reward.  For all other types: always returns `true` on success.
  ///
  /// Returns `false` if the ad wasn't loaded or failed to show.
  Future<bool> show();

  /// Release any SDK resources held by this adapter.
  void dispose();

  /// Whether an ad is currently loaded and ready to show.
  bool get isLoaded;
}
