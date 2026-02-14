<template>
    <v-app class="bg-user-gradient-light">
        <!-- App Bar -->
        <UserAppBar 
            title="Alerts" 
            :subtitle="activeRescueCount > 0 ? activeRescueCount + ' active rescue' + (activeRescueCount !== 1 ? 's' : '') : 'Stay informed about your rescue status'"
            :notification-count="activeRescueCount"
            @toggle-drawer="drawer = !drawer"
        >
            <template #actions>
                <v-btn icon variant="text" class="bar-btn" style="color: white;" :loading="refreshing" @click="refreshNotifications">
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
            </template>
        </UserAppBar>

        <!-- Navigation Drawer -->
        <UserMenu v-model="drawer" />

        <!-- Main Content -->
        <v-main class="notifications-main">

            <!-- Loading State -->
            <div v-if="loading" class="loading-container">
                <v-progress-circular indeterminate color="primary" size="48" />
                <p>Loading your alerts...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="notifications.length === 0" class="empty-state">
                <div class="empty-icon-wrapper">
                    <v-icon size="64" color="grey-lighten-1">mdi-bell-off-outline</v-icon>
                </div>
                <h3>No alerts yet</h3>
                <p>When you request help, your rescue status updates will appear here.</p>
                <v-btn 
                    variant="tonal" 
                    color="primary" 
                    class="mt-4 rounded-xl"
                    @click="refreshNotifications"
                >
                    <v-icon start>mdi-refresh</v-icon>
                    Refresh
                </v-btn>
            </div>

            <!-- Notifications List -->
            <div v-else class="notifications-list">
                <TransitionGroup name="notification">
                    <div 
                        v-for="notification in notifications" 
                        :key="notification.id"
                        :class="['notification-card', getStatusClass(notification.status)]"
                        @click="handleNotificationClick(notification)"
                    >
                        <!-- Status Indicator -->
                        <div :class="['status-indicator', getStatusIndicatorClass(notification.status)]"></div>
                        
                        <!-- Card Content -->
                        <div class="card-content">
                            <div class="card-header">
                                <div class="avatar-section">
                                    <v-avatar 
                                        :color="getStatusColor(notification.status)" 
                                        size="48"
                                        class="notification-avatar"
                                    >
                                        <v-icon color="white" size="24">
                                            {{ getStatusIcon(notification.status) }}
                                        </v-icon>
                                    </v-avatar>
                                    <div v-if="isActiveStatus(notification.status)" class="pulse-dot"></div>
                                </div>
                                
                                <div class="card-info">
                                    <h3 class="notification-title">
                                        {{ getNotificationTitle(notification) }}
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
                                        :color="getStatusChipColor(notification.status)"
                                        size="small"
                                        variant="flat"
                                        class="status-chip"
                                    >
                                        {{ formatStatus(notification.status) }}
                                    </v-chip>
                                    <v-chip
                                        v-if="notification.urgency_level"
                                        :color="getUrgencyColor(notification.urgency_level)"
                                        size="x-small"
                                        variant="tonal"
                                        class="urgency-chip"
                                    >
                                        {{ notification.urgency_level }}
                                    </v-chip>
                                </div>
                            </div>
                            
                            <!-- Rescuer Info (if assigned) -->
                            <div v-if="notification.rescuer" class="rescuer-info">
                                <v-avatar size="24" color="success">
                                    <v-img v-if="getRescuerPicture(notification.rescuer)" :src="getRescuerPicture(notification.rescuer)" cover />
                                    <span v-else class="text-caption text-white">{{ getRescuerInitials(notification.rescuer) }}</span>
                                </v-avatar>
                                <span class="rescuer-name">{{ getRescuerFullName(notification.rescuer) }}</span>
                                <v-chip v-if="notification.status === 'en_route'" color="info" size="x-small" variant="tonal">
                                    On the way
                                </v-chip>
                            </div>
                            
                            <!-- Action hint -->
                            <div class="card-action-hint">
                                <span>Tap to view details</span>
                                <v-icon size="16" color="grey">mdi-chevron-right</v-icon>
                            </div>
                        </div>
                    </div>
                </TransitionGroup>
            </div>
        </v-main>

        <!-- Bottom Navigation -->
        <UserBottomNav :notification-count="activeRescueCount" :message-count="unreadMessageCount" />

        <!-- Toast Notification -->
        <v-snackbar v-model="showToast" :color="toastColor" location="top" timeout="3000">
            {{ toastMessage }}
            <template v-slot:actions>
                <v-btn variant="text" @click="showToast = false">Close</v-btn>
            </template>
        </v-snackbar>

        <!-- Notification Popup -->
        <NotificationPopup
            :show="popupAlert.show"
            :title="popupAlert.title"
            :message="popupAlert.message"
            :type="popupAlert.type"
            :icon="popupAlert.icon"
            @close="popupAlert.show = false"
            @click="popupAlert.show = false"
        />
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { apiFetch, getProfilePictureUrl } from '@/Composables/useApi';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import { useUnreadMessages } from '@/Composables/useUnreadMessages';
import UserMenu from '@/Components/Pages/User/Menu/UserMenu.vue';
import UserAppBar from '@/Components/Pages/User/Menu/UserAppBar.vue';
import UserBottomNav from '@/Components/Pages/User/Menu/UserBottomNav.vue';
import NotificationPopup from '@/Components/NotificationPopup.vue';

// Auth
const page = usePage();
const authUser = computed(() => page.props?.auth?.user);

// Notification sound
const { playNotificationSound, vibrate, notify } = useNotificationAlert();

// State
const drawer = ref(false);
const loading = ref(true);
const refreshing = ref(false);
const notifications = ref([]);

// Unread messages count from composable with new message callback
const { unreadCount: unreadMessageCount, onNewMessages } = useUnreadMessages();

// Cleanup function for new message callback
let unregisterNewMessages = null;

// Toast
const showToast = ref(false);
const toastMessage = ref('');
const toastColor = ref('success');

// Notification Banner
const notificationBanner = ref({
    show: false,
    title: '',
    message: '',
    type: 'info',
    icon: 'mdi-information',
});

// Popup Alert
const popupAlert = ref({
    show: false,
    title: '',
    message: '',
    type: 'info',
    icon: 'mdi-information',
});

// Polling
let pollingInterval = null;
const POLLING_INTERVAL = 5000;
const previousStatuses = ref({});

// Computed
const activeRescueCount = computed(() => 
    notifications.value.filter(n => isActiveStatus(n.status)).length
);

// Methods
const fetchNotifications = async (silent = false) => {
    try {
        const userId = authUser.value?.id;
        if (!userId) return;
        
        const response = await apiFetch(`/api/rescue-requests/user/${userId}`);
        const data = response?.data || response;
        
        if (data && Array.isArray(data)) {
            // Check for status changes
            data.forEach(item => {
                const prevStatus = previousStatuses.value[item.id];
                if (prevStatus && prevStatus !== item.status) {
                    triggerStatusNotification(prevStatus, item.status, item);
                }
                previousStatuses.value[item.id] = item.status;
            });
            
            // Sort by created_at descending (newest first)
            notifications.value = data.sort((a, b) => 
                new Date(b.created_at) - new Date(a.created_at)
            );
        }
    } catch (error) {
        console.error('Error fetching notifications:', error);
        if (!silent) {
            showToastMessage('Failed to load notifications', 'error');
        }
    } finally {
        loading.value = false;
        refreshing.value = false;
    }
};

const refreshNotifications = async () => {
    refreshing.value = true;
    await fetchNotifications();
    showToastMessage('Alerts refreshed', 'success');
};

const handleNotificationClick = (notification) => {
    // Navigate to HelpComing page with the rescue code
    if (notification.rescue_code) {
        localStorage.setItem('lastRescueCode', notification.rescue_code);
        localStorage.setItem('lastRescueRequestId', notification.id);
        router.visit(`/user/help-coming/${notification.rescue_code}`);
    } else {
        router.visit(`/user/help-coming`);
    }
};

const triggerStatusNotification = (oldStatus, newStatus, notification) => {
    let notificationData = null;

    switch (newStatus) {
        case 'assigned':
        case 'accepted':
            notificationData = {
                title: '🚨 Rescuer Assigned!',
                message: 'A rescuer has been assigned to help you. Help is on the way!',
                type: 'success',
                icon: 'mdi-account-check',
                sound: 'notification',
                vibratePattern: 'urgent'
            };
            break;
        case 'en_route':
        case 'in_progress':
            notificationData = {
                title: '🏃 Rescuer En Route!',
                message: 'The rescuer is now on their way to your location.',
                type: 'info',
                icon: 'mdi-run-fast',
                sound: 'notification',
                vibratePattern: 'standard'
            };
            break;
        case 'on_scene':
            notificationData = {
                title: '📍 Rescuer Arrived!',
                message: 'The rescuer has arrived at your location.',
                type: 'success',
                icon: 'mdi-map-marker-check',
                sound: 'success',
                vibratePattern: 'urgent'
            };
            break;
        case 'rescued':
        case 'safe':
        case 'completed':
            notificationData = {
                title: '✅ Rescue Complete!',
                message: 'You have been marked as safe. Stay safe!',
                type: 'success',
                icon: 'mdi-check-circle',
                sound: 'success',
                vibratePattern: 'success'
            };
            break;
    }

    if (notificationData) {
        // Play sound and vibrate
        playNotificationSound(notificationData.sound);
        vibrate(notificationData.vibratePattern);

        // Show popup alert (single notification only)
        popupAlert.value = {
            show: true,
            title: notificationData.title,
            message: notificationData.message,
            type: notificationData.type,
            icon: notificationData.icon,
        };

        // Browser notification
        notify({
            title: notificationData.title,
            body: notificationData.message,
            icon: '/images/logo.png'
        });

        // Auto-hide after 5 seconds
        setTimeout(() => {
            popupAlert.value.show = false;
        }, 5000);
    }
};

const showToastMessage = (message, color = 'success') => {
    toastMessage.value = message;
    toastColor.value = color;
    showToast.value = true;
};

// Handle new message notifications
const handleNewMessages = (newCount, totalCount) => {
    // Show popup
    popupAlert.value = {
        show: true,
        title: '💬 New Message',
        message: `You have ${newCount} new message${newCount > 1 ? 's' : ''}`,
        type: 'info',
        icon: 'mdi-message-text',
    };
    
    // Play sound and vibrate
    playNotificationSound('message');
    vibrate([100, 50, 100]);
    
    // Browser notification
    notify({
        title: '💬 New Message',
        body: `You have ${newCount} new message${newCount > 1 ? 's' : ''}`,
        icon: '/images/logo.png'
    });
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        popupAlert.value.show = false;
    }, 5000);
};

// Helpers
const isActiveStatus = (status) => {
    return ['pending', 'assigned', 'en_route', 'in_progress', 'on_scene'].includes(status);
};

const getNotificationTitle = (notification) => {
    const statusTitles = {
        'pending': 'Help Request Pending',
        'assigned': 'Rescuer Assigned',
        'en_route': 'Help is On the Way',
        'in_progress': 'Rescue In Progress',
        'on_scene': 'Rescuer Has Arrived',
        'rescued': 'Rescue Completed',
        'safe': 'Marked as Safe',
        'completed': 'Rescue Completed',
        'cancelled': 'Request Cancelled',
    };
    return statusTitles[notification.status] || 'Rescue Request';
};

const getLocationDisplay = (request) => {
    const roomName = request.room?.room_name || request.room_name;
    const floorName = request.floor?.floor_name || request.floor_name;
    const buildingName = request.building?.name || request.building_name;
    
    if (roomName && buildingName) {
        return `${roomName}, ${buildingName}`;
    }
    if (buildingName) {
        return buildingName;
    }
    return request.location || 'Unknown Location';
};

const formatTimeAgo = (dateString) => {
    if (!dateString) return 'Unknown';
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    return `${Math.floor(seconds / 86400)}d ago`;
};

const truncateText = (text, maxLength) => {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
};

const getStatusColor = (status) => {
    const colors = {
        'pending': '#FF9800',
        'assigned': '#2196F3',
        'en_route': '#3674B5',
        'in_progress': '#3674B5',
        'on_scene': '#4CAF50',
        'rescued': '#4CAF50',
        'safe': '#4CAF50',
        'completed': '#4CAF50',
        'cancelled': '#9E9E9E',
    };
    return colors[status] || '#78909c';
};

const getStatusChipColor = (status) => {
    const colors = {
        'pending': 'warning',
        'assigned': 'info',
        'en_route': 'primary',
        'in_progress': 'primary',
        'on_scene': 'success',
        'rescued': 'success',
        'safe': 'success',
        'completed': 'success',
        'cancelled': 'grey',
    };
    return colors[status] || 'grey';
};

const getStatusIcon = (status) => {
    const icons = {
        'pending': 'mdi-clock-outline',
        'assigned': 'mdi-account-check',
        'en_route': 'mdi-run-fast',
        'in_progress': 'mdi-progress-clock',
        'on_scene': 'mdi-map-marker-check',
        'rescued': 'mdi-check-circle',
        'safe': 'mdi-shield-check',
        'completed': 'mdi-check-all',
        'cancelled': 'mdi-close-circle',
    };
    return icons[status] || 'mdi-bell';
};

const getStatusClass = (status) => {
    return `status-${status?.replace('_', '-') || 'unknown'}`;
};

const getStatusIndicatorClass = (status) => {
    if (['pending'].includes(status)) return 'indicator-warning';
    if (['assigned', 'en_route', 'in_progress'].includes(status)) return 'indicator-primary';
    if (['on_scene', 'rescued', 'safe', 'completed'].includes(status)) return 'indicator-success';
    return 'indicator-grey';
};

const formatStatus = (status) => {
    const labels = {
        'pending': 'Pending',
        'assigned': 'Assigned',
        'en_route': 'En Route',
        'in_progress': 'In Progress',
        'on_scene': 'Arrived',
        'rescued': 'Rescued',
        'safe': 'Safe',
        'completed': 'Completed',
        'cancelled': 'Cancelled',
    };
    return labels[status] || status;
};

const getUrgencyColor = (urgency) => {
    const colors = {
        'critical': 'error',
        'high': 'orange',
        'medium': 'warning',
        'low': 'success',
    };
    return colors[urgency?.toLowerCase()] || 'grey';
};

const getRescuerPicture = (rescuer) => {
    const picturePath = rescuer?.profile_picture || rescuer?.avatar;
    if (!picturePath) return null;
    if (picturePath.startsWith('http') || picturePath.startsWith('data:')) return picturePath;
    return getProfilePictureUrl(picturePath);
};

const getRescuerInitials = (rescuer) => {
    const first = rescuer?.first_name?.[0] || '';
    const last = rescuer?.last_name?.[0] || '';
    return (first + last).toUpperCase() || 'R';
};

const getRescuerFullName = (rescuer) => {
    return `${rescuer?.first_name || ''} ${rescuer?.last_name || ''}`.trim() || 'Rescuer';
};

// Lifecycle
onMounted(async () => {
    await fetchNotifications();
    // unreadMessageCount is now managed by useUnreadMessages composable
    
    // Register new message callback
    unregisterNewMessages = onNewMessages(handleNewMessages);
    
    // Initialize previous statuses
    notifications.value.forEach(n => {
        previousStatuses.value[n.id] = n.status;
    });
    
    // Start polling for notifications only (messages handled by composable)
    pollingInterval = setInterval(async () => {
        await fetchNotifications(true);
    }, POLLING_INTERVAL);
});

onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    if (unregisterNewMessages) {
        unregisterNewMessages();
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

.menu-btn, .refresh-btn {
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
    padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 120px);
    min-height: 100vh;
}

/* Notification Banner */
.notification-banner {
    position: sticky;
    top: 60px;
    z-index: 90;
    animation: slideDown 0.3s ease-out;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

@keyframes slideDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
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
    color: #78909c;
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
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
}

.empty-state h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #263238;
    margin: 0 0 8px 0;
}

.empty-state p {
    font-size: 0.9rem;
    color: #78909c;
    margin: 0;
    max-width: 280px;
}

/* Notifications List */
.notifications-list {
    padding: 16px;
    padding-bottom: 32px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* Notification Card */
.notification-card {
    position: relative;
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.notification-card:active {
    transform: scale(0.98);
}

.notification-card:hover {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

/* Status Indicator */
.status-indicator {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
}

.status-indicator.indicator-warning {
    background: linear-gradient(180deg, #FF9800, #FFC107);
}

.status-indicator.indicator-primary {
    background: linear-gradient(180deg, #3674B5, #2196F3);
}

.status-indicator.indicator-success {
    background: linear-gradient(180deg, #4CAF50, #8BC34A);
}

.status-indicator.indicator-grey {
    background: #9E9E9E;
}

/* Card Content */
.card-content {
    padding: 16px;
    padding-left: 20px;
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
    color: #263238;
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
    color: #78909c;
}

.meta-item.time {
    color: #90a4ae;
}

.card-badges {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
    flex-shrink: 0;
}

.status-chip {
    font-size: 0.7rem !important;
    font-weight: 600;
}

.urgency-chip {
    font-size: 0.6rem !important;
    text-transform: uppercase;
}

/* Rescuer Info */
.rescuer-info {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 10px;
}

.rescuer-name {
    flex: 1;
    font-size: 0.8rem;
    font-weight: 500;
    color: #333;
}

/* Card Description */
.card-description {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.card-description p {
    font-size: 0.8rem;
    color: #546e7a;
    margin: 0;
    line-height: 1.5;
}

/* Action Hint */
.card-action-hint {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.card-action-hint span {
    font-size: 0.7rem;
    color: #9e9e9e;
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
    .notifications-main {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 130px);
    }
    
    .notifications-list {
        padding-bottom: 40px;
    }
}

@media (min-width: 1024px) {
    .notifications-main {
        margin-left: 0;
        padding-bottom: 40px;
        width: 100%;
    }
    
    .notifications-list {
        max-width: 800px;
        margin: 0 auto;
        padding-left: clamp(24px, 5vw, 80px);
        padding-right: clamp(24px, 5vw, 80px);
    }
}

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
}
</style>
