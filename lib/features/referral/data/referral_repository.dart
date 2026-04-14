import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/features/referral/domain/referral_item.dart';

class ReferralRepository {
  const ReferralRepository({required ApiClient apiClient})
    : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<ReferralStats> fetchReferrals() async {
    final data = await _apiClient.get('/referrals');
    return ReferralStats.fromJson(data);
  }

  Future<void> applyCode(String code) async {
    await _apiClient.post('/apply-code', {'code': code});
  }
}
