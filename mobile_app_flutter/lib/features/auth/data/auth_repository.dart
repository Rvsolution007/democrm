import 'dart:convert';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/storage/secure_storage.dart';
import '../../../config.dart';
import '../domain/user_model.dart';

/// Provider for AuthRepository
final authRepositoryProvider = Provider<AuthRepository>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return AuthRepository(apiClient);
});

/// Authentication repository
class AuthRepository {
  final ApiClient _apiClient;
  final SecureStorageService _storage = SecureStorageService.instance;

  AuthRepository(this._apiClient);

  /// Login with email and password
  Future<LoginResponse> login({
    required String email,
    required String password,
  }) async {
    final response = await _apiClient.post('/auth/login', data: {
      'email': email,
      'password': password,
      'device_name': AppConfig.deviceName,
    });

    final loginResponse = LoginResponse.fromJson(response.data as Map<String, dynamic>);
    
    // Save token and user data
    await _storage.saveToken(loginResponse.token);
    await _storage.saveUserData(jsonEncode(loginResponse.user.toJson()));
    
    return loginResponse;
  }

  /// Get current user profile
  Future<User> getCurrentUser() async {
    final response = await _apiClient.get('/auth/me');
    final data = response.data as Map<String, dynamic>;
    return User.fromJson(data['user'] as Map<String, dynamic>);
  }

  /// Logout (revoke current token)
  Future<void> logout() async {
    try {
      await _apiClient.post('/auth/logout');
    } finally {
      // Always clear local storage even if API call fails
      await _storage.clearAll();
    }
  }

  /// Check if user is logged in
  Future<bool> isLoggedIn() async {
    return await _storage.hasToken();
  }

  /// Get stored user data
  Future<User?> getStoredUser() async {
    final userData = await _storage.getUserData();
    if (userData != null) {
      try {
        return User.fromJson(jsonDecode(userData) as Map<String, dynamic>);
      } catch (_) {
        return null;
      }
    }
    return null;
  }

  /// Get stored token
  Future<String?> getToken() async {
    return await _storage.getToken();
  }
}
