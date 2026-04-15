import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';
import 'package:mohish/core/data/ad_placement_service.dart';
import 'package:mohish/core/models/ad_placement_response.dart';

/// An adaptive-banner ad whose ad unit ID is resolved at runtime from the
/// backend placement engine.
///
/// Usage:
///   BannerAdWidget(placement: 'home_banner')
///   BannerAdWidget(placement: 'wallet_banner')
///
/// The widget calls AdPlacementService → backend → AdsDecisionEngine, which
/// returns the highest-CPM ad unit ID for the current platform (iOS or Android).
/// If the backend is unreachable the [_fallbackAdUnitId] constant is used.
class BannerAdWidget extends StatefulWidget {
  const BannerAdWidget({super.key, this.placement = 'home_banner'});

  final String placement;

  @override
  State<BannerAdWidget> createState() => _BannerAdWidgetState();
}

class _BannerAdWidgetState extends State<BannerAdWidget> {
  // Hardcoded AdMob test banner used when the backend returns nothing.
  static const String _fallbackAdUnitId = String.fromEnvironment(
    'ADMOB_BANNER_UNIT_ID',
    defaultValue: 'ca-app-pub-3940256099942544/9214589741',
  );

  BannerAd? _bannerAd;
  bool _isLoaded = false;
  int? _impressionId;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_bannerAd == null) {
      _resolveAndLoad();
    }
  }

  Future<void> _resolveAndLoad() async {
    // 1. Ask the backend for the best ad unit ID for this placement + platform.
    AdPlacementResponse? placement;
    try {
      final service = context.read<AdPlacementService>();
      placement = await service.getPlacement(widget.placement);
    } catch (_) {
      // AdPlacementService not in tree → fall back silently
    }

    if (!mounted) return;

    // 2. If the placement is blocked (VPN, budget exhausted, etc.) show nothing.
    if (placement?.blocked == true) return;

    final adUnitId = placement?.adUnitId ?? _fallbackAdUnitId;
    _impressionId = placement?.impressionId;

    // 3. Load the banner with the resolved (or fallback) unit ID.
    final width = MediaQuery.of(context).size.width.truncate();
    final size = await AdSize.getCurrentOrientationAnchoredAdaptiveBannerAdSize(
      width,
    );
    if (size == null || !mounted) return;

    final ad = BannerAd(
      adUnitId: adUnitId,
      size: size,
      request: const AdRequest(),
      listener: BannerAdListener(
        onAdLoaded: (_) {
          if (mounted) setState(() => _isLoaded = true);
        },
        onAdFailedToLoad: (ad, _) {
          ad.dispose();
          _bannerAd = null;
        },
        onAdClicked: (_) {
          // Record click back to the backend for CTR tracking.
          final id = _impressionId;
          if (id != null) {
            try {
              context.read<AdPlacementService>().recordClick(id);
            } catch (_) {}
          }
        },
      ),
    );

    await ad.load();
    if (mounted) {
      setState(() => _bannerAd = ad);
    } else {
      ad.dispose();
    }
  }

  @override
  void dispose() {
    _bannerAd?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (!_isLoaded || _bannerAd == null) return const SizedBox.shrink();
    return SafeArea(
      top: false,
      child: SizedBox(
        width: _bannerAd!.size.width.toDouble(),
        height: _bannerAd!.size.height.toDouble(),
        child: AdWidget(ad: _bannerAd!),
      ),
    );
  }
}
