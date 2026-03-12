/**
 * RV CRM - WhatsApp Lead Capture Extension
 * 
 * Ctrl+Shift+L press karne pe WhatsApp Web se phone number extract karke
 * CRM me Lead form open karta hai number pre-filled ke saath.
 * 
 * CRM URL: https://crm.rvallsolutions.com/
 */

(function () {
    'use strict';

    // ─── Configuration ───────────────────────────────────────────────────
    const CRM_BASE_URL = 'https://crm.rvallsolutions.com';
    const LEADS_PATH = '/admin/leads';
    const SHORTCUT_KEY = 'l';       // Ctrl + Shift + L (L = Lead)
    const USE_CTRL = true;
    const USE_SHIFT = true;
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Extract phone number from the currently open WhatsApp chat header.
     * WhatsApp Web stores the chat contact info in the header area.
     * 
     * Multiple strategies are used because WhatsApp Web DOM changes frequently.
     */
    function extractPhoneNumber() {
        let phone = null;

        // ── Strategy 1: Header title (contact name / number) ──
        // The chat header shows either a saved contact name or phone number.
        // For unsaved contacts, it shows the phone number directly.
        const headerTitle = document.querySelector('header span[title]');
        if (headerTitle) {
            const titleText = headerTitle.getAttribute('title') || '';
            // Check if title looks like a phone number (contains digits, spaces, +, -)
            const cleaned = titleText.replace(/[\s\-\(\)]/g, '');
            if (/^\+?\d{7,15}$/.test(cleaned)) {
                phone = cleaned.replace(/^\+/, '');
                console.log('[RV CRM] Phone from header title:', phone);
                return phone;
            }
        }

        // ── Strategy 2: Chat info panel (if open) ──
        // When user clicks on the contact header, a details panel opens on the right
        // which shows the phone number even for saved contacts.
        const infoSection = document.querySelectorAll('section span[title]');
        for (let i = 0; i < infoSection.length; i++) {
            const text = (infoSection[i].getAttribute('title') || '').replace(/[\s\-\(\)]/g, '');
            if (/^\+?\d{7,15}$/.test(text)) {
                phone = text.replace(/^\+/, '');
                console.log('[RV CRM] Phone from info panel:', phone);
                return phone;
            }
        }

        // ── Strategy 3: The "data-id" from the active chat list item ──
        // Chat list items have data attributes with the JID (phone@s.whatsapp.net)
        const activeChat = document.querySelector('[data-testid="cell-frame-container"][class*="active"], div[tabindex="-1"][data-testid="cell-frame-container"]._amig');
        if (activeChat) {
            // Look for data attribute patterns
            const ariaText = activeChat.getAttribute('aria-label') || '';
            const numMatch = ariaText.match(/(\+?\d[\d\s\-]{6,14}\d)/);
            if (numMatch) {
                phone = numMatch[1].replace(/[\s\-\+]/g, '');
                console.log('[RV CRM] Phone from active chat aria:', phone);
                return phone;
            }
        }

        // ── Strategy 4: Scan conversation header more broadly ──
        // Sometimes the number is in a different element within the header
        const headerContainer = document.querySelector('header');
        if (headerContainer) {
            const allSpans = headerContainer.querySelectorAll('span');
            for (let i = 0; i < allSpans.length; i++) {
                const text = (allSpans[i].textContent || '').trim().replace(/[\s\-\(\)]/g, '');
                if (/^\+?\d{8,15}$/.test(text)) {
                    phone = text.replace(/^\+/, '');
                    console.log('[RV CRM] Phone from header scan:', phone);
                    return phone;
                }
            }
        }

        return null;
    }

    /**
     * Show a floating notification toast on WhatsApp Web page
     */
    function showToast(message, type) {
        // Remove existing toast
        const existing = document.getElementById('rvcrm-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'rvcrm-toast';

        const isError = type === 'error';
        const bgColor = isError ? '#dc2626' : '#25D366';
        const icon = isError ? '⚠️' : '🚀';

        toast.innerHTML = `
            <div style="
                position: fixed;
                bottom: 24px;
                right: 24px;
                z-index: 99999;
                background: ${bgColor};
                color: white;
                padding: 14px 22px;
                border-radius: 12px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                font-size: 14px;
                font-weight: 500;
                box-shadow: 0 8px 30px rgba(0,0,0,0.25);
                display: flex;
                align-items: center;
                gap: 10px;
                animation: rvcrm-slide-in 0.3s ease-out;
                cursor: pointer;
                max-width: 400px;
            " onclick="this.parentElement.remove()">
                <span style="font-size:18px">${icon}</span>
                <span>${message}</span>
            </div>
        `;

        // Add animation keyframes if not already present
        if (!document.getElementById('rvcrm-styles')) {
            const style = document.createElement('style');
            style.id = 'rvcrm-styles';
            style.textContent = `
                @keyframes rvcrm-slide-in {
                    from { transform: translateX(100px); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes rvcrm-slide-out {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100px); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(toast);

        // Auto-remove after 4 seconds
        setTimeout(function () {
            const el = document.getElementById('rvcrm-toast');
            if (el) {
                el.querySelector('div').style.animation = 'rvcrm-slide-out 0.3s ease-in forwards';
                setTimeout(function () { el.remove(); }, 300);
            }
        }, 4000);
    }

    /**
     * Main shortcut handler
     */
    function handleShortcut(e) {
        // Check: Ctrl + Shift + L (case insensitive)
        if (USE_CTRL && !e.ctrlKey) return;
        if (USE_SHIFT && !e.shiftKey) return;
        if (e.key.toLowerCase() !== SHORTCUT_KEY) return;
        if (e.altKey || e.metaKey) return;

        // Prevent default browser action
        e.preventDefault();
        e.stopPropagation();

        console.log('[RV CRM] Shortcut Ctrl+Shift+L detected!');

        const phone = extractPhoneNumber();

        if (!phone) {
            showToast('Phone number nahi mila! Pehle koi chat open karo ya unsaved number ki chat kholke try karo.', 'error');
            return;
        }

        // Clean the phone number - ensure it has country code
        let cleanPhone = phone.replace(/\D/g, '');

        // Build CRM URL
        const crmUrl = CRM_BASE_URL + LEADS_PATH + '?quick_add=1&phone=' + encodeURIComponent(cleanPhone);

        showToast('Lead form open ho raha hai: ' + cleanPhone, 'success');

        // Open CRM in new tab
        setTimeout(function () {
            window.open(crmUrl, '_blank');
        }, 500);
    }

    // ─── Register keyboard shortcut ──────────────────────────────────────
    document.addEventListener('keydown', handleShortcut, true);

    // ─── Confirmation that extension is loaded ───────────────────────────
    console.log('[RV CRM] WhatsApp Lead Capture extension loaded! Press Ctrl+Shift+L to capture a lead.');

})();
