@extends('admin.layouts.app')

@section('title', 'Payments')
@section('breadcrumb', 'Payments')

@section('content')
    <div class="page-header" style="margin-bottom:24px">
        <div class="page-header-content"
            style="display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <h1 class="page-title">Payments</h1>
                <p class="page-description">Track all received payments</p>
            </div>

            <div style="flex:2;min-width:300px;max-width:450px">
                <div style="display:flex;gap:20px;">
                    <!-- Total Received Card -->
                    <div
                        style="flex:1;background:linear-gradient(135deg,#059669 0%,#10b981 100%);padding:14px 24px;border-radius:12px;box-shadow:0 4px 10px rgba(16,185,129,0.3);color:white;display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <p id="summary-total-label"
                                style="margin:0 0 4px 0;font-size:12px;font-weight:600;color:#d1fae5;text-transform:uppercase;letter-spacing:0.05em">
                                Total Received</p>
                            <h3 id="total-received-amount"
                                style="margin:0;font-size:24px;font-weight:800;letter-spacing:-0.5px">
                                ₹{{ number_format($totalReceived, 2) }}</h3>
                        </div>
                        <div
                            style="width:44px;height:44px;background:rgba(255,255,255,0.2);backdrop-filter:blur(4px);border-radius:12px;display:flex;align-items:center;justify-content:center">
                            <i data-lucide="wallet" style="width:24px;height:24px;stroke-width:2.5px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div
            style="padding:12px 20px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:8px;margin-bottom:20px;display:flex;align-items:center;gap:8px">
            <i data-lucide="check-circle" style="width:18px;height:18px"></i> {{ session('success') }}
        </div>
    @endif



    <div class="table-container">
        <div class="table-toolbar" style="flex-wrap:wrap;gap:10px;padding:14px 20px">
            <form method="GET" id="payments-filter-form"
                style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;flex:1">
                {{-- Search --}}
                <div style="min-width:200px;max-width:280px;flex:1">
                    <label
                        style="display:block;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Search</label>
                    <div style="position:relative">
                        <i data-lucide="search"
                            style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#94a3b8"></i>
                        <input type="text" id="payments-search" name="search" class="form-input"
                            placeholder="Quote No, Client, Lead..." value="{{ request('search') }}"
                            style="width:100%;padding-left:34px;font-size:13px;height:38px;border-color:#e2e8f0;background:#f8fafc;transition:all 0.2s"
                            onfocus="this.style.borderColor='var(--primary)';this.style.background='#fff'"
                            onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'">
                    </div>
                </div>

                {{-- Custom Date Range --}}
                <div style="min-width:180px">
                    <label
                        style="display:block;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Custom
                        Date Range</label>
                    <div style="position:relative">
                        <i data-lucide="calendar"
                            style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#94a3b8"></i>
                        <input type="text" id="date-range-picker" name="date_range" class="form-input"
                            placeholder="Select Date Range" value="{{ request('date_range') }}"
                            style="width:100%;padding-left:32px;font-size:13px;height:38px;border-color:#e2e8f0;background:#fff;cursor:pointer">
                        <input type="hidden" name="start_date" id="start_date" value="{{ request('start_date') }}">
                        <input type="hidden" name="end_date" id="end_date" value="{{ request('end_date') }}">
                    </div>
                </div>

                @if(can('quotes.global') || auth()->user()->isAdmin())
                    <div style="min-width:140px">
                        <label
                            style="display:block;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Recorded
                            By</label>
                        <select name="user_id" class="form-select" style="width:100%;font-size:13px;height:38px"
                            onchange="triggerPaymentsAjax()">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div style="min-width:140px">
                    <label
                        style="display:block;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Payment
                        Type</label>
                    <select name="payment_type" class="form-select" style="width:100%;font-size:13px;height:38px"
                        onchange="triggerPaymentsAjax()">
                        <option value="">All Types</option>
                        @foreach($paymentTypes as $type)
                            <option value="{{ $type }}" {{ request('payment_type') === $type ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;width:100%">
                <div style="display:flex;align-items:center;gap:12px">
                    <div id="payments-count-badge"
                        style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:#f1f5f9;border-radius:20px;border:1px solid #e2e8f0;font-size:12px">
                        <span style="display:flex;align-items:center;gap:6px">
                            <i data-lucide="hash" style="width:14px;height:14px;color:#64748b"></i>
                            <span style="font-weight:700;color:#334155"
                                id="payments-total-count">{{ $payments->total() }}</span>
                            <span style="color:#64748b;font-weight:500">Payments Found</span>
                        </span>
                    </div>
                </div>
                <div style="display:flex;gap:12px;align-items:center;">
                    <a href="{{ route('admin.payments.index') }}" id="btn-clear-filters" class="btn btn-outline"
                        style="height:34px;padding:0 14px;font-size:12px;border-color:#e2e8f0;color:#64748b;background:#fff;{{ request()->hasAny(['search', 'user_id', 'payment_type', 'date_range']) ? 'display:flex;align-items:center;' : 'display:none;' }}">
                        <i data-lucide="x-circle" style="width:14px;height:14px;margin-right:6px"></i> Clear Filters
                    </a>
                </div>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Client / Lead</th>
                        <th>Quote No</th>
                        <th>Amount Paid</th>
                        <th>Payment Type</th>
                        <th>Date & Time</th>
                        <th>Recorded By</th>
                        <th>Notes</th>
                        <th style="width: 100px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="payments-tbody">
                    @include('admin.payments.partials.payments_table_body', ['payments' => $payments])
                </tbody>
            </table>
            <div class="table-footer" id="payments-pagination">
                <span>Showing {{ $payments->count() }} of {{ $payments->total() }} entries</span>
                {{ $payments->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Include Flatpickr for Date Range Selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let startDateVal = document.getElementById('start_date').value;
            let endDateVal = document.getElementById('end_date').value;
            let defaultDates = [];
            if (startDateVal && endDateVal) {
                defaultDates = [startDateVal, endDateVal];
            }

            // Initialize Flatpickr for date range
            flatpickr("#date-range-picker", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: defaultDates,
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        document.getElementById('start_date').value = instance.formatDate(selectedDates[0], "Y-m-d");
                        document.getElementById('end_date').value = instance.formatDate(selectedDates[1], "Y-m-d");
                        triggerPaymentsAjax();
                    } else if (selectedDates.length === 0) {
                        document.getElementById('start_date').value = '';
                        document.getElementById('end_date').value = '';
                        triggerPaymentsAjax();
                    }
                }
            });

            const searchInput = document.getElementById('payments-search');
            let debounceTimer = null;

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => triggerPaymentsAjax(), 600);
                });

                // Prevent form submit on Enter
                searchInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(debounceTimer);
                        triggerPaymentsAjax();
                    }
                });
            }

            // Also hijack pagination clicks
            document.getElementById('payments-pagination').addEventListener('click', function (e) {
                const link = e.target.closest('a');
                if (link) {
                    e.preventDefault();
                    fetchPayments(link.href);
                }
            });
        });

        function triggerPaymentsAjax() {
            const form = document.getElementById('payments-filter-form');
            const formData = new FormData(form);
            const params = new URLSearchParams(formData).toString();
            const url = '{{ route("admin.payments.index") }}?' + params;
            fetchPayments(url);

            // Toggle clear filters button visibility
            const hasFilters = Array.from(formData.entries()).some(([k, v]) => v.trim() !== '' && k !== 'month');
            document.getElementById('btn-clear-filters').style.display = hasFilters ? 'flex' : 'none';
        }

        function fetchPayments(url) {
            const tbody = document.getElementById('payments-tbody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:30px 0;color:#94a3b8"><div style="display:flex;align-items:center;justify-content:center;gap:8px"><svg style="animation:spin 1s linear infinite;width:18px;height:18px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/></svg> Loading Payments...</div></td></tr>';
            }

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.html !== undefined) {
                        document.getElementById('payments-tbody').innerHTML = data.html;
                        document.getElementById('payments-pagination').innerHTML = data.pagination;
                        document.getElementById('total-received-amount').innerHTML = '₹' + data.total_received;

                        const countEl = document.getElementById('payments-total-count');
                        if (countEl) countEl.textContent = data.total_count;

                        // Re-bind pagination clicks inside the new pagination HTML
                        const newPagination = document.getElementById('payments-pagination');
                        newPagination.querySelectorAll('a').forEach(a => {
                            a.addEventListener('click', function (e) {
                                e.preventDefault();
                                fetchPayments(this.href);
                            });
                        });

                        if (typeof window.lucide !== 'undefined') {
                            window.lucide.createIcons();
                        }
                    }
                })
                .catch(err => {
                    console.error("Failed to fetch payments:", err);
                });
        }

        function openEditPaymentModal(id, amount, paymentType, paymentDate, notes) {
            document.getElementById('edit_payment_id').value = id;
            document.getElementById('edit_amount').value = amount;
            document.getElementById('edit_payment_type').value = paymentType;
            document.getElementById('edit_payment_date').value = paymentDate;
            document.getElementById('edit_notes').value = notes;

            document.getElementById('edit_payment_form').action = `/admin/payments/${id}`;
            document.getElementById('edit_payment_modal').classList.add('active');
            document.getElementById('edit_payment_modal_overlay').classList.add('active');
        }

        function closeEditPaymentModal() {
            document.getElementById('edit_payment_modal').classList.remove('active');
            document.getElementById('edit_payment_modal_overlay').classList.remove('active');
        }
    </script>

    <!-- Edit Payment Modal -->
    <div class="modal-overlay" id="edit_payment_modal_overlay" onclick="closeEditPaymentModal()"></div>
    <div class="modal" id="edit_payment_modal" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title">Edit Payment</h3>
            <button class="modal-close" onclick="closeEditPaymentModal()"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <form id="edit_payment_form" method="POST" action="">
                @csrf
                @method('PUT')

                <input type="hidden" id="edit_payment_id" name="id">

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Amount (₹) *</label>
                    <input type="number" name="amount" id="edit_amount" class="form-input" step="0.01" min="0.01" required>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Payment Type *</label>
                    <select name="payment_type" id="edit_payment_type" class="form-select" required>
                        @foreach($paymentTypes as $type)
                            <option value="{{ $type }}">{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Payment Date *</label>
                    <input type="datetime-local" name="payment_date" id="edit_payment_date" class="form-input" required>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" id="edit_notes" class="form-input" rows="3"
                        placeholder="Additional details..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                    <button type="button" class="btn btn-outline" onclick="closeEditPaymentModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Payment</button>
                </div>
            </form>
        </div>
    </div>
@endpush