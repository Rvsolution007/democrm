import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/customers_repository.dart';
import '../domain/customer_model.dart';

/// State for customers list
class CustomersListState {
  final List<Customer> customers;
  final bool isLoading;
  final bool isLoadingMore;
  final String? error;
  final int currentPage;
  final int lastPage;
  final String? searchQuery;

  const CustomersListState({
    this.customers = const [],
    this.isLoading = false,
    this.isLoadingMore = false,
    this.error,
    this.currentPage = 1,
    this.lastPage = 1,
    this.searchQuery,
  });

  CustomersListState copyWith({
    List<Customer>? customers,
    bool? isLoading,
    bool? isLoadingMore,
    String? error,
    int? currentPage,
    int? lastPage,
    String? searchQuery,
  }) {
    return CustomersListState(
      customers: customers ?? this.customers,
      isLoading: isLoading ?? this.isLoading,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      error: error,
      currentPage: currentPage ?? this.currentPage,
      lastPage: lastPage ?? this.lastPage,
      searchQuery: searchQuery ?? this.searchQuery,
    );
  }

  bool get hasMore => currentPage < lastPage;
}

/// Customers list notifier
class CustomersListNotifier extends StateNotifier<CustomersListState> {
  final CustomersRepository _repository;

  CustomersListNotifier(this._repository) : super(const CustomersListState()) {
    loadCustomers();
  }

  /// Load customers (first page)
  Future<void> loadCustomers() async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final response = await _repository.getCustomers(
        page: 1,
        search: state.searchQuery,
      );
      
      state = state.copyWith(
        customers: response.data,
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

  /// Load more customers
  Future<void> loadMore() async {
    if (state.isLoadingMore || !state.hasMore) return;
    
    state = state.copyWith(isLoadingMore: true);
    
    try {
      final response = await _repository.getCustomers(
        page: state.currentPage + 1,
        search: state.searchQuery,
      );
      
      state = state.copyWith(
        customers: [...state.customers, ...response.data],
        isLoadingMore: false,
        currentPage: response.meta.currentPage,
        lastPage: response.meta.lastPage,
      );
    } catch (e) {
      state = state.copyWith(isLoadingMore: false);
    }
  }

  /// Search customers
  Future<void> search(String query) async {
    state = state.copyWith(searchQuery: query.isEmpty ? null : query);
    await loadCustomers();
  }

  /// Refresh customers
  Future<void> refresh() async {
    await loadCustomers();
  }
}

/// Provider for customers list
final customersListProvider = StateNotifierProvider<CustomersListNotifier, CustomersListState>((ref) {
  final repository = ref.watch(customersRepositoryProvider);
  return CustomersListNotifier(repository);
});

/// State for customer detail
class CustomerDetailState {
  final Customer? customer;
  final bool isLoading;
  final String? error;

  const CustomerDetailState({
    this.customer,
    this.isLoading = false,
    this.error,
  });

  CustomerDetailState copyWith({
    Customer? customer,
    bool? isLoading,
    String? error,
  }) {
    return CustomerDetailState(
      customer: customer ?? this.customer,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

/// Customer detail notifier
class CustomerDetailNotifier extends StateNotifier<CustomerDetailState> {
  final CustomersRepository _repository;
  final int customerId;

  CustomerDetailNotifier(this._repository, this.customerId) : super(const CustomerDetailState()) {
    loadCustomer();
  }

  /// Load customer detail
  Future<void> loadCustomer() async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final customer = await _repository.getCustomer(customerId);
      state = state.copyWith(customer: customer, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }
}

/// Provider for customer detail (family)
final customerDetailProvider = StateNotifierProvider.family<CustomerDetailNotifier, CustomerDetailState, int>((ref, customerId) {
  final repository = ref.watch(customersRepositoryProvider);
  return CustomerDetailNotifier(repository, customerId);
});
