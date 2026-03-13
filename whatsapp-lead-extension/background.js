/**
 * RV CRM - Background Service Worker
 * Handles cross-origin requests to the CRM server
 */

const CRM_BASE_URL = 'https://crm.rvallsolutions.com';

chrome.runtime.onMessage.addListener(function(message, sender, sendResponse) {
    if (message.action === 'getCSRFToken') {
        fetchCSRFToken().then(token => {
            sendResponse({ success: true, token: token });
        }).catch(err => {
            sendResponse({ success: false, error: err.message });
        });
        return true; // Keep message channel open for async response
    }

    if (message.action === 'saveLead') {
        saveLead(message.data).then(result => {
            sendResponse(result);
        }).catch(err => {
            sendResponse({ success: false, error: err.message });
        });
        return true; // Keep message channel open for async response
    }
});

async function fetchCSRFToken() {
    const response = await fetch(CRM_BASE_URL + '/admin/leads', {
        method: 'GET',
        credentials: 'include',
        headers: { 'Accept': 'text/html' }
    });
    
    if (!response.ok) {
        throw new Error('CRM pe login nahi hai. Pehle CRM login karo: ' + CRM_BASE_URL);
    }
    
    const html = await response.text();
    
    // Try to find CSRF token
    let match = html.match(/name="_token"\s+(?:value|content)="([^"]+)"/);
    if (match) return match[1];
    
    match = html.match(/meta\s+name="csrf-token"\s+content="([^"]+)"/);
    if (match) return match[1];
    
    throw new Error('CSRF token nahi mila. CRM me login check karo!');
}

async function saveLead(data) {
    // Get CSRF token first
    const csrfToken = await fetchCSRFToken();
    
    // Build form data
    const formData = new URLSearchParams();
    formData.append('_token', csrfToken);
    formData.append('name', data.name);
    formData.append('phone', data.phone);
    formData.append('source', 'whatsapp');
    formData.append('stage', 'new');
    if (data.email) formData.append('email', data.email);
    if (data.city) formData.append('city', data.city);
    if (data.state) formData.append('state', data.state);
    if (data.notes) formData.append('notes', data.notes);
    
    const response = await fetch(CRM_BASE_URL + '/admin/leads', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html, application/json'
        },
        body: formData.toString()
    });
    
    if (response.ok || response.redirected) {
        return { success: true };
    } else {
        const text = await response.text();
        throw new Error('Lead save failed (Status: ' + response.status + ')');
    }
}
