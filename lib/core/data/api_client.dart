import 'dart:convert';

import 'package:dio/dio.dart';

class ApiClient {
  ApiClient({Dio? dio})
    : _dio =
          dio ??
          Dio(
            BaseOptions(
              baseUrl: _defaultBaseUrl,
              headers: const {'Content-Type': 'application/json'},
            ),
          );

  static const String _defaultBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://127.0.0.1:8000/api',
  );

  final Dio _dio;

  void setToken(String? token) {
    if (token == null) {
      _dio.options.headers.remove('Authorization');
      return;
    }
    _dio.options.headers['Authorization'] = 'Bearer $token';
  }

  Future<Map<String, dynamic>> get(String path) async {
    try {
      final response = await _dio.get<dynamic>(path);
      return _decodeData(response.data);
    } on DioException catch (exception) {
      throw _decodeException(exception);
    }
  }

  Future<Map<String, dynamic>> post(
    String path,
    Map<String, dynamic> body,
  ) async {
    try {
      final response = await _dio.post<dynamic>(path, data: body);
      return _decodeData(response.data);
    } on DioException catch (exception) {
      throw _decodeException(exception);
    }
  }

  Map<String, dynamic> _decodeData(dynamic data) {
    if (data == null) {
      return <String, dynamic>{};
    }
    if (data is Map<String, dynamic>) {
      return data;
    }
    if (data is Map) {
      return data.map((key, value) => MapEntry(key.toString(), value));
    }
    if (data is String && data.isNotEmpty) {
      final decoded = jsonDecode(data);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
    }
    return <String, dynamic>{};
  }

  ApiException _decodeException(DioException exception) {
    final decoded = _decodeData(exception.response?.data);
    return ApiException(
      code: decoded['code']?.toString() ?? 'api_error',
      message: decoded['message']?.toString() ?? 'Request failed.',
      retryAfter: _toInt(decoded['retry_after']),
    );
  }

  int? _toInt(dynamic value) {
    if (value is int) {
      return value;
    }
    if (value is num) {
      return value.toInt();
    }
    if (value is String) {
      return int.tryParse(value);
    }
    return null;
  }
}

class ApiException implements Exception {
  const ApiException({
    required this.code,
    required this.message,
    this.retryAfter,
  });

  final String code;
  final String message;
  final int? retryAfter;
}
