import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../domain/customer_model.dart';

/// Provider for CustomersRepository
final customersRepositoryProvider = Provider<CustomersRepository>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return CustomersRepository(apiClient);
});

/// Customers repository
class CustomersRepository {
  final ApiClient _apiClient;

  CustomersRepository(this._apiClient);

  /// Get paginated customers with optional filters
  Future<CustomersResponse> getCustomers({
    int page = 1,
    int perPage = 15,
    String? search,
    String? status,
    String sortBy = 'created_at',
    String order = 'desc',
  }) async {
    final queryParams = <String, dynamic>{
      'page': page,
      'per_page': perPage,
      'sort': sortBy,
      'order': order,
    };

    if (search != null && search.isNotEmpty) queryParams['search'] = search;
    if (status != null && status.isNotEmpty) queryParams['status'] = status;

    final response = await _apiClient.get('/clients', queryParameters: queryParams);
    return CustomersResponse.fromJson(response.data as Map<String, dynamic>);
  }

  /// Get single customer by ID
  Future<Customer> getCustomer(int id) async {
    final response = await _apiClient.get('/clients/$id');
    final data = response.data as Map<String, dynamic>;
    return Customer.fromJson(data['data'] as Map<String, dynamic>);
  }

  /// Delete customer
  Future<void> deleteCustomer(int id) async {
    await _apiClient.delete('/clients/$id');
  }
}
