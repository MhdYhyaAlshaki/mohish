import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/features/ads/domain/ad_models.dart';

class AdsRepository {
  const AdsRepository({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<AdStartResult> startAd() async {
    final data = await _apiClient.post('/ad/start', {
      'ad_type': 'rewarded',
      'vpn_flag': false,
    });
    return AdStartResult.fromJson(data);
  }

  Future<AdCompleteResult> completeAd(String sessionId) async {
    final data = await _apiClient.post('/ad/complete', {
      'session_id': sessionId,
    });
    return AdCompleteResult.fromJson(data);
  }
}
