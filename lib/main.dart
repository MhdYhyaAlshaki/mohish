import 'package:flutter/material.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';

import 'app/mohish_app.dart';
import 'core/data/app_open_ad_service.dart';
import 'core/data/rewarded_ad_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await MobileAds.instance.initialize();

  // Start the App Open ad lifecycle observer so the first ad is ready
  // before the user ever leaves the app.
  final appOpenAdService = AppOpenAdService();
  appOpenAdService.initialize();

  // Warm-up the rewarded ad cache so "Watch & Earn" has zero wait time.
  final rewardedAdService = RewardedAdService();
  rewardedAdService.preload();

  runApp(
    MohishApp(
      appOpenAdService: appOpenAdService,
      rewardedAdService: rewardedAdService,
    ),
  );
}
