import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../domain/lead_model.dart';

/// Provider for LeadsRepository
final leadsRepositoryProvider = Provider<LeadsRepository>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return LeadsRepository(apiClient);
});

/// Leads repository
class LeadsRepository {
  final ApiClient _apiClient;

  LeadsRepository(this._apiClient);

  /// Get paginated leads with optional filters
  Future<LeadsResponse> getLeads({
    int page = 1,
    int perPage = 15,
    String? search,
    String? stage,
    String? source,
    String? assignedTo,
    bool? overdueOnly,
    String sortBy = 'created_at',
    String order = 'desc',
    bool all = false,
  }) async {
    final queryParams = <String, dynamic>{
      'page': page,
      'per_page': perPage,
      'sort': sortBy,
      'order': order,
    };

    if (all) queryParams['all'] = 1;
    if (search != null && search.isNotEmpty) queryParams['search'] = search;
    if (stage != null && stage.isNotEmpty) queryParams['stage'] = stage;
    if (source != null && source.isNotEmpty) queryParams['source'] = source;
    if (assignedTo != null) queryParams['assigned_to'] = assignedTo;
    if (overdueOnly == true) queryParams['overdue_only'] = '1';

    final response = await _apiClient.get('/leads', queryParameters: queryParams);
    return LeadsResponse.fromJson(response.data as Map<String, dynamic>);
  }

  /// Get single lead by ID
  Future<Lead> getLead(int id) async {
    final response = await _apiClient.get('/leads/$id');
    final data = response.data as Map<String, dynamic>;
    return Lead.fromJson(data['data'] as Map<String, dynamic>);
  }

  /// Create new lead
  Future<Lead> createLead({
    required String name,
    required String phone,
    String? email,
    String? city,
    String? state,
    String source = 'other',
    double? expectedValue,
    String? notes,
  }) async {
    final response = await _apiClient.post('/leads', data: {
      'name': name,
      'phone': phone,
      'email': email,
      'city': city,
      'state': state,
      'source': source,
      'expected_value': expectedValue,
      'notes': notes,
    });

    final data = response.data as Map<String, dynamic>;
    return Lead.fromJson(data['data'] as Map<String, dynamic>);
  }

  /// Update lead
  Future<Lead> updateLead(int id, {
    String? name,
    String? phone,
    String? email,
    String? city,
    String? state,
    String? stage,
    String? source,
    double? expectedValue,
    String? notes,
    DateTime? nextFollowUpAt,
  }) async {
    final data = <String, dynamic>{};
    
    if (name != null) data['name'] = name;
    if (phone != null) data['phone'] = phone;
    if (email != null) data['email'] = email;
    if (city != null) data['city'] = city;
    if (state != null) data['state'] = state;
    if (stage != null) data['stage'] = stage;
    if (source != null) data['source'] = source;
    if (expectedValue != null) data['expected_value'] = expectedValue;
    if (notes != null) data['notes'] = notes;
    if (nextFollowUpAt != null) data['next_follow_up_at'] = nextFollowUpAt.toIso8601String();

    final response = await _apiClient.patch('/leads/$id', data: data);
    final responseData = response.data as Map<String, dynamic>;
    return Lead.fromJson(responseData['data'] as Map<String, dynamic>);
  }

  /// Update lead stage
  Future<Lead> updateStage(int id, String stage) async {
    final response = await _apiClient.post('/leads/$id/stage', data: {
      'stage': stage,
    });
    final data = response.data as Map<String, dynamic>;
    return Lead.fromJson(data['data'] as Map<String, dynamic>);
  }

  /// Set follow-up date
  Future<Lead> setFollowUp(int id, DateTime nextFollowUpAt) async {
    final response = await _apiClient.post('/leads/$id/follow-up', data: {
      'next_follow_up_at': nextFollowUpAt.toIso8601String(),
    });
    final data = response.data as Map<String, dynamic>;
    return Lead.fromJson(data['data'] as Map<String, dynamic>);
  }

  /// Delete lead
  Future<void> deleteLead(int id) async {
    await _apiClient.delete('/leads/$id');
  }
}
