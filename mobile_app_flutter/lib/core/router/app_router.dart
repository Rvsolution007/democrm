import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../features/auth/providers/auth_provider.dart';
import '../../features/auth/presentation/login_screen.dart';
import '../../features/leads/presentation/leads_list_screen.dart';
import '../../features/leads/presentation/lead_detail_screen.dart';
import '../../features/customers/presentation/customers_list_screen.dart';
import '../../features/customers/presentation/customer_detail_screen.dart';
import '../../features/profile/presentation/profile_screen.dart';
import '../../widgets/app_shell.dart';

/// Provider for GoRouter
final appRouterProvider = Provider<GoRouter>((ref) {
  final authStatus = ref.watch(authStatusProvider);
  
  return GoRouter(
    initialLocation: '/leads',
    debugLogDiagnostics: true,
    redirect: (context, state) {
      final isLoggedIn = authStatus == AuthStatus.authenticated;
      final isLoginPage = state.matchedLocation == '/login';
      final isInitial = authStatus == AuthStatus.initial;
      
      // Still loading auth state
      if (isInitial) return null;
      
      // Not logged in and not on login page -> redirect to login
      if (!isLoggedIn && !isLoginPage) return '/login';
      
      // Logged in and on login page -> redirect to leads
      if (isLoggedIn && isLoginPage) return '/leads';
      
      return null;
    },
    routes: [
      // Login route (no shell)
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      
      // App shell with navigation
      ShellRoute(
        builder: (context, state, child) => AppShell(child: child),
        routes: [
          // Leads
          GoRoute(
            path: '/leads',
            builder: (context, state) => const LeadsListScreen(),
            routes: [
              GoRoute(
                path: ':id',
                builder: (context, state) {
                  final id = int.parse(state.pathParameters['id']!);
                  return LeadDetailScreen(leadId: id);
                },
              ),
            ],
          ),
          
          // Customers
          GoRoute(
            path: '/customers',
            builder: (context, state) => const CustomersListScreen(),
            routes: [
              GoRoute(
                path: ':id',
                builder: (context, state) {
                  final id = int.parse(state.pathParameters['id']!);
                  return CustomerDetailScreen(customerId: id);
                },
              ),
            ],
          ),
          
          // Profile
          GoRoute(
            path: '/profile',
            builder: (context, state) => const ProfileScreen(),
          ),
        ],
      ),
    ],
    errorBuilder: (context, state) => Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, size: 64, color: Colors.red),
            const SizedBox(height: 16),
            Text('Page not found: ${state.matchedLocation}'),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () => context.go('/leads'),
              child: const Text('Go Home'),
            ),
          ],
        ),
      ),
    ),
  );
});
