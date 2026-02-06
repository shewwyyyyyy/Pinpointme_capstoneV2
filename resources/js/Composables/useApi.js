import { ref } from 'vue';

// API Base URL configuration - use empty string for same-origin requests
// This avoids CORS issues between localhost and 127.0.0.1
const API_BASE = import.meta.env.VITE_API_BASE_URL || '';

/**
 * Generic API fetch function
 */
export async function apiFetch(path, options = {}) {
    const url = `${API_BASE}${path.startsWith('/') ? path : '/' + path}`;
    const headers = {
        ...(options.headers || {}),
        'Accept': 'application/json',
    };

    // Attach JSON header when body is plain object
    if (options.body && !(options.body instanceof FormData)) {
        headers['Content-Type'] = headers['Content-Type'] || 'application/json';
    }

    // Optional auth token from localStorage
    const token = localStorage.getItem('authToken') || localStorage.getItem('token');
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    // Add CSRF token for API routes if available
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken && (options.method === 'POST' || options.method === 'PUT' || options.method === 'PATCH' || options.method === 'DELETE')) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }

    const resp = await fetch(url, { credentials: 'include', ...options, headers });

    if (!resp.ok) {
        let text;
        let jsonData = null;
        try {
            text = await resp.text();
            // Try to parse as JSON to get structured error data
            try {
                jsonData = JSON.parse(text);
            } catch {
                // Not JSON, use text
            }
        } catch {
            /* ignore */
        }
        let message = `HTTP ${resp.status}`;
        if (text) message += `: ${text.slice(0, 300)}`;
        const error = new Error(message);
        error.status = resp.status;
        error.data = jsonData;
        throw error;
    }

    const ct = resp.headers.get('content-type') || '';
    if (ct.includes('application/json')) return resp.json();
    return resp.text();
}

/**
 * Buildings API
 */
export async function getBuildingsFullStructure() {
    try {
        const data = await apiFetch('/api/buildings', { method: 'GET' });
        let buildings = Array.isArray(data) ? data : (data?.data || data?.buildings || []);

        buildings = buildings.map((b) => ({
            id: String(b.id),
            name: b.name || b.building_name || b.title || 'Building',
            floors: (b.floors || []).map((f) => ({
                id: String(f.id),
                floor_name: f.floor_name || f.name || f.label || `Floor ${f.id}`,
                floor_plan_url: f.floor_plan_url || null,
                rooms: (f.rooms || []).map((r) => ({
                    id: String(r.id),
                    room_name: r.room_name || r.name || r.label || `Room ${r.id}`,
                    ...r,
                })),
                ...f,
            })),
            ...b,
        }));
        return buildings;
    } catch (e) {
        console.error('getBuildingsFullStructure error', e);
        throw e;
    }
}

/**
 * Rescue Request API
 */
export async function createRescueRequest(payload, isFormData = false) {
    let options = { method: 'POST' };
    
    if (isFormData) {
        // For FormData (file uploads), don't set Content-Type - browser will set it with boundary
        options.body = payload;
        options.headers = {}; // Remove default JSON content-type
    } else {
        options.body = JSON.stringify(payload);
    }
    
    const data = await apiFetch('/api/rescue-requests', options, isFormData);
    const rescueCode = data.rescue_code || data.rescueCode || data.code || '';
    const requestId = String(data.id || data.requestId || data.rescue_request_id || '');
    return { rescueCode, requestId, raw: data };
}

export async function getRescueRequestById(id) {
    return apiFetch(`/api/rescue-requests/${id}`, { method: 'GET' });
}

export async function getRescueRequestByCode(code) {
    return apiFetch(`/api/rescue-requests/code/${code}`, { method: 'GET' });
}

export async function updateRescueStatus(code, status) {
    return apiFetch(`/api/rescue-requests/code/${code}/status`, {
        method: 'PATCH',
        body: JSON.stringify({ status }),
    });
}

export async function markRescueSafe(rescueRequestId) {
    return apiFetch(`/api/rescue-requests/${rescueRequestId}/mark-safe`, { method: 'POST' });
}

export async function getUserActiveRescueRequest(userId) {
    return apiFetch(`/api/users/${userId}/active-rescue`, { method: 'GET' });
}

export async function getUserRescueHistory(userId) {
    return apiFetch(`/api/users/${userId}/rescue-history`, { method: 'GET' });
}

// ── Admin force-alert & pending-too-long APIs ──────────────────
export async function triggerForceAlert(rescueRequestId) {
    return apiFetch(`/api/rescue-requests/${rescueRequestId}/force-alert`, { method: 'POST' });
}

export async function getPendingTooLong() {
    return apiFetch('/api/rescue-requests/pending-too-long', { method: 'GET' });
}

// ── Get all rescue requests (for admin polling) ────────────────
export async function getAllRescueRequests() {
    return apiFetch('/api/rescue-requests', { method: 'GET' });
}

// ── Get rescuer feed ───────────────────────────────────────────
export async function getRescuerFeed(rescuerId) {
    return apiFetch(`/api/rescue-requests/rescuer/${rescuerId}`, { method: 'GET' });
}

// ── Admin: get ALL conversations (read-only overview) ──────────
export async function getAdminConversations() {
    return apiFetch('/api/conversations/admin', { method: 'GET' });
}

export async function getLocationDetails(buildingId, floorId, roomId) {
    // Make this request without auth token since it's a public endpoint
    const url = `${API_BASE}/api/location-details/${buildingId}/${floorId}/${roomId}`;
    const response = await fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        credentials: 'include'
    });
    
    if (!response.ok) {
        const text = await response.text();
        throw new Error(`HTTP ${response.status}: ${text.slice(0, 300)}`);
    }
    
    return response.json();
}

/**
 * OpenAI / AI Assisted Endpoints
 */
export async function transcribeAudio(audioBlob) {
    const form = new FormData();
    form.append('file', audioBlob, 'audio.webm');
    form.append('audio', audioBlob, 'audio.webm');
    const data = await apiFetch('/openai/transcribe', { method: 'POST', body: form });
    if (typeof data === 'string') return data;
    return data.text || data.transcript || data.transcription || '';
}

export async function extractEmergencyFields(transcript) {
    const data = await apiFetch('/openai/extract', {
        method: 'POST',
        body: JSON.stringify({ transcript }),
    });
    return data?.fields ? data.fields : data;
}

export async function extractFieldsAndInferLocation(transcript) {
    try {
        const data = await apiFetch('/openai/extract-full', {
            method: 'POST',
            body: JSON.stringify({ transcript }),
        });
        const fields = data?.fields ? { ...data.fields } : { ...data };
        if (data?.location_inference) {
            fields.location_inference = data.location_inference;
        } else if (data?.locationInference) {
            fields.location_inference = data.locationInference;
        }
        return fields;
    } catch (e) {
        console.warn('extract-full failed, falling back to extract', e);
        return extractEmergencyFields(transcript);
    }
}

/**
 * Conversations & Messages API
 */
export async function getConversations(userId = null) {
    let url = '/api/conversations';
    if (userId) {
        url += `?user_id=${userId}`;
    }
    return apiFetch(url, { method: 'GET' });
}

/**
 * Get total unread message count for a user
 * Returns the sum of unread_count across all conversations
 */
export async function getUnreadMessageCount(userId) {
    try {
        if (!userId) {
            console.warn('getUnreadMessageCount: No userId provided');
            return 0;
        }
        const response = await getConversations(userId);
        // Handle nested data structure from API
        const conversations = Array.isArray(response) ? response : (response?.data || []);
        const total = conversations.reduce((acc, conv) => acc + (conv.unread_count || 0), 0);
        console.log('Unread message count:', total, 'from', conversations.length, 'conversations');
        return total;
    } catch (error) {
        console.error('Error getting unread message count:', error);
        return 0;
    }
}

export async function getConversation(id) {
    return apiFetch(`/api/conversations/${id}`, { method: 'GET' });
}

export async function createConversation(rescueRequestId) {
    return apiFetch('/api/conversations', {
        method: 'POST',
        body: JSON.stringify({ rescue_request_id: rescueRequestId }),
    });
}

/**
 * Get or create conversation for a rescue request
 * This is the main entry point for chat - only works when rescuer is assigned
 */
export async function getOrCreateConversation(rescueRequestId) {
    return apiFetch(`/api/conversations/rescue/${rescueRequestId}`, { method: 'GET' });
}

export async function getConversationMessages(conversationId) {
    return apiFetch(`/api/conversations/${conversationId}/messages`, { method: 'GET' });
}

export async function sendMessage(conversationId, content, senderId, attachments = null) {
    const payload = { 
        content,
        sender_id: senderId
    };
    if (attachments) {
        payload.attachment_url = attachments.url;
        payload.attachment_type = attachments.type;
        payload.attachment_name = attachments.name;
    }
    return apiFetch(`/api/conversations/${conversationId}/messages`, {
        method: 'POST',
        body: JSON.stringify(payload),
    });
}

export async function sendMessageWithFile(conversationId, content, senderId, file) {
    const formData = new FormData();
    formData.append('content', content || '');
    formData.append('sender_id', senderId);
    
    if (file) {
        // Check if it's an audio file
        if (file.type && file.type.includes('audio')) {
            formData.append('audio', file, file.name || 'audio.webm');
            formData.append('message_type', 'audio');
        } else {
            formData.append('file', file);
        }
    }
    
    const url = `${API_BASE}/api/conversations/${conversationId}/messages`;
    const token = localStorage.getItem('authToken') || localStorage.getItem('token');
    
    const headers = {
        'Accept': 'application/json',
    };
    
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    
    const resp = await fetch(url, {
        method: 'POST',
        credentials: 'include',
        headers,
        body: formData,
    });
    
    if (!resp.ok) {
        let text;
        try {
            text = await resp.text();
        } catch { /* ignore */ }
        let message = `HTTP ${resp.status}`;
        if (text) message += `: ${text.slice(0, 300)}`;
        throw new Error(message);
    }
    
    return resp.json();
}

export async function markConversationRead(conversationId, userId) {
    return apiFetch(`/api/conversations/${conversationId}/read`, { 
        method: 'POST',
        body: JSON.stringify({ user_id: userId }),
    });
}

export async function deleteConversation(conversationId) {
    return apiFetch(`/api/conversations/${conversationId}`, { method: 'DELETE' });
}

/**
 * User Authentication API
 */
export async function loginUser(email, password) {
    return apiFetch('/api/login', {
        method: 'POST',
        body: JSON.stringify({ 
            email: email,
            password: password,
            username: email // Support both email and username
        }),
    });
}

export async function getUserById(userId) {
    return apiFetch(`/api/users/${userId}`, { method: 'GET' });
}

export async function getCurrentUser() {
    return apiFetch('/api/user', { method: 'GET' });
}

export async function updateUser(userId, data) {
    return apiFetch(`/api/users/${userId}`, {
        method: 'PUT',
        body: JSON.stringify(data),
    });
}

export async function uploadProfilePicture(userId, file) {
    const formData = new FormData();
    formData.append('profile_picture', file);
    
    const url = `${API_BASE}/api/users/${userId}/profile-picture`;
    const token = localStorage.getItem('authToken') || localStorage.getItem('token');
    
    const headers = {
        'Accept': 'application/json',
    };
    
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    
    const resp = await fetch(url, {
        method: 'POST',
        credentials: 'include',
        headers,
        body: formData,
    });
    
    if (!resp.ok) {
        let text;
        try {
            text = await resp.text();
        } catch { /* ignore */ }
        let message = `HTTP ${resp.status}`;
        if (text) message += `: ${text.slice(0, 300)}`;
        throw new Error(message);
    }
    
    return resp.json();
}

export async function deleteProfilePicture(userId) {
    return apiFetch(`/api/users/${userId}/profile-picture`, {
        method: 'DELETE',
    });
}

export function getProfilePictureUrl(path) {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    return `${API_BASE}/storage/${path}`;
}

export async function registerUser(userData) {
    return apiFetch('/api/users/register', {
        method: 'POST',
        body: JSON.stringify(userData),
    });
}

export async function logoutUser() {
    return apiFetch('/api/users/logout', { method: 'POST' });
}

/**
 * Composable for loading state
 */
export function useApiLoading() {
    const isLoading = ref(false);
    const error = ref(null);

    const withLoading = async (fn) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await fn();
        } catch (e) {
            error.value = e.message || 'An error occurred';
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    return { isLoading, error, withLoading };
}

/**
 * useApi composable - provides get, post, put, delete methods
 * Used by Vue components for API calls
 */
export function useApi() {
    const loading = ref(false);
    const error = ref(null);

    const get = async (path, params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            // Build query string from params
            const queryString = Object.keys(params).length 
                ? '?' + new URLSearchParams(params).toString() 
                : '';
            const response = await apiFetch(`${path}${queryString}`, { method: 'GET' });
            return { data: response };
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const post = async (path, data = {}, options = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const isFormData = data instanceof FormData;
            const response = await apiFetch(path, {
                method: 'POST',
                body: isFormData ? data : JSON.stringify(data),
                headers: isFormData ? {} : { 'Content-Type': 'application/json' },
                ...options,
            });
            return { data: response };
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const put = async (path, data = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await apiFetch(path, {
                method: 'PUT',
                body: JSON.stringify(data),
                headers: { 'Content-Type': 'application/json' },
            });
            return { data: response };
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const patch = async (path, data = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await apiFetch(path, {
                method: 'PATCH',
                body: JSON.stringify(data),
                headers: { 'Content-Type': 'application/json' },
            });
            return { data: response };
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const del = async (path) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await apiFetch(path, { method: 'DELETE' });
            return { data: response };
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    };

    return { get, post, put, patch, del, delete: del, loading, error };
}
