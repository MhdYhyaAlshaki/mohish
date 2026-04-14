import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:mohish/core/data/interstitial_ad_service.dart';
import 'package:mohish/features/ads/presentation/ads_cubit.dart';
import 'package:mohish/features/auth/presentation/auth_cubit.dart';
import 'package:mohish/features/dashboard/presentation/dashboard_cubit.dart';
import 'package:mohish/features/referral/presentation/referral_cubit.dart';
import 'package:mohish/features/wallet/presentation/wallet_cubit.dart';

import '../../../core/widgets/banner_ad_widget.dart';

class HomeShell extends StatefulWidget {
  const HomeShell({super.key});

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _currentTab = 0;

  @override
  void initState() {
    super.initState();
    context.read<DashboardCubit>().load();
    context.read<WalletCubit>().load();
    context.read<ReferralCubit>().load();
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
      const _HomeTab(),
      const _DashboardTab(),
      const _WalletTab(),
      const _ReferralTab(),
      const _SettingsTab(),
    ];

    // Sticky adaptive banner on Dashboard/Wallet/Referral tabs (indices 1-3).
    // Not shown on the rewarded-ad Home tab (competing earn flow) or Settings.
    final showBanner = _currentTab >= 0 && _currentTab <= 3;

    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            pages[_currentTab],
            Expanded(
              flex: 2,
              key: Key('${_currentTab}-banner-area'),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  Row(
                    children: [
                      if (showBanner) Expanded(child: const BannerAdWidget()),
                      if (showBanner) Expanded(child: const BannerAdWidget()),
                    ],
                  ),
                  Row(
                    children: [
                      if (showBanner) Expanded(child: const BannerAdWidget()),
                      if (showBanner) Expanded(child: const BannerAdWidget()),
                    ],
                  ),
                  Row(
                    children: [
                      if (showBanner) Expanded(child: const BannerAdWidget()),
                      if (showBanner) Expanded(child: const BannerAdWidget()),
                    ],
                  ),
                  Row(
                    children: [
                      if (showBanner) Expanded(child: const BannerAdWidget()),
                      if (showBanner) Expanded(child: const BannerAdWidget()),
                    ],
                  ),
                  Row(
                    children: [
                      if (showBanner) Expanded(child: const BannerAdWidget()),
                      if (showBanner) Expanded(child: const BannerAdWidget()),
                    ],
                  ),
                  // if (showBanner) const BannerAdWidget(),
                ],
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentTab,
        onDestinationSelected: (index) => setState(() => _currentTab = index),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.play_circle_outline),
            label: 'Home',
          ),
          NavigationDestination(icon: Icon(Icons.insights), label: 'Dashboard'),
          NavigationDestination(
            icon: Icon(Icons.account_balance_wallet),
            label: 'Wallet',
          ),
          NavigationDestination(icon: Icon(Icons.group), label: 'Referral'),
          NavigationDestination(icon: Icon(Icons.settings), label: 'Settings'),
        ],
      ),
    );
  }
}

class _HomeTab extends StatelessWidget {
  const _HomeTab();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: BlocBuilder<AuthCubit, AuthState>(
        builder: (context, authState) {
          return BlocBuilder<AdsCubit, AdsState>(
            builder: (context, adState) {
              final rewardColor =
                  adState.visualStatus == RewardVisualStatus.confirmed
                  ? Colors.green
                  : Colors.amber;
              return Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Hello, ${authState.user?.name ?? 'User'}'),
                          const SizedBox(height: 8),
                          Text(
                            'Balance: ${adState.balance > 0 ? adState.balance : authState.user?.points ?? 0} points',
                            style: Theme.of(context).textTheme.titleLarge,
                          ),
                          const SizedBox(height: 8),
                          Text('Ads watched today: ${adState.dailyCount}'),
                          Text('Remaining today: ${adState.remainingToday}'),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(16),
                      color: rewardColor.withValues(alpha: 0.15),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          adState.visualStatus == RewardVisualStatus.pending
                              ? 'Reward pending...'
                              : adState.visualStatus ==
                                    RewardVisualStatus.confirmed
                              ? '+${adState.lastAwarded} points confirmed'
                              : 'Ready to earn',
                          style: Theme.of(context).textTheme.titleMedium
                              ?.copyWith(color: rewardColor.shade800),
                        ),
                        const SizedBox(height: 8),
                        LinearProgressIndicator(
                          value: (adState.dailyCount / 12).clamp(0, 1),
                          minHeight: 8,
                        ),
                        const SizedBox(height: 8),
                        Text(
                          adState.cooldownRemaining > 0
                              ? 'Cooldown: ${adState.cooldownRemaining}s'
                              : 'Reward added after completion.',
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  FilledButton.icon(
                    onPressed: adState.canWatch
                        ? () => context.read<AdsCubit>().watchAndEarn()
                        : null,
                    icon: const Icon(Icons.play_arrow),
                    label: const Text('Watch & Earn'),
                  ),
                  if (adState.errorMessage != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 12),
                      child: Text(
                        adState.errorMessage!,
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.error,
                        ),
                      ),
                    ),
                ],
              );
            },
          );
        },
      ),
    );
  }
}

class _DashboardTab extends StatelessWidget {
  const _DashboardTab();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: BlocBuilder<DashboardCubit, DashboardState>(
        builder: (context, state) {
          if (state.loading && state.stats == null) {
            return const Center(child: CircularProgressIndicator());
          }
          final stats = state.stats;
          if (stats == null) {
            return Center(
              child: Text(state.errorMessage ?? 'No dashboard data yet.'),
            );
          }
          final spots = List.generate(
            stats.weeklyPoints.length,
            (index) =>
                FlSpot(index.toDouble(), stats.weeklyPoints[index].toDouble()),
          );
          return Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Wrap(
                spacing: 12,
                runSpacing: 12,
                children: [
                  _MetricCard(label: 'Total', value: '${stats.totalPoints}'),
                  _MetricCard(label: 'Today', value: '${stats.todayPoints}'),
                  _MetricCard(
                    label: 'Ads Today',
                    value: '${stats.adsWatchedToday}',
                  ),
                ],
              ),
              const SizedBox(height: 20),
              Text(
                'Weekly Growth',
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: 12),
              SizedBox(
                height: 220,
                child: LineChart(
                  LineChartData(
                    gridData: const FlGridData(show: true),
                    borderData: FlBorderData(show: false),
                    titlesData: const FlTitlesData(show: false),
                    lineBarsData: [
                      LineChartBarData(
                        spots: spots,
                        isCurved: true,
                        color: Theme.of(context).colorScheme.primary,
                        barWidth: 3,
                        dotData: const FlDotData(show: false),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _WalletTab extends StatefulWidget {
  const _WalletTab();

  @override
  State<_WalletTab> createState() => _WalletTabState();
}

class _WalletTabState extends State<_WalletTab> {
  int _withdrawPoints = 1000;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: BlocConsumer<WalletCubit, WalletState>(
        // Fire the interstitial whenever a brand-new success message arrives.
        listenWhen: (prev, curr) =>
            curr.message != null && curr.message != prev.message,
        listener: (context, _) {
          context.read<InterstitialAdService>().showIfReady();
        },
        builder: (context, state) {
          final points = state.wallet?.points ?? 0;
          return Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Text(
                    'Wallet Balance: $points points',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                ),
              ),
              const SizedBox(height: 12),
              FilledButton(
                onPressed: state.loading
                    ? null
                    : () => context.read<WalletCubit>().claimDailyReward(),
                child: const Text('Claim Daily Reward'),
              ),
              const SizedBox(height: 12),
              TextFormField(
                initialValue: '1000',
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Withdraw points'),
                onChanged: (value) {
                  _withdrawPoints = int.tryParse(value) ?? 0;
                },
              ),
              const SizedBox(height: 12),
              OutlinedButton(
                onPressed: state.loading
                    ? null
                    : () => context.read<WalletCubit>().requestWithdraw(
                        _withdrawPoints,
                      ),
                child: const Text('Request Withdrawal'),
              ),
              if (state.message != null)
                Padding(
                  padding: const EdgeInsets.only(top: 8),
                  child: Text(
                    state.message!,
                    style: const TextStyle(color: Colors.green),
                  ),
                ),
              if (state.errorMessage != null)
                Padding(
                  padding: const EdgeInsets.only(top: 8),
                  child: Text(
                    state.errorMessage!,
                    style: const TextStyle(color: Colors.red),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

class _ReferralTab extends StatefulWidget {
  const _ReferralTab();

  @override
  State<_ReferralTab> createState() => _ReferralTabState();
}

class _ReferralTabState extends State<_ReferralTab> {
  final _controller = TextEditingController();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: BlocConsumer<ReferralCubit, ReferralState>(
        listenWhen: (prev, curr) =>
            curr.message != null && curr.message != prev.message,
        listener: (context, _) {
          context.read<InterstitialAdService>().showIfReady();
        },
        builder: (context, state) {
          final stats = state.stats;
          return Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Your code: ${stats?.referralCode ?? '-'}'),
                      Text(
                        'Total referral earnings: ${stats?.totalEarnings ?? 0}',
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _controller,
                decoration: const InputDecoration(
                  labelText: 'Apply referral code',
                ),
              ),
              const SizedBox(height: 12),
              FilledButton(
                onPressed: state.loading
                    ? null
                    : () => context.read<ReferralCubit>().applyCode(
                        _controller.text.trim(),
                      ),
                child: const Text('Apply'),
              ),
              const SizedBox(height: 12),
              Expanded(
                child: ListView.builder(
                  itemCount: stats?.items.length ?? 0,
                  itemBuilder: (_, index) {
                    final item = stats!.items[index];
                    return ListTile(
                      leading: const Icon(Icons.person),
                      title: Text(item.name),
                      trailing: Text('+${item.earnings}'),
                    );
                  },
                ),
              ),
              if (state.errorMessage != null)
                Text(
                  state.errorMessage!,
                  style: const TextStyle(color: Colors.red),
                ),
              if (state.message != null)
                Text(
                  state.message!,
                  style: const TextStyle(color: Colors.green),
                ),
            ],
          );
        },
      ),
    );
  }
}

class _SettingsTab extends StatelessWidget {
  const _SettingsTab();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: FilledButton(
        onPressed: () => context.read<AuthCubit>().logout(),
        child: const Text('Logout'),
      ),
    );
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 110,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        color: Theme.of(context).colorScheme.primaryContainer,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: Theme.of(context).textTheme.bodySmall),
          const SizedBox(height: 4),
          Text(value, style: Theme.of(context).textTheme.titleMedium),
        ],
      ),
    );
  }
}
