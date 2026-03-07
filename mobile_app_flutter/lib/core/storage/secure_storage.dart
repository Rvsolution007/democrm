import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../../config.dart';

/// Secure storage service for sensitive data like tokens
class SecureStorageService {
  static SecureStorageService? _instance;
  late final FlutterSecureStorage _storage;

  SecureStorageService._() {
    _storage = const FlutterSecureStorage(
      aOptions: AndroidOptions(encryptedSharedPreferences: true),
      iOptions: IOSOptions(accessibility: KeychainAccessibility.first_unlock),
    );
  }

  static SecureStorageService get instance {
    _instance ??= SecureStorageService._();
    return _instance!;
  }

  /// Save auth token
  Future<void> saveToken(String token) async {
    await _storage.write(key: AppConfig.tokenStorageKey, value: token);
  }

  /// Get auth token
  Future<String?> getToken() async {
    return await _storage.read(key: AppConfig.tokenStorageKey);
  }

  /// Delete auth token
  Future<void> deleteToken() async {
    await _storage.delete(key: AppConfig.tokenStorageKey);
  }

  /// Save user data as JSON string
  Future<void> saveUserData(String userData) async {
    await _storage.write(key: AppConfig.userStorageKey, value: userData);
  }

  /// Get user data
  Future<String?> getUserData() async {
    return await _storage.read(key: AppConfig.userStorageKey);
  }

  /// Delete user data
  Future<void> deleteUserData() async {
    await _storage.delete(key: AppConfig.userStorageKey);
  }

  /// Clear all stored data
  Future<void> clearAll() async {
    await _storage.deleteAll();
  }

  /// Check if token exists
  Future<bool> hasToken() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }
}
