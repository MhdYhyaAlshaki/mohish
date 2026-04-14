import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/features/dashboard/domain/dashboard_stats.dart';

class DashboardRepository {
  const DashboardRepository({required ApiClient apiClient})
    : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<DashboardStats> fetchDashboard() async {
    final data = await _apiClient.get('/dashboard');
    return DashboardStats.fromJson(data);
  }
}
