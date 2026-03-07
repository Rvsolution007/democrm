/// VyaparCRM Mobile App Configuration
/// 
/// Change BASE_URL based on your environment:
/// - For local dev with Android emulator: http://10.0.2.2:8000
/// - For local dev with iOS simulator: http://localhost:8000
/// - For local dev with physical device: http://YOUR_LAN_IP:8000
/// - For production: https://your-api-domain.com

class AppConfig {
  /// API Base URL - Change this based on your environment
  /// For Android Emulator use: http://10.0.2.2:8000
  /// For Physical Device use your PC's LAN IP: http://192.168.x.x:8000
  static const String baseUrl = 'http://192.168.1.68:8000';
  
  /// API Version prefix
  static const String apiPrefix = '/api/v1';
  
  /// Full API URL
  static String get apiUrl => '$baseUrl$apiPrefix';
  
  /// App name
  static const String appName = 'VyaparCRM';
  
  /// Token storage key
  static const String tokenStorageKey = 'auth_token';
  
  /// User storage key
  static const String userStorageKey = 'user_data';
  
  /// Request timeout in seconds
  static const int requestTimeout = 30;
  
  /// Device name for token identification
  static const String deviceName = 'flutter_mobile_app';
}
