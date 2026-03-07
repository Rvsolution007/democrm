import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
import '../../../config.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/storage/secure_storage.dart';
import '../domain/user_model.dart';
import 'dart:convert';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _isLoading = false;
  String? _errorMessage;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    // Hide keyboard
    FocusScope.of(context).unfocus();

    try {
      // Create Dio instance for direct API call
      final dio = Dio(BaseOptions(
        baseUrl: AppConfig.apiUrl,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ));

      // Make login request
      final response = await dio.post('/auth/login', data: {
        'email': _emailController.text.trim(),
        'password': _passwordController.text,
        'device_name': AppConfig.deviceName,
      });

      if (response.statusCode == 200) {
        final data = response.data as Map<String, dynamic>;
        
        // Parse response
        final token = data['token'] as String;
        final userData = data['user'] as Map<String, dynamic>;
        
        // Save to secure storage
        final storage = SecureStorageService.instance;
        await storage.saveToken(token);
        await storage.saveUserData(jsonEncode(userData));

        if (mounted) {
          // Show success and restart app
          showDialog(
            context: context,
            barrierDismissible: false,
            builder: (ctx) => AlertDialog(
              title: const Row(
                children: [
                  Icon(Icons.check_circle, color: Colors.green, size: 28),
                  SizedBox(width: 8),
                  Expanded(child: Text('Login Successful!')),
                ],
              ),
              content: Text('Welcome, ${userData['name']}!\n\nPlease restart the app to continue to dashboard.'),
              actions: [
                ElevatedButton(
                  onPressed: () {
                    Navigator.of(ctx).pop();
                    // Force app to check auth again by triggering a rebuild
                    Navigator.of(context).pushNamedAndRemoveUntil('/', (route) => false);
                  },
                  child: const Text('Restart App'),
                ),
              ],
            ),
          );
        }
      } else {
        setState(() {
          _errorMessage = 'Login failed with status: ${response.statusCode}';
        });
      }
    } on DioException catch (e) {
      String errorMsg = 'Network error';
      String debugInfo = '';
      
      if (e.response != null) {
        debugInfo = 'Status: ${e.response!.statusCode}\nResponse: ${e.response!.data}';
        
        if (e.response!.statusCode == 422) {
          // Validation error
          final data = e.response!.data;
          if (data is Map && data['message'] != null) {
            errorMsg = data['message'];
          } else if (data is Map && data['errors'] != null) {
            errorMsg = 'Validation failed: ${data['errors']}';
          } else {
            errorMsg = 'Invalid credentials';
          }
        } else if (e.response!.statusCode == 401) {
          errorMsg = 'Invalid email or password';
        } else if (e.response!.statusCode == 200) {
          // Success but parsing failed
          errorMsg = 'Login successful but response parsing failed';
          debugInfo = 'Response data: ${e.response!.data}';
        } else {
          errorMsg = 'Server error: ${e.response!.statusCode}';
        }
      } else if (e.type == DioExceptionType.connectionTimeout) {
        errorMsg = 'Connection timeout. Please check your network.';
      } else if (e.type == DioExceptionType.connectionError) {
        errorMsg = 'Cannot connect to server. Check IP: ${AppConfig.baseUrl}';
      } else {
        errorMsg = 'Error: ${e.message}';
      }

      setState(() {
        _errorMessage = '$errorMsg\n\nDebug Info:\n$debugInfo';
      });
    } catch (e) {
      setState(() {
        _errorMessage = 'Unexpected error: $e';
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _testConnection() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final dio = Dio(BaseOptions(
        baseUrl: AppConfig.baseUrl,
        connectTimeout: const Duration(seconds: 10),
        receiveTimeout: const Duration(seconds: 10),
        validateStatus: (status) => true,
      ));
      
      final response = await dio.get('/');
      
      if (mounted) {
        showDialog(
          context: context,
          builder: (context) => AlertDialog(
            title: const Row(
              children: [
                Icon(Icons.check_circle, color: Colors.green),
                SizedBox(width: 8),
                Text('Connection OK!'),
              ],
            ),
            content: Text('Server responded with status: ${response.statusCode}\n\nConnection is working. You can now login.'),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('OK'),
              ),
            ],
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        showDialog(
          context: context,
          builder: (context) => AlertDialog(
            title: const Row(
              children: [
                Icon(Icons.error, color: Colors.red),
                SizedBox(width: 8),
                Text('Connection Failed'),
              ],
            ),
            content: SingleChildScrollView(
              child: Text('Error: $e\n\n1. Check PC and Phone on same WiFi\n2. Check server: php artisan serve --host 0.0.0.0\n3. Check IP: ${AppConfig.baseUrl}'),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('OK'),
              ),
            ],
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Logo
                  Container(
                    height: 100,
                    width: 100,
                    decoration: BoxDecoration(
                      color: AppTheme.primaryColor.withOpacity(0.1),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.business_center,
                      size: 50,
                      color: AppTheme.primaryColor,
                    ),
                  ),
                  const SizedBox(height: 32),
                  
                  // Title
                  Text(
                    'VyaparCRM',
                    style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                      color: AppTheme.primaryColor,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Sign in to continue',
                    style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: Colors.grey[600],
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 48),

                  // Error message
                  if (_errorMessage != null)
                    Container(
                      padding: const EdgeInsets.all(12),
                      margin: const EdgeInsets.only(bottom: 16),
                      decoration: BoxDecoration(
                        color: Colors.red.shade50,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.red.shade200),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.error_outline, color: Colors.red.shade700),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              _errorMessage!,
                              style: TextStyle(color: Colors.red.shade700),
                            ),
                          ),
                        ],
                      ),
                    ),

                  // Email field
                  TextFormField(
                    controller: _emailController,
                    keyboardType: TextInputType.emailAddress,
                    textInputAction: TextInputAction.next,
                    enabled: !_isLoading,
                    decoration: const InputDecoration(
                      labelText: 'Email',
                      hintText: 'Enter your email',
                      prefixIcon: Icon(Icons.email_outlined),
                    ),
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Please enter your email';
                      }
                      if (!value.contains('@')) {
                        return 'Please enter a valid email';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 16),

                  // Password field
                  TextFormField(
                    controller: _passwordController,
                    obscureText: _obscurePassword,
                    textInputAction: TextInputAction.done,
                    enabled: !_isLoading,
                    onFieldSubmitted: (_) => _handleLogin(),
                    decoration: InputDecoration(
                      labelText: 'Password',
                      hintText: 'Enter your password',
                      prefixIcon: const Icon(Icons.lock_outlined),
                      suffixIcon: IconButton(
                        icon: Icon(
                          _obscurePassword ? Icons.visibility_off : Icons.visibility,
                        ),
                        onPressed: () {
                          setState(() {
                            _obscurePassword = !_obscurePassword;
                          });
                        },
                      ),
                    ),
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Please enter your password';
                      }
                      if (value.length < 6) {
                        return 'Password must be at least 6 characters';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 32),

                  // Login button
                  SizedBox(
                    height: 50,
                    child: ElevatedButton(
                      onPressed: _isLoading ? null : _handleLogin,
                      child: _isLoading
                          ? const SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Text('Sign In'),
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Test Connection
                  OutlinedButton.icon(
                    onPressed: _isLoading ? null : _testConnection,
                    icon: const Icon(Icons.wifi_find),
                    label: const Text('Test Connection'),
                  ),
                  const SizedBox(height: 24),

                  // Demo credentials
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.blue.shade200),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Row(
                              children: [
                                Icon(Icons.info_outline, size: 18, color: Colors.blue.shade700),
                                const SizedBox(width: 8),
                                Text(
                                  'Demo Credentials',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w600,
                                    color: Colors.blue.shade700,
                                  ),
                                ),
                              ],
                            ),
                            TextButton(
                              onPressed: () {
                                _emailController.text = 'admin@vyaparcrm.local';
                                _passwordController.text = 'password123';
                              },
                              child: const Text('Fill'),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Email: admin@vyaparcrm.local',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.blue.shade900,
                            fontFamily: 'monospace',
                          ),
                        ),
                        Text(
                          'Password: password123',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.blue.shade900,
                            fontFamily: 'monospace',
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Server IP: ${AppConfig.baseUrl}',
                          style: TextStyle(
                            fontSize: 10,
                            color: Colors.blue.shade700,
                            fontFamily: 'monospace',
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
