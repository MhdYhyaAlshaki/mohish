import 'dart:io';

import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/features/ads/domain/ad_models.dart';

class AdsRepository {
  const AdsRepository({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  /// Returns the platform string expected by the backend.
  static String get _platform {
    if (Platform.isIOS) return 'ios';
    if (Platform.isAndroid) return 'android';
    return 'web';
  }

  /// Start an ad session.
  ///
  /// The backend uses [platform] to:
  ///   1. Pick the correct ad unit ID (iOS vs Android).
  ///   2. Estimate per-impression revenue (iOS CPM > Android CPM).
  ///   3. Route to the highest-value network for this platform.
  Future<AdStartResult> startAd({
    String placementKey = 'home_rewarded',
  }) async {
    final data = await _apiClient.post('/ad/start', {
      'ad_type':    'rewarded',
      'vpn_flag':   false,
      'platform':   _platform,
      'placement':  placementKey,
    });
    return AdStartResult.fromJson(data);
  }

  Future<AdCompleteResult> completeAd(String sessionId) async {
    final data = await _apiClient.post('/ad/complete', {
      'session_id': sessionId,
      'platform':   _platform,
    });
    return AdCompleteResult.fromJson(data);
  }
}
