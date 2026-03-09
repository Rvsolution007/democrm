@extends('admin.layouts.app')

@push('styles')
    <style>
        /* Modern UI 2026 Styles for WhatsApp Campaigns */
        .page-header-modern {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 1.5rem 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px -10px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .page-title-modern {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .modern-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.03);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .modern-card-header {
            padding: 1.5rem 1.5rem 1rem;
            background: transparent;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modern-card-header h6 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .modern-card-body {
            padding: 1.5rem;
        }

        /* Form Styles */
        .modern-form-label {
            font-weight: 600;
            color: #475569;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .modern-form-select {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            color: #1e293b;
            transition: all 0.2s;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02);
            width: 100%;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/200.svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px;
        }

        .modern-form-select:focus {
            background-color: #ffffff;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .modern-help-text {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 0.4rem;
            display: block;
        }

        /* Buttons */
        .btn-preview-modern {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            color: white;
            border: none;
            padding: 0.85rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px -3px rgba(14, 165, 233, 0.4);
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-preview-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -5px rgba(14, 165, 233, 0.5);
            color: white;
        }

        .btn-start-modern {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 0.85rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px -3px rgba(16, 185, 129, 0.4);
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-start-modern:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -5px rgba(16, 185, 129, 0.5);
            color: white;
        }

        .btn-start-modern:disabled {
            background: #cbd5e1;
            box-shadow: none;
            cursor: not-allowed;
            color: #64748b;
            opacity: 0.7;
        }

        /* Stats Cards in Preview */
        .stat-box {
            background: #f8fafc;
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-box-blue {
            border-left: 4px solid #3b82f6;
        }

        .stat-box-blue::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            transform: translate(20px, -20px);
        }

        .stat-box-amber {
            border-left: 4px solid #f59e0b;
            background: #fffbeb;
        }

        .stat-box-amber::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.15) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            transform: translate(20px, -20px);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.25rem;
            letter-spacing: -0.5px;
        }

        .stat-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: #64748b;
        }

        /* Table Styles */
        .modern-table th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .modern-table td {
            padding: 1.25rem 1.5rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.95rem;
            color: #334155;
        }

        .modern-table tr:last-child td {
            border-bottom: none;
        }

        /* Badges */
        .val-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .bg-soft-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .bg-soft-primary {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .bg-soft-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .bg-soft-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        /* Empty state */
        .empty-preview-icon {
            width: 64px;
            height: 64px;
            background: #f1f5f9;
            color: #94a3b8;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
    </style>
@endpush

@section('title', 'WhatsApp Bulk Campaigns')

@section('content')
    <div class="container-fluid" style="padding: 1.5rem;">
        <!-- Modern Header -->
        <div class="page-header-modern">
            <div>
                <h2 class="page-title-modern">WhatsApp Bulk Sender</h2>
                <p class="text-muted mt-1 mb-0" style="font-size: 0.9rem;">Broadcast rich interactive messages to your
                    targeted leads at scale</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert"
                style="border-radius: 12px; border: none; background: #ecfdf5; color: #047857; box-shadow: 0 4px 15px -5px rgba(0,0,0,0.05); padding: 1rem 1.5rem;">
                <div class="d-flex align-items-center">
                    <i data-lucide="check-circle" style="width: 20px; height: 20px; margin-right: 12px;"></i>
                    <strong>Success!</strong> &nbsp;{{ session('success') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"
                    style="padding: 1.25rem;"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert"
                style="border-radius: 12px; border: none; background: #fef2f2; color: #b91c1c; box-shadow: 0 4px 15px -5px rgba(0,0,0,0.05); padding: 1rem 1.5rem;">
                <div class="d-flex align-items-center">
                    <i data-lucide="alert-circle" style="width: 20px; height: 20px; margin-right: 12px;"></i>
                    <strong>Error:</strong> &nbsp;{{ session('error') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"
                    style="padding: 1.25rem;"></button>
            </div>
        @endif

        <div class="row">
            <!-- New Campaign Form -->
            <div class="col-lg-5">
                <div class="modern-card">
                    <div class="modern-card-header">
                        <div style="background: #eff6ff; padding: 8px; border-radius: 10px; color: #3b82f6;">
                            <i data-lucide="send" style="width: 18px; height: 18px;"></i>
                        </div>
                        <h6>Start New Campaign</h6>
                    </div>
                    <div class="modern-card-body">
                        <form action="{{ route('admin.whatsapp-campaigns.store') }}" method="POST" id="campaignForm">
                            @csrf

                            <div class="mb-4">
                                <label class="modern-form-label">Select Template <span class="text-danger">*</span></label>
                                <select name="template_id" id="template_id" class="modern-form-select select2" required>
                                    <option value="">-- Choose a template --</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}
                                            ({{ ucfirst($template->type) }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="modern-form-label">Target Lead Stage</label>
                                <select name="target_stage" id="target_stage" class="modern-form-select">
                                    <option value="">All Stages</option>
                                    @foreach($stages as $stage)
                                        @if($stage)
                                            <option value="{{ $stage }}">{{ ucfirst($stage) }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <span class="modern-help-text">Leave empty to broadcast to everyone in all pipelines</span>
                            </div>

                            <div class="mb-4">
                                <label class="modern-form-label">Target Product Interest</label>
                                <select name="target_product_id" id="target_product_id" class="modern-form-select select2">
                                    <option value="">All Products</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                                <span class="modern-help-text">Filter audience by product interest</span>
                            </div>

                            <hr style="border-color: #f1f5f9; margin: 1.5rem 0;">

                            <div class="d-flex flex-column gap-3">
                                <button type="button" class="btn-preview-modern" onclick="previewCampaign()">
                                    <i data-lucide="search" style="width: 18px; height: 18px;"></i>
                                    Calculate Audience Size
                                </button>

                                <button type="submit" class="btn-start-modern" id="btnStart" disabled>
                                    <i data-lucide="rocket" style="width: 18px; height: 18px;"></i>
                                    Deploy Campaign
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Preview Results -->
            <div class="col-lg-7">
                <div class="modern-card" id="previewCard" style="opacity: 0.6; transition: all 0.4s ease;">
                    <div class="modern-card-header">
                        <div style="background: #fef2f2; padding: 8px; border-radius: 10px; color: #ef4444;">
                            <i data-lucide="bar-chart-2" style="width: 18px; height: 18px;"></i>
                        </div>
                        <h6>Audience Preview & Metrics</h6>
                    </div>

                    <div class="modern-card-body">
                        <div id="previewEmpty" class="text-center py-5">
                            <div class="empty-preview-icon">
                                <i data-lucide="users" style="width: 28px; height: 28px;"></i>
                            </div>
                            <h5 style="color: #334155; font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem;">
                                Real-time Campaign Analysis</h5>
                            <p style="color: #64748b; font-size: 0.95rem; max-width: 350px; margin: 0 auto;">Select your
                                filters and deploy 'Calculate Audience Size' to securely preview total recipients and ETA
                                metrics.</p>
                        </div>

                        <div id="previewResults" style="display:none;">
                            <div class="row mb-4 g-3">
                                <div class="col-md-6">
                                    <div class="stat-box stat-box-blue">
                                        <div class="stat-value text-primary" id="resCount">0</div>
                                        <div class="stat-label">Total Verified Recipients</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-box stat-box-amber">
                                        <div class="stat-value text-warning" id="resEta">0 Mins</div>
                                        <div class="stat-label">Max Completion Time (ETA)</div>
                                    </div>
                                </div>
                            </div>

                            <h6 class="font-weight-bold mb-3" style="color: #334155; font-size: 1rem;">Sample Audience
                                Segment <span style="font-size:0.8rem;color:#94a3b8;font-weight:400;">(Top 10)</span></h6>
                            <div class="border rounded-4 overflow-hidden mb-4" style="border-color: #e2e8f0 !important;">
                                <table class="table modern-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Contact Name</th>
                                            <th>WhatsApp Number</th>
                                        </tr>
                                    </thead>
                                    <tbody id="resTableBody">
                                        <!-- Populated via JS -->
                                    </tbody>
                                </table>
                            </div>

                            <div
                                style="background: #fff8f1; border: 1px dashed #fdba74; border-radius: 12px; padding: 1rem; display: flex; gap: 1rem; align-items: flex-start;">
                                <div style="color: #f97316; margin-top: 2px;">
                                    <i data-lucide="shield-alert" style="width: 20px; height: 20px;"></i>
                                </div>
                                <div>
                                    <h6
                                        style="color: #9a3412; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.25rem;">
                                        Meta Compliance Security</h6>
                                    <p style="color: #c2410c; font-size: 0.85rem; margin: 0; line-height: 1.5;">Campaign
                                        processing operates within an isolated background chron engine utilizing a dynamic
                                        <b>20-second dispersal delay</b> per message to explicitly prevent algorithmic
                                        ban-flags by the Meta ecosystem. Safe to navigate away post-deployment.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Past Campaigns Table -->
        <div class="modern-card mt-2">
            <div class="modern-card-header">
                <div style="background: #f3f4f6; padding: 8px; border-radius: 10px; color: #4b5563;">
                    <i data-lucide="history" style="width: 18px; height: 18px;"></i>
                </div>
                <h6>Historical Campaign Manifest</h6>
            </div>

            <div class="table-responsive">
                <table class="table modern-table mb-0" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Template Reference</th>
                            <th>Target Parameters</th>
                            <th>Delivery Metrics</th>
                            <th>Status Vector</th>
                            <th class="text-end">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaigns as $campaign)
                            <tr>
                                <td>
                                    <span
                                        style="font-family: monospace; font-weight: 600; color: #64748b;">#{{ str_pad($campaign->id, 4, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #1e293b;">
                                        {{ $campaign->template->name ?? 'Archived Template' }}</div>
                                    @if($campaign->template)
                                        <span
                                            style="font-size: 0.75rem; color: #cbd5e1; text-transform: uppercase;">{{ $campaign->template->type }}
                                            FORMAT</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <div class="d-flex align-items-center" style="font-size: 0.85rem; color: #475569;">
                                            <i data-lucide="git-merge"
                                                style="width: 12px; height: 12px; margin-right: 6px; opacity: 0.6;"></i>
                                            <span
                                                style="max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                                title="{{ $campaign->target_stage ? ucfirst($campaign->target_stage) : 'Global Pipeline (All Stages)' }}">
                                                {{ $campaign->target_stage ? ucfirst($campaign->target_stage) : 'Global Pipeline (All Stages)' }}
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center" style="font-size: 0.85rem; color: #475569;">
                                            <i data-lucide="box"
                                                style="width: 12px; height: 12px; margin-right: 6px; opacity: 0.6;"></i>
                                            <span
                                                style="max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                                title="{{ $campaign->product->name ?? 'Global Inventory (All Products)' }}">
                                                {{ $campaign->product->name ?? 'Global Inventory (All Products)' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-3 align-items-center">
                                        <div class="text-center">
                                            <div
                                                style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; text-transform: uppercase;">
                                                Total</div>
                                            <div style="font-weight: 700; color: #3b82f6; font-size: 0.95rem;">
                                                {{ $campaign->total_recipients }}</div>
                                        </div>
                                        <div style="width: 1px; height: 24px; background: #e2e8f0;"></div>
                                        <div class="text-center">
                                            <div
                                                style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; text-transform: uppercase;">
                                                Sent</div>
                                            <div style="font-weight: 700; color: #10b981; font-size: 0.95rem;">
                                                {{ $campaign->total_sent }}</div>
                                        </div>
                                        <div style="width: 1px; height: 24px; background: #e2e8f0;"></div>
                                        <div class="text-center">
                                            <div
                                                style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; text-transform: uppercase;">
                                                Fail</div>
                                            <div style="font-weight: 700; color: #ef4444; font-size: 0.95rem;">
                                                {{ $campaign->total_failed }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($campaign->status === 'completed')
                                        <span class="val-badge bg-soft-success"><i data-lucide="check-circle"
                                                style="width: 12px; height: 12px;"></i> Completed</span>
                                    @elseif($campaign->status === 'processing')
                                        <span class="val-badge bg-soft-primary"><i data-lucide="loader"
                                                style="width: 12px; height: 12px;" class="fa-spin"></i> Processing</span>
                                    @elseif($campaign->status === 'failed')
                                        <span class="val-badge bg-soft-danger"><i data-lucide="x-circle"
                                                style="width: 12px; height: 12px;"></i> Failed</span>
                                        @if($campaign->error_message)
                                            <div style="font-size: 0.75rem; color: #ef4444; margin-top: 6px; max-width: 200px; line-height: 1.2;">
                                                <strong>Reason:</strong> {{ $campaign->error_message }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="val-badge bg-soft-secondary"><i data-lucide="clock"
                                                style="width: 12px; height: 12px;"></i> Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-end"
                                        style="font-size: 0.85rem; color: #64748b; font-weight: 500;">
                                        <i data-lucide="calendar"
                                            style="width:14px;height:14px; margin-right: 6px; opacity: 0.7;"></i>
                                        <span>{{ $campaign->created_at->format('d M Y, H:i') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="text-center py-5">
                                        <div class="empty-preview-icon"
                                            style="background: transparent; border: 1px dashed #cbd5e1;">
                                            <i data-lucide="database" style="width: 24px; height: 24px; opacity: 0.5;"></i>
                                        </div>
                                        <p style="color: #94a3b8; font-weight: 500;">Archive manifests are empty. Deploy a
                                            campaign to generate telemetry.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($campaigns->hasPages())
                <div class="modern-card-body" style="padding-top: 0; padding-bottom: 1rem;">
                    <div class="mt-3">
                        {{ $campaigns->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });

        function previewCampaign() {
            const stage = document.getElementById('target_stage').value;
            const productId = document.getElementById('target_product_id').value;
            const btnStart = document.getElementById('btnStart');

            // Disable start button during preview fetch
            btnStart.disabled = true;
            btnStart.style.cursor = 'not-allowed';
            btnStart.style.opacity = '0.6';

            const previewCard = document.getElementById('previewCard');
            previewCard.style.opacity = '1';

            // Add loading state
            document.getElementById('previewEmpty').innerHTML = '<p>Loading preview...</p>';
            document.getElementById('previewEmpty').style.display = 'block';
            document.getElementById('previewResults').style.display = 'none';

            fetch("{{ route('admin.whatsapp-campaigns.preview') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    stage: stage,
                    product_id: productId
                })
            })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('previewEmpty').style.display = 'none';
                    document.getElementById('previewResults').style.display = 'block';

                    document.getElementById('resCount').textContent = data.count;
                    document.getElementById('resEta').textContent = data.eta_readable;

                    const tbody = document.getElementById('resTableBody');
                    tbody.innerHTML = '';

                    if (data.leads.length > 0) {
                        data.leads.forEach(lead => {
                            tbody.innerHTML += `
                                        <tr>
                                            <td>${lead.name || 'N/A'}</td>
                                            <td>${lead.phone}</td>
                                        </tr>
                                    `;
                        });

                        // Enable Start button if fields selected
                        if (document.getElementById('template_id').value !== '') {
                            btnStart.disabled = false;
                            btnStart.style.cursor = 'pointer';
                            btnStart.style.opacity = '1';
                        }
                    } else {
                        tbody.innerHTML = '<tr><td colspan="2" class="text-center text-danger">No leads found matching criteria.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('previewEmpty').innerHTML = '<p class="text-danger">Failed to load preview.</p>';
                });
        }

        // Also re-enable button if template changes after successful preview > 0
        document.getElementById('template_id').addEventListener('change', function () {
            const count = parseInt(document.getElementById('resCount').textContent || 0);
            const btnStart = document.getElementById('btnStart');
            if (this.value !== '' && count > 0) {
                btnStart.disabled = false;
                btnStart.style.cursor = 'pointer';
                btnStart.style.opacity = '1';
            } else {
                btnStart.disabled = true;
                btnStart.style.cursor = 'not-allowed';
                btnStart.style.opacity = '0.6';
            }
        });
    </script>
@endpush