// RV CRM - Main JavaScript

// ==================== UTILITY FUNCTIONS ====================

// Format currency in INR
function formatINR(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

// Format date as DD/MM/YYYY
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

// Format relative time
function formatRelativeTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const diffDays = Math.floor(diff / (1000 * 60 * 60 * 24));
    const diffHours = Math.floor(diff / (1000 * 60 * 60));
    const diffMins = Math.floor(diff / (1000 * 60));

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays}d ago`;
    return formatDate(dateString);
}

// Check if date is overdue
function isOverdue(dateString) {
    if (!dateString) return false;
    return new Date(dateString) < new Date();
}

// Get user by ID
function getUserById(userId) {
    return DATA.users.find(u => u.id === userId) || { name: 'Unassigned', avatar: '?' };
}

// Get stage label
function getStageLabel(stage) {
    const found = DATA.LEAD_STAGES.find(s => s.value === stage);
    return found ? found.label : stage;
}

// Get source label
function getSourceLabel(source) {
    const found = DATA.LEAD_SOURCES.find(s => s.value === source);
    return found ? found.label : source;
}

// ==================== SIDEBAR & NAVIGATION ====================

function initSidebar() {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    // Set active nav link
    const currentPage = window.location.pathname.split('/').pop() || 'dashboard.html';
    document.querySelectorAll('.nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'dashboard.html')) {
            link.classList.add('active');
        }
    });
}

// ==================== DARK MODE ====================

function initDarkMode() {
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    const isDark = localStorage.getItem('darkMode') === 'true';

    if (isDark) {
        document.body.classList.add('dark');
    }

    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark');
            localStorage.setItem('darkMode', document.body.classList.contains('dark'));
        });
    }
}

// ==================== TOAST NOTIFICATIONS ====================

function showToast(message, type = 'success', duration = 3000) {
    const container = document.getElementById('toast-container') || createToastContainer();

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    const icons = {
        success: '<i data-lucide="check-circle"></i>',
        error: '<i data-lucide="x-circle"></i>',
        warning: '<i data-lucide="alert-triangle"></i>',
        info: '<i data-lucide="info"></i>'
    };

    toast.innerHTML = `
    <span class="toast-icon">${icons[type] || icons.info}</span>
    <div class="toast-content">
      <p class="toast-title">${message}</p>
    </div>
    <button class="toast-close" onclick="this.parentElement.remove()">
      <i data-lucide="x" style="width:16px;height:16px"></i>
    </button>
  `;

    container.appendChild(toast);
    lucide.createIcons();

    setTimeout(() => {
        toast.classList.add('exiting');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

// ==================== MODALS & DRAWERS ====================

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    const overlay = document.getElementById('modal-overlay');
    if (modal) modal.classList.add('active');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    const overlay = document.getElementById('modal-overlay');
    if (modal) modal.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

function openDrawer(drawerId) {
    const drawer = document.getElementById(drawerId);
    const overlay = document.getElementById('drawer-overlay');
    if (drawer) drawer.classList.add('active');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeDrawer(drawerId) {
    const drawer = document.getElementById(drawerId);
    const overlay = document.getElementById('drawer-overlay');
    if (drawer) drawer.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

// Close on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.active, .drawer.active').forEach(el => {
            el.classList.remove('active');
        });
        document.querySelectorAll('.overlay.active').forEach(el => {
            el.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
});

// ==================== TABS ====================

function initTabs() {
    document.querySelectorAll('.tabs-list').forEach(tabsList => {
        const triggers = tabsList.querySelectorAll('.tabs-trigger');
        triggers.forEach(trigger => {
            trigger.addEventListener('click', () => {
                const tabId = trigger.dataset.tab;
                const parent = trigger.closest('.tabs');

                // Update active trigger
                triggers.forEach(t => t.classList.remove('active'));
                trigger.classList.add('active');

                // Update active content
                parent.querySelectorAll('.tabs-content').forEach(content => {
                    content.classList.remove('active');
                    if (content.id === tabId) content.classList.add('active');
                });
            });
        });
    });
}

// ==================== TABLE FUNCTIONS ====================

function initTableSearch(tableId, searchInputId) {
    const input = document.getElementById(searchInputId);
    const table = document.getElementById(tableId);

    if (!input || !table) return;

    input.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });

        updateTableCount(tableId);
    });
}

function sortTable(tableId, columnIndex, type = 'string') {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const th = table.querySelectorAll('th')[columnIndex];

    // Toggle sort direction
    const isAsc = th.classList.contains('sorted-asc');
    table.querySelectorAll('th').forEach(h => {
        h.classList.remove('sorted', 'sorted-asc', 'sorted-desc');
    });
    th.classList.add('sorted', isAsc ? 'sorted-desc' : 'sorted-asc');

    rows.sort((a, b) => {
        let aVal = a.cells[columnIndex].textContent.trim();
        let bVal = b.cells[columnIndex].textContent.trim();

        if (type === 'number') {
            aVal = parseFloat(aVal.replace(/[₹,]/g, '')) || 0;
            bVal = parseFloat(bVal.replace(/[₹,]/g, '')) || 0;
        } else if (type === 'date') {
            aVal = new Date(aVal.split('/').reverse().join('-'));
            bVal = new Date(bVal.split('/').reverse().join('-'));
        } else {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }

        if (isAsc) {
            return aVal > bVal ? -1 : aVal < bVal ? 1 : 0;
        }
        return aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
    });

    rows.forEach(row => tbody.appendChild(row));
}

function updateTableCount(tableId) {
    const table = document.getElementById(tableId);
    const countEl = document.getElementById(`${tableId}-count`);
    if (!table || !countEl) return;

    const visible = table.querySelectorAll('tbody tr:not([style*="display: none"])').length;
    const total = table.querySelectorAll('tbody tr').length;
    countEl.textContent = `Showing ${visible} of ${total} entries`;
}

// ==================== FORM HANDLING ====================

function clearForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
        form.querySelectorAll('.form-error').forEach(el => el.remove());
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    }
}

function validateForm(formId) {
    const form = document.getElementById(formId);
    let isValid = true;

    // Clear previous errors
    form.querySelectorAll('.form-error').forEach(el => el.remove());
    form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

    // Check required fields
    form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            const error = document.createElement('span');
            error.className = 'form-error';
            error.textContent = 'This field is required';
            field.parentElement.appendChild(error);
        }
    });

    // Check phone number (Indian format)
    form.querySelectorAll('[data-validate="phone"]').forEach(field => {
        const phone = field.value.replace(/\D/g, '');
        if (phone && phone.length !== 10) {
            isValid = false;
            field.classList.add('error');
            const error = document.createElement('span');
            error.className = 'form-error';
            error.textContent = 'Enter valid 10-digit phone number';
            field.parentElement.appendChild(error);
        }
    });

    // Check email
    form.querySelectorAll('[data-validate="email"]').forEach(field => {
        const email = field.value;
        if (email && !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            isValid = false;
            field.classList.add('error');
            const error = document.createElement('span');
            error.className = 'form-error';
            error.textContent = 'Enter valid email address';
            field.parentElement.appendChild(error);
        }
    });

    return isValid;
}

// ==================== DROPDOWN ====================

function toggleDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        dropdown.classList.toggle('active');
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
            menu.classList.remove('active');
        });
    }
});

// ==================== CONFIRMATION DIALOG ====================

function showConfirmDialog(title, message, onConfirm, confirmText = 'Delete', variant = 'destructive') {
    const modal = document.getElementById('confirm-modal');
    if (!modal) return;

    modal.querySelector('.modal-title').textContent = title;
    modal.querySelector('.modal-body p').textContent = message;

    const confirmBtn = modal.querySelector('.btn-confirm');
    confirmBtn.textContent = confirmText;
    confirmBtn.className = `btn btn-${variant}`;
    confirmBtn.onclick = () => {
        onConfirm();
        closeModal('confirm-modal');
    };

    openModal('confirm-modal');
}

// ==================== INITIALIZE ====================

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initDarkMode();
    initTabs();

    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
