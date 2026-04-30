import 'dart:io';

/// Strongly-typed model for the GET /api/v1/config response.
///
/// Every field has a safe default so the app can operate even if the backend
/// returns a partial payload or the request fails entirely.
class RemoteConfig {
  const RemoteConfig({
    this.adsEnabled = true,
    this.sdkInit = const SdkInitConfig(),
    this.featureFlags = const AdFeatureFlags(),
    this.fallbackPlacements = const {},
  });

  final bool adsEnabled;
  final SdkInitConfig sdkInit;
  final AdFeatureFlags featureFlags;

  /// Map of placement key → [FallbackPlacement].
  /// Keys: "home_rewarded", "splash_interstitial", "app_open"
  final Map<String, FallbackPlacement> fallbackPlacements;

  factory RemoteConfig.fromJson(Map<String, dynamic> json) {
    final flagsJson = json['feature_flags'] as Map<String, dynamic>? ?? {};
    final sdkJson = json['sdk_init'] as Map<String, dynamic>? ?? {};
    final fallbackJson =
        json['fallback_placements'] as Map<String, dynamic>? ?? {};

    return RemoteConfig(
      adsEnabled: json['ads_enabled'] as bool? ?? true,
      sdkInit: SdkInitConfig.fromJson(sdkJson),
      featureFlags: AdFeatureFlags.fromJson(flagsJson),
      fallbackPlacements: fallbackJson.map(
        (key, value) => MapEntry(
          key,
          FallbackPlacement.fromJson(
            key,
            value as Map<String, dynamic>? ?? {},
          ),
        ),
      ),
    );
  }

  /// Safe default used on failure / offline cold-start.
  static const RemoteConfig fallback = RemoteConfig();
}

// ── Sub-models ─────────────────────────────────────────────────────────────

class SdkInitConfig {
  const SdkInitConfig({
    this.admobAppIdAndroid = '',
    this.admobAppIdIos = '',
  });

  final String admobAppIdAndroid;
  final String admobAppIdIos;

  factory SdkInitConfig.fromJson(Map<String, dynamic> json) {
    return SdkInitConfig(
      admobAppIdAndroid:
          json['admob_app_id_android'] as String? ?? '',
      admobAppIdIos: json['admob_app_id_ios'] as String? ?? '',
    );
  }

  /// Returns the correct app ID for the current platform.
  String get currentPlatformAppId =>
      Platform.isIOS ? admobAppIdIos : admobAppIdAndroid;
}

class AdFeatureFlags {
  const AdFeatureFlags({
    this.rewardedAds = true,
    this.interstitialAds = true,
    this.appOpenAds = true,
  });

  final bool rewardedAds;
  final bool interstitialAds;
  final bool appOpenAds;

  factory AdFeatureFlags.fromJson(Map<String, dynamic> json) {
    return AdFeatureFlags(
      rewardedAds: json['rewarded_ads'] as bool? ?? true,
      interstitialAds: json['interstitial_ads'] as bool? ?? true,
      appOpenAds: json['app_open_ads'] as bool? ?? true,
    );
  }
}

class FallbackPlacement {
  const FallbackPlacement({
    required this.placementKey,
    required this.network,
    required this.androidAdUnitId,
    required this.iosAdUnitId,
  });

  final String placementKey;
  final String network;
  final String androidAdUnitId;
  final String iosAdUnitId;

  factory FallbackPlacement.fromJson(
    String key,
    Map<String, dynamic> json,
  ) {
    return FallbackPlacement(
      placementKey: key,
      network: json['network'] as String? ?? 'admob',
      androidAdUnitId: json['android_ad_unit_id'] as String? ?? '',
      iosAdUnitId: json['ios_ad_unit_id'] as String? ?? '',
    );
  }

  /// The correct ad unit ID for the current platform.
  String get currentPlatformUnitId =>
      Platform.isIOS ? iosAdUnitId : androidAdUnitId;
}
