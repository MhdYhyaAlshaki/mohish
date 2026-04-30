import 'package:mohish/core/ads/ad_network_adapter.dart';
import 'package:mohish/core/ads/admob_adapter.dart';

/// AdAdapterFactory
/// ─────────────────
/// Maps network slugs returned by the backend → concrete [AdNetworkAdapter].
///
/// To add a new network (Meta, Unity, AppLovin):
///   1. Create a new file  `lib/core/ads/<network>_adapter.dart`
///   2. Implement [AdNetworkAdapter]
///   3. Add a case below
///
/// The Flutter app never needs updating just because you added a network on
/// the backend — you merge the adapter class, re-deploy, and backend config
/// does the rest.
class AdAdapterFactory {
  AdAdapterFactory._();

  /// Returns an adapter for [network].
  ///
  /// Falls back to [AdmobAdapter] for unknown slugs so the app never crashes
  /// when the backend introduces a new network the current app version doesn't
  /// know about yet.
  static AdNetworkAdapter forNetwork(String network) {
    return switch (network.toLowerCase()) {
      'admob'     => AdmobAdapter(),
      // 'meta'   => MetaAdapter(),   // add when Meta Audience Network SDK is integrated
      // 'unity'  => UnityAdapter(),  // add when Unity Ads SDK is integrated
      // 'applovin' => ApplovinAdapter(),
      _           => AdmobAdapter(), // safe fallback
    };
  }
}
