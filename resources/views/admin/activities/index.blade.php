@extends('admin.layouts.app')

@section('title', 'Activity Hub')
@section('breadcrumb', 'Activity Hub')

@section('content')
    <style>
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInItem {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulseGlow {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.3);
            }

            50% {
                box-shadow: 0 0 12px 4px rgba(79, 70, 229, 0.12);
            }
        }

        /* Stat Cards */
        .act-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 24px;
        }

        .act-stat-card {
            border-radius: 18px;
            padding: 24px 28px;
            color: #fff;
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
            transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .act-stat-card:hover {
            transform: translateY(-3px);
        }

        .act-stat-card .orb {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0) 70%);
        }

        .act-stat-card:nth-child(2) {
            animation-delay: 0.08s;
        }

        .act-stat-card:nth-child(3) {
            animation-delay: 0.16s;
        }

        .act-stat-card:nth-child(4) {
            animation-delay: 0.24s;
        }

        .act-stat-icon {
            width: 42px;
            height: 42px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(4px);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .act-stat-label {
            font-size: 12px;
            font-weight: 600;
            opacity: 0.85;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .act-stat-value {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.5px;
            position: relative;
            z-index: 1;
            margin-top: 4px;
        }

        /* Filter Bar */
        .act-filter-bar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            animation: slideInUp 0.5s 0.3s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .act-filter-bar input,
        .act-filter-bar select {
            padding: 8px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13px;
            background: #fff;
            color: #334155;
            outline: none;
            transition: border-color 0.2s;
        }

        .act-filter-bar input:focus,
        .act-filter-bar select:focus {
            border-color: #4f46e5;
        }

        .act-filter-bar .search-box {
            flex: 1;
            min-width: 180px;
        }

        .act-filter-btn {
            padding: 8px 20px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .act-filter-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .act-clear-btn {
            padding: 8px 16px;
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .act-clear-btn:hover {
            background: #e2e8f0;
        }

        /* Activity Feed Card */
        .act-feed-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            animation: slideInUp 0.5s 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .act-feed-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .act-feed-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .act-feed-count {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #fff;
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 700;
        }

        /* Activity Item */
        .act-item {
            display: flex;
            gap: 14px;
            padding: 16px 24px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
            transition: all 0.2s;
            animation: fadeInItem 0.4s ease both;
        }

        .act-item:hover {
            background: rgba(79, 70, 229, 0.02);
        }

        .act-item:last-child {
            border-bottom: none;
        }

        /* Type Icon */
        .act-type-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .act-type-icon.call {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            color: #16a34a;
        }

        .act-type-icon.whatsapp {
            background: linear-gradient(135deg, #dcfce7, #a7f3d0);
            color: #059669;
        }

        .act-type-icon.email {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #2563eb;
        }

        .act-type-icon.note {
            background: linear-gradient(135deg, #f3e8ff, #e9d5ff);
            color: #7c3aed;
        }

        .act-type-icon.meeting {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
        }

        .act-type-icon.task {
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            color: #4f46e5;
        }

        .act-type-icon.status_change {
            background: linear-gradient(135deg, #dbeafe, #93c5fd);
            color: #1d4ed8;
        }

        .act-type-icon.client_reply {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #047857;
        }

        .act-type-icon.revision {
            background: linear-gradient(135deg, #fef3c7, #fcd34d);
            color: #b45309;
        }

        .act-type-icon.file_upload {
            background: linear-gradient(135deg, #fce7f3, #fbcfe8);
            color: #be185d;
        }

        /* User Avatar */
        .act-avatar {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Content */
        .act-content {
            flex: 1;
            min-width: 0;
        }

        .act-summary {
            font-size: 13.5px;
            color: #334155;
            margin: 0 0 4px 0;
            line-height: 1.45;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .act-summary strong {
            color: #0f172a;
            font-weight: 600;
        }

        .act-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .act-user-name {
            font-size: 12px;
            font-weight: 600;
            color: #4f46e5;
        }

        .act-time {
            font-size: 11.5px;
            color: #94a3b8;
            font-weight: 500;
        }

        .act-type-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .act-entity-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .act-entity-badge:hover {
            background: #e2e8f0;
        }

        .act-entity-badge.lead {
            background: #fef3c7;
            color: #92400e;
        }

        .act-entity-badge.client {
            background: #e0e7ff;
            color: #3730a3;
        }

        .act-entity-badge.quote {
            background: #d1fae5;
            color: #065f46;
        }

        .act-entity-badge.task {
            background: #fce7f3;
            color: #9d174d;
        }

        /* Right side time */
        .act-right {
            flex-shrink: 0;
            text-align: right;
            min-width: 90px;
        }

        .act-date-full {
            font-size: 11px;
            color: #94a3b8;
        }

        /* Empty */
        .act-empty {
            padding: 60px 20px;
            text-align: center;
            color: #94a3b8;
        }

        .act-empty i {
            display: block;
            margin: 0 auto 12px;
            opacity: 0.4;
        }

        .act-empty p {
            font-size: 14px;
            margin: 0;
        }

        /* Loading */
        .act-loading {
            padding: 30px;
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
        }

        @media(max-width:768px) {
            .act-stat-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .act-filter-bar {
                flex-direction: column;
            }

            .act-filter-bar input,
            .act-filter-bar select {
                width: 100%;
            }
        }
    </style>

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title" style="display:flex;align-items:center;gap:10px;">
                    <div
                        style="width:36px;height:36px;background:linear-gradient(135deg,#4f46e5,#6366f1);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="activity" style="width:18px;height:18px;color:#fff;"></i>
                    </div>
                    Activity Hub
                    <span class="act-feed-count" id="total-badge">{{ $allActivities->count() }}</span>
                </h1>
                <p class="page-description">Unified activity feed across all users, leads, tasks & projects</p>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="act-stat-grid">
        <!-- Total Activities -->
        <div class="act-stat-card"
            style="background:linear-gradient(135deg,#4f46e5 0%,#6366f1 50%,#818cf8 100%);box-shadow:0 12px 28px -6px rgba(79,70,229,0.4);">
            <div class="orb" style="width:120px;height:120px;top:-30px;right:-30px;"></div>
            <div class="orb" style="width:80px;height:80px;bottom:-20px;left:-20px;"></div>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                <div class="act-stat-icon"><i data-lucide="layers"
                        style="width:18px;height:18px;color:#fff;stroke-width:2.5;"></i></div>
                <span class="act-stat-label">Total Activities</span>
            </div>
            <div class="act-stat-value">{{ $totalActivities }}</div>
        </div>
        <!-- Today -->
        <div class="act-stat-card"
            style="background:linear-gradient(135deg,#059669 0%,#10b981 50%,#34d399 100%);box-shadow:0 12px 28px -6px rgba(5,150,105,0.4);">
            <div class="orb" style="width:120px;height:120px;top:-30px;right:-30px;"></div>
            <div class="orb" style="width:80px;height:80px;bottom:-20px;left:-20px;"></div>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                <div class="act-stat-icon"><i data-lucide="zap"
                        style="width:18px;height:18px;color:#fff;stroke-width:2.5;"></i></div>
                <span class="act-stat-label">Today's Activities</span>
            </div>
            <div class="act-stat-value">{{ $todayActivities }}</div>
        </div>
        <!-- Most Active User -->
        <div class="act-stat-card"
            style="background:linear-gradient(135deg,#d97706 0%,#f59e0b 50%,#fbbf24 100%);box-shadow:0 12px 28px -6px rgba(217,119,6,0.4);">
            <div class="orb" style="width:120px;height:120px;top:-30px;right:-30px;"></div>
            <div class="orb" style="width:80px;height:80px;bottom:-20px;left:-20px;"></div>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                <div class="act-stat-icon"><i data-lucide="trophy"
                        style="width:18px;height:18px;color:#fff;stroke-width:2.5;"></i></div>
                <span class="act-stat-label">Most Active User</span>
            </div>
            <div class="act-stat-value" style="font-size:22px;">{{ $mostActiveUser }}</div>
            <div style="font-size:12px;opacity:0.8;margin-top:2px;">{{ $mostActiveCount }} activities</div>
        </div>
        <!-- Top Entity -->
        <div class="act-stat-card"
            style="background:linear-gradient(135deg,#ec4899 0%,#f43f5e 50%,#fb7185 100%);box-shadow:0 12px 28px -6px rgba(244,63,94,0.4);">
            <div class="orb" style="width:120px;height:120px;top:-30px;right:-30px;"></div>
            <div class="orb" style="width:80px;height:80px;bottom:-20px;left:-20px;"></div>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                <div class="act-stat-icon"><i data-lucide="target"
                        style="width:18px;height:18px;color:#fff;stroke-width:2.5;"></i></div>
                <span class="act-stat-label">Top Entity</span>
            </div>
            <div class="act-stat-value" style="font-size:22px;">{{ $topEntityLabel }}</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="act-filter-bar" id="filter-bar">
        <input type="text" class="search-box" id="filter-search" placeholder="Search activities..." />
        <select id="filter-user">
            <option value="">All Users</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>
        <select id="filter-type">
            <option value="">All Types</option>
            <optgroup label="General">
                <option value="call">Call</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="email">Email</option>
                <option value="note">Note</option>
                <option value="meeting">Meeting</option>
            </optgroup>
            <optgroup label="Task">
                <option value="status_change">Status Change</option>
                <option value="client_reply">Client Reply</option>
                <option value="revision">Revision</option>
                <option value="file_upload">File Upload</option>
            </optgroup>
        </select>
        <select id="filter-entity">
            <option value="">All Entities</option>
            <option value="lead">Leads</option>
            <option value="client">Clients</option>
            <option value="quote">Quotes</option>
            <option value="task">Tasks</option>
        </select>
        <input type="date" id="filter-date-from" title="From Date" />
        <input type="date" id="filter-date-to" title="To Date" />
        <button class="act-filter-btn" onclick="applyFilters()"><i data-lucide="filter"
                style="width:14px;height:14px;margin-right:4px;"></i> Filter</button>
        <button class="act-clear-btn" onclick="clearFilters()"><i data-lucide="x"
                style="width:14px;height:14px;margin-right:2px;"></i> Clear</button>
    </div>

    <!-- Activity Feed -->
    <div class="act-feed-card">
        <div class="act-feed-header">
            <h3>
                <span
                    style="width:8px;height:8px;border-radius:50%;background:linear-gradient(135deg,#4f46e5,#6366f1);display:inline-block;"></span>
                Activity Timeline
            </h3>
            <span class="act-feed-count" id="feed-count">{{ $allActivities->count() }} Activities</span>
        </div>
        <div id="activity-feed">
            @if($allActivities->isEmpty())
                <div class="act-empty">
                    <i data-lucide="inbox" style="width:48px;height:48px;"></i>
                    <p>No activities recorded yet</p>
                </div>
            @else
                @foreach($allActivities as $idx => $act)
                    @php
                        $typeIcons = [
                            'call' => 'phone',
                            'whatsapp' => 'message-circle',
                            'email' => 'mail',
                            'note' => 'sticky-note',
                            'meeting' => 'calendar',
                            'task' => 'check-square',
                            'status_change' => 'refresh-cw',
                            'client_reply' => 'message-square',
                            'revision' => 'rotate-ccw',
                            'file_upload' => 'upload'
                        ];
                        $typeColors = [
                            'call' => '#16a34a',
                            'whatsapp' => '#059669',
                            'email' => '#2563eb',
                            'note' => '#7c3aed',
                            'meeting' => '#d97706',
                            'task' => '#4f46e5',
                            'status_change' => '#1d4ed8',
                            'client_reply' => '#047857',
                            'revision' => '#b45309',
                            'file_upload' => '#be185d'
                        ];
                        $icon = $typeIcons[$act['type']] ?? 'activity';
                        $color = $typeColors[$act['type']] ?? '#64748b';
                        $typeClass = $act['type'];
                    @endphp
                    <div class="act-item" style="animation-delay:{{ $idx * 0.03 }}s;">
                        <div class="act-type-icon {{ $typeClass }}">
                            <i data-lucide="{{ $icon }}" style="width:18px;height:18px;"></i>
                        </div>
                        <div class="act-avatar">{{ $act['user_initials'] }}</div>
                        <div class="act-content">
                            <p class="act-summary">{{ $act['summary'] }}</p>
                            <div class="act-meta">
                                <span class="act-user-name">{{ $act['user_name'] }}</span>
                                <span class="act-type-badge"
                                    style="background:{{ $color }}15;color:{{ $color }};">{{ ucfirst(str_replace('_', ' ', $act['type'])) }}</span>
                                @if(!empty($act['entity_type']))
                                    <span class="act-entity-badge {{ $act['entity_type'] }}">{{ ucfirst($act['entity_type']) }}</span>
                                @endif
                                <span class="act-time">{{ $act['created_at_human'] }}</span>
                            </div>
                        </div>
                        <div class="act-right">
                            <span class="act-date-full">{{ $act['created_at_formatted'] }}</span>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const ACTIVITY_URL = @json(route('admin.activities.index'));

        function applyFilters() {
            const params = new URLSearchParams();
            const search = document.getElementById('filter-search').value;
            const userId = document.getElementById('filter-user').value;
            const type = document.getElementById('filter-type').value;
            const entity = document.getElementById('filter-entity').value;
            const dateFrom = document.getElementById('filter-date-from').value;
            const dateTo = document.getElementById('filter-date-to').value;

            if (search) params.set('search', search);
            if (userId) params.set('user_id', userId);
            if (type) params.set('type', type);
            if (entity) params.set('entity_type', entity);
            if (dateFrom) params.set('date_from', dateFrom);
            if (dateTo) params.set('date_to', dateTo);

            const feed = document.getElementById('activity-feed');
            feed.innerHTML = '<div class="act-loading"><i data-lucide="loader" style="width:24px;height:24px;animation:spin 1s linear infinite;"></i><p>Loading...</p></div>';

            fetch(ACTIVITY_URL + '?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('feed-count').textContent = data.total + ' Activities';
                    document.getElementById('total-badge').textContent = data.total;

                    if (!data.activities || data.activities.length === 0) {
                        feed.innerHTML = '<div class="act-empty"><i data-lucide="inbox" style="width:48px;height:48px;"></i><p>No activities found for this filter</p></div>';
                        lucide.createIcons();
                        return;
                    }

                    const typeIcons = {
                        call: 'phone', whatsapp: 'message-circle', email: 'mail', note: 'sticky-note',
                        meeting: 'calendar', task: 'check-square', status_change: 'refresh-cw',
                        client_reply: 'message-square', revision: 'rotate-ccw', file_upload: 'upload'
                    };
                    const typeColors = {
                        call: '#16a34a', whatsapp: '#059669', email: '#2563eb', note: '#7c3aed',
                        meeting: '#d97706', task: '#4f46e5', status_change: '#1d4ed8',
                        client_reply: '#047857', revision: '#b45309', file_upload: '#be185d'
                    };

                    let html = '';
                    data.activities.forEach((act, i) => {
                        const icon = typeIcons[act.type] || 'activity';
                        const color = typeColors[act.type] || '#64748b';
                        const typeLabel = (act.type || '').replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
                        const entityBadge = act.entity_type
                            ? `<span class="act-entity-badge ${act.entity_type}">${act.entity_type.charAt(0).toUpperCase() + act.entity_type.slice(1)}</span>`
                            : '';

                        html += `
                    <div class="act-item" style="animation-delay:${i * 0.03}s;">
                        <div class="act-type-icon ${act.type}">
                            <i data-lucide="${icon}" style="width:18px;height:18px;"></i>
                        </div>
                        <div class="act-avatar">${act.user_initials || 'SY'}</div>
                        <div class="act-content">
                            <p class="act-summary">${act.summary || act.subject || ''}</p>
                            <div class="act-meta">
                                <span class="act-user-name">${act.user_name || 'System'}</span>
                                <span class="act-type-badge" style="background:${color}15;color:${color};">${typeLabel}</span>
                                ${entityBadge}
                                <span class="act-time">${act.created_at_human}</span>
                            </div>
                        </div>
                        <div class="act-right">
                            <span class="act-date-full">${act.created_at_formatted}</span>
                        </div>
                    </div>`;
                    });
                    feed.innerHTML = html;
                    lucide.createIcons();
                })
                .catch(() => {
                    feed.innerHTML = '<div class="act-empty"><p>Failed to load activities</p></div>';
                });
        }

        function clearFilters() {
            document.getElementById('filter-search').value = '';
            document.getElementById('filter-user').value = '';
            document.getElementById('filter-type').value = '';
            document.getElementById('filter-entity').value = '';
            document.getElementById('filter-date-from').value = '';
            document.getElementById('filter-date-to').value = '';
            applyFilters();
        }
    </script>
@endpush