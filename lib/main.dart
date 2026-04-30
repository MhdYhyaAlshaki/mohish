import 'dart:io';

import 'package:flutter/material.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';
import 'package:mohish/core/data/ad_placement_service.dart';
import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/core/data/remote_config_service.dart';

import 'app/mohish_app.dart';
import 'core/data/app_open_ad_service.dart';
import 'core/data/interstitial_ad_service.dart';
import 'core/data/rewarded_ad_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Single shared ApiClient — passed through the whole app.
  // All ad services and repositories use the same instance.
  final apiClient = ApiClient();

  // 1. Remote config — must complete before anything else so feature flags
  //    and fallback unit IDs are available synchronously.
  final remoteConfig = RemoteConfigService(apiClient: apiClient);
  await remoteConfig.load(
    platform: Platform.isIOS ? 'ios' : 'android',
  );

  // 2. Placement service — shared across RewardedAd, Interstitial, AppOpen.
  final placementService = AdPlacementService(apiClient: apiClient);

  // 3. Initialise AdMob SDK.
  await MobileAds.instance.initialize();

  // 4. Ad services — all use backend-resolved unit IDs, no dart-define needed.
  final rewardedAdService = RewardedAdService();
  await rewardedAdService.preload(placementService, remoteConfig);

  final interstitialAdService = InterstitialAdService();
  await interstitialAdService.initialize(
    placementService: placementService,
    remoteConfig: remoteConfig,
    placementKey: 'splash_interstitial',
  );

  final appOpenAdService = AppOpenAdService(
    placementService: placementService,
    remoteConfig: remoteConfig,
  );
  await appOpenAdService.initialize();

  runApp(
    MohishApp(
      apiClient: apiClient,
      remoteConfig: remoteConfig,
      placementService: placementService,
      appOpenAdService: appOpenAdService,
      rewardedAdService: rewardedAdService,
      interstitialAdService: interstitialAdService,
    ),
  );
}
