@extends('admin.layouts.app')

@push('styles')
    <style>
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

        /* Stats Row */
        .campaign-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: #f8fafc;
            border-radius: 16px;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }

        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.25rem;
            letter-spacing: -0.5px;
        }

        .stat-card .stat-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-total { border-left: 4px solid #3b82f6; }
        .stat-total .stat-value { color: #3b82f6; }

        .stat-sent { border-left: 4px solid #10b981; }
        .stat-sent .stat-value { color: #10b981; }

        .stat-failed { border-left: 4px solid #ef4444; }
        .stat-failed .stat-value { color: #ef4444; }

        .stat-pending { border-left: 4px solid #f59e0b; }
        .stat-pending .stat-value { color: #f59e0b; }

        /* Error Banner */
        .campaign-error-banner {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fecaca;
            border-left: 5px solid #ef4444;
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .campaign-error-banner .error-title {
            font-weight: 700;
            color: #991b1b;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .campaign-error-banner .error-message {
            color: #b91c1c;
            font-size: 0.9rem;
            line-height: 1.5;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            background: rgba(255, 255, 255, 0.5);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            word-break: break-all;
        }

        /* Meta Info */
        .campaign-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .meta-item .meta-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .meta-item .meta-label {
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-item .meta-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1e293b;
        }

        /* Table */
        .modern-table th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .modern-table td {
            padding: 0.85rem 1.25rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #334155;
        }

        .modern-table tr:last-child td {
            border-bottom: none;
        }

        .modern-table tr.row-failed {
            background: #fef2f2;
        }

        /* Badges */
        .val-badge {
            padding: 0.3rem 0.65rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .bg-soft-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .bg-soft-primary { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .bg-soft-danger  { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .bg-soft-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .bg-soft-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        .error-cell {
            font-size: 0.8rem;
            color: #ef4444;
            max-width: 350px;
            word-break: break-word;
            line-height: 1.4;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        }

        /* Filter tabs */
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .filter-tab:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #334155;
        }

        .filter-tab.active {
            background: #1e293b;
            color: #ffffff;
            border-color: #1e293b;
        }

        .filter-tab .count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
            padding: 0 6px;
            font-size: 0.75rem;
            margin-left: 4px;
            min-width: 20px;
        }

        .filter-tab.active .count {
            background: rgba(255,255,255,0.2);
        }
    </style>
@endpush

@section('title', 'Campaign Details #' . str_pad($campaign->id, 4, '0', STR_PAD_LEFT))

@section('content')
    <div class="container-fluid" style="padding: 1.5rem;">
        <!-- Header -->
        <div class="page-header-modern">
            <div>
                <h2 class="page-title-modern">Campaign #{{ str_pad($campaign->id, 4, '0', STR_PAD_LEFT) }} — Details</h2>
                <p class="text-muted mt-1 mb-0" style="font-size: 0.9rem;">View complete campaign status, errors, and recipient delivery log</p>
            </div>
            <div>
                <a href="{{ route('admin.whatsapp-campaigns.index') }}" class="btn btn-sm" style="background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.6rem 1rem; font-weight: 600; color: #475569; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem;">
                    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Back to Campaigns
                </a>
            </div>
        </div>

        <!-- Campaign-Level Error Banner -->
        @if($campaign->status === 'failed' && $campaign->error_message)
            <div class="campaign-error-banner">
                <div class="error-title">
                    <i data-lucide="alert-triangle" style="width: 18px; height: 18px;"></i>
                    Campaign Failed — Error Details
                </div>
                <div class="error-message">{{ $campaign->error_message }}</div>
            </div>
        @endif

        <!-- Campaign Meta Info -->
        <div class="campaign-meta">
            <div class="meta-item">
                <div class="meta-icon" style="background: #eff6ff; color: #3b82f6;">
                    <i data-lucide="file-text" style="width: 18px; height: 18px;"></i>
                </div>
                <div>
                    <div class="meta-label">Template</div>
                    <div class="meta-value">{{ $campaign->template->name ?? 'Deleted' }}</div>
                </div>
            </div>
            <div class="meta-item">
                <div class="meta-icon" style="background: #f0fdf4; color: #22c55e;">
                    <i data-lucide="git-merge" style="width: 18px; height: 18px;"></i>
                </div>
                <div>
                    <div class="meta-label">Target Stage</div>
                    <div class="meta-value">{{ $campaign->target_stage ? ucfirst($campaign->target_stage) : 'All Stages' }}</div>
                </div>
            </div>
            <div class="meta-item">
                <div class="meta-icon" style="background: #fdf4ff; color: #a855f7;">
                    <i data-lucide="box" style="width: 18px; height: 18px;"></i>
                </div>
                <div>
                    <div class="meta-label">Product</div>
                    <div class="meta-value">{{ $campaign->product->name ?? 'All Products' }}</div>
                </div>
            </div>
            <div class="meta-item">
                <div class="meta-icon" style="background: #fff7ed; color: #f97316;">
                    <i data-lucide="user" style="width: 18px; height: 18px;"></i>
                </div>
                <div>
                    <div class="meta-label">Created By</div>
                    <div class="meta-value">{{ $campaign->user->name ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="meta-item">
                <div class="meta-icon" style="background: #f1f5f9; color: #64748b;">
                    <i data-lucide="calendar" style="width: 18px; height: 18px;"></i>
                </div>
                <div>
                    <div class="meta-label">Created At</div>
                    <div class="meta-value">{{ $campaign->created_at->format('d M Y, H:i') }}</div>
                </div>
            </div>
            <div class="meta-item">
                <div class="meta-icon" style="background: {{ $campaign->status === 'failed' ? '#fef2f2' : ($campaign->status === 'completed' ? '#f0fdf4' : '#eff6ff') }}; color: {{ $campaign->status === 'failed' ? '#ef4444' : ($campaign->status === 'completed' ? '#22c55e' : '#3b82f6') }};">
                    <i data-lucide="{{ $campaign->status === 'failed' ? 'x-circle' : ($campaign->status === 'completed' ? 'check-circle' : 'loader') }}" style="width: 18px; height: 18px;"></i>
                </div>
                <div>
                    <div class="meta-label">Status</div>
                    <div class="meta-value">{{ ucfirst($campaign->status) }}</div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="campaign-stats">
            <div class="stat-card stat-total">
                <div class="stat-value">{{ $campaign->total_recipients }}</div>
                <div class="stat-label">Total Recipients</div>
            </div>
            <div class="stat-card stat-sent">
                <div class="stat-value">{{ $campaign->total_sent }}</div>
                <div class="stat-label">Sent</div>
            </div>
            <div class="stat-card stat-failed">
                <div class="stat-value">{{ $campaign->total_failed }}</div>
                <div class="stat-label">Failed</div>
            </div>
            <div class="stat-card stat-pending">
                <div class="stat-value">{{ $recipients->where('status', 'pending')->count() }}</div>
                <div class="stat-label">Pending</div>
            </div>
        </div>

        <!-- Recipients Table -->
        <div class="modern-card">
            <div class="modern-card-header" style="justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="background: #f3f4f6; padding: 8px; border-radius: 10px; color: #4b5563;">
                        <i data-lucide="list" style="width: 18px; height: 18px;"></i>
                    </div>
                    <h6>Recipient Delivery Log</h6>
                </div>
            </div>

            <div class="modern-card-body" style="padding-bottom: 0.5rem;">
                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <span class="filter-tab active" data-filter="all">All <span class="count">{{ $recipients->count() }}</span></span>
                    <span class="filter-tab" data-filter="failed" style="border-color: #fecaca; color: #991b1b; background: #fef2f2;">
                        Failed <span class="count">{{ $recipients->where('status', 'failed')->count() }}</span>
                    </span>
                    <span class="filter-tab" data-filter="pending">Pending <span class="count">{{ $recipients->where('status', 'pending')->count() }}</span></span>
                    <span class="filter-tab" data-filter="sent">Sent <span class="count">{{ $recipients->where('status', 'sent')->count() }}</span></span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table modern-table mb-0" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Lead / Contact</th>
                            <th>Phone Number</th>
                            <th>Status</th>
                            <th>Error Message</th>
                            <th>Sent At</th>
                        </tr>
                    </thead>
                    <tbody id="recipientTableBody">
                        @forelse($recipients as $index => $r)
                            <tr class="recipient-row {{ $r->status === 'failed' ? 'row-failed' : '' }}" data-status="{{ $r->status }}">
                                <td>
                                    <span style="font-family: monospace; font-weight: 600; color: #94a3b8;">{{ $index + 1 }}</span>
                                </td>
                                <td>
                                    @if($r->lead)
                                        <a href="{{ route('admin.leads.show', $r->lead_id) }}" style="font-weight: 600; color: #1e293b; text-decoration: none;">
                                            {{ $r->lead->name ?? ($r->lead->first_name . ' ' . $r->lead->last_name) }}
                                        </a>
                                    @else
                                        <span style="color: #94a3b8;">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-family: monospace; font-weight: 500;">{{ $r->phone_number }}</span>
                                </td>
                                <td>
                                    @if($r->status === 'sent')
                                        <span class="val-badge bg-soft-success">
                                            <i data-lucide="check-circle" style="width: 11px; height: 11px;"></i> Sent
                                        </span>
                                    @elseif($r->status === 'failed')
                                        <span class="val-badge bg-soft-danger">
                                            <i data-lucide="x-circle" style="width: 11px; height: 11px;"></i> Failed
                                        </span>
                                    @else
                                        <span class="val-badge bg-soft-warning">
                                            <i data-lucide="clock" style="width: 11px; height: 11px;"></i> Pending
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($r->error_message)
                                        <div class="error-cell">{{ $r->error_message }}</div>
                                    @else
                                        <span style="color: #cbd5e1;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($r->sent_at)
                                        <span style="font-size: 0.85rem; color: #64748b;">{{ \Carbon\Carbon::parse($r->sent_at)->format('d M, H:i') }}</span>
                                    @else
                                        <span style="color: #cbd5e1;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="text-center py-4">
                                        <p style="color: #94a3b8; font-weight: 500;">No recipients recorded for this campaign.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Filter tabs
            document.querySelectorAll('.filter-tab').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const filter = this.dataset.filter;

                    // Update active tab
                    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    // Filter rows
                    document.querySelectorAll('.recipient-row').forEach(function (row) {
                        if (filter === 'all' || row.dataset.status === filter) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
@endpush
