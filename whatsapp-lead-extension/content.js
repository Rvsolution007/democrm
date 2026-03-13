/**
 * RV CRM - WhatsApp Lead Capture Extension
 * 
 * Features:
 * - Auto-detects phone number from current WhatsApp chat
 * - Shows lead form popup directly inside WhatsApp (no redirect)
 * - Button injection in chat header
 * - Shortcuts: Alt+X, Ctrl+Shift+L
 * - Lead status badges on sidebar chats
 * - Stage-wise filter bar above chat list
 */

(function () {
    'use strict';

    const CRM_BASE_URL = 'https://crm.rvallsolutions.com';

    // ====== Stage Colors (fallback, overridden by CRM response) ======
    let STAGE_COLORS = {
        'new': '#3b82f6',
        'contacted': '#f97316',
        'qualified': '#8b5cf6',
        'proposal': '#6366f1',
        'negotiation': '#f59e0b',
        'won': '#22c55e',
        'lost': '#ef4444',
    };

    let STAGE_LIST = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
    let leadDataCache = {}; // phone → { id, name, stage }
    let activeStageFilter = 'all';
    let isLookupInProgress = false;

    // ====== Phone Number Extraction ======

    function extractPhoneNumber() {
        let phone = null;

        // Method 1: Extract from page URL (most reliable)
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

    // ====== Extract Phone from Chat Item ======

    function getChatTitleElement(chatItem) {
        if (!chatItem) return null;
        
        // 1: span with title attribute
        let spansWithTitle = chatItem.querySelectorAll('span[title]');
        for (let s of spansWithTitle) {
            if (s.getAttribute('title').trim() !== '') return s;
        }
        
        // 2: div with title attribute
        let divsWithTitle = chatItem.querySelectorAll('div[title]');
        for (let d of divsWithTitle) {
            if (d.getAttribute('title').trim() !== '') return d;
        }

        // 3: span with dir="auto"
        let dirAutoSpans = chatItem.querySelectorAll('span[dir="auto"]');
        for (let s of dirAutoSpans) {
            const txt = s.textContent || '';
            if (txt.trim().length > 1 && !/^\d{1,2}:\d{2}/.test(txt.trim())) {
                return s;
            }
        }
        
        return null;
    }

    function getChatTitleText(chatItem) {
        const el = getChatTitleElement(chatItem);
        if (!el) return '';
        return el.getAttribute('title') || el.textContent || '';
    }

    function extractPhoneFromChatItem(chatItem) {
        const title = getChatTitleText(chatItem);
        const cleaned = title.replace(/[\s\-\(\)\+]/g, '');

        if (/^\d{10,15}$/.test(cleaned)) {
            return cleaned;
        }

        return null;
    }

    function getChatName(chatItem) {
        return getChatTitleText(chatItem);
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

        document.getElementById('rvcrm-close-popup').addEventListener('click', closeLeadPopup);
        document.getElementById('rvcrm-cancel-btn').addEventListener('click', closeLeadPopup);
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closeLeadPopup();
        });

        document.getElementById('rvcrm-save-btn').addEventListener('click', submitLead);

        setTimeout(() => {
            document.getElementById('rvcrm-name').focus();
        }, 200);

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

        chrome.runtime.sendMessage({
            action: 'saveLead',
            data: { name, phone, email, city, state, notes }
        }, function(response) {
            if (response && response.success) {
                closeLeadPopup();
                showToast('Lead "' + name + '" saved successfully! ✅', 'success');
                // Refresh lead data after saving
                setTimeout(scanAndLookupLeads, 1500);
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

        let headerArea = null;
        const mainPanel = document.querySelector('#main');
        if (!mainPanel) return;
        
        const header = mainPanel.querySelector('header');
        if (!header) return;

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
        
        btnContainer.querySelector('#rvcrm-header-btn').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            captureLead();
        });
        
        headerArea.insertBefore(btnContainer, headerArea.firstChild);
    }

    // ====== Stage Filter Bar ======

    function injectFilterBar() {
        if (document.getElementById('rvcrm-filter-bar')) return;

        // Find the chat list panel (left sidebar)
        const sidePanel = document.querySelector('#pane-side');
        if (!sidePanel) return;

        const filterBar = document.createElement('div');
        filterBar.id = 'rvcrm-filter-bar';
        filterBar.style.cssText = `
            display:flex;align-items:center;gap:6px;padding:8px 12px;
            overflow-x:auto;background:#f0f2f5;border-bottom:1px solid #e2e8f0;
            scrollbar-width:none;-ms-overflow-style:none;
            font-family:'Segoe UI',sans-serif;flex-shrink:0;
        `;

        // Build filter pills
        buildFilterPills(filterBar);

        // Insert the filter bar before the chat list
        sidePanel.parentNode.insertBefore(filterBar, sidePanel);
    }

    function buildFilterPills(container) {
        if (!container) container = document.getElementById('rvcrm-filter-bar');
        if (!container) return;

        container.innerHTML = '';

        // Count leads per stage
        const stageCounts = {};
        let totalLeads = 0;
        for (const phone in leadDataCache) {
            const stage = leadDataCache[phone].stage;
            stageCounts[stage] = (stageCounts[stage] || 0) + 1;
            totalLeads++;
        }

        // "All" pill
        const allPill = createFilterPill('All', totalLeads, 'all', '#6b7280');
        container.appendChild(allPill);

        // Stage pills
        STAGE_LIST.forEach(stage => {
            const count = stageCounts[stage] || 0;
            const color = STAGE_COLORS[stage] || '#6b7280';
            const label = stage.charAt(0).toUpperCase() + stage.slice(1);
            const pill = createFilterPill(label, count, stage, color);
            container.appendChild(pill);
        });
    }

    function createFilterPill(label, count, stageValue, color) {
        const isActive = activeStageFilter === stageValue;
        
        const pill = document.createElement('button');
        pill.className = 'rvcrm-filter-pill';
        pill.dataset.stage = stageValue;
        pill.style.cssText = `
            display:flex;align-items:center;gap:5px;padding:5px 12px;
            border-radius:20px;border:1.5px solid ${isActive ? color : '#d1d5db'};
            background:${isActive ? color : 'white'};
            color:${isActive ? 'white' : '#374151'};
            font-size:11px;font-weight:600;cursor:pointer;white-space:nowrap;
            transition:all 0.2s;font-family:'Segoe UI',sans-serif;
            box-shadow:${isActive ? '0 2px 8px ' + color + '40' : 'none'};
        `;

        pill.innerHTML = `
            <span>${label}</span>
            <span style="
                background:${isActive ? 'rgba(255,255,255,0.3)' : color + '18'};
                color:${isActive ? 'white' : color};
                padding:1px 6px;border-radius:10px;font-size:10px;font-weight:700;
                min-width:14px;text-align:center;
            ">${count}</span>
        `;

        pill.addEventListener('click', function() {
            activeStageFilter = stageValue;
            buildFilterPills();
            applyStageFilter();
        });

        pill.addEventListener('mouseover', function() {
            if (!isActive) {
                this.style.borderColor = color;
                this.style.background = color + '10';
            }
        });

        pill.addEventListener('mouseout', function() {
            if (activeStageFilter !== stageValue) {
                this.style.borderColor = '#d1d5db';
                this.style.background = 'white';
            }
        });

        return pill;
    }

    // ====== Apply Stage Filter to Chat Items ======

    function applyStageFilter() {
        const chatItems = getChatListItems();
        
        chatItems.forEach(item => {
            const phone = getPhoneForChatItem(item);
            const leadData = phone ? leadDataCache[phone] : null;

            if (activeStageFilter === 'all') {
                item.style.display = '';
            } else {
                if (leadData && leadData.stage === activeStageFilter) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            }
        });
    }

    // ====== Inject Stage Badges on Chat Items ======

    function injectStageBadges() {
        const chatItems = getChatListItems();

        chatItems.forEach(item => {
            const phone = getPhoneForChatItem(item);
            if (!phone) {
                // Remove badge if no phone match
                removeBadge(item);
                return;
            }

            const leadData = leadDataCache[phone];
            if (!leadData) {
                removeBadge(item);
                return;
            }

            // Check if badge already exists and is correct
            const existingBadge = item.querySelector('.rvcrm-stage-badge');
            if (existingBadge && existingBadge.dataset.stage === leadData.stage) {
                return; // Badge already correct
            }

            // Remove old badge
            removeBadge(item);

            // Find the name/title area to append the badge
            const titleRow = getChatTitleElement(item);
            if (!titleRow) return;

            const parentRow = titleRow.parentElement || titleRow.closest('div');
            if (!parentRow) return;

            const stage = leadData.stage;
            const color = STAGE_COLORS[stage] || '#6b7280';
            const label = stage.charAt(0).toUpperCase() + stage.slice(1);

            const badge = document.createElement('span');
            badge.className = 'rvcrm-stage-badge';
            badge.dataset.stage = stage;
            badge.style.cssText = `
                display:inline-flex;align-items:center;gap:3px;
                background:${color}18;color:${color};
                padding:1px 8px;border-radius:10px;
                font-size:10px;font-weight:700;font-family:'Segoe UI',sans-serif;
                margin-left:6px;border:1px solid ${color}30;
                letter-spacing:0.3px;white-space:nowrap;vertical-align:middle;
                line-height:16px;flex-shrink:0;
            `;
            badge.innerHTML = `<span style="width:5px;height:5px;border-radius:50%;background:${color};display:inline-block"></span>${label}`;

            parentRow.style.display = 'flex';
            parentRow.style.alignItems = 'center';
            parentRow.appendChild(badge);
        });
    }

    function removeBadge(chatItem) {
        const badge = chatItem.querySelector('.rvcrm-stage-badge');
        if (badge) badge.remove();
    }

    function getChatListItems() {
        // WhatsApp Web chat list items
        const paneContent = document.querySelector('#pane-side');
        if (!paneContent) return [];

        // Chat items are inside divs with role="listitem" or inside the scrollable container
        let items = paneContent.querySelectorAll('[role="listitem"]');
        if (items.length === 0) {
            items = paneContent.querySelectorAll('div[data-testid="cell-frame-container"]');
        }
        if (items.length === 0) {
            // New WhatsApp classes
            items = paneContent.querySelectorAll('.x1n2onr6.x1y1aw1k');
        }
        if (items.length === 0) {
            // Highly generic fallback
            items = paneContent.querySelectorAll('div[tabindex="-1"]');
        }

        return Array.from(items);
    }

    function getPhoneForChatItem(chatItem) {
        // Strategy 1: Check if the title is a phone number
        const title = getChatTitleText(chatItem);
        if (!title) return null;

        // Match phone number from the title (unsaved contacts show phone as name)
        const cleaned = title.replace(/[\s\-\(\)\+]/g, '');
        if (/^\d{10,15}$/.test(cleaned)) {
            return cleaned;
        }

        // Strategy 2: Check if this chat name matches a lead name in our cache
        // Build a reverse lookup: lead name → phone
        for (const phone in leadDataCache) {
            if (leadDataCache[phone].name && leadDataCache[phone].name.toLowerCase() === title.toLowerCase()) {
                return phone;
            }
        }

        return null;
    }

    // ====== Scan Sidebar and Lookup Leads ======

    function scanAndLookupLeads() {
        if (isLookupInProgress) return;
        isLookupInProgress = true;

        const chatItems = getChatListItems();
        const phones = [];
        const names = [];

        chatItems.forEach(item => {
            const title = getChatTitleText(item);
            if (!title) return;
            const cleaned = title.replace(/[\s\-\(\)\+]/g, '');
            
            if (/^\d{10,15}$/.test(cleaned)) {
                phones.push(cleaned);
            }
            // Also collect names to search for
            if (!/^\d{10,15}$/.test(cleaned)) {
                names.push(title);
            }
        });

        if (phones.length === 0 && names.length === 0) {
            isLookupInProgress = false;
            return;
        }

        // Send phone numbers to background for CRM lookup
        const allPhones = [...new Set(phones)];
        
        if (allPhones.length === 0) {
            isLookupInProgress = false;
            // Still inject filter bar even if no phones found
            injectFilterBar();
            return;
        }

        chrome.runtime.sendMessage({
            action: 'lookupLeads',
            phones: allPhones
        }, function(response) {
            isLookupInProgress = false;

            if (response && response.success) {
                // Update cache
                leadDataCache = response.leads || {};

                // Update stages if provided
                if (response.stages && response.stages.length > 0) {
                    STAGE_LIST = response.stages;
                }
                if (response.stageColors) {
                    STAGE_COLORS = { ...STAGE_COLORS, ...response.stageColors };
                }

                console.log('[RV CRM] Lead lookup complete:', Object.keys(leadDataCache).length, 'leads found');

                // Inject UI
                injectFilterBar();
                injectStageBadges();
                applyStageFilter();
            } else {
                console.log('[RV CRM] Lead lookup failed:', response?.error || 'Unknown error');
                // Still show filter bar
                injectFilterBar();
            }
        });
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
            #rvcrm-filter-bar::-webkit-scrollbar {
                display: none;
            }
            .rvcrm-stage-badge {
                animation: rvcrm-fadeIn 0.3s ease;
            }
        `;
        document.head.appendChild(style);
    }

    // ====== Initialize ======

    injectStyles();
    setInterval(injectButton, 2000);

    // Scan for leads every 15 seconds (first scan after 3s to let WhatsApp load)
    setTimeout(scanAndLookupLeads, 3000);
    setInterval(scanAndLookupLeads, 15000);

    // Re-inject badges when DOM changes (chat list scroll etc)
    setInterval(() => {
        if (Object.keys(leadDataCache).length > 0) {
            injectStageBadges();
            // Re-inject filter bar if it was removed by WhatsApp re-render
            if (!document.getElementById('rvcrm-filter-bar')) {
                injectFilterBar();
            }
        }
    }, 3000);

    console.log('[RV CRM] Extension loaded! Use Alt+X, Ctrl+Shift+L, or the Header Button.');
    console.log('[RV CRM] Lead status badges & stage filter enabled.');

})();
