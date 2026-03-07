@extends('admin.layouts.app')

@section('title', 'Purchase Payments')
@section('breadcrumb', 'Purchase Payments')

@section('content')
    <div class="page-header" style="margin-bottom:24px">
        <div class="page-header-content"
            style="display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <h1 class="page-title">Purchase Payments</h1>
                <p class="page-description">Track all outgoing payments for purchases</p>
            </div>

            <div style="flex:2;min-width:300px;max-width:450px">
                <div style="display:flex;gap:20px;">
                    <!-- Total Paid Card -->
                    <div
                        style="flex:1;background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);padding:14px 24px;border-radius:12px;box-shadow:0 4px 10px rgba(245,158,11,0.3);color:white;display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <p id="summary-total-label"
                                style="margin:0 0 4px 0;font-size:12px;font-weight:600;color:#fef3c7;text-transform:uppercase;letter-spacing:0.05em">
                                Total Paid</p>
                            <h3 id="total-paid-amount"
                                style="margin:0;font-size:24px;font-weight:800;letter-spacing:-0.5px">
                                ₹{{ number_format($totalPaid, 2) }}</h3>
                        </div>
                        <div
                            style="width:44px;height:44px;background:rgba(255,255,255,0.2);backdrop-filter:blur(4px);border-radius:12px;display:flex;align-items:center;justify-content:center">
                            <i data-lucide="banknote" style="width:24px;height:24px;stroke-width:2.5px;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page-actions"
                style="display:flex;gap:12px;align-items:center;align-self:flex-end;margin-bottom:8px">
                <a href="{{ route('admin.purchases.index') }}" class="btn btn-primary"
                    style="display:inline-flex;align-items:center;gap:8px">
                    <i data-lucide="shopping-cart" style="width:16px;height:16px"></i> Go to Purchases
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div
            style="padding:12px 20px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:8px;margin-bottom:20px;display:flex;align-items:center;gap:8px">
            <i data-lucide="check-circle" style="width:18px;height:18px"></i> {{ session('success') }}
        </div>
    @endif



    <!-- Main Table Container -->
    <div class="table-container" style="background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05)">

        <!-- Filters Section -->
        <div style="padding:20px;border-bottom:1px solid #e2e8f0;background:#f8fafc;border-radius:12px 12px 0 0">
            <form method="GET" id="payments-filter-form" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap">

                {{-- Search --}}
                <div style="flex:1;min-width:250px;max-width:350px;">
                    <label
                        style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px">Search</label>
                    <div style="position:relative">
                        <i data-lucide="search"
                            style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#94a3b8"></i>
                        <input type="text" id="payments-search" name="search" class="form-input"
                            placeholder="Purchase No, Vendor, Client..." value="{{ request('search') }}"
                            style="width:100%;padding-left:38px;font-size:13px;height:40px;border-color:#e2e8f0;border-radius:8px;background:#fff;transition:all 0.2s;box-shadow:0 1px 2px rgba(0,0,0,0.02)">
                    </div>
                </div>

                {{-- Date Range --}}
                <div style="min-width:200px">
                    <label
                        style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px">Custom
                        Date Range</label>
                    <div style="position:relative">
                        <i data-lucide="calendar"
                            style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#94a3b8"></i>
                        <input type="text" id="date-range-picker" class="form-input" placeholder="Select Date Range"
                            style="width:100%;padding-left:36px;font-size:13px;height:40px;border-color:#e2e8f0;border-radius:8px;background:#fff;cursor:pointer;box-shadow:0 1px 2px rgba(0,0,0,0.02)">
                        <input type="hidden" name="start_date" id="start-date" value="{{ request('start_date') }}">
                        <input type="hidden" name="end_date" id="end-date" value="{{ request('end_date') }}">
                    </div>
                </div>

                {{-- Payment Type --}}
                <div style="min-width:160px">
                    <label
                        style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px">Payment
                        Type</label>
                    <select name="payment_type" class="form-select"
                        style="width:100%;font-size:13px;height:40px;border-color:#e2e8f0;border-radius:8px;background:#fff"
                        onchange="triggerPaymentsAjax()">
                        <option value="">All Types</option>
                        @foreach($paymentTypes as $type)
                            <option value="{{ $type }}" {{ request('payment_type') === $type ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Actions --}}
                <div style="display:flex;gap:10px;align-items:center;">
                    <a href="{{ route('admin.purchase-payments.index') }}" id="btn-clear-filters" class="btn btn-outline"
                        style="height:40px;padding:0 16px;font-size:13px;font-weight:500;border-color:#e2e8f0;color:#64748b;background:#fff;border-radius:8px;{{ request()->hasAny(['search', 'payment_type', 'start_date', 'end_date']) ? 'display:inline-flex;align-items:center;' : 'display:none;' }}">
                        <i data-lucide="x-circle" style="width:14px;height:14px;margin-right:6px"></i> Clear
                    </a>
                </div>

            </form>
        </div>

        <!-- Table -->
        <div class="table-wrapper" style="overflow-x:auto">
            <table class="table" style="width:100%;border-collapse:collapse;min-width:900px">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0">
                        <th
                            style="padding:14px 20px;font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:0.05em;text-align:left">
                            Vendor / Client
                        </th>
                        <th
                            style="padding:14px 20px;font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:0.05em;text-align:left">
                            Purchase No
                        </th>
                        <th
                            style="padding:14px 20px;font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:0.05em;text-align:right">
                            Amount Paid
                        </th>
                        <th
                            style="padding:14px 20px;font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:0.05em;text-align:left">
                            Payment Type
                        </th>
                        <th
                            style="padding:14px 20px;font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:0.05em;text-align:left">
                            Date
                        </th>
                        <th
                            style="padding:14px 20px;font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:0.05em;text-align:left">
                            Proof / Ref
                        </th>
                        <th
                            style="padding:14px 20px;font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:0.05em;text-align:left">
                            Notes
                        </th>
                        <th
                            style="padding:14px 20px;font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:0.05em;text-align:center;width:100px;">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody id="payments-tbody">
                    @include('admin.purchase-payments.partials.payments_table_body', ['payments' => $payments])
                </tbody>
            </table>
        </div>

        <!-- Pagination & Footer info -->
        <div id="payments-pagination-container"
            style="padding:16px 20px;border-top:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;background:#fff;border-radius:0 0 12px 12px">
            <div style="font-size:13px;color:#64748b;font-weight:500">
                <span id="payments-total-count">{{ $payments->total() }}</span> payments found
            </div>
            <div>
                {!! $payments->links() !!}
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <!-- Include Flatpickr for Date Range Selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        var searchTimer = null;

        document.addEventListener('DOMContentLoaded', function () {
            let startDateVal = document.getElementById('start-date').value;
            let endDateVal = document.getElementById('end-date').value;
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
                        document.getElementById('start-date').value = instance.formatDate(selectedDates[0], "Y-m-d");
                        document.getElementById('end-date').value = instance.formatDate(selectedDates[1], "Y-m-d");
                        triggerPaymentsAjax();
                    } else if (selectedDates.length === 0) {
                        document.getElementById('start-date').value = '';
                        document.getElementById('end-date').value = '';
                        triggerPaymentsAjax();
                    }
                }
            });

            // Search Input delay
            document.getElementById('payments-search').addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    triggerPaymentsAjax();
                }, 400); // 400ms delay for typing
            });

            // Handle pagination clicks via AJAX
            document.addEventListener('click', function (e) {
                var link = e.target.closest('#payments-pagination-container a.page-link');
                if (link) {
                    e.preventDefault();
                    var url = new URL(link.href);
                    fetchPaymentsData(url.toString());
                }
            });
        });

        function triggerPaymentsAjax() {
            var search = document.getElementById('payments-search').value;
            var paymentType = document.querySelector('select[name="payment_type"]').value;
            var startDate = document.getElementById('start-date').value;
            var endDate = document.getElementById('end-date').value;

            var url = new URL('{{ route("admin.purchase-payments.index") }}');
            if (search) url.searchParams.append('search', search);
            if (paymentType) url.searchParams.append('payment_type', paymentType);
            if (startDate) url.searchParams.append('start_date', startDate);
            if (endDate) url.searchParams.append('end_date', endDate);

            // Toggle clear filters button
            var clearBtn = document.getElementById('btn-clear-filters');
            if (search || paymentType || startDate || endDate) {
                clearBtn.style.display = 'inline-flex';
                clearBtn.style.alignItems = 'center';
            } else {
                clearBtn.style.display = 'none';
            }

            fetchPaymentsData(url.toString());
        }

        function fetchPaymentsData(url) {
            // Dim table to indicate loading
            document.getElementById('payments-tbody').style.opacity = '0.5';

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('payments-tbody').innerHTML = data.html;
                    document.getElementById('payments-tbody').style.opacity = '1';

                    var pagContainer = document.querySelector('#payments-pagination-container > div:last-child');
                    if (pagContainer) pagContainer.innerHTML = data.pagination;

                    document.getElementById('total-paid-amount').textContent = '₹' + data.total_paid;
                    document.getElementById('payments-total-count').textContent = data.total_count;

                    if (typeof lucide !== 'undefined') lucide.createIcons();
                })
                .catch(error => {
                    console.error('Error fetching payments:', error);
                    document.getElementById('payments-tbody').style.opacity = '1';
                });
        }

        function openEditPaymentModal(id, amount, paymentType, paymentDate, notes) {
            document.getElementById('edit_payment_id').value = id;
            document.getElementById('edit_amount').value = amount;
            document.getElementById('edit_payment_type').value = paymentType;
            document.getElementById('edit_payment_date').value = paymentDate;
            document.getElementById('edit_notes').value = notes;

            document.getElementById('edit_payment_form').action = `/admin/purchase-payments/${id}`;
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
            <h3 class="modal-title">Edit Purchase Payment</h3>
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