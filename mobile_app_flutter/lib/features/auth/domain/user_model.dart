/// User model matching Laravel UserResource
class User {
  final int id;
  final int? companyId;
  final int? roleId;
  final String name;
  final String email;
  final String? phone;
  final String? avatar;
  final String status;
  final DateTime? lastLoginAt;
  final DateTime createdAt;
  final Role? role;
  final Company? company;

  User({
    required this.id,
    this.companyId,
    this.roleId,
    required this.name,
    required this.email,
    this.phone,
    this.avatar,
    required this.status,
    this.lastLoginAt,
    required this.createdAt,
    this.role,
    this.company,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] as int,
      companyId: json['company_id'] as int?,
      roleId: json['role_id'] as int?,
      name: json['name'] as String,
      email: json['email'] as String,
      phone: json['phone'] as String?,
      avatar: json['avatar'] as String?,
      status: json['status'] as String? ?? 'active',
      lastLoginAt: json['last_login_at'] != null 
          ? DateTime.parse(json['last_login_at'] as String) 
          : null,
      createdAt: DateTime.parse(json['created_at'] as String),
      role: json['role'] != null ? Role.fromJson(json['role'] as Map<String, dynamic>) : null,
      company: json['company'] != null ? Company.fromJson(json['company'] as Map<String, dynamic>) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'company_id': companyId,
      'role_id': roleId,
      'name': name,
      'email': email,
      'phone': phone,
      'avatar': avatar,
      'status': status,
      'last_login_at': lastLoginAt?.toIso8601String(),
      'created_at': createdAt.toIso8601String(),
      'role': role?.toJson(),
      'company': company?.toJson(),
    };
  }

  bool get isActive => status == 'active';
}

/// Role model
class Role {
  final int id;
  final String name;
  final List<String>? permissions;

  Role({
    required this.id,
    required this.name,
    this.permissions,
  });

  factory Role.fromJson(Map<String, dynamic> json) {
    return Role(
      id: json['id'] as int,
      name: json['name'] as String,
      permissions: json['permissions'] != null 
          ? List<String>.from(json['permissions'] as List) 
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'permissions': permissions,
    };
  }
}

/// Company model
class Company {
  final int id;
  final String name;

  Company({
    required this.id,
    required this.name,
  });

  factory Company.fromJson(Map<String, dynamic> json) {
    return Company(
      id: json['id'] as int,
      name: json['name'] as String,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
    };
  }
}

/// Login response model
class LoginResponse {
  final String message;
  final User user;
  final String token;
  final String tokenType;
  final DateTime? expiresAt;

  LoginResponse({
    required this.message,
    required this.user,
    required this.token,
    required this.tokenType,
    this.expiresAt,
  });

  factory LoginResponse.fromJson(Map<String, dynamic> json) {
    return LoginResponse(
      message: json['message'] as String,
      user: User.fromJson(json['user'] as Map<String, dynamic>),
      token: json['token'] as String,
      tokenType: json['token_type'] as String,
      expiresAt: json['expires_at'] != null 
          ? DateTime.parse(json['expires_at'] as String) 
          : null,
    );
  }
}
