<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - RV CRM Admin</title>
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%23f59e0b' /%3E%3Cstop offset='100%25' stop-color='%2310b981' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='25' fill='url(%23g)' /%3E%3Ctext x='50' y='68' font-family='Arial, sans-serif' font-weight='900' font-size='52' fill='white' text-anchor='middle'%3ERV%3C/text%3E%3C/svg%3E">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin-style.css') }}?v={{ filemtime(public_path('css/admin-style.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/responsive-overrides.css') }}">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script type="module">
        import * as Turbo from 'https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.4/+esm';
    </script>
    <style>
        .turbo-progress-bar {
            height: 3px;
            background-color: #3b82f6;
        }
    </style>
    @hasSection('has_charts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endif
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @stack('styles')
</head>

<body>
    <div class="app-container">
        @include('admin.partials.sidebar')
        <div id="sidebar-overlay" class="overlay"></div>

        <main class="main-content">
            @include('admin.partials.header')

            <div class="page-content">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Confirm Modal -->
    <div id="modal-overlay" class="overlay" onclick="closeModal('confirm-modal')"></div>
    <div id="confirm-modal" class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Action</h3>
            <button class="modal-close" onclick="closeModal('confirm-modal')"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to proceed?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('confirm-modal')">Cancel</button>
            <button class="btn btn-destructive btn-confirm">Confirm</button>
        </div>
    </div>

    <!-- jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script src="{{ asset('js/admin-main.js') }}?v={{ filemtime(public_path('js/admin-main.js')) }}"></script>
    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
        document.addEventListener('turbo:load', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
        document.addEventListener('turbo:render', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>

</html>