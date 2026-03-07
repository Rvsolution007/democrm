import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/auth_repository.dart';
import '../domain/user_model.dart';

/// Auth state enum
enum AuthStatus { initial, authenticated, unauthenticated, loading }

/// Auth state class
class AuthState {
  final AuthStatus status;
  final User? user;
  final String? error;

  const AuthState({
    this.status = AuthStatus.initial,
    this.user,
    this.error,
  });

  AuthState copyWith({
    AuthStatus? status,
    User? user,
    String? error,
  }) {
    return AuthState(
      status: status ?? this.status,
      user: user ?? this.user,
      error: error,
    );
  }

  bool get isAuthenticated => status == AuthStatus.authenticated;
  bool get isLoading => status == AuthStatus.loading;
}

/// Auth state notifier
class AuthNotifier extends StateNotifier<AuthState> {
  final AuthRepository _authRepository;

  AuthNotifier(this._authRepository) : super(const AuthState()) {
    _init();
  }

  /// Initialize auth state
  Future<void> _init() async {
    final isLoggedIn = await _authRepository.isLoggedIn();
    if (isLoggedIn) {
      final user = await _authRepository.getStoredUser();
      if (user != null) {
        state = state.copyWith(
          status: AuthStatus.authenticated,
          user: user,
        );
        return;
      }
    }
    state = state.copyWith(status: AuthStatus.unauthenticated);
  }

  /// Login
  Future<bool> login({
    required String email,
    required String password,
  }) async {
    state = state.copyWith(status: AuthStatus.loading, error: null);
    
    try {
      print('AuthProvider: Starting login for $email');
      final response = await _authRepository.login(
        email: email,
        password: password,
      );
      
      print('AuthProvider: Login successful, user: ${response.user.name}');
      state = state.copyWith(
        status: AuthStatus.authenticated,
        user: response.user,
      );
      return true;
    } catch (e, stackTrace) {
      print('AuthProvider: Login failed with error: $e');
      print('AuthProvider: Stack trace: $stackTrace');
      state = state.copyWith(
        status: AuthStatus.unauthenticated,
        error: 'Login error: $e',
      );
      return false;
    }
  }

  /// Logout
  Future<void> logout() async {
    state = state.copyWith(status: AuthStatus.loading);
    
    try {
      await _authRepository.logout();
    } finally {
      state = const AuthState(status: AuthStatus.unauthenticated);
    }
  }

  /// Refresh user data
  Future<void> refreshUser() async {
    try {
      final user = await _authRepository.getCurrentUser();
      state = state.copyWith(user: user);
    } catch (_) {
      // Ignore errors, keep current state
    }
  }

  /// Clear error
  void clearError() {
    state = state.copyWith(error: null);
  }
}

/// Provider for auth state
final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  final authRepository = ref.watch(authRepositoryProvider);
  return AuthNotifier(authRepository);
});

/// Provider for current user (convenience)
final currentUserProvider = Provider<User?>((ref) {
  return ref.watch(authProvider).user;
});

/// Provider for auth status (convenience)
final authStatusProvider = Provider<AuthStatus>((ref) {
  return ref.watch(authProvider).status;
});
