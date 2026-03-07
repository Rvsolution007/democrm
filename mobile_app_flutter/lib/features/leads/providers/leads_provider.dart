import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/leads_repository.dart';
import '../domain/lead_model.dart';

/// State for leads list
class LeadsListState {
  final List<Lead> leads;
  final bool isLoading;
  final bool isLoadingMore;
  final String? error;
  final int currentPage;
  final int lastPage;
  final String? searchQuery;
  final String? stageFilter;
  final String viewMode; // 'list' or 'kanban'

  const LeadsListState({
    this.leads = const [],
    this.isLoading = false,
    this.isLoadingMore = false,
    this.error,
    this.currentPage = 1,
    this.lastPage = 1,
    this.searchQuery,
    this.stageFilter,
    this.viewMode = 'list',
  });

  LeadsListState copyWith({
    List<Lead>? leads,
    bool? isLoading,
    bool? isLoadingMore,
    String? error,
    int? currentPage,
    int? lastPage,
    String? searchQuery,
    String? stageFilter,
    String? viewMode,
  }) {
    return LeadsListState(
      leads: leads ?? this.leads,
      isLoading: isLoading ?? this.isLoading,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      error: error,
      currentPage: currentPage ?? this.currentPage,
      lastPage: lastPage ?? this.lastPage,
      searchQuery: searchQuery ?? this.searchQuery,
      stageFilter: stageFilter ?? this.stageFilter,
      viewMode: viewMode ?? this.viewMode,
    );
  }

  bool get hasMore => currentPage < lastPage;
}

/// Leads list notifier
class LeadsListNotifier extends StateNotifier<LeadsListState> {
  final LeadsRepository _repository;

  LeadsListNotifier(this._repository) : super(const LeadsListState()) {
    loadLeads();
  }

  /// Set view mode (list/kanban)
  Future<void> setViewMode(String mode) async {
    if (state.viewMode == mode) return;
    state = state.copyWith(viewMode: mode);
    await loadLeads();
  }

  /// Load leads (first page or all for kanban)
  Future<void> loadLeads() async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final isKanban = state.viewMode == 'kanban';
      final response = await _repository.getLeads(
        page: 1,
        search: state.searchQuery,
        stage: state.stageFilter,
        all: isKanban,
      );
      
      state = state.copyWith(
        leads: response.data,
        isLoading: false,
        currentPage: response.meta.currentPage,
        lastPage: response.meta.lastPage,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  /// Load more leads (pagination) - only for List view
  Future<void> loadMore() async {
    if (state.viewMode == 'kanban') return; // No pagination in Kanban
    if (state.isLoadingMore || !state.hasMore) return;
    
    state = state.copyWith(isLoadingMore: true);
    
    try {
      final response = await _repository.getLeads(
        page: state.currentPage + 1,
        search: state.searchQuery,
        stage: state.stageFilter,
      );
      
      state = state.copyWith(
        leads: [...state.leads, ...response.data],
        isLoadingMore: false,
        currentPage: response.meta.currentPage,
        lastPage: response.meta.lastPage,
      );
    } catch (e) {
      state = state.copyWith(isLoadingMore: false);
    }
  }

  /// Search leads
  Future<void> search(String query) async {
    state = state.copyWith(searchQuery: query.isEmpty ? null : query);
    await loadLeads();
  }

  /// Filter by stage
  Future<void> filterByStage(String? stage) async {
    state = state.copyWith(stageFilter: stage);
    await loadLeads();
  }

  /// Refresh leads
  Future<void> refresh() async {
    await loadLeads();
  }

  /// Update a lead in the list
  void updateLeadInList(Lead updatedLead) {
    final index = state.leads.indexWhere((l) => l.id == updatedLead.id);
    if (index != -1) {
      final newLeads = [...state.leads];
      newLeads[index] = updatedLead;
      state = state.copyWith(leads: newLeads);
    }
  }
  
  /// Add a lead to the list
  void addLeadToList(Lead newLead) {
    state = state.copyWith(leads: [newLead, ...state.leads]);
  }
  
  /// Remove a lead from the list
  void removeLeadFromList(int leadId) {
    state = state.copyWith(
      leads: state.leads.where((l) => l.id != leadId).toList(),
    );
  }
}

/// Provider for leads list
final leadsListProvider = StateNotifierProvider<LeadsListNotifier, LeadsListState>((ref) {
  final repository = ref.watch(leadsRepositoryProvider);
  return LeadsListNotifier(repository);
});

/// State for single lead detail
class LeadDetailState {
  final Lead? lead;
  final bool isLoading;
  final bool isUpdating;
  final String? error;

  const LeadDetailState({
    this.lead,
    this.isLoading = false,
    this.isUpdating = false,
    this.error,
  });

  LeadDetailState copyWith({
    Lead? lead,
    bool? isLoading,
    bool? isUpdating,
    String? error,
  }) {
    return LeadDetailState(
      lead: lead ?? this.lead,
      isLoading: isLoading ?? this.isLoading,
      isUpdating: isUpdating ?? this.isUpdating,
      error: error,
    );
  }
}

/// Lead detail notifier
class LeadDetailNotifier extends StateNotifier<LeadDetailState> {
  final LeadsRepository _repository;
  final int leadId;

  LeadDetailNotifier(this._repository, this.leadId) : super(const LeadDetailState()) {
    loadLead();
  }

  /// Load lead detail
  Future<void> loadLead() async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final lead = await _repository.getLead(leadId);
      state = state.copyWith(lead: lead, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  /// Update lead stage
  Future<bool> updateStage(String stage) async {
    state = state.copyWith(isUpdating: true, error: null);
    
    try {
      final updatedLead = await _repository.updateStage(leadId, stage);
      state = state.copyWith(lead: updatedLead, isUpdating: false);
      return true;
    } catch (e) {
      state = state.copyWith(isUpdating: false, error: e.toString());
      return false;
    }
  }

  /// Update lead notes
  Future<bool> updateNotes(String notes) async {
    state = state.copyWith(isUpdating: true, error: null);
    
    try {
      final updatedLead = await _repository.updateLead(leadId, notes: notes);
      state = state.copyWith(lead: updatedLead, isUpdating: false);
      return true;
    } catch (e) {
      state = state.copyWith(isUpdating: false, error: e.toString());
      return false;
    }
  }
}

/// Provider for lead detail (family)
final leadDetailProvider = StateNotifierProvider.family<LeadDetailNotifier, LeadDetailState, int>((ref, leadId) {
  final repository = ref.watch(leadsRepositoryProvider);
  return LeadDetailNotifier(repository, leadId);
});
