import 'package:flutter/material.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';
import 'package:mohish/core/data/ad_placement_service.dart';
import 'package:mohish/core/data/api_client.dart';

import 'app/mohish_app.dart';
import 'core/data/app_open_ad_service.dart';
import 'core/data/rewarded_ad_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await MobileAds.instance.initialize();

  // App Open ad observer
  final appOpenAdService = AppOpenAdService();
  appOpenAdService.initialize();

  // Rewarded ad – resolve placement from backend so iOS devices get the
  // highest-CPM unit ID automatically.
  final rewardedAdService = RewardedAdService();
  final tempApiClient = ApiClient();
  final placementService = AdPlacementService(apiClient: tempApiClient);
  await rewardedAdService.preload(placementService);

  runApp(
    MohishApp(
      appOpenAdService: appOpenAdService,
      rewardedAdService: rewardedAdService,
    ),
  );
}
