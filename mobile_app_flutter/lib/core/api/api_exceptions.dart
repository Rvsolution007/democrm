/// Custom API exceptions for better error handling
class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final dynamic data;

  ApiException({
    required this.message,
    this.statusCode,
    this.data,
  });

  @override
  String toString() => message;

  /// Create from status code
  factory ApiException.fromStatusCode(int statusCode, [dynamic data]) {
    String message;
    switch (statusCode) {
      case 400:
        message = 'Bad request';
        break;
      case 401:
        message = 'Unauthorized. Please login again.';
        break;
      case 403:
        message = 'You don\'t have permission to perform this action';
        break;
      case 404:
        message = 'Resource not found';
        break;
      case 422:
        message = _extractValidationMessage(data) ?? 'Validation error';
        break;
      case 429:
        message = 'Too many requests. Please try again later.';
        break;
      case 500:
        message = 'Server error. Please try again later.';
        break;
      default:
        message = 'Something went wrong';
    }
    return ApiException(message: message, statusCode: statusCode, data: data);
  }

  /// Extract validation message from Laravel response
  static String? _extractValidationMessage(dynamic data) {
    if (data is Map<String, dynamic>) {
      // Laravel validation error format
      if (data.containsKey('errors')) {
        final errors = data['errors'] as Map<String, dynamic>;
        final firstError = errors.values.firstOrNull;
        if (firstError is List && firstError.isNotEmpty) {
          return firstError.first.toString();
        }
      }
      // Simple message format
      if (data.containsKey('message')) {
        return data['message'].toString();
      }
    }
    return null;
  }
}

/// Network-related exceptions
class NetworkException implements Exception {
  final String message;

  NetworkException([this.message = 'No internet connection']);

  @override
  String toString() => message;
}

/// Timeout exception
class TimeoutException implements Exception {
  final String message;

  TimeoutException([this.message = 'Request timed out']);

  @override
  String toString() => message;
}
