<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - RV CRM Admin</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin-style.css') }}">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    @hasSection('has_charts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endif
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

    <script src="{{ asset('js/admin-main.js') }}"></script>
    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
    </script>
</body>

</html>