<template>
    <v-app class="app-container">
        <!-- Navigation Drawer -->
        <RescuerMenu v-model="drawer" />

        <!-- Notification Panel -->
        <v-navigation-drawer
            v-model="showNotificationPanel"
            location="right"
            temporary
            :width="notificationDrawerWidth"
            class="notification-drawer"
            :scrim="true"
            touchless
            disable-resize-watcher
        >
            <v-list-item class="pa-4 bg-primary">
                <template v-slot:prepend>
                    <v-icon color="white">mdi-bell</v-icon>
                </template>
                <v-list-item-title class="text-white font-weight-bold">
                    Notifications
                </v-list-item-title>
                <v-list-item-subtitle class="text-white">
                    {{ counts.pending }} pending rescue{{ counts.pending !== 1 ? 's' : '' }}
                </v-list-item-subtitle>
            </v-list-item>

            <v-divider />

            <div v-if="pendingRequests.length === 0" class="text-center py-8">
                <v-icon size="48" color="grey">mdi-bell-off-outline</v-icon>
                <p class="text-grey mt-2">No new notifications</p>
            </div>

            <v-list v-else lines="three">
                <v-list-item
                    v-for="request in pendingRequests"
                    :key="request.id"
                    @click="handleNotificationClick(request)"
                    class="notification-item"
                >
                    <template v-slot:prepend>
                        <v-avatar :color="getUrgencyColor(request.urgency_level)" size="40">
                            <v-icon color="white">mdi-alert</v-icon>
                        </v-avatar>
                    </template>
                    <v-list-item-title class="font-weight-bold">
                        {{ request.firstName || 'Someone' }} needs help!
                    </v-list-item-title>
                    <v-list-item-subtitle>
                        <v-icon size="12">mdi-map-marker</v-icon>
                        {{ getLocationDisplay(request) }}
                    </v-list-item-subtitle>
                    <v-list-item-subtitle>
                        <v-icon size="12">mdi-clock</v-icon>
                        {{ formatTimeAgo(request.created_at) }}
                    </v-list-item-subtitle>
                    <template v-slot:append>
                        <v-chip
                            :color="getUrgencyColor(request.urgency_level)"
                            size="x-small"
                            variant="flat"
                        >
                            {{ request.urgency_level || 'Unknown' }}
                        </v-chip>
                    </template>
                </v-list-item>
            </v-list>

            <template v-slot:append>
                <div class="pa-4">
                    <v-btn
                        block
                        color="primary"
                        variant="tonal"
                        @click="showNotificationPanel = false; selectedTab = 'pending'"
                    >
                        View All Requests
                    </v-btn>
                </div>
            </template>
        </v-navigation-drawer>

        <v-main class="main-container">
            <!-- Header -->
            <div class="dashboard-header">
                <div class="header-content">
                    <v-btn icon variant="text" @click="drawer = !drawer" class="menu-btn desktop-only">
                        <v-icon>mdi-menu</v-icon>
                    </v-btn>
                    <div class="header-title">
                        <h1>PinPointMe</h1>
                        <p>COMING YOUR WAY.</p>
                    </div>
                    <v-btn icon variant="text" @click="refreshData" class="refresh-btn">
                        <v-icon size="22">mdi-refresh</v-icon>
                    </v-btn>
                </div>
            </div>
            
            <div class="header-section">

                <!-- Tab Pills -->
                <div class="tab-pills-container">
                    <button 
                        :class="['tab-pill', selectedTab === 'pending' ? 'tab-pill-pending active' : '']"
                        @click="selectedTab = 'pending'"
                    >
                        Need Help ({{ counts.pending }})
                    </button>
                    <button 
                        :class="['tab-pill', selectedTab === 'inProgress' ? 'tab-pill-progress active' : '']"
                        @click="selectedTab = 'inProgress'"
                    >
                        In progress ({{ counts.inProgress }})
                    </button>
                    <button 
                        :class="['tab-pill', selectedTab === 'rescued' ? 'tab-pill-rescued active' : '']"
                        @click="selectedTab = 'rescued'"
                    >
                        Rescued ({{ counts.completed }})
                    </button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Loading State -->
                <div v-if="loading" class="d-flex justify-center py-8">
                    <v-progress-circular indeterminate color="primary" size="48" />
                </div>

                <!-- Request Lists -->
                <div v-else class="request-list">
                    <!-- Pending/Need Help Tab -->
                    <div v-if="selectedTab === 'pending'">
                        <div v-if="pendingRequests.length === 0" class="empty-state">
                            <v-icon size="64" color="grey-lighten-1">mdi-inbox-outline</v-icon>
                            <p class="text-grey mt-2">No pending rescue requests</p>
                        </div>
                        <div
                            v-for="request in pendingRequests"
                            :key="request.id"
                            class="request-card"
                            @click="viewPendingRequest(request)"
                        >
                            <div class="request-card-content">
                                <div class="request-info">
                                    <h3 class="request-name">
                                        {{ request.firstName || 'User Name' }} {{ request.lastName || '' }}
                                        <span v-if="request.description" class="injury-type">({{ getInjuryType(request.description) }})</span>
                                    </h3>
                                    <p class="request-location">
                                        Scanned at {{ getLocationDisplay(request) }} on ...
                                    </p>
                                </div>
                                <div class="request-status">
                                    <div :class="['timer-badge', getTimerColorClass(request.urgency_level)]">
                                        {{ formatElapsedTime(request.created_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- In Progress Tab -->
                    <div v-if="selectedTab === 'inProgress'">
                        <div v-if="inProgressRequests.length === 0" class="empty-state">
                            <v-icon size="64" color="grey-lighten-1">mdi-progress-clock</v-icon>
                            <p class="text-grey mt-2">No rescues in progress</p>
                        </div>
                        <div
                            v-for="request in inProgressRequests"
                            :key="request.id"
                            class="request-card"
                            @click="viewInProgressRequest(request)"
                        >
                            <div class="request-card-content">
                                <div class="request-info">
                                    <h3 class="request-name">
                                        {{ request.firstName || 'User Name' }} {{ request.lastName || '' }}
                                    </h3>
                                    <p class="request-location">
                                        Scanned at {{ getLocationDisplay(request) }} on ...
                                    </p>
                                </div>
                                <div class="request-status">
                                    <div class="status-icon progress">
                                        <v-icon color="white" size="20">mdi-check</v-icon>
                                    </div>
                                </div>
                            </div>
                            <div class="request-actions" @click.stop>
                                <v-btn
                                    color="grey-darken-1"
                                    variant="text"
                                    size="small"
                                    @click.stop="openChat(request)"
                                >
                                    <v-icon start size="18">mdi-message</v-icon>
                                    Message
                                </v-btn>
                                <v-btn
                                    color="success"
                                    variant="flat"
                                    size="small"
                                    rounded="pill"
                                    @click.stop="markAsSafe(request)"
                                    :loading="updatingId === request.id"
                                >
                                    Mark Safe
                                </v-btn>
                            </div>
                        </div>
                    </div>

                    <!-- Rescued Tab -->
                    <div v-if="selectedTab === 'rescued'">
                        <div v-if="rescuedRequests.length === 0" class="empty-state">
                            <v-icon size="64" color="grey-lighten-1">mdi-check-circle-outline</v-icon>
                            <p class="text-grey mt-2">No rescued users yet</p>
                        </div>
                        <div
                            v-for="request in rescuedRequests"
                            :key="request.id"
                            class="request-card rescued"
                            @click="viewRescuedRequest(request)"
                        >
                            <div class="request-card-content">
                                <div class="request-info">
                                    <h3 class="request-name">
                                        {{ request.firstName || 'User Name' }} {{ request.lastName || '' }}
                                    </h3>
                                    <p class="request-location">
                                        Scanned at {{ getLocationDisplay(request) }} on ...
                                    </p>
                                </div>
                                <div class="request-status">
                                    <div class="status-icon rescued">
                                        <v-icon color="white" size="20">mdi-check</v-icon>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Navigation (Mobile/Tablet only) -->
            <RescuerBottomNav 
                :notification-count="counts.pending"
                :message-count="unreadMessageCount"
                @open-notifications="showNotificationPanel = true"
            />

            <!-- Toast -->
            <v-snackbar v-model="showToast" :color="toastColor" location="top">
                {{ toastMessage }}
                <template v-slot:actions>
                    <v-btn variant="text" @click="showToast = false">Close</v-btn>
                </template>
            </v-snackbar>
            
            <!-- Popup Notification Alert -->
            <NotificationPopup
                :show="popupAlert.show"
                :title="popupAlert.title"
                :message="popupAlert.message"
                :type="popupAlert.type"
                :icon="popupAlert.icon"
                @close="popupAlert.show = false"
                @click="handlePopupClick"
            />
        </v-main>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { apiFetch, getProfilePictureUrl, getUnreadMessageCount } from '@/Composables/useApi';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import RescuerMenu from '@/Components/Pages/Rescuer/Menu/RescuerMenu.vue';
import RescuerBottomNav from '@/Components/Pages/Rescuer/Menu/RescuerBottomNav.vue';
import NotificationPopup from '@/Components/NotificationPopup.vue';

// Get Inertia page for auth
const page = usePage();
const authUser = computed(() => page.props?.auth?.user);

// Notification Alert System
const { playNotificationSound, vibrate } = useNotificationAlert();

// Popup alert state
const popupAlert = ref({
    show: false,
    title: '',
    message: '',
    type: 'info',
    icon: 'mdi-bell',
    callback: null,
});

// Polling
let pollingInterval = null;
const POLLING_INTERVAL = 8000; // 8 seconds for rescuer - faster updates
const previousPendingCount = ref(0);
const previousPendingIds = ref([]);

// State
const drawer = ref(false);
const loading = ref(true);
const selectedTab = ref('pending');
const updatingId = ref(null);
const rescueRequests = ref([]);
const rescuerName = ref('Rescuer');
const rescuerId = ref('');
const userData = ref(null);
const showNotificationPanel = ref(false);
const unreadMessageCount = ref(0);

// Computed property for user's profile picture
const userProfilePicture = computed(() => {
    const picturePath = userData.value?.profile_picture || userData.value?.avatar;
    if (!picturePath) return null;
    if (picturePath.startsWith('http') || picturePath.startsWith('data:')) return picturePath;
    return getProfilePictureUrl(picturePath);
});

// Responsive notification drawer width
const notificationDrawerWidth = computed(() => {
    if (typeof window !== 'undefined') {
        return window.innerWidth < 400 ? window.innerWidth * 0.85 : 300;
    }
    return 300;
});

// Counts
const counts = computed(() => ({
    total: rescueRequests.value.length,
    pending: pendingRequests.value.length,
    inProgress: inProgressRequests.value.length,
    completed: rescuedRequests.value.length,
}));

// Filtered requests
const pendingRequests = computed(() =>
    rescueRequests.value.filter((r) => r.status === 'pending')
);
const inProgressRequests = computed(() =>
    rescueRequests.value.filter((r) => r.status === 'assigned' || r.status === 'in_progress')
);
const rescuedRequests = computed(() =>
    rescueRequests.value.filter((r) => r.status === 'rescued' || r.status === 'safe' || r.status === 'completed')
);

// Check if rescuer has an active assignment (assigned or in_progress)
const hasActiveAssignment = computed(() => {
    return rescueRequests.value.some((r) => 
        (r.status === 'assigned' || r.status === 'in_progress') &&
        (String(r.assigned_rescuer) === String(rescuerId.value) || String(r.rescuer_id) === String(rescuerId.value))
    );
});

// Toast
const showToast = ref(false);
const toastMessage = ref('');
const toastColor = ref('success');

onMounted(async () => {
    // Prevent zoom and ensure proper viewport
    const viewport = document.querySelector('meta[name="viewport"]');
    if (viewport) {
        viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover');
    } else {
        const meta = document.createElement('meta');
        meta.name = 'viewport';
        meta.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover';
        document.head.appendChild(meta);
    }
    
    // Prevent pull-to-refresh and overscroll
    document.body.style.overscrollBehavior = 'none';
    document.documentElement.style.overscrollBehavior = 'none';
    
    // Check for tab query parameter (used when returning from chat)
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam && ['pending', 'inProgress', 'rescued'].includes(tabParam)) {
        selectedTab.value = tabParam;
    }
    
    loadUserData();
    await fetchRescueRequests();
    await fetchUnreadMessageCount();
    
    // Store initial pending state
    previousPendingCount.value = pendingRequests.value.length;
    previousPendingIds.value = pendingRequests.value.map(r => r.id);
    
    // Start polling
    startPolling();
});

onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
});

// Start polling for new rescue requests
const startPolling = () => {
    if (pollingInterval) clearInterval(pollingInterval);
    
    pollingInterval = setInterval(async () => {
        await pollForNewRequests();
    }, POLLING_INTERVAL);
};

// Poll for new requests
const pollForNewRequests = async () => {
    try {
        const endpoint = rescuerId.value 
            ? `/api/rescue-requests/rescuer/${rescuerId.value}`
            : '/api/rescue-requests';
        
        const response = await apiFetch(endpoint, { method: 'GET' });
        const data = response?.data || response;
        rescueRequests.value = Array.isArray(data) ? data : [];
        
        // Also refresh unread message count during polling
        await fetchUnreadMessageCount();
        
        // Check for new pending requests
        const currentPending = pendingRequests.value;
        const currentPendingIds = currentPending.map(r => r.id);
        
        // Find new requests (IDs that weren't in the previous list)
        const newRequests = currentPending.filter(r => !previousPendingIds.value.includes(r.id));
        
        if (newRequests.length > 0) {
            // Trigger notification for new emergency request
            triggerNewRequestNotification(newRequests[0], newRequests.length);
        }
        
        // Update previous state
        previousPendingCount.value = currentPending.length;
        previousPendingIds.value = currentPendingIds;
        
    } catch (error) {
        console.error('Polling error:', error);
    }
};

// Trigger notification for new rescue request
const triggerNewRequestNotification = (request, totalNew) => {
    const urgencyText = request.urgency_level || 'Unknown';
    const location = getLocationDisplay(request);
    const name = request.firstName || 'Someone';
    
    showPopupNotification(
        `🚨 NEW EMERGENCY${totalNew > 1 ? ` (${totalNew})` : ''}`,
        `${name} needs help at ${location}! Urgency: ${urgencyText}`,
        'error',
        'mdi-alert-circle',
        () => {
            showNotificationPanel.value = false;
            selectedTab.value = 'pending';
        }
    );
};

// Show popup notification with sound and vibration
const showPopupNotification = (title, message, type = 'info', icon = 'mdi-bell', callback = null) => {
    popupAlert.value = {
        show: true,
        title,
        message,
        type,
        icon,
        callback,
    };
    
    // Emergency sound and vibration for new requests
    playNotificationSound('emergency');
    vibrate([300, 100, 300, 100, 300, 100, 300]); // Long urgent pattern
    
    // Auto-hide after 10 seconds for emergencies
    setTimeout(() => {
        popupAlert.value.show = false;
    }, 10000);
};

// Handle popup click
const handlePopupClick = () => {
    if (popupAlert.value.callback) {
        popupAlert.value.callback();
    }
    popupAlert.value.show = false;
};

const loadUserData = () => {
    const storedData = localStorage.getItem('userData');
    if (storedData) {
        const parsed = JSON.parse(storedData);
        userData.value = parsed;
        rescuerId.value = parsed.id || '';
        rescuerName.value = parsed.firstName
            ? `${parsed.firstName} ${parsed.lastName || ''}`.trim()
            : parsed.name || 'Rescuer';
    }
};

const fetchRescueRequests = async () => {
    loading.value = true;
    try {
        // Use rescuer feed if we have rescuer ID, otherwise get all requests
        const endpoint = rescuerId.value 
            ? `/api/rescue-requests/rescuer/${rescuerId.value}`
            : '/api/rescue-requests';
        
        const response = await apiFetch(endpoint, { method: 'GET' });
        const data = response?.data || response;
        rescueRequests.value = Array.isArray(data) ? data : [];
    } catch (error) {
        console.error('Failed to fetch rescue requests:', error);
        showNotification('Failed to load rescue requests', 'error');
        rescueRequests.value = [];
    } finally {
        loading.value = false;
    }
};

// Fetch unread message count
const fetchUnreadMessageCount = async () => {
    if (!rescuerId.value) {
        console.warn('fetchUnreadMessageCount: No rescuerId available');
        return;
    }
    try {
        console.log('Fetching unread message count for rescuer:', rescuerId.value);
        unreadMessageCount.value = await getUnreadMessageCount(rescuerId.value);
        console.log('Updated unreadMessageCount:', unreadMessageCount.value);
    } catch (error) {
        console.error('Failed to fetch unread message count:', error);
    }
};

const refreshData = async () => {
    await fetchRescueRequests();
    await fetchUnreadMessageCount();
    showNotification('Data refreshed', 'success');
};

const acceptRescue = async (request) => {
    // Check if rescuer already has an active assignment
    if (hasActiveAssignment.value) {
        showNotification('You are only allowed to accept requests one at a time.', 'warning');
        return;
    }
    
    updatingId.value = request.id;
    try {
        const response = await apiFetch(`/api/rescue-requests/${request.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                status: 'assigned',
                assigned_rescuer: rescuerId.value,
            }),
        });
        showNotification('Rescue accepted!', 'success');
        await fetchRescueRequests();
        router.visit(`/rescuer/active/${request.id}`);
    } catch (error) {
        console.error('Failed to accept rescue:', error);
        showNotification('Failed to accept rescue', 'error');
    } finally {
        updatingId.value = null;
    }
};

const markAsSafe = async (request) => {
    updatingId.value = request.id;
    try {
        const response = await apiFetch(`/api/rescue-requests/${request.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ status: 'safe' }),
        });
        showNotification('Marked as safe!', 'success');
        await fetchRescueRequests();
    } catch (error) {
        console.error('Failed to mark as safe:', error);
        showNotification('Failed to update status', 'error');
    } finally {
        updatingId.value = null;
    }
};

const viewPendingRequest = (request) => {
    // Navigate to active rescue page to view details
    router.visit(`/rescuer/active/${request.id}`);
};

const viewInProgressRequest = (request) => {
    // Navigate to active rescue page to manage in-progress rescue
    router.visit(`/rescuer/active/${request.id}`);
};

const viewRescuedRequest = (request) => {
    // Navigate to active rescue page to view completed rescue
    router.visit(`/rescuer/active/${request.id}`);
};

const openChat = (request) => {
    // Always use rescue-chat route which handles conversation creation properly
    router.visit(`/rescuer/rescue-chat/${request.id}?from=dashboard-inprogress`);
};

const getInitials = (name) => {
    if (!name) return 'R';
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const getLocationDisplay = (request) => {
    const parts = [];
    if (request.building?.name) parts.push(request.building.name);
    if (request.floor?.floor_name) parts.push(request.floor.floor_name);
    if (request.room?.room_name) parts.push(request.room.room_name);
    return parts.length > 0 ? parts.join(', ') : 'Unknown Location';
};

const getUrgencyColor = (level) => {
    switch (level?.toLowerCase()) {
        case 'critical':
            return 'error';
        case 'high':
            return 'warning';
        case 'medium':
            return 'info';
        default:
            return 'grey';
    }
};

const getTimerColorClass = (level) => {
    switch (level?.toLowerCase()) {
        case 'critical':
            return 'timer-critical';
        case 'high':
            return 'timer-high';
        case 'medium':
            return 'timer-medium';
        default:
            return 'timer-low';
    }
};

const getInjuryType = (description) => {
    if (!description) return '';
    // Extract first word or keyword from description
    const keywords = ['Seizure', 'Sprains', 'Fracture', 'Dislocation', 'Bleeding', 'Burns', 'Injury', 'Trapped'];
    for (const keyword of keywords) {
        if (description.toLowerCase().includes(keyword.toLowerCase())) {
            return keyword;
        }
    }
    // Return first 15 chars of description
    return description.length > 15 ? description.substring(0, 15) + '...' : description;
};

const formatElapsedTime = (timestamp) => {
    if (!timestamp) return '0:00';
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffSecs = Math.floor((diffMs % 60000) / 1000);
    return `${diffMins}:${diffSecs.toString().padStart(2, '0')}`;
};

const formatTime = (timestamp) => {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    return date.toLocaleString('en-PH', { timeZone: 'Asia/Manila' });
};

const formatTimeAgo = (timestamp) => {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
};

const handleNotificationClick = (request) => {
    showNotificationPanel.value = false;
    // Go to pending tab and highlight this request
    selectedTab.value = 'pending';
    // Navigate to accept/view the rescue
    router.visit(`/rescuer/active/${request.id}`);
};

const showNotification = (message, color = 'info') => {
    toastMessage.value = message;
    toastColor.value = color;
    showToast.value = true;
};
</script>

<style scoped>
/* App Container - Prevent all unwanted scrolling/dragging */
.app-container {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    overflow: hidden !important;
    touch-action: none;
}

.app-container :deep(.v-application__wrap) {
    min-height: 100vh !important;
    max-height: 100vh !important;
    overflow: hidden !important;
}

/* Main Container */
.main-container {
    background: linear-gradient(180deg, #e8f5f3 0%, #f5f9f8 50%, #ffffff 100%);
    height: 100vh !important;
    max-height: 100vh !important;
    overflow: hidden !important;
    display: flex;
    flex-direction: column;
}

/* Dashboard Header - matches Notifications header */
.dashboard-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #3674B5;
    padding: env(safe-area-inset-top, 0) 0 0 0;
    flex-shrink: 0;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    gap: 12px;
}

.menu-btn, .refresh-btn {
    color: white;
}

.header-title {
    flex: 1;
    text-align: center;
}

.header-title h1 {
    font-size: 1.25rem;
    font-weight: 700;
    font-style: italic;
    color: white;
    margin: 0;
}

.header-title p {
    font-size: 0.65rem;
    letter-spacing: 2px;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    text-transform: uppercase;
}

/* Header Section (tabs area) */
.header-section {
    padding-bottom: 8px;
    flex-shrink: 0;
}

/* Tab Pills */
.tab-pills-container {
    display: flex;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    flex-wrap: wrap;
    flex-shrink: 0;
}

.tab-pill {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #e0e0e0;
    color: #666;
}

.tab-pill:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.tab-pill.active {
    color: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.tab-pill-pending.active {
    background: #26a69a;
}

.tab-pill-progress.active {
    background: #42a5f5;
}

.tab-pill-rescued.active {
    background: #66bb6a;
}

/* Content Area */
.content-area {
    padding: 0 16px;
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
    touch-action: pan-y;
    overscroll-behavior: contain;
    /* padding-bottom is handled by media queries for responsive bottom nav */
}

.request-list {
    padding-bottom: 20px;
}

/* Request Cards */
.request-card {
    background: white;
    border-radius: 12px;
    margin-bottom: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
}

.request-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.request-card:active {
    transform: translateY(0);
}

.request-card-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
}

.request-info {
    flex: 1;
    min-width: 0;
}

.request-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 4px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.injury-type {
    font-weight: 400;
    color: #666;
}

.request-location {
    font-size: 0.75rem;
    color: #7f8c8d;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.request-status {
    margin-left: 12px;
    flex-shrink: 0;
}

/* Timer Badge */
.timer-badge {
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: white;
    min-width: 50px;
    text-align: center;
}

.timer-critical {
    background: #e53935;
}

.timer-high {
    background: #ffb300;
}

.timer-medium {
    background: #fb8c00;
}

.timer-low {
    background: #66bb6a;
}

/* Status Icons */
.status-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.status-icon.progress {
    background: #42a5f5;
}

.status-icon.rescued {
    background: #66bb6a;
}

/* Request Actions */
.request-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
    padding: 0 16px 16px;
}

/* Rescued Card Styling */
.request-card.rescued {
    border-left-color: #66bb6a;
    background: linear-gradient(90deg, #f1f8f4 0%, white 20%);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 48px 24px;
}

/* Desktop-only elements (hidden on mobile/tablet) */
.desktop-only {
    display: flex;
}

/* Mobile-only elements (hidden on desktop) */
.mobile-only {
    display: none;
}

/* Responsive visibility */
@media (max-width: 1024px) {
    /* On mobile/tablet: hide desktop menu button, content doesn't need bottom padding on desktop */
    .desktop-only {
        display: none;
    }
    
    .mobile-only {
        display: flex;
    }
    
    /* Add padding for bottom nav on mobile/tablet */
    .content-area {
        padding-bottom: 80px;
    }
}

@media (min-width: 1025px) {
    /* On desktop: no bottom padding needed since no bottom nav */
    .content-area {
        padding-bottom: 20px;
    }
}

/* Notification Panel Styles */
.notification-drawer {
    z-index: 2000 !important;
}

.notification-drawer :deep(.v-navigation-drawer__content) {
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
}

.notification-item {
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    cursor: pointer;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: rgba(var(--v-theme-primary), 0.08);
}

/* Mobile Responsive */
@media (max-width: 600px) {
    .brand-title {
        font-size: 1.25rem;
    }
    
    .tab-pill {
        padding: 6px 10px;
        font-size: 0.65rem;
    }
    
    .tab-pills-container {
        gap: 4px;
        padding: 0 8px 12px;
    }
    
    .request-card-content {
        padding: 12px;
    }
    
    .request-name {
        font-size: 0.85rem;
    }
    
    .request-location {
        font-size: 0.7rem;
    }
    
    .timer-badge {
        padding: 6px 10px;
        font-size: 0.75rem;
    }
    
    .header-section {
        padding-bottom: 8px;
    }
    
    .section-title {
        padding: 8px 16px;
    }
}

@media (max-width: 360px) {
    .tab-pill {
        padding: 5px 8px;
        font-size: 0.6rem;
    }
}

/* Scrollbar Styling */
.content-area::-webkit-scrollbar {
    width: 4px;
}

.content-area::-webkit-scrollbar-track {
    background: transparent;
}

.content-area::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.2);
    border-radius: 4px;
}

/* Prevent text selection everywhere except inputs */
* {
    -webkit-user-select: none;
    user-select: none;
}

input, textarea {
    -webkit-user-select: auto;
    user-select: auto;
}
</style>
