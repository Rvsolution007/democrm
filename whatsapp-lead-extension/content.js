/**
 * RV CRM - WhatsApp Lead Capture Extension
 */

(function () {
    'use strict';

    const CRM_BASE_URL = 'https://crm.rvallsolutions.com';
    const LEADS_PATH = '/admin/leads';

    // Extract phone number robustly from WhatsApp Web DOM
    function extractPhoneNumber() {
        let phone = null;

        // Try extracting from the "info" sidebar if open (most reliable for saved contacts)
        const sidebarSpans = document.querySelectorAll('section span[dir="auto"], section div[dir="auto"]');
        for (let span of sidebarSpans) {
            const text = span.textContent || '';
            // Match numbers like: +91 98765 43210 or 9876543210
            const cln = text.replace(/[\s\-\(\)]/g, '');
            if (/^\+?\d{10,15}$/.test(cln) && !text.includes('last seen') && !text.includes('online')) {
                return cln.replace(/^\+/, '');
            }
        }

        // Try extracting from the main chat header title
        const headerTitle = document.querySelector('#main header span[title]');
        if (headerTitle) {
            const titleText = headerTitle.getAttribute('title') || '';
            const cleaned = titleText.replace(/[\s\-\(\)]/g, '');
            if (/^\+?\d{10,15}$/.test(cleaned)) {
                return cleaned.replace(/^\+/, '');
            }
        }

        return null;
    }

    // Floating Toast Notification
    function showToast(message, type) {
        const existing = document.getElementById('rvcrm-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'rvcrm-toast';
        const isError = type === 'error';
        const bgColor = isError ? '#dc2626' : '#25D366';
        
        toast.innerHTML = `
            <div style="position:fixed;bottom:24px;right:24px;z-index:99999;background:${bgColor};color:white;padding:14px 22px;border-radius:12px;font-family:sans-serif;font-size:14px;font-weight:500;box-shadow:0 8px 30px rgba(0,0,0,0.25);display:flex;align-items:center;gap:10px;cursor:pointer;" onclick="this.parentElement.remove()">
                <span style="font-size:18px">${isError ? '⚠️' : '🚀'}</span>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => { if (document.getElementById('rvcrm-toast')) document.getElementById('rvcrm-toast').remove(); }, 5000);
    }

    // Capture Lead Action
    function captureLead() {
        console.log('[RV CRM] Capture action triggered');
        let phone = extractPhoneNumber();

        // If phone not found automatically, ask the user to input it
        if (!phone) {
            // Wait, for saved contacts, the user MUST open the info panel.
            // Let's prompt them visually if we can't find it.
            const manualPhone = prompt('Phone number automatically nahi mila.\n(Ya to right-side "Contact Info" panel open karo, YA number yahan type karo):', '+91');
            if (manualPhone && manualPhone.trim() !== '' && manualPhone !== '+91') {
                phone = manualPhone;
            } else {
                showToast('Number enter nahi kiya. Pehle Contact Info kholo ya number type karo.', 'error');
                return;
            }
        }

        // Clean phone
        let cleanPhone = phone.replace(/\D/g, '');
        const crmUrl = CRM_BASE_URL + LEADS_PATH + '?quick_add=1&phone=' + encodeURIComponent(cleanPhone);
        
        showToast('Lead form open ho raha hai: ' + cleanPhone, 'success');
        
        setTimeout(() => { window.open(crmUrl, '_blank'); }, 300);
    }

    // Shortcut Ctrl+Shift+L
    document.addEventListener('keydown', function(e) {
        // Ctrl + Shift + L
        if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'l') {
            e.preventDefault();
            e.stopPropagation();
            captureLead();
        }
        
        // Backup shortcut: Alt + X
        if (e.altKey && e.key.toLowerCase() === 'x') {
            e.preventDefault();
            e.stopPropagation();
            captureLead();
        }
    }, true);

    // Inject UI Button into WhatsApp Header
    function injectButton() {
        if (document.getElementById('rvcrm-btn')) return;

        // WhatsApp header right-side menu container
        const headerMenu = document.querySelector('#main header div[role="button"]')?.parentElement?.parentElement;
        if (!headerMenu) return;

        const btnContainer = document.createElement('div');
        btnContainer.id = 'rvcrm-btn';
        btnContainer.style.marginRight = '10px';
        btnContainer.innerHTML = `
            <button style="background:#2563eb;color:white;border:none;border-radius:6px;padding:6px 12px;font-weight:bold;cursor:pointer;display:flex;align-items:center;gap:6px;font-size:13px;box-shadow:0 2px 5px rgba(0,0,0,0.2);">
                <span>➕</span> RV CRM Lead
            </button>
        `;
        
        btnContainer.querySelector('button').addEventListener('click', captureLead);
        
        // Insert right before the other icons
        headerMenu.insertBefore(btnContainer, headerMenu.firstChild);
    }

    // Keep checking to inject button because WhatsApp is a SPA
    setInterval(injectButton, 2000);

    console.log('[RV CRM] Extension loaded! Use Alt+X, Ctrl+Shift+L, or the Header Button.');

})();
