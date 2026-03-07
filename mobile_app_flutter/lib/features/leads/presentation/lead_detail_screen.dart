import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/theme/app_theme.dart';
import '../providers/leads_provider.dart';
import '../domain/lead_model.dart';

class LeadDetailScreen extends ConsumerWidget {
  final int leadId;

  const LeadDetailScreen({super.key, required this.leadId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(leadDetailProvider(leadId));
    
    return Scaffold(
      appBar: AppBar(
        title: Text(state.lead?.name ?? 'Lead Details'),
        actions: [
          if (state.lead != null)
            IconButton(
              icon: const Icon(Icons.edit),
              onPressed: () => _showStageUpdateDialog(context, ref, state.lead!),
            ),
        ],
      ),
      body: _buildBody(context, ref, state),
    );
  }

  Widget _buildBody(BuildContext context, WidgetRef ref, LeadDetailState state) {
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
              onPressed: () => ref.read(leadDetailProvider(leadId).notifier).loadLead(),
              child: const Text('Retry'),
            ),
          ],
        ),
      );
    }

    final lead = state.lead;
    if (lead == null) {
      return const Center(child: Text('Lead not found'));
    }

    final stageColor = AppTheme.stageColors[lead.stage] ?? Colors.grey;
    final dateFormat = DateFormat('dd MMM yyyy, HH:mm');

    return RefreshIndicator(
      onRefresh: () => ref.read(leadDetailProvider(leadId).notifier).loadLead(),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Stage card
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Container(
                      height: 50,
                      width: 50,
                      decoration: BoxDecoration(
                        color: stageColor.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(Icons.leaderboard, color: stageColor),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Stage', style: TextStyle(color: Colors.grey, fontSize: 12)),
                          const SizedBox(height: 4),
                          Text(
                            lead.stageDisplayName,
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: stageColor,
                            ),
                          ),
                        ],
                      ),
                    ),
                    ElevatedButton(
                      onPressed: state.isUpdating ? null : () => _showStageUpdateDialog(context, ref, lead),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: stageColor,
                        foregroundColor: Colors.white,
                      ),
                      child: state.isUpdating
                          ? const SizedBox(
                              height: 16,
                              width: 16,
                              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                            )
                          : const Text('Update'),
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
                    _buildInfoRow(Icons.person, 'Name', lead.name),
                    _buildInfoRow(Icons.phone, 'Phone', lead.phone),
                    if (lead.email != null) _buildInfoRow(Icons.email, 'Email', lead.email!),
                    if (lead.city != null || lead.state != null)
                      _buildInfoRow(Icons.location_on, 'Location', [lead.city, lead.state].where((e) => e != null).join(', ')),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Lead info
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Lead Information', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    const Divider(),
                    _buildInfoRow(Icons.source, 'Source', lead.sourceDisplayName),
                    if (lead.expectedValue > 0)
                      _buildInfoRow(Icons.currency_rupee, 'Expected Value', '₹${lead.expectedValue.toStringAsFixed(0)}'),
                    if (lead.nextFollowUpAt != null)
                      _buildInfoRow(
                        Icons.calendar_today,
                        'Next Follow-up',
                        dateFormat.format(lead.nextFollowUpAt!),
                        valueColor: lead.hasOverdueFollowUp ? Colors.orange : null,
                      ),
                    if (lead.assignedTo != null)
                      _buildInfoRow(Icons.person_pin, 'Assigned To', lead.assignedTo!.name),
                    _buildInfoRow(Icons.access_time, 'Created', lead.createdAt != null ? dateFormat.format(lead.createdAt!) : 'N/A'),
                  ],
                ),
              ),
            ),

            // Notes
            if (lead.notes != null && lead.notes!.isNotEmpty) ...[
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Notes', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      const Divider(),
                      Text(lead.notes!),
                    ],
                  ),
                ),
              ),
            ],

            // Query details
            if (lead.queryMessage != null && lead.queryMessage!.isNotEmpty) ...[
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Query Details', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      const Divider(),
                      if (lead.queryType != null)
                        _buildInfoRow(Icons.category, 'Type', lead.queryType!),
                      if (lead.productName != null)
                        _buildInfoRow(Icons.shopping_bag, 'Product', lead.productName!),
                      const SizedBox(height: 8),
                      Text(lead.queryMessage!),
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

  void _showStageUpdateDialog(BuildContext context, WidgetRef ref, Lead lead) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Update Stage'),
        content: SizedBox(
          width: double.maxFinite,
          child: ListView.builder(
            shrinkWrap: true,
            itemCount: Lead.stages.length,
            itemBuilder: (context, index) {
              final stage = Lead.stages[index];
              final isSelected = stage == lead.stage;
              final stageColor = AppTheme.stageColors[stage] ?? Colors.grey;
              
              return ListTile(
                leading: Container(
                  height: 12,
                  width: 12,
                  decoration: BoxDecoration(
                    color: stageColor,
                    shape: BoxShape.circle,
                  ),
                ),
                title: Text(_getStageDisplayName(stage)),
                trailing: isSelected ? const Icon(Icons.check, color: Colors.green) : null,
                onTap: () async {
                  Navigator.pop(context);
                  final success = await ref.read(leadDetailProvider(leadId).notifier).updateStage(stage);
                  if (success) {
                    // Update in the list as well
                    ref.read(leadsListProvider.notifier).updateLeadInList(
                      lead.copyWith(stage: stage),
                    );
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Stage updated successfully')),
                      );
                    }
                  }
                },
              );
            },
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
        ],
      ),
    );
  }

  String _getStageDisplayName(String stage) {
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
}
