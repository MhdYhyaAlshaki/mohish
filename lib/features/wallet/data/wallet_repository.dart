import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/features/wallet/domain/wallet_info.dart';

class WalletRepository {
  const WalletRepository({required ApiClient apiClient})
    : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<WalletInfo> fetchWallet() async {
    final data = await _apiClient.get('/profile');
    return WalletInfo.fromProfile(data);
  }

  Future<Map<String, dynamic>> claimReward() =>
      _apiClient.post('/claim-reward', {});

  Future<Map<String, dynamic>> withdraw(int points) =>
      _apiClient.post('/withdraw', {'points': points});
}
