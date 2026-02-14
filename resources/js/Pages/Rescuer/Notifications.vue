<template>
    <v-app class="bg-user-gradient-light">
        <!-- Header -->
        <div class="notifications-header">
            <div class="header-content">
                <v-btn icon variant="text" @click="drawer = !drawer" class="menu-btn desktop-only">
                    <v-icon>mdi-menu</v-icon>
                </v-btn>
                <div class="header-title">
                    <h1>Notifications</h1>
                    <p v-if="pendingCount > 0">{{ pendingCount }} pending rescue{{ pendingCount !== 1 ? 's' : '' }}</p>
                </div>
                <v-btn 
                    icon 
                    variant="text" 
                    class="refresh-btn"
                    :loading="refreshing"
                    @click="refreshNotifications"
                >
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
            </div>
        </div>

        <!-- Navigation Drawer (Desktop only) -->
        <RescuerMenu v-model="drawer" />

        <!-- Main Content -->
        <v-main class="notifications-main">
            <!-- Restriction Warning -->
            <v-alert
                v-if="isRescuerRestricted"
                type="warning"
                variant="tonal"
                prominent
                border="start"
                class="ma-4"
            >
                <template v-slot:prepend>
                    <v-icon size="28">mdi-lock</v-icon>
                </template>
                <div class="text-subtitle-2 font-weight-bold mb-1">Access Restricted</div>
                <div class="text-caption">
                    Your status is set to <strong>{{ rescuerStatus }}</strong> by an administrator. 
                    You cannot access notifications until your status is changed.
                </div>
            </v-alert>

            <!-- Loading State -->
            <div v-if="loading && !isRescuerRestricted" class="loading-container">
                <v-progress-circular indeterminate color="primary" size="48" />
                <p>Loading notifications...</p>
            </div>

            <!-- Restriction Empty State -->
            <div v-else-if="isRescuerRestricted && !loading" class="empty-state restriction-state">
                <div class="empty-icon-wrapper">
                    <v-icon size="80" color="grey-lighten-2">mdi-bell-lock</v-icon>
                </div>
                <h3>Notifications Unavailable</h3>
                <p>Contact an administrator to change your status to access notifications.</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredNotifications.length === 0 && !isRescuerRestricted" class="empty-state">
                <div class="empty-icon-wrapper">
                    <v-icon size="64" color="grey-lighten-1">mdi-bell-off-outline</v-icon>
                </div>
                <h3>No notifications</h3>
                <p>You're all caught up! New rescue requests will appear here.</p>
                <v-btn 
                    variant="tonal" 
                    color="primary" 
                    class="mt-4"
                    @click="refreshNotifications"
                >
                    <v-icon start>mdi-refresh</v-icon>
                    Refresh
                </v-btn>
            </div>

            <!-- Notifications List (only if not restricted) -->
            <div v-else-if="!isRescuerRestricted" class="notifications-list">
                <TransitionGroup name="notification">
                    <div 
                        v-for="notification in filteredNotifications" 
                        :key="notification.id"
                        :class="['notification-card', getStatusClass(notification.status)]"
                        @click="handleNotificationClick(notification)"
                    >
                        <!-- Urgency Indicator -->
                        <div :class="['urgency-indicator', getUrgencyClass(notification.urgency_level)]"></div>
                        
                        <!-- Card Content -->
                        <div class="card-content">
                            <div class="card-header">
                                <div class="avatar-section">
                                    <v-avatar 
                                        :color="getUrgencyColor(notification.urgency_level)" 
                                        size="48"
                                        class="notification-avatar"
                                    >
                                        <v-icon color="white" size="24">
                                            {{ getStatusIcon(notification.status) }}
                                        </v-icon>
                                    </v-avatar>
                                    <div v-if="notification.status === 'pending'" class="pulse-dot"></div>
                                </div>
                                
                                <div class="card-info">
                                    <h3 class="notification-title">
                                        {{ notification.firstName || 'Someone' }} needs help!
                                    </h3>
                                    <div class="notification-meta">
                                        <span class="meta-item">
                                            <v-icon size="14">mdi-map-marker</v-icon>
                                            {{ getLocationDisplay(notification) }}
                                        </span>
                                        <span class="meta-item time">
                                            <v-icon size="14">mdi-clock-outline</v-icon>
                                            {{ formatTimeAgo(notification.created_at) }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="card-badges">
                                    <v-chip
                                        :color="getUrgencyColor(notification.urgency_level)"
                                        size="small"
                                        variant="flat"
                                        class="urgency-chip"
                                    >
                                        {{ notification.urgency_level || 'Unknown' }}
                                    </v-chip>
                                    <v-chip
                                        :color="getStatusColor(notification.status)"
                                        size="x-small"
                                        variant="tonal"
                                        class="status-chip"
                                    >
                                        {{ formatStatus(notification.status) }}
                                    </v-chip>
                                </div>
                            </div>
                            
                            <!-- Additional Info -->
                            <div v-if="notification.description" class="card-description">
                                <p>{{ truncateText(notification.description, 100) }}</p>
                            </div>
                            
                            <!-- Quick Actions -->
                           
                        </div>
                    </div>
                </TransitionGroup>
            </div>
        </v-main>

        <!-- Bottom Navigation -->
        <RescuerBottomNav :notification-count="pendingCount" :message-count="unreadMessageCount" />

        <!-- Toast Notification -->
        <v-snackbar v-model="showToast" :color="toastColor" location="top" timeout="3000">
            {{ toastMessage }}
            <template v-slot:actions>
                <v-btn variant="text" @click="showToast = false">Close</v-btn>
            </template>
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { apiFetch } from '@/Composables/useApi';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import { useUnreadMessages } from '@/Composables/useUnreadMessages';
import RescuerMenu from '@/Components/Pages/Rescuer/Menu/RescuerMenu.vue';
import RescuerBottomNav from '@/Components/Pages/Rescuer/Menu/RescuerBottomNav.vue';

// Auth
const page = usePage();
const authUser = computed(() => page.props?.auth?.user);

// Notification sound
const { playNotificationSound, vibrate } = useNotificationAlert();

// State
const drawer = ref(false);
const loading = ref(true);
const refreshing = ref(false);
const rescueRequests = ref([]);
const processingId = ref(null);
const { unreadCount: unreadMessageCount, onNewMessages } = useUnreadMessages();
const rescuerStatus = ref('available');

// Check if rescuer is restricted
const isRescuerRestricted = computed(() => {
    const status = rescuerStatus.value?.toLowerCase();
    return status === 'off_duty' || status === 'unavailable';
});

// Toast
const showToast = ref(false);
const toastMessage = ref('');
const toastColor = ref('success');

// Polling
let pollingInterval = null;
const POLLING_INTERVAL = 8000;
const previousPendingCount = ref(0);

// Computed
const pendingRequests = computed(() => 
    rescueRequests.value.filter(r => r.status === 'pending')
);

const inProgressRequests = computed(() => 
    rescueRequests.value.filter(r => r.status === 'assigned' || r.status === 'in_progress')
);

const rescuedRequests = computed(() => 
    rescueRequests.value.filter(r => r.status === 'rescued' || r.status === 'safe' || r.status === 'completed')
);

// Check if rescuer has an active assignment (assigned or in_progress)
const hasActiveAssignment = computed(() => {
    const rescuerId = authUser.value?.id;
    return rescueRequests.value.some((r) => 
        (r.status === 'assigned' || r.status === 'in_progress') &&
        (String(r.assigned_rescuer) === String(rescuerId) || String(r.rescuer_id) === String(rescuerId))
    );
});

const allNotifications = computed(() => rescueRequests.value);

const pendingCount = computed(() => pendingRequests.value.length);

const filteredNotifications = computed(() => {
    // Show all notifications (pending and in-progress primarily)
    return allNotifications.value;
});

// Methods
const goBack = () => {
    router.visit('/rescuer/dashboard');
};

const fetchNotifications = async () => {
    try {
        const rescuerId = authUser.value?.id;
        if (!rescuerId) return;
        
        // Update rescuer status from localStorage
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        rescuerStatus.value = userData.status || 'available';
        
        // Don't fetch notifications if rescuer is restricted
        if (isRescuerRestricted.value) {
            console.log('[Notifications] Rescuer is restricted, skipping notification fetch');
            rescueRequests.value = [];
            loading.value = false;
            refreshing.value = false;
            return;
        }
        
        const response = await apiFetch(`/api/rescue-requests/rescuer/${rescuerId}`);
        // Handle wrapped response { success: true, data: [...] }
        const data = response?.data || response;
        if (data && Array.isArray(data)) {
            rescueRequests.value = data;
            
            // Check for new notifications
            const newPendingCount = pendingRequests.value.length;
            if (newPendingCount > previousPendingCount.value && previousPendingCount.value > 0) {
                playNotificationSound('emergency');
                vibrate([300, 100, 300, 100, 300]);
            }
            previousPendingCount.value = newPendingCount;
        }
    } catch (error) {
        console.error('Error fetching notifications:', error);
    } finally {
        loading.value = false;
        refreshing.value = false;
    }
};

const refreshNotifications = async () => {
    refreshing.value = true;
    await fetchNotifications();
    showToastMessage('Notifications refreshed', 'success');
};

const handleNotificationClick = (notification) => {
    // Prevent viewing notification if rescuer is restricted
    if (isRescuerRestricted.value) {
        showToastMessage('You cannot access rescue requests while your status is restricted', 'warning');
        return;
    }
    
    viewRescue(notification);
};

const viewRescue = (notification) => {
    // Always go to ActiveRescue page which handles all statuses
    router.visit(`/rescuer/active/${notification.id}`);
};

const acceptRescue = async (notification) => {
    // Check if rescuer already has an active assignment
    if (hasActiveAssignment.value) {
        showToastMessage('You are only allowed to accept requests one at a time.', 'warning');
        return;
    }
    
    processingId.value = notification.id;
    try {
        const rescuerId = authUser.value?.id;
        const response = await apiFetch(`/api/rescue-requests/${notification.id}`, {
            method: 'PUT',
            body: JSON.stringify({
                status: 'assigned',
                assigned_rescuer: rescuerId,
            }),
        });
        
        if (response) {
            showToastMessage('Rescue accepted! Redirecting...', 'success');
            setTimeout(() => {
                router.visit(`/rescuer/active/${notification.id}`);
            }, 500);
        }
    } catch (error) {
        console.error('Error accepting rescue:', error);
        showToastMessage('Failed to accept rescue. Please try again.', 'error');
    } finally {
        processingId.value = null;
    }
};

const showToastMessage = (message, color = 'success') => {
    toastMessage.value = message;
    toastColor.value = color;
    showToast.value = true;
};

// Helpers
const getLocationDisplay = (request) => {
    // Handle nested objects from API response
    const roomName = request.room?.room_name || request.room_name;
    const floorName = request.floor?.floor_name || request.floor_name;
    const buildingName = request.building?.name || request.building_name;
    
    if (roomName) {
        return `${roomName}, ${floorName || 'Floor'}, ${buildingName || 'Building'}`;
    }
    return request.location || 'Unknown Location';
};

const formatTimeAgo = (dateString) => {
    if (!dateString) return 'Unknown';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) {
        return `0:${String(diffMins).padStart(2, '0')}`;
    }
    if (diffHours < 24) {
        const mins = diffMins % 60;
        return `${diffHours}:${String(mins).padStart(2, '0')}`;
    }
    return `${diffDays}d ago`;
};

const truncateText = (text, maxLength) => {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
};

const getUrgencyColor = (urgency) => {
    const colors = {
        'critical': '#d32f2f',
        'high': '#f57c00',
        'medium': '#fbc02d',
        'low': '#388e3c',
    };
    return colors[urgency?.toLowerCase()] || '#78909c';
};

const getUrgencyClass = (urgency) => {
    return `urgency-${urgency?.toLowerCase() || 'unknown'}`;
};

const getStatusColor = (status) => {
    const colors = {
        'pending': 'warning',
        'assigned': 'info',
        'in_progress': 'primary',
        'rescued': 'success',
        'safe': 'success',
        'completed': 'success',
    };
    return colors[status] || 'grey';
};

const getStatusIcon = (status) => {
    const icons = {
        'pending': 'mdi-alert',
        'assigned': 'mdi-account-check',
        'in_progress': 'mdi-run',
        'rescued': 'mdi-check-circle',
        'safe': 'mdi-shield-check',
        'completed': 'mdi-check-all',
    };
    return icons[status] || 'mdi-bell';
};

const getStatusClass = (status) => {
    return `status-${status?.replace('_', '-') || 'unknown'}`;
};

const formatStatus = (status) => {
    const labels = {
        'pending': 'Pending',
        'assigned': 'Assigned',
        'in_progress': 'In Progress',
        'rescued': 'Rescued',
        'safe': 'Safe',
        'completed': 'Completed',
    };
    return labels[status] || status;
};

// Lifecycle
onMounted(async () => {
    // Initialize rescuer status from localStorage
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    rescuerStatus.value = userData.status || 'available';
    
    await fetchNotifications();
    
    // Register new message notification callback (only if not restricted)
    if (!isRescuerRestricted.value) {
        onNewMessages((newCount) => {
            playNotificationSound('message');
            vibrate([100, 50, 100]);
        });
    }
    
    // Start polling for rescue notifications
    pollingInterval = setInterval(async () => {
        await fetchNotifications();
    }, POLLING_INTERVAL);
});

onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
</script>

<style scoped>
/* Header */
.notifications-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #3674B5;
    padding: env(safe-area-inset-top, 0) 0 0 0;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    gap: 12px;
}

.menu-btn, .back-btn, .refresh-btn {
    color: white;
}

/* Desktop only visibility */
.desktop-only {
    display: flex;
}

@media (max-width: 1024px) {
    .desktop-only {
        display: none !important;
    }
}

.header-title {
    flex: 1;
    text-align: center;
}

.header-title h1 {
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
    margin: 0;
}

.header-title p {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
}

/* Main Content */
.notifications-main {
    padding-top: 16px;
    padding-bottom: calc(140px + env(safe-area-inset-bottom, 0px));
    min-height: 100vh;
}

/* Loading State */
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 64px 24px;
    gap: 16px;
}

.loading-container p {
    color: var(--ppm-text-muted, #78909c);
    font-size: 0.9rem;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 64px 24px;
    text-align: center;
}

.empty-icon-wrapper {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: var(--ppm-glass-bg, rgba(255, 255, 255, 0.8));
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
}

.empty-state h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--ppm-text-primary, #263238);
    margin: 0 0 8px 0;
}

.empty-state p {
    font-size: 0.9rem;
    color: var(--ppm-text-muted, #78909c);
    margin: 0;
    max-width: 280px;
}

/* Notifications List */
.notifications-list {
    padding: 0 16px 32px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* Notification Card - Enhanced Design */
.notification-card {
    position: relative;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 6px 24px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.notification-card:active {
    transform: translateY(-2px);
}

.notification-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    border-color: rgba(54, 116, 181, 0.3);
}

/* Status-based card styling */
.notification-card.status-pending {
    border-left: 4px solid #ff9800;
}

.notification-card.status-assigned,
.notification-card.status-in-progress {
    border-left: 4px solid #1976D2;
}

.notification-card.status-rescued,
.notification-card.status-safe,
.notification-card.status-completed {
    border-left: 4px solid #4caf50;
    opacity: 0.85;
}

/* Urgency Indicator */
.urgency-indicator {
    position: absolute;
    top: 0;
    right: 0;
    width: 8px;
    height: 8px;
    border-radius: 0 16px 0 8px;
}

.urgency-indicator.urgency-critical {
    background: #d32f2f;
    width: 12px;
    height: 12px;
    animation: pulse-urgent 1.5s infinite;
}

.urgency-indicator.urgency-high {
    background: #f57c00;
}

.urgency-indicator.urgency-medium {
    background: #fbc02d;
}

.urgency-indicator.urgency-low {
    background: #388e3c;
}

@keyframes pulse-urgent {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Card Content */
.card-content {
    padding: 20px;
}

.card-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.avatar-section {
    position: relative;
    flex-shrink: 0;
}

.notification-avatar {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.pulse-dot {
    position: absolute;
    top: -2px;
    right: -2px;
    width: 14px;
    height: 14px;
    background: #f44336;
    border-radius: 50%;
    border: 2px solid white;
    animation: pulse-dot 1.5s infinite;
}

@keyframes pulse-dot {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

.card-info {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--ppm-text-primary, #263238);
    margin: 0 0 6px 0;
    line-height: 1.3;
}

.notification-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: var(--ppm-text-muted, #78909c);
}

.meta-item.time {
    color: var(--ppm-text-muted, #90a4ae);
}

.card-badges {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
    flex-shrink: 0;
}

.urgency-chip {
    font-size: 0.65rem !important;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-chip {
    font-size: 0.6rem !important;
}

/* Card Description */
.card-description {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.card-description p {
    font-size: 0.8rem;
    color: var(--ppm-text-secondary, #546e7a);
    margin: 0;
    line-height: 1.5;
}

/* Card Actions */
.card-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.action-btn {
    font-size: 0.75rem !important;
    font-weight: 600;
    text-transform: none;
    border-radius: 12px;
}

.view-btn {
    color: var(--ppm-text-muted, #78909c);
}

/* Transition Animations */
.notification-enter-active,
.notification-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.notification-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.notification-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

.notification-move {
    transition: transform 0.4s ease;
}

/* Responsive */
@media (max-width: 600px) {
    .card-header {
        flex-wrap: wrap;
    }
    
    .card-badges {
        flex-direction: row;
        width: 100%;
        margin-top: 8px;
        justify-content: flex-start;
    }
    
    .notifications-main {
        padding-bottom: calc(140px + env(safe-area-inset-bottom, 0px));
    }
}

@media (max-width: 360px) {
    .notification-title {
        font-size: 0.85rem;
    }
    
    .meta-item {
        font-size: 0.65rem;
    }
    
    .header-title h1 {
        font-size: 1.1rem;
    }
    
    .header-title p {
        font-size: 0.65rem;
    }
    
    .notifications-main {
        padding-bottom: calc(140px + env(safe-area-inset-bottom, 0px));
    }
}
</style>
