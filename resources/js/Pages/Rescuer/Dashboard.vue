<template>
    <v-app>
        <v-app-bar color="primary" density="compact">
            <v-app-bar-nav-icon @click="drawer = !drawer" />
            <v-app-bar-title>Rescuer Dashboard</v-app-bar-title>
            <v-spacer />
            <v-btn icon @click="refreshData">
                <v-icon>mdi-refresh</v-icon>
            </v-btn>
            <!-- Notification Bell with Badge -->
            <v-btn icon @click="showNotificationPanel = !showNotificationPanel">
                <v-badge
                    :content="counts.pending"
                    :model-value="counts.pending > 0"
                    color="error"
                    overlap
                >
                    <v-icon>mdi-bell</v-icon>
                </v-badge>
            </v-btn>
        </v-app-bar>

        <!-- Navigation Drawer -->
        <RescuerMenu v-model="drawer" />

        <!-- Notification Panel -->
        <v-navigation-drawer
            v-model="showNotificationPanel"
            location="right"
            temporary
            width="350"
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

        <v-main class="bg-user-gradient-light">
            <v-container fluid class="pa-4">
                <!-- Tabs -->
                <v-tabs v-model="selectedTab" color="primary" class="mb-4" grow>
                    <v-tab value="pending">
                        <v-badge :content="counts.pending" color="warning" inline>
                            Trapped
                        </v-badge>
                    </v-tab>
                    <v-tab value="inProgress">
                        <v-badge :content="counts.inProgress" color="info" inline>
                            In Progress
                        </v-badge>
                    </v-tab>
                    <v-tab value="rescued">
                        <v-badge :content="counts.completed" color="success" inline>
                            Rescued
                        </v-badge>
                    </v-tab>
                </v-tabs>

                <!-- Loading State -->
                <div v-if="loading" class="d-flex justify-center py-8">
                    <v-progress-circular indeterminate color="primary" size="48" />
                </div>

                <!-- Request Lists -->
                <v-window v-else v-model="selectedTab">
                    <!-- Pending/Trapped Tab -->
                    <v-window-item value="pending">
                        <div v-if="pendingRequests.length === 0" class="text-center py-8">
                            <v-icon size="64" color="grey">mdi-inbox-outline</v-icon>
                            <p class="text-grey mt-2">No pending rescue requests</p>
                        </div>
                        <v-card
                            v-for="request in pendingRequests"
                            :key="request.id"
                            class="mb-3"
                            elevation="2"
                            rounded="lg"
                            @click="viewPendingRequest(request)"
                        >
                            <v-card-text>
                                <div class="d-flex justify-space-between align-start">
                                    <div>
                                        <v-chip color="warning" size="small" class="mb-2">
                                            {{ request.rescue_code }}
                                        </v-chip>
                                        <h3 class="text-subtitle-1 font-weight-bold">
                                            {{ request.firstName || 'Unknown' }} {{ request.lastName || '' }}
                                        </h3>
                                        <p class="text-caption text-grey mb-1">
                                            <v-icon size="14">mdi-map-marker</v-icon>
                                            {{ getLocationDisplay(request) }}
                                        </p>
                                        <p class="text-caption text-grey mb-0">
                                            <v-icon size="14">mdi-clock</v-icon>
                                            {{ formatTime(request.created_at) }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <v-chip
                                            :color="getUrgencyColor(request.urgency_level)"
                                            size="small"
                                            variant="tonal"
                                        >
                                            {{ request.urgency_level || 'Unknown' }}
                                        </v-chip>
                                    </div>
                                </div>
                                <v-divider class="my-2" />
                                <p class="text-body-2 text-truncate mb-0">
                                    {{ request.description || 'No description provided' }}
                                </p>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer />
                                <v-btn
                                    color="primary"
                                    variant="flat"
                                    size="small"
                                    @click.stop="acceptRescue(request)"
                                    :loading="updatingId === request.id"
                                >
                                    Accept Rescue
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-window-item>

                    <!-- In Progress Tab -->
                    <v-window-item value="inProgress">
                        <div v-if="inProgressRequests.length === 0" class="text-center py-8">
                            <v-icon size="64" color="grey">mdi-progress-clock</v-icon>
                            <p class="text-grey mt-2">No rescues in progress</p>
                        </div>
                        <v-card
                            v-for="request in inProgressRequests"
                            :key="request.id"
                            class="mb-3"
                            elevation="2"
                            rounded="lg"
                            @click="viewInProgressRequest(request)"
                        >
                            <v-card-text>
                                <div class="d-flex justify-space-between align-start">
                                    <div>
                                        <v-chip color="info" size="small" class="mb-2">
                                            {{ request.rescue_code }}
                                        </v-chip>
                                        <h3 class="text-subtitle-1 font-weight-bold">
                                            {{ request.firstName || 'Unknown' }} {{ request.lastName || '' }}
                                        </h3>
                                        <p class="text-caption text-grey mb-1">
                                            <v-icon size="14">mdi-map-marker</v-icon>
                                            {{ getLocationDisplay(request) }}
                                        </p>
                                    </div>
                                    <v-chip color="info" size="small" variant="tonal">
                                        In Progress
                                    </v-chip>
                                </div>
                            </v-card-text>
                            <v-card-actions>
                                <v-btn
                                    color="primary"
                                    variant="text"
                                    size="small"
                                    @click.stop="openChat(request)"
                                >
                                    <v-icon start>mdi-message</v-icon>
                                    Message
                                </v-btn>
                                <v-spacer />
                                <v-btn
                                    color="success"
                                    variant="flat"
                                    size="small"
                                    @click.stop="markAsRescued(request)"
                                    :loading="updatingId === request.id"
                                >
                                    Mark Rescued
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-window-item>

                    <!-- Rescued Tab -->
                    <v-window-item value="rescued">
                        <div v-if="rescuedRequests.length === 0" class="text-center py-8">
                            <v-icon size="64" color="grey">mdi-check-circle-outline</v-icon>
                            <p class="text-grey mt-2">No rescued users yet</p>
                        </div>
                        <v-card
                            v-for="request in rescuedRequests"
                            :key="request.id"
                            class="mb-3"
                            elevation="2"
                            rounded="lg"
                            @click="viewRescuedRequest(request)"
                        >
                            <v-card-text>
                                <div class="d-flex justify-space-between align-start">
                                    <div>
                                        <v-chip color="success" size="small" class="mb-2">
                                            {{ request.rescue_code }}
                                        </v-chip>
                                        <h3 class="text-subtitle-1 font-weight-bold">
                                            {{ request.firstName || 'Unknown' }} {{ request.lastName || '' }}
                                        </h3>
                                        <p class="text-caption text-grey mb-1">
                                            <v-icon size="14">mdi-map-marker</v-icon>
                                            {{ getLocationDisplay(request) }}
                                        </p>
                                        <p class="text-caption text-grey mb-0">
                                            <v-icon size="14">mdi-clock</v-icon>
                                            Rescued: {{ formatTime(request.updated_at) }}
                                        </p>
                                    </div>
                                    <v-chip color="success" size="small" variant="tonal">
                                        <v-icon start size="14">mdi-check</v-icon>
                                        Rescued
                                    </v-chip>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-window-item>
                </v-window>
            </v-container>

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
import { apiFetch, getProfilePictureUrl } from '@/Composables/useApi';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import RescuerMenu from '@/Components/Pages/Rescuer/Menu/RescuerMenu.vue';
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

// Computed property for user's profile picture
const userProfilePicture = computed(() => {
    const picturePath = userData.value?.profile_picture || userData.value?.avatar;
    if (!picturePath) return null;
    if (picturePath.startsWith('http') || picturePath.startsWith('data:')) return picturePath;
    return getProfilePictureUrl(picturePath);
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

// Toast
const showToast = ref(false);
const toastMessage = ref('');
const toastColor = ref('success');

onMounted(async () => {
    loadUserData();
    await fetchRescueRequests();
    
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

const refreshData = async () => {
    await fetchRescueRequests();
    showNotification('Data refreshed', 'success');
};

const acceptRescue = async (request) => {
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
        router.visit(`/rescuer/active/${request.rescue_code}`);
    } catch (error) {
        console.error('Failed to accept rescue:', error);
        showNotification('Failed to accept rescue', 'error');
    } finally {
        updatingId.value = null;
    }
};

const markAsRescued = async (request) => {
    updatingId.value = request.id;
    try {
        const response = await apiFetch(`/api/rescue-requests/${request.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ status: 'rescued' }),
        });
        showNotification('Marked as rescued!', 'success');
        await fetchRescueRequests();
    } catch (error) {
        console.error('Failed to mark as rescued:', error);
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
    router.visit(`/rescuer/chat/${request.conversation_id || 'new'}?user=${request.user_id}`);
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
/* Component-specific styles */
.notification-item {
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    cursor: pointer;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: rgba(var(--v-theme-primary), 0.08);
}
</style>
