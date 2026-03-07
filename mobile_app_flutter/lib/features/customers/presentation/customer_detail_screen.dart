import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../providers/customers_provider.dart';

class CustomerDetailScreen extends ConsumerWidget {
  final int customerId;

  const CustomerDetailScreen({super.key, required this.customerId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(customerDetailProvider(customerId));
    
    return Scaffold(
      appBar: AppBar(
        title: Text(state.customer?.displayName ?? 'Customer Details'),
      ),
      body: _buildBody(context, ref, state),
    );
  }

  Widget _buildBody(BuildContext context, WidgetRef ref, CustomerDetailState state) {
    if (state.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (state.error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(state.error!, style: TextStyle(color: Colors.grey[600])),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () => ref.read(customerDetailProvider(customerId).notifier).loadCustomer(),
              child: const Text('Retry'),
            ),
          ],
        ),
      );
    }

    final customer = state.customer;
    if (customer == null) {
      return const Center(child: Text('Customer not found'));
    }

    final dateFormat = DateFormat('dd MMM yyyy');

    return RefreshIndicator(
      onRefresh: () => ref.read(customerDetailProvider(customerId).notifier).loadCustomer(),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header card
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Container(
                      height: 60,
                      width: 60,
                      decoration: BoxDecoration(
                        color: Colors.indigo.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Center(
                        child: Text(
                          customer.displayName.isNotEmpty ? customer.displayName[0].toUpperCase() : 'C',
                          style: const TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: Colors.indigo,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            customer.displayName,
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          if (customer.businessName != null) ...[
                            const SizedBox(height: 4),
                            Text(
                              customer.businessName!,
                              style: TextStyle(color: Colors.grey[600]),
                            ),
                          ],
                          const SizedBox(height: 4),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: customer.isActive ? Colors.green.withOpacity(0.1) : Colors.grey.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Text(
                              customer.isActive ? 'Active' : 'Inactive',
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                                color: customer.isActive ? Colors.green : Colors.grey,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Contact info
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Contact Information', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    const Divider(),
                    _buildInfoRow(Icons.phone, 'Phone', customer.phone),
                    if (customer.email != null) _buildInfoRow(Icons.email, 'Email', customer.email!),
                    if (customer.contactName != null) _buildInfoRow(Icons.person, 'Contact Person', customer.contactName!),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Business info
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Business Information', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    const Divider(),
                    _buildInfoRow(Icons.business, 'Type', customer.isBusiness ? 'Business' : 'Individual'),
                    if (customer.gstin != null) _buildInfoRow(Icons.receipt, 'GSTIN', customer.gstin!),
                    if (customer.pan != null) _buildInfoRow(Icons.credit_card, 'PAN', customer.pan!),
                    if (customer.paymentTermsDays != null)
                      _buildInfoRow(Icons.calendar_today, 'Payment Terms', '${customer.paymentTermsDays} days'),
                    _buildInfoRow(Icons.access_time, 'Customer Since', dateFormat.format(customer.createdAt)),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Financial info
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Financial Information', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    const Divider(),
                    _buildInfoRow(Icons.account_balance_wallet, 'Credit Limit', '₹${customer.creditLimit.toStringAsFixed(0)}'),
                    _buildInfoRow(
                      Icons.pending,
                      'Outstanding Amount',
                      '₹${customer.outstandingAmount.toStringAsFixed(0)}',
                      valueColor: customer.outstandingAmount > 0 ? Colors.orange : null,
                    ),
                  ],
                ),
              ),
            ),

            // Billing Address
            if (customer.billingAddress != null && customer.billingAddress!.formatted.isNotEmpty) ...[
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Billing Address', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      const Divider(),
                      Text(customer.billingAddress!.formatted),
                    ],
                  ),
                ),
              ),
            ],

            // Notes
            if (customer.notes != null && customer.notes!.isNotEmpty) ...[
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Notes', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      const Divider(),
                      Text(customer.notes!),
                    ],
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value, {Color? valueColor}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: Colors.grey[600]),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: TextStyle(color: Colors.grey[600], fontSize: 12)),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 14,
                    color: valueColor,
                    fontWeight: valueColor != null ? FontWeight.w600 : null,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
