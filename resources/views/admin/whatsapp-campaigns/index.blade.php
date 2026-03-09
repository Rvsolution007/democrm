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

        .header-actions {
            display: flex;
            gap: 1rem;
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
            padding: 1rem 1.5rem;
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
                <h2 class="page-title-modern">Historical Campaign Manifest</h2>
                <p class="text-muted mt-1 mb-0" style="font-size: 0.9rem;">Review and monitor all your past and current WhatsApp broadcasts</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('admin.whatsapp-campaigns.create') }}" class="btn-start-modern" style="text-decoration: none; width: auto;">
                    <i data-lucide="plus-circle" style="width: 18px; height: 18px;"></i> Start New Campaign
                </a>
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
                            <th class="text-end">Actions</th>
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
                                    <div class="d-flex flex-wrap gap-2">
                                        <span style="font-size: 0.8rem; background: #f8fafc; border: 1px solid #e2e8f0; padding: 4px 8px; border-radius: 6px; color: #475569; font-weight: 500; display: inline-flex; align-items: center; gap: 4px;">
                                            <i data-lucide="git-merge" style="width: 12px; height: 12px; opacity: 0.7;"></i> {{ $campaign->target_stage ? ucfirst($campaign->target_stage) : 'All Stages' }}
                                        </span>
                                        <span style="font-size: 0.8rem; background: #f8fafc; border: 1px solid #e2e8f0; padding: 4px 8px; border-radius: 6px; color: #475569; font-weight: 500; display: inline-flex; align-items: center; gap: 4px;">
                                            <i data-lucide="box" style="width: 12px; height: 12px; opacity: 0.7;"></i> {{ $campaign->product->name ?? 'All Products' }}
                                        </span>
                                        <span style="font-size: 0.8rem; background: #eff6ff; border: 1px solid #bfdbfe; padding: 4px 8px; border-radius: 6px; color: #1e40af; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                            <i data-lucide="users" style="width: 12px; height: 12px;"></i> {{ $campaign->total_recipients }} Recipients
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span style="font-size: 0.8rem; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; color: #475569; font-weight: 600;">Total: <span style="color:#3b82f6">{{$campaign->total_recipients}}</span></span>
                                        <span style="font-size: 0.8rem; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; color: #475569; font-weight: 600;">Sent: <span style="color:#10b981">{{$campaign->total_sent}}</span></span>
                                        <span style="font-size: 0.8rem; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; color: #475569; font-weight: 600;">Fail: <span style="color:#ef4444">{{$campaign->total_failed}}</span></span>
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
                                <td>
                                    <div class="d-flex justify-content-end">
                                        <form action="{{ route('admin.whatsapp-campaigns.destroy', $campaign->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this campaign permanently? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm text-danger" style="background:#fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 0.4rem 0.6rem; transition: all 0.2s;" title="Delete Campaign">
                                                <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
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

    </script>
@endpush