/// Lead model matching Laravel LeadResource
class Lead {
  final int id;
  final String? source;
  final String? sourceProvider;
  final String name;
  final String phone;
  final String? email;
  final String? city;
  final String? state;
  final String stage;
  final double expectedValue;
  final DateTime? nextFollowUpAt;
  final String? notes;
  final String? queryType;
  final String? queryMessage;
  final String? productName;
  final int? assignedToUserId;
  final AssignedUser? assignedTo;
  final bool hasOverdueFollowUp;
  final DateTime? createdAt;

  Lead({
    required this.id,
    this.source,
    this.sourceProvider,
    required this.name,
    required this.phone,
    this.email,
    this.city,
    this.state,
    required this.stage,
    required this.expectedValue,
    this.nextFollowUpAt,
    this.notes,
    this.queryType,
    this.queryMessage,
    this.productName,
    this.assignedToUserId,
    this.assignedTo,
    required this.hasOverdueFollowUp,
    this.createdAt,
  });

  factory Lead.fromJson(Map<String, dynamic> json) {
    return Lead(
      id: json['id'] as int,
      source: json['source'] as String?,
      sourceProvider: json['source_provider'] as String?,
      name: json['name'] as String,
      phone: json['phone'] as String,
      email: json['email'] as String?,
      city: json['city'] as String?,
      state: json['state'] as String?,
      stage: json['stage'] as String? ?? 'new',
      expectedValue: (json['expected_value'] as num?)?.toDouble() ?? 0.0,
      nextFollowUpAt: json['next_follow_up_at'] != null 
          ? DateTime.parse(json['next_follow_up_at'] as String) 
          : null,
      notes: json['notes'] as String?,
      queryType: json['query_type'] as String?,
      queryMessage: json['query_message'] as String?,
      productName: json['product_name'] as String?,
      assignedToUserId: json['assigned_to_user_id'] as int?,
      assignedTo: json['assigned_to'] != null 
          ? AssignedUser.fromJson(json['assigned_to'] as Map<String, dynamic>) 
          : null,
      hasOverdueFollowUp: json['has_overdue_follow_up'] as bool? ?? false,
      createdAt: json['created_at'] != null ? DateTime.parse(json['created_at'] as String) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'source': source,
      'source_provider': sourceProvider,
      'name': name,
      'phone': phone,
      'email': email,
      'city': city,
      'state': state,
      'stage': stage,
      'expected_value': expectedValue,
      'next_follow_up_at': nextFollowUpAt?.toIso8601String(),
      'notes': notes,
      'query_type': queryType,
      'query_message': queryMessage,
      'product_name': productName,
      'assigned_to_user_id': assignedToUserId,
      'has_overdue_follow_up': hasOverdueFollowUp,
      'created_at': createdAt?.toIso8601String(),
    };
  }

  /// Lead stages
  static const List<String> stages = [
    'new',
    'contacted',
    'qualified',
    'proposal',
    'negotiation',
    'won',
    'lost',
  ];

  /// Lead sources
  static const List<String> sources = [
    'website',
    'indiamart',
    'justdial',
    'facebook',
    'google',
    'referral',
    'cold_call',
    'exhibition',
    'other',
  ];

  /// Get display name for stage
  String get stageDisplayName {
    switch (stage) {
      case 'new': return 'New';
      case 'contacted': return 'Contacted';
      case 'qualified': return 'Qualified';
      case 'proposal': return 'Proposal';
      case 'negotiation': return 'Negotiation';
      case 'won': return 'Won';
      case 'lost': return 'Lost';
      default: return stage;
    }
  }

  /// Get display name for source
  String get sourceDisplayName {
    switch (source) {
      case 'website': return 'Website';
      case 'indiamart': return 'IndiaMART';
      case 'justdial': return 'JustDial';
      case 'facebook': return 'Facebook';
      case 'google': return 'Google';
      case 'referral': return 'Referral';
      case 'cold_call': return 'Cold Call';
      case 'exhibition': return 'Exhibition';
      case 'other': return 'Other';
      default: return source ?? 'Unknown';
    }
  }

  /// Copy with new values
  Lead copyWith({
    int? id,
    String? source,
    String? sourceProvider,
    String? name,
    String? phone,
    String? email,
    String? city,
    String? state,
    String? stage,
    double? expectedValue,
    DateTime? nextFollowUpAt,
    String? notes,
    String? queryType,
    String? queryMessage,
    String? productName,
    int? assignedToUserId,
    AssignedUser? assignedTo,
    bool? hasOverdueFollowUp,
    DateTime? createdAt,
  }) {
    return Lead(
      id: id ?? this.id,
      source: source ?? this.source,
      sourceProvider: sourceProvider ?? this.sourceProvider,
      name: name ?? this.name,
      phone: phone ?? this.phone,
      email: email ?? this.email,
      city: city ?? this.city,
      state: state ?? this.state,
      stage: stage ?? this.stage,
      expectedValue: expectedValue ?? this.expectedValue,
      nextFollowUpAt: nextFollowUpAt ?? this.nextFollowUpAt,
      notes: notes ?? this.notes,
      queryType: queryType ?? this.queryType,
      queryMessage: queryMessage ?? this.queryMessage,
      productName: productName ?? this.productName,
      assignedToUserId: assignedToUserId ?? this.assignedToUserId,
      assignedTo: assignedTo ?? this.assignedTo,
      hasOverdueFollowUp: hasOverdueFollowUp ?? this.hasOverdueFollowUp,
      createdAt: createdAt ?? this.createdAt,
    );
  }
}

/// Assigned user (minimal)
class AssignedUser {
  final int id;
  final String name;

  AssignedUser({
    required this.id,
    required this.name,
  });

  factory AssignedUser.fromJson(Map<String, dynamic> json) {
    return AssignedUser(
      id: json['id'] as int,
      name: json['name'] as String,
    );
  }
}

/// Leads list response with pagination
class LeadsResponse {
  final List<Lead> data;
  final PaginationMeta meta;

  LeadsResponse({
    required this.data,
    required this.meta,
  });

  factory LeadsResponse.fromJson(Map<String, dynamic> json) {
    return LeadsResponse(
      data: (json['data'] as List)
          .map((e) => Lead.fromJson(e as Map<String, dynamic>))
          .toList(),
      meta: PaginationMeta.fromJson(json['meta'] as Map<String, dynamic>),
    );
  }
}

/// Pagination metadata
class PaginationMeta {
  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;

  PaginationMeta({
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
  });

  factory PaginationMeta.fromJson(Map<String, dynamic> json) {
    return PaginationMeta(
      currentPage: json['current_page'] as int,
      lastPage: json['last_page'] as int,
      perPage: json['per_page'] as int,
      total: json['total'] as int,
    );
  }

  bool get hasNextPage => currentPage < lastPage;
  bool get hasPreviousPage => currentPage > 1;
}
