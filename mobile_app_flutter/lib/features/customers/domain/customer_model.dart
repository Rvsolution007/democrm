import '../../leads/domain/lead_model.dart';

/// Customer model matching Laravel ClientResource
class Customer {
  final int id;
  final String type;
  final String? businessName;
  final String? contactName;
  final String displayName;
  final String phone;
  final String? email;
  final String? gstin;
  final String? pan;
  final Address? billingAddress;
  final Address? shippingAddress;
  final double creditLimit;
  final double outstandingAmount;
  final int? paymentTermsDays;
  final String status;
  final String? notes;
  final int? leadId;
  final DateTime createdAt;

  Customer({
    required this.id,
    required this.type,
    this.businessName,
    this.contactName,
    required this.displayName,
    required this.phone,
    this.email,
    this.gstin,
    this.pan,
    this.billingAddress,
    this.shippingAddress,
    required this.creditLimit,
    required this.outstandingAmount,
    this.paymentTermsDays,
    required this.status,
    this.notes,
    this.leadId,
    required this.createdAt,
  });

  factory Customer.fromJson(Map<String, dynamic> json) {
    return Customer(
      id: json['id'] as int,
      type: json['type'] as String? ?? 'business',
      businessName: json['business_name'] as String?,
      contactName: json['contact_name'] as String?,
      displayName: json['display_name'] as String? ?? json['contact_name'] as String? ?? 'Unknown',
      phone: json['phone'] as String,
      email: json['email'] as String?,
      gstin: json['gstin'] as String?,
      pan: json['pan'] as String?,
      billingAddress: json['billing_address'] != null 
          ? Address.fromJson(json['billing_address'] is Map ? json['billing_address'] as Map<String, dynamic> : {})
          : null,
      shippingAddress: json['shipping_address'] != null 
          ? Address.fromJson(json['shipping_address'] is Map ? json['shipping_address'] as Map<String, dynamic> : {})
          : null,
      creditLimit: (json['credit_limit'] as num?)?.toDouble() ?? 0.0,
      outstandingAmount: (json['outstanding_amount'] as num?)?.toDouble() ?? 0.0,
      paymentTermsDays: json['payment_terms_days'] as int?,
      status: json['status'] as String? ?? 'active',
      notes: json['notes'] as String?,
      leadId: json['lead_id'] as int?,
      createdAt: DateTime.parse(json['created_at'] as String),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'type': type,
      'business_name': businessName,
      'contact_name': contactName,
      'display_name': displayName,
      'phone': phone,
      'email': email,
      'gstin': gstin,
      'pan': pan,
      'billing_address': billingAddress?.toJson(),
      'shipping_address': shippingAddress?.toJson(),
      'credit_limit': creditLimit,
      'outstanding_amount': outstandingAmount,
      'payment_terms_days': paymentTermsDays,
      'status': status,
      'notes': notes,
      'lead_id': leadId,
      'created_at': createdAt.toIso8601String(),
    };
  }

  bool get isActive => status == 'active';
  bool get isBusiness => type == 'business';
}

/// Address model
class Address {
  final String? line1;
  final String? line2;
  final String? city;
  final String? state;
  final String? pincode;
  final String? country;

  Address({
    this.line1,
    this.line2,
    this.city,
    this.state,
    this.pincode,
    this.country,
  });

  factory Address.fromJson(Map<String, dynamic> json) {
    return Address(
      line1: json['line1'] as String?,
      line2: json['line2'] as String?,
      city: json['city'] as String?,
      state: json['state'] as String?,
      pincode: json['pincode'] as String?,
      country: json['country'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'line1': line1,
      'line2': line2,
      'city': city,
      'state': state,
      'pincode': pincode,
      'country': country,
    };
  }

  String get formatted {
    final parts = <String>[];
    if (line1 != null && line1!.isNotEmpty) parts.add(line1!);
    if (line2 != null && line2!.isNotEmpty) parts.add(line2!);
    if (city != null && city!.isNotEmpty) parts.add(city!);
    if (state != null && state!.isNotEmpty) parts.add(state!);
    if (pincode != null && pincode!.isNotEmpty) parts.add(pincode!);
    return parts.join(', ');
  }
}

/// Customers list response with pagination
class CustomersResponse {
  final List<Customer> data;
  final PaginationMeta meta;

  CustomersResponse({
    required this.data,
    required this.meta,
  });

  factory CustomersResponse.fromJson(Map<String, dynamic> json) {
    return CustomersResponse(
      data: (json['data'] as List)
          .map((e) => Customer.fromJson(e as Map<String, dynamic>))
          .toList(),
      meta: PaginationMeta.fromJson(json['meta'] as Map<String, dynamic>),
    );
  }
}
