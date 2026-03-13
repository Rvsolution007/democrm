/**
 * RV CRM - WhatsApp Lead Capture Extension
 * 
 * Features:
 * - Auto-detects phone number from current WhatsApp chat
 * - Shows lead form popup directly inside WhatsApp (no redirect)
 * - Button injection in chat header
 * - Shortcuts: Alt+X, Ctrl+Shift+L
 */

(function () {
    'use strict';

    const CRM_BASE_URL = 'https://crm.rvallsolutions.com';

    // ====== Phone Number Extraction ======

    function extractPhoneNumber() {
        let phone = null;

        // Method 1: Extract from page URL (most reliable)
        // WhatsApp Web URL format: https://web.whatsapp.com/#/chat/919876543210
        // or sometimes in the URL hash
        try {
            const hash = window.location.hash || '';
            const urlMatch = hash.match(/(\d{10,15})/);
            if (urlMatch) {
                phone = urlMatch[1];
                if (phone) return phone;
            }
        } catch (e) {}

        // Method 2: Extract from chat header (works for unsaved contacts)
        try {
            // Try multiple selectors for the header title
            const selectors = [
                '#main header span[title]',
                '#main header [data-testid="conversation-info-header-chat-title"] span[dir="ltr"]',
                '#main header [data-testid="conversation-info-header-chat-title"] span',
                'header span[title][dir="auto"]'
            ];
            
            for (let sel of selectors) {
                const el = document.querySelector(sel);
                if (el) {
                    const titleText = el.getAttribute('title') || el.textContent || '';
                    const cleaned = titleText.replace(/[\s\-\(\)]/g, '');
                    if (/^\+?\d{10,15}$/.test(cleaned)) {
                        return cleaned.replace(/^\+/, '');
                    }
                }
            }
        } catch (e) {}

        // Method 3: Check the contact info sidebar if open
        try {
            const sidebarSpans = document.querySelectorAll('section span[dir="auto"], section div[dir="auto"]');
            for (let span of sidebarSpans) {
                const text = span.textContent || '';
                const cln = text.replace(/[\s\-\(\)]/g, '');
                if (/^\+?\d{10,15}$/.test(cln) && !text.includes('last seen') && !text.includes('online')) {
                    return cln.replace(/^\+/, '');
                }
            }
        } catch (e) {}

        // Method 4: Try data attributes and aria labels
        try {
            const chatPanel = document.querySelector('#main');
            if (chatPanel) {
                const allSpans = chatPanel.querySelectorAll('header span');
                for (let span of allSpans) {
                    const text = (span.getAttribute('title') || span.textContent || '').trim();
                    const cleaned = text.replace(/[\s\-\(\)\+]/g, '');
                    if (/^\d{10,15}$/.test(cleaned)) {
                        return cleaned;
                    }
                }
            }
        } catch (e) {}

        return null;
    }

    // ====== Toast Notification ======

    function showToast(message, type) {
        const existing = document.getElementById('rvcrm-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'rvcrm-toast';
        const isError = type === 'error';
        const bgColor = isError ? '#dc2626' : '#25D366';
        
        toast.innerHTML = `
            <div style="position:fixed;bottom:24px;right:24px;z-index:999999;background:${bgColor};color:white;padding:14px 22px;border-radius:12px;font-family:'Segoe UI',sans-serif;font-size:14px;font-weight:500;box-shadow:0 8px 30px rgba(0,0,0,0.25);display:flex;align-items:center;gap:10px;cursor:pointer;animation:rvcrm-slideIn 0.3s ease" onclick="this.parentElement.remove()">
                <span style="font-size:18px">${isError ? '⚠️' : '✅'}</span>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => { const t = document.getElementById('rvcrm-toast'); if (t) t.remove(); }, 4000);
    }

    // ====== Inline Lead Form Popup ======

    function createLeadPopup(phone) {
        // Remove any existing popup
        const existing = document.getElementById('rvcrm-lead-popup');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.id = 'rvcrm-lead-popup';
        overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999998;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(3px);animation:rvcrm-fadeIn 0.2s ease';

        const popup = document.createElement('div');
        popup.style.cssText = 'background:white;border-radius:16px;width:95%;max-width:480px;max-height:85vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,0.3);font-family:"Segoe UI","Helvetica Neue",sans-serif;animation:rvcrm-scaleIn 0.25s ease';

        popup.innerHTML = `
            <div style="padding:18px 22px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#2563eb,#3b82f6);border-radius:16px 16px 0 0">
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:20px">🚀</span>
                    <h3 style="margin:0;font-size:17px;font-weight:700;color:white">Add Lead to RV CRM</h3>
                </div>
                <button id="rvcrm-close-popup" style="background:rgba(255,255,255,0.2);border:none;font-size:16px;cursor:pointer;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;transition:all 0.15s" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">&times;</button>
            </div>
            <div style="padding:20px 22px">
                <div style="margin-bottom:14px">
                    <label style="display:block;margin-bottom:5px;font-weight:600;font-size:13px;color:#374151">Name <span style="color:#ef4444">*</span></label>
                    <input type="text" id="rvcrm-name" required placeholder="Enter lead name" style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none;box-sizing:border-box;transition:border-color 0.15s" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                    <div>
                        <label style="display:block;margin-bottom:5px;font-weight:600;font-size:13px;color:#374151">Phone <span style="color:#ef4444">*</span></label>
                        <input type="tel" id="rvcrm-phone" required placeholder="Phone number" style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none;box-sizing:border-box;background:#f8fafc;color:#334155" value="${phone || ''}">
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:5px;font-weight:600;font-size:13px;color:#374151">Email</label>
                        <input type="email" id="rvcrm-email" placeholder="optional" style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none;box-sizing:border-box;transition:border-color 0.15s" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                    <div>
                        <label style="display:block;margin-bottom:5px;font-weight:600;font-size:13px;color:#374151">City</label>
                        <input type="text" id="rvcrm-city" placeholder="City" style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none;box-sizing:border-box;transition:border-color 0.15s" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:5px;font-weight:600;font-size:13px;color:#374151">State</label>
                        <input type="text" id="rvcrm-state" placeholder="State" style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none;box-sizing:border-box;transition:border-color 0.15s" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>
                <div style="margin-bottom:14px">
                    <label style="display:block;margin-bottom:5px;font-weight:600;font-size:13px;color:#374151">Notes</label>
                    <textarea id="rvcrm-notes" rows="2" placeholder="Optional notes..." style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none;box-sizing:border-box;resize:vertical;transition:border-color 0.15s;font-family:inherit" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
                </div>
            </div>
            <div style="padding:14px 22px;border-top:1px solid #f0f0f0;display:flex;justify-content:flex-end;gap:10px;background:#fafbfc;border-radius:0 0 16px 16px">
                <button id="rvcrm-cancel-btn" style="padding:9px 18px;border:1.5px solid #e2e8f0;background:white;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;color:#64748b;transition:all 0.15s" onmouseover="this.style.borderColor='#cbd5e1';this.style.background='#f8fafc'" onmouseout="this.style.borderColor='#e2e8f0';this.style.background='white'">Cancel</button>
                <button id="rvcrm-save-btn" style="padding:9px 22px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;transition:all 0.15s;box-shadow:0 2px 8px rgba(37,99,235,0.3)" onmouseover="this.style.boxShadow='0 4px 12px rgba(37,99,235,0.4)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(37,99,235,0.3)'">Save Lead</button>
            </div>
        `;

        overlay.appendChild(popup);
        document.body.appendChild(overlay);

        // Event handlers
        document.getElementById('rvcrm-close-popup').addEventListener('click', closeLeadPopup);
        document.getElementById('rvcrm-cancel-btn').addEventListener('click', closeLeadPopup);
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closeLeadPopup();
        });

        document.getElementById('rvcrm-save-btn').addEventListener('click', submitLead);

        // Focus on name field
        setTimeout(() => {
            document.getElementById('rvcrm-name').focus();
        }, 200);

        // Allow Enter key to submit
        popup.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeLeadPopup();
        });
    }

    function closeLeadPopup() {
        const popup = document.getElementById('rvcrm-lead-popup');
        if (popup) popup.remove();
    }

    // ====== Submit Lead via CRM ======

    function submitLead() {
        const name = document.getElementById('rvcrm-name').value.trim();
        const phone = document.getElementById('rvcrm-phone').value.trim();
        const email = document.getElementById('rvcrm-email').value.trim();
        const city = document.getElementById('rvcrm-city').value.trim();
        const state = document.getElementById('rvcrm-state').value.trim();
        const notes = document.getElementById('rvcrm-notes').value.trim();

        if (!name) {
            showToast('Name is required!', 'error');
            document.getElementById('rvcrm-name').focus();
            return;
        }
        if (!phone) {
            showToast('Phone is required!', 'error');
            document.getElementById('rvcrm-phone').focus();
            return;
        }

        const saveBtn = document.getElementById('rvcrm-save-btn');
        saveBtn.textContent = 'Saving...';
        saveBtn.style.opacity = '0.7';
        saveBtn.disabled = true;

        // Send lead data to background service worker for cross-origin submission
        chrome.runtime.sendMessage({
            action: 'saveLead',
            data: { name, phone, email, city, state, notes }
        }, function(response) {
            if (response && response.success) {
                closeLeadPopup();
                showToast('Lead "' + name + '" saved successfully! ✅', 'success');
            } else {
                const errMsg = (response && response.error) || 'Error saving lead. CRM me login ho?';
                showToast(errMsg, 'error');
                saveBtn.textContent = 'Save Lead';
                saveBtn.style.opacity = '1';
                saveBtn.disabled = false;
            }
        });
    }

    // ====== Capture Lead Action ======

    function captureLead() {
        console.log('[RV CRM] Capture action triggered');
        const phone = extractPhoneNumber();
        
        if (phone) {
            console.log('[RV CRM] Phone detected: ' + phone);
        } else {
            console.log('[RV CRM] Phone not found in DOM, popup will open with empty phone');
        }

        // Show inline lead form popup
        createLeadPopup(phone || '');
    }

    // ====== Keyboard Shortcuts ======

    document.addEventListener('keydown', function(e) {
        // Alt + X
        if (e.altKey && e.key.toLowerCase() === 'x') {
            e.preventDefault();
            e.stopPropagation();
            captureLead();
        }
        // Ctrl + Shift + L
        if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'l') {
            e.preventDefault();
            e.stopPropagation();
            captureLead();
        }
    }, true);

    // ====== Inject Button into WhatsApp Header ======

    function injectButton() {
        if (document.getElementById('rvcrm-btn')) return;

        // Try multiple strategies to find the header menu area
        let headerArea = null;
        
        // Strategy 1: Find the menu/actions area in the main chat header
        const mainPanel = document.querySelector('#main');
        if (!mainPanel) return;
        
        const header = mainPanel.querySelector('header');
        if (!header) return;

        // Find the rightmost container in the header (where action buttons are)
        const headerChildren = header.children;
        if (headerChildren.length > 0) {
            headerArea = headerChildren[headerChildren.length - 1];
        }

        if (!headerArea) return;

        const btnContainer = document.createElement('div');
        btnContainer.id = 'rvcrm-btn';
        btnContainer.style.cssText = 'margin-right:8px;display:flex;align-items:center;';
        btnContainer.innerHTML = `
            <button id="rvcrm-header-btn" style="background:linear-gradient(135deg,#2563eb,#3b82f6);color:white;border:none;border-radius:8px;padding:7px 14px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;font-size:12px;box-shadow:0 2px 8px rgba(37,99,235,0.35);transition:all 0.2s;font-family:'Segoe UI',sans-serif;white-space:nowrap" onmouseover="this.style.boxShadow='0 4px 12px rgba(37,99,235,0.5)';this.style.transform='translateY(-1px)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(37,99,235,0.35)';this.style.transform='translateY(0)'">
                <span style="font-size:13px">➕</span> RV CRM Lead
            </button>
        `;
        
        // Add click handler
        btnContainer.querySelector('#rvcrm-header-btn').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            captureLead();
        });
        
        // Insert at the beginning of the header area
        headerArea.insertBefore(btnContainer, headerArea.firstChild);
    }

    // ====== Inject CSS Animations ======

    function injectStyles() {
        if (document.getElementById('rvcrm-styles')) return;
        const style = document.createElement('style');
        style.id = 'rvcrm-styles';
        style.textContent = `
            @keyframes rvcrm-fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes rvcrm-scaleIn {
                from { opacity: 0; transform: scale(0.95) translateY(10px); }
                to { opacity: 1; transform: scale(1) translateY(0); }
            }
            @keyframes rvcrm-slideIn {
                from { opacity: 0; transform: translateX(30px); }
                to { opacity: 1; transform: translateX(0); }
            }
        `;
        document.head.appendChild(style);
    }

    // ====== Initialize ======

    injectStyles();
    setInterval(injectButton, 2000);

    console.log('[RV CRM] Extension loaded! Use Alt+X, Ctrl+Shift+L, or the Header Button.');

})();
