import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/core/data/app_open_ad_service.dart';
import 'package:mohish/core/data/auth_storage.dart';
import 'package:mohish/core/data/interstitial_ad_service.dart';
import 'package:mohish/core/data/rewarded_ad_service.dart';
import 'package:mohish/features/ads/data/ads_repository.dart';
import 'package:mohish/features/ads/presentation/ads_cubit.dart';
import 'package:mohish/features/auth/data/auth_repository.dart';
import 'package:mohish/features/auth/presentation/auth_cubit.dart';
import 'package:mohish/features/auth/presentation/auth_gate_screen.dart';
import 'package:mohish/features/dashboard/data/dashboard_repository.dart';
import 'package:mohish/features/dashboard/presentation/dashboard_cubit.dart';
import 'package:mohish/features/referral/data/referral_repository.dart';
import 'package:mohish/features/referral/presentation/referral_cubit.dart';
import 'package:mohish/features/wallet/data/wallet_repository.dart';
import 'package:mohish/features/wallet/presentation/wallet_cubit.dart';

class MohishApp extends StatelessWidget {
  const MohishApp({
    super.key,
    required this.appOpenAdService,
    required this.rewardedAdService,
  });

  final AppOpenAdService appOpenAdService;
  final RewardedAdService rewardedAdService;

  @override
  Widget build(BuildContext context) {
    final apiClient = ApiClient();
    final authStorage = AuthStorage();
    final authRepository = AuthRepository(
      apiClient: apiClient,
      storage: authStorage,
    );
    final dashboardRepository = DashboardRepository(apiClient: apiClient);
    final walletRepository = WalletRepository(apiClient: apiClient);
    final referralRepository = ReferralRepository(apiClient: apiClient);
    final adsRepository = AdsRepository(apiClient: apiClient);
    final interstitialAdService = InterstitialAdService();

    return MultiRepositoryProvider(
      providers: [
        RepositoryProvider<ApiClient>.value(value: apiClient),
        RepositoryProvider<AuthRepository>.value(value: authRepository),
        RepositoryProvider<DashboardRepository>.value(
          value: dashboardRepository,
        ),
        RepositoryProvider<WalletRepository>.value(value: walletRepository),
        RepositoryProvider<ReferralRepository>.value(value: referralRepository),
        RepositoryProvider<AdsRepository>.value(value: adsRepository),
        RepositoryProvider<AppOpenAdService>.value(value: appOpenAdService),
        RepositoryProvider<RewardedAdService>.value(value: rewardedAdService),
        RepositoryProvider<InterstitialAdService>.value(
          value: interstitialAdService,
        ),
      ],
      child: MultiBlocProvider(
        providers: [
          BlocProvider<AuthCubit>(
            create: (_) => AuthCubit(authRepository)..bootstrap(),
          ),
          BlocProvider<DashboardCubit>(
            create: (_) => DashboardCubit(dashboardRepository),
          ),
          BlocProvider<WalletCubit>(
            create: (_) => WalletCubit(walletRepository),
          ),
          BlocProvider<ReferralCubit>(
            create: (_) => ReferralCubit(referralRepository),
          ),
          BlocProvider<AdsCubit>(
            create: (_) => AdsCubit(
              repository: adsRepository,
              adService: rewardedAdService,
            ),
          ),
        ],
        child: MaterialApp(
          title: 'Mohish Rewards',
          debugShowCheckedModeBanner: false,
          theme: ThemeData(
            colorScheme: ColorScheme.fromSeed(
              seedColor: const Color(0xFFF0B429),
            ),
            useMaterial3: true,
          ),
          home: const AuthGateScreen(),
        ),
      ),
    );
  }
}
