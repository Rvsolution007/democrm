import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/theme/app_theme.dart';
import '../providers/leads_provider.dart';
import '../domain/lead_model.dart';
import '../data/leads_repository.dart';

class LeadsListScreen extends ConsumerStatefulWidget {
  const LeadsListScreen({super.key});

  @override
  ConsumerState<LeadsListScreen> createState() => _LeadsListScreenState();
}

class _LeadsListScreenState extends ConsumerState<LeadsListScreen> {
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();
  
  // For filters
  String? _selectedAssignedUser;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    // Only load more in list view
    if (ref.read(leadsListProvider).viewMode == 'list') {
      if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
        ref.read(leadsListProvider.notifier).loadMore();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(leadsListProvider);
    final isKanban = state.viewMode == 'kanban';

    return Scaffold(
      backgroundColor: Colors.grey[100],
      appBar: AppBar(
        title: const Text('Leads'),
        actions: [
          // View Toggle
          Container(
            margin: const EdgeInsets.symmetric(vertical: 8, horizontal: 8),
            decoration: BoxDecoration(
              color: Colors.white24,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              children: [
                _buildViewToggleButton(
                  icon: Icons.list,
                  isSelected: !isKanban,
                  onTap: () => ref.read(leadsListProvider.notifier).setViewMode('list'),
                ),
                _buildViewToggleButton(
                  icon: Icons.view_kanban,
                  isSelected: isKanban,
                  onTap: () => ref.read(leadsListProvider.notifier).setViewMode('kanban'),
                ),
              ],
            ),
          ),
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _showAddLeadDialog(context),
          ),
        ],
      ),
      body: Column(
        children: [
          // Search and Filter Section
          Container(
            padding: const EdgeInsets.all(16),
            color: Colors.white,
            child: Column(
              children: [
                // Search Bar
                TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    hintText: 'Search by name, phone...',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: _searchController.text.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () {
                              _searchController.clear();
                              ref.read(leadsListProvider.notifier).search('');
                            },
                          )
                        : null,
                    contentPadding: const EdgeInsets.symmetric(vertical: 10, horizontal: 16),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: BorderSide(color: Colors.grey.shade300),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: BorderSide(color: Colors.grey.shade300),
                    ),
                  ),
                  onSubmitted: (value) {
                    ref.read(leadsListProvider.notifier).search(value);
                  },
                ),
                const SizedBox(height: 12),
                
                // Filters Row
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      // Stage Filter
                      if (!isKanban) ...[ // Kanban is already split by stage
                        DropdownButton(
                          hint: const Text('Stage'),
                          value: state.stageFilter,
                          items: [
                            const DropdownMenuItem(value: null, child: Text('All Stages')),
                            ...Lead.stages.map((s) => DropdownMenuItem(
                              value: s, 
                              child: Text(_getStageDisplayName(s))
                            )),
                          ],
                          onChanged: (val) => ref.read(leadsListProvider.notifier).filterByStage(val as String?),
                          underline: const SizedBox(),
                        ),
                        const SizedBox(width: 16),
                      ],
                      // Assigned Filter (Mock for now, extracting from leads would be better but complex)
                      // Ideally we fetch users list. For now, showing placeholder if API supported it in filter params
                      // Since we don't have users list here easily, omitting or static list
                      // Let's rely on standard stage filtering which is most important.
                    ],
                  ),
                ),
              ],
            ),
          ),
          
          if (state.isLoading && state.leads.isEmpty)
            const Expanded(child: Center(child: CircularProgressIndicator()))
          else if (state.error != null && state.leads.isEmpty)
            Expanded(
              child: Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.error_outline, size: 48, color: Colors.grey),
                    const SizedBox(height: 16),
                    Text(state.error!),
                    TextButton(
                      onPressed: () => ref.read(leadsListProvider.notifier).refresh(),
                      child: const Text('Retry'),
                    )
                  ],
                ),
              ),
            )
          else
            Expanded(
              child: isKanban 
                  ? _buildKanbanView(state.leads) 
                  : _buildListView(state),
            ),
        ],
      ),
    );
  }

  Widget _buildViewToggleButton({
    required IconData icon,
    required bool isSelected,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: isSelected ? Colors.white : Colors.transparent,
          borderRadius: BorderRadius.circular(6),
        ),
        child: Icon(
          icon,
          size: 20,
          color: isSelected ? AppTheme.primaryColor : Colors.white70,
        ),
      ),
    );
  }

  // --- List View ---
  Widget _buildListView(LeadsListState state) {
    if (state.leads.isEmpty) {
      return const Center(child: Text('No leads found'));
    }

    return RefreshIndicator(
      onRefresh: () => ref.read(leadsListProvider.notifier).refresh(),
      child: ListView.separated(
        controller: _scrollController,
        padding: const EdgeInsets.all(16),
        itemCount: state.leads.length + (state.isLoadingMore ? 1 : 0),
        separatorBuilder: (_, __) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          if (index == state.leads.length) {
            return const Center(child: Padding(padding: EdgeInsets.all(16), child: CircularProgressIndicator()));
          }
          return _LeadCard(lead: state.leads[index], compact: false);
        },
      ),
    );
  }

  // --- Kanban View ---
  Widget _buildKanbanView(List<Lead> leads) {
    return RefreshIndicator(
        onRefresh: () => ref.read(leadsListProvider.notifier).refresh(),
        child: SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.all(16),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: Lead.stages.map((stage) {
              final stageLeads = leads.where((l) => l.stage == stage).toList();
              final stageColor = AppTheme.stageColors[stage] ?? Colors.grey;

              return Container(
                width: 300,
                margin: const EdgeInsets.only(right: 16),
                decoration: BoxDecoration(
                  color: Colors.grey[200],
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Column(
                  children: [
                    // Kanban Header
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: const BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.vertical(top: Radius.circular(12)),
                        border: Border(bottom: BorderSide(color: Colors.black12)),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              Container(
                                width: 12,
                                height: 12,
                                decoration: BoxDecoration(
                                  color: stageColor,
                                  shape: BoxShape.circle,
                                ),
                              ),
                              const SizedBox(width: 8),
                              Text(
                                _getStageDisplayName(stage),
                                style: const TextStyle(fontWeight: FontWeight.bold),
                              ),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.grey[100],
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              '${stageLeads.length}',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                            ),
                          ),
                        ],
                      ),
                    ),
                    // Kanban List
                    ConstrainedBox(
                      constraints: BoxConstraints(
                        maxHeight: MediaQuery.of(context).size.height * 0.7,
                      ),
                      child: ListView.separated(
                        padding: const EdgeInsets.all(12),
                        shrinkWrap: true,
                        itemCount: stageLeads.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 12),
                        itemBuilder: (context, index) {
                          return _LeadCard(lead: stageLeads[index], compact: true);
                        },
                      ),
                    ),
                  ],
                ),
              );
            }).toList(),
          ),
        ),
    );
  }

  void _showAddLeadDialog(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => const AddLeadSheet(),
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

class _LeadCard extends StatelessWidget {
  final Lead lead;
  final bool compact;

  const _LeadCard({required this.lead, this.compact = false});

  @override
  Widget build(BuildContext context) {
    final stageColor = AppTheme.stageColors[lead.stage] ?? Colors.grey;

    return Card(
      elevation: compact ? 1 : 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      child: InkWell(
        onTap: () => context.push('/leads/${lead.id}'),
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      lead.name,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  if (!compact)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: stageColor.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        lead.stageDisplayName,
                        style: TextStyle(color: stageColor, fontSize: 12, fontWeight: FontWeight.bold),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  const Icon(Icons.phone, size: 14, color: Colors.grey),
                  const SizedBox(width: 4),
                  Text(lead.phone, style: const TextStyle(color: Colors.grey, fontSize: 13)),
                ],
              ),
              const SizedBox(height: 8),
              if (lead.city != null || lead.state != null) ...[
                Row(
                  children: [
                    const Icon(Icons.location_on, size: 14, color: Colors.grey),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        [lead.city, lead.state].where((e) => e != null).join(', '),
                        style: const TextStyle(color: Colors.grey, fontSize: 13),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
              ],
              
              const Divider(height: 16),
              
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  if (lead.assignedTo != null)
                    Expanded(
                       child: Text(
                        'Assigned: ${lead.assignedTo!.name}',
                        style: const TextStyle(fontSize: 12, color: Colors.grey),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    )
                  else
                    const Text('Unassigned', style: TextStyle(fontSize: 12, color: Colors.grey)),
                    
                  Row(
                    children: [
                      _ActionButton(
                        icon: Icons.phone, 
                        color: Colors.green, 
                        onTap: () => launchUrl(Uri.parse('tel:${lead.phone}')),
                      ),
                      const SizedBox(width: 8),
                      // Extract number for WA
                      _ActionButton(
                        icon: Icons.message, 
                        color: Colors.teal, 
                        onTap: () {
                           final number = lead.phone.replaceAll(RegExp(r'\D'), '');
                           launchUrl(Uri.parse('https://wa.me/91$number'));
                        }
                      ),
                       const SizedBox(width: 8),
                      _ActionButton(
                        icon: Icons.edit, 
                        color: Colors.blue, 
                        onTap: () => _showEditLeadDialog(context, lead),
                      ),
                    ],
                  )
                ],
              )
            ],
          ),
        ),
      ),
    );
  }

  void _showEditLeadDialog(BuildContext context, Lead lead) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => AddLeadSheet(lead: lead),
    );
  }
}

class _ActionButton extends StatelessWidget {
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  const _ActionButton({required this.icon, required this.color, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(8), // Increased touch area
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          shape: BoxShape.circle,
        ),
        child: Icon(icon, size: 16, color: color),
      ),
    );
  }
}

class AddLeadSheet extends ConsumerStatefulWidget {
  final Lead? lead;
  
  const AddLeadSheet({super.key, this.lead});

  @override
  ConsumerState<AddLeadSheet> createState() => _AddLeadSheetState();
}

class _AddLeadSheetState extends ConsumerState<AddLeadSheet> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _phoneController;
  late TextEditingController _emailController;
  late TextEditingController _cityController;
  late String _source;
  late String _stage;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    final l = widget.lead;
    _nameController = TextEditingController(text: l?.name ?? '');
    _phoneController = TextEditingController(text: l?.phone ?? '');
    _emailController = TextEditingController(text: l?.email ?? '');
    _cityController = TextEditingController(text: l?.city ?? '');
    _source = l?.source ?? Lead.sources.first;
    _stage = l?.stage ?? Lead.stages.first;
    
    // Ensure values are in list
    if (!Lead.sources.contains(_source)) _source = Lead.sources.first;
    if (!Lead.stages.contains(_stage)) _stage = Lead.stages.first;
  }
  
  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _cityController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isEdit = widget.lead != null;
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      padding: EdgeInsets.fromLTRB(20, 20, 20, MediaQuery.of(context).viewInsets.bottom + 20),
      child: Form(
        key: _formKey,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(isEdit ? 'Edit Lead' : 'Add New Lead', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const SizedBox(height: 20),
            TextFormField(
              controller: _nameController,
              decoration: const InputDecoration(labelText: 'Name *', prefixIcon: Icon(Icons.person)),
              validator: (v) => v?.isEmpty == true ? 'Required' : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _phoneController,
              decoration: const InputDecoration(labelText: 'Phone *', prefixIcon: Icon(Icons.phone)),
              keyboardType: TextInputType.phone,
              validator: (v) => v?.isEmpty == true ? 'Required' : null,
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                 Expanded(
                   child: DropdownButtonFormField<String>(
                    value: _source,
                    decoration: const InputDecoration(labelText: 'Source', prefixIcon: Icon(Icons.source)),
                    items: Lead.sources.map((s) => DropdownMenuItem(value: s, child: Text(s))).toList(),
                    onChanged: (v) => setState(() => _source = v!),
                   ),
                 ),
                 const SizedBox(width: 12),
                 Expanded(
                   child: DropdownButtonFormField<String>(
                    value: _stage,
                    decoration: const InputDecoration(labelText: 'Stage', prefixIcon: Icon(Icons.flag)),
                    items: Lead.stages.map((s) => DropdownMenuItem(value: s, child: Text(s))).toList(),
                    onChanged: (v) => setState(() => _stage = v!),
                   ),
                 ),
              ],
            ),
            if (!isEdit || widget.lead!.email != null || widget.lead!.city != null) ...[
              const SizedBox(height: 12),
              Row(
                children: [
                   Expanded(
                    child: TextFormField(
                      controller: _emailController,
                      decoration: const InputDecoration(labelText: 'Email', prefixIcon: Icon(Icons.email)),
                      keyboardType: TextInputType.emailAddress,
                    ),
                   ),
                   const SizedBox(width: 12),
                   Expanded(
                    child: TextFormField(
                      controller: _cityController,
                      decoration: const InputDecoration(labelText: 'City', prefixIcon: Icon(Icons.location_city)),
                    ),
                   ),
                ],
              ),
            ],
             const SizedBox(height: 20),
             SizedBox(
               width: double.infinity,
               child: ElevatedButton(
                 onPressed: _isLoading ? null : _save,
                 child: _isLoading 
                    ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) 
                    : Text(isEdit ? 'Update Lead' : 'Save Lead'),
               ),
             )
          ],
        ),
      ),
    );
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    
    setState(() => _isLoading = true);
    
    try {
      final repo = ref.read(leadsRepositoryProvider);
      
      if (widget.lead != null) {
        // Edit Mode
        final updated = await repo.updateLead(
          widget.lead!.id,
          name: _nameController.text,
          phone: _phoneController.text,
          email: _emailController.text,
          city: _cityController.text,
          source: _source,
          stage: _stage,
        );
        ref.read(leadsListProvider.notifier).updateLeadInList(updated);
         if (mounted) {
          Navigator.pop(context);
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Lead updated')));
        }
      } else {
        // Create Mode
        final newLead = await repo.createLead(
          name: _nameController.text,
          phone: _phoneController.text,
          email: _emailController.text.isEmpty ? null : _emailController.text,
          city: _cityController.text.isEmpty ? null : _cityController.text,
          source: _source,
        );
        
        if (_stage != 'new') {
           await repo.updateStage(newLead.id, _stage);
        }
        
        ref.read(leadsListProvider.notifier).addLeadToList(newLead);
        
        if (mounted) {
          Navigator.pop(context);
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Lead created')));
        }
      }

    } catch (e) {
      if (mounted) {
         ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }
}
