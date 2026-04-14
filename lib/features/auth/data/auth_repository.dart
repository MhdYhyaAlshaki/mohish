import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/core/data/auth_storage.dart';
import 'package:mohish/features/auth/domain/auth_user.dart';

class AuthResult {
  const AuthResult({required this.token, required this.user});

  final String token;
  final AuthUser user;
}

class AuthRepository {
  const AuthRepository({
    required ApiClient apiClient,
    required AuthStorage storage,
  }) : _apiClient = apiClient,
       _storage = storage;

  final ApiClient _apiClient;
  final AuthStorage _storage;

  Future<AuthResult> register({
    required String name,
    required String email,
    required String password,
  }) async {
    final data = await _apiClient.post('/register', {
      'name': name,
      'email': email,
      'password': password,
    });
    return _consumeAuthResponse(data);
  }

  Future<AuthResult> login({
    required String email,
    required String password,
  }) async {
    final data = await _apiClient.post('/login', {
      'email': email,
      'password': password,
    });
    return _consumeAuthResponse(data);
  }

  Future<AuthUser> profile() async {
    final data = await _apiClient.get('/profile');
    return AuthUser.fromJson(data);
  }

  Future<void> logout() async {
    _apiClient.setToken(null);
    await _storage.clear();
  }

  Future<String?> restoreToken() async {
    final token = await _storage.readToken();
    _apiClient.setToken(token);
    return token;
  }

  Future<AuthResult> _consumeAuthResponse(Map<String, dynamic> data) async {
    final token = data['token'] as String;
    final user = AuthUser.fromJson(data['user'] as Map<String, dynamic>);
    _apiClient.setToken(token);
    await _storage.writeToken(token);
    return AuthResult(token: token, user: user);
  }
}
