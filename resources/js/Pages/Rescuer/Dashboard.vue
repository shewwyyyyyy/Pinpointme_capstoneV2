<template>
    <v-app class="bg-user-gradient-light">
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

            <!-- Show restriction message if rescuer is restricted -->
            <div v-if="isRescuerRestricted" class="text-center py-8 px-4">
                <v-icon size="48" color="warning">mdi-lock-alert</v-icon>
                <p class="text-grey mt-2 font-weight-bold">Access Restricted</p>
                <p class="text-grey text-caption">
                    Your status is set to {{ rescuerStatus }} by an administrator.
                </p>
            </div>

            <!-- Show empty state or pending requests only if not restricted -->
            <template v-else>
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
            </template>

            <template v-slot:append>
                <div class="pa-4" v-if="!isRescuerRestricted">
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

                <!-- Tab Pills - Enhanced Design -->
                <div class="tab-pills-container">
                    <button 
                        :class="['tab-pill tab-pill-enhanced', selectedTab === 'pending' ? 'tab-pill-pending active' : '']"
                        @click="selectedTab = 'pending'"
                    >
                        <v-icon size="16" class="tab-icon">mdi-alert-circle</v-icon>
                        <span class="tab-text">Need Help</span>
                        <v-chip size="x-small" color="white" variant="flat" class="tab-count">{{ counts.pending }}</v-chip>
                    </button>
                    <button 
                        :class="['tab-pill tab-pill-enhanced', selectedTab === 'inProgress' ? 'tab-pill-progress active' : '']"
                        @click="selectedTab = 'inProgress'"
                    >
                        <v-icon size="16" class="tab-icon">mdi-progress-clock</v-icon>
                        <span class="tab-text">In Progress</span>
                        <v-chip size="x-small" color="white" variant="flat" class="tab-count">{{ counts.inProgress }}</v-chip>
                    </button>
                    <button 
                        :class="['tab-pill tab-pill-enhanced', selectedTab === 'rescued' ? 'tab-pill-rescued active' : '']"
                        @click="selectedTab = 'rescued'"
                    >
                        <v-icon size="16" class="tab-icon">mdi-check-circle</v-icon>
                        <span class="tab-text">Rescued</span>
                        <v-chip size="x-small" color="white" variant="flat" class="tab-count">{{ counts.completed }}</v-chip>
                    </button>
                </div>
            </div>

            <!-- Admin Restriction Warning -->
            <v-alert
                v-if="isRescuerRestricted"
                type="warning"
                variant="tonal"
                prominent
                border="start"
                class="mx-4 mt-3 mb-2"
            >
                <template v-slot:prepend>
                    <v-icon size="28">mdi-lock</v-icon>
                </template>
                <div class="text-subtitle-2 font-weight-bold mb-1">Account Restricted</div>
                <div class="text-caption">
                    Your status is set to <strong>{{ rescuerStatus }}</strong> by an administrator. 
                    You cannot view or accept rescue requests. Please contact an admin to change your status.
                </div>
            </v-alert>

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
                        <!-- Show restriction message if rescuer is off_duty or unavailable -->
                        <div v-if="isRescuerRestricted" class="empty-state">
                            <v-icon size="64" color="warning">mdi-lock-alert</v-icon>
                            <p class="text-grey mt-2 text-h6">Access Restricted</p>
                            <p class="text-grey text-body-2 px-4">
                                Your account is currently set to <strong>{{ rescuerStatus }}</strong>.<br>
                                Contact an administrator to change your status.
                            </p>
                        </div>
                        
                        <!-- Show requests only if not restricted -->
                        <template v-else>
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
                        </template>
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
                        <!-- Search and Date Filter -->
                        <div class="rescued-filter-section">
                            <v-text-field
                                v-model="rescuedSearchQuery"
                                placeholder="Search by name..."
                                variant="outlined"
                                density="compact"
                                hide-details
                                clearable
                                prepend-inner-icon="mdi-magnify"
                                class="rescued-search-input"
                            />
                            <v-menu
                                v-model="showRescuedDatePicker"
                                :close-on-content-click="false"
                                location="bottom"
                            >
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-bind="props"
                                        variant="outlined"
                                        class="rescued-date-btn"
                                        :color="rescuedSelectedDate ? 'primary' : 'grey'"
                                    >
                                        <v-icon start size="18">mdi-calendar</v-icon>
                                        <span class="date-btn-text">{{ rescuedSelectedDate ? formatSelectedDate(rescuedSelectedDate) : 'Date' }}</span>
                                    </v-btn>
                                </template>
                                <v-date-picker
                                    v-model="rescuedSelectedDate"
                                    @update:model-value="showRescuedDatePicker = false"
                                    color="primary"
                                />
                            </v-menu>
                            <v-btn
                                v-if="rescuedSelectedDate || rescuedSearchQuery"
                                icon
                                variant="text"
                                size="small"
                                color="grey"
                                @click="clearRescuedFilters"
                            >
                                <v-icon>mdi-close</v-icon>
                            </v-btn>
                        </div>

                        <div v-if="filteredRescuedRequests.length === 0" class="empty-state">
                            <v-icon size="64" color="grey-lighten-1">mdi-check-circle-outline</v-icon>
                            <p class="text-grey mt-2">
                                {{ rescuedSearchQuery || rescuedSelectedDate ? 'No rescued persons match your filters.' : 'No rescued users yet' }}
                            </p>
                            <v-btn 
                                v-if="rescuedSearchQuery || rescuedSelectedDate"
                                variant="tonal" 
                                color="primary" 
                                class="mt-4"
                                @click="clearRescuedFilters"
                            >
                                <v-icon start>mdi-filter-off</v-icon>
                                Clear Filters
                            </v-btn>
                        </div>
                        <div
                            v-for="request in filteredRescuedRequests"
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
                                        Scanned at {{ getLocationDisplay(request) }} on {{ formatRescuedDate(request.created_at) }}
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

            <!-- Force Alert Overlay (Unstoppable until rescuer accepts) -->
            <v-overlay
                v-model="isForceAlertPlaying"
                persistent
                :close-on-back="false"
                class="force-alert-overlay"
                :scrim="true"
                scrim-opacity="0.92"
            >
                <div class="force-alert-card">
                    <!-- Animated pulse rings behind icon -->
                    <div class="fa-icon-area">
                        <div class="fa-ring fa-ring-1"></div>
                        <div class="fa-ring fa-ring-2"></div>
                        <div class="fa-ring fa-ring-3"></div>
                        <div class="fa-icon-circle">
                            <v-icon size="40" color="white" class="fa-icon-shake">mdi-alarm-light</v-icon>
                        </div>
                    </div>

                    <!-- Text content -->
                    <div class="fa-label">URGENT</div>
                    <h2 class="fa-title">Rescue Request Waiting</h2>
                    <p class="fa-subtitle">
                        Admin has triggered a force alert.<br>
                        A request has been pending too long — please accept immediately.
                    </p>

                    <!-- CTA button -->
                    <button class="fa-accept-btn" @click="goToPendingAndStopAlert">
                        <v-icon size="20" class="mr-2">mdi-check-circle</v-icon>
                        View & Accept Request
                    </button>
                </div>
            </v-overlay>
        </v-main>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { apiFetch, getProfilePictureUrl } from '@/Composables/useApi';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import { useUnreadMessages } from '@/Composables/useUnreadMessages';
import RescuerMenu from '@/Components/Pages/Rescuer/Menu/RescuerMenu.vue';
import RescuerBottomNav from '@/Components/Pages/Rescuer/Menu/RescuerBottomNav.vue';
import NotificationPopup from '@/Components/NotificationPopup.vue';

// Get Inertia page for auth
const page = usePage();
const authUser = computed(() => page.props?.auth?.user);

// Notification Alert System
const { playNotificationSound, vibrate, stopForceAlert, isForceAlertPlaying } = useNotificationAlert();

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
const { unreadCount: unreadMessageCount, onNewMessages } = useUnreadMessages();

// Rescued tab filter state
const rescuedSearchQuery = ref('');
const rescuedSelectedDate = ref(null);
const showRescuedDatePicker = ref(false);

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

// Filtered rescued requests with search and date filter
const filteredRescuedRequests = computed(() => {
    let results = rescuedRequests.value;
    
    // Apply search filter
    if (rescuedSearchQuery.value) {
        const query = rescuedSearchQuery.value.toLowerCase();
        results = results.filter(r => {
            const firstName = (r.firstName || r.first_name || '').toLowerCase();
            const lastName = (r.lastName || r.last_name || '').toLowerCase();
            const fullName = `${firstName} ${lastName}`.trim();
            return firstName.includes(query) || lastName.includes(query) || fullName.includes(query);
        });
    }
    
    // Apply date filter
    if (rescuedSelectedDate.value) {
        const filterDate = new Date(rescuedSelectedDate.value);
        results = results.filter(r => {
            const createdDate = new Date(r.created_at);
            return createdDate.toDateString() === filterDate.toDateString();
        });
    }
    
    return results;
});

// Check if rescuer has an active assignment (assigned or in_progress)
const hasActiveAssignment = computed(() => {
    return rescueRequests.value.some((r) => 
        (r.status === 'assigned' || r.status === 'in_progress') &&
        (String(r.assigned_rescuer) === String(rescuerId.value) || String(r.rescuer_id) === String(rescuerId.value))
    );
});

// Rescuer's current status (refreshed on each poll)
const rescuerStatus = ref(authUser.value?.status || 'available');

// Check if rescuer is restricted (off_duty or unavailable)
const isRescuerRestricted = computed(() => {
    const status = rescuerStatus.value?.toLowerCase();
    return status === 'off_duty' || status === 'unavailable';
});

// Eligibility check — rescuer can receive force alert only if ALL are true:
// 1. No active rescue (active_request = none)
// 2. Status is "available"
// 3. Has not accepted another request (covered by #1)
// 4. Not restricted by admin
const isEligibleForForceAlert = computed(() => {
    return !hasActiveAssignment.value && 
           rescuerStatus.value?.toLowerCase() === 'available' &&
           !isRescuerRestricted.value;
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
    
    // Refresh rescuer status on mount to get latest from server
    await refreshRescuerStatus();
    
    // Register new message notification callback
    const unregisterMessageCallback = onNewMessages((newCount) => {
        // Don't show message notifications if rescuer is restricted
        if (isRescuerRestricted.value) {
            console.log('[Dashboard] Rescuer is restricted, skipping message notification');
            return;
        }
        
        showPopupNotification(
            '\uD83D\uDCAC New Message',
            `You have ${newCount} new message${newCount > 1 ? 's' : ''}`,
            'info',
            'mdi-message-text',
            () => { router.visit('/rescuer/chats'); }
        );
    });
    
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

        // Refresh rescuer status for eligibility check
        await refreshRescuerStatus();
        
        // Check for new pending requests
        const currentPending = pendingRequests.value;
        const currentPendingIds = currentPending.map(r => r.id);
        
        // Find new requests (IDs that weren't in the previous list)
        const newRequests = currentPending.filter(r => !previousPendingIds.value.includes(r.id));
        
        if (newRequests.length > 0) {
            // Don't send any notifications if rescuer is restricted by admin
            if (isRescuerRestricted.value) {
                console.log('[Dashboard] Rescuer is restricted, skipping notifications');
            }
            // If rescuer has active assignment → soft notification only
            else if (hasActiveAssignment.value) {
                triggerSoftRequestNotification(newRequests[0], newRequests.length);
            }
            // If rescuer is available → emergency alarm
            else {
                triggerNewRequestNotification(newRequests[0], newRequests.length);
            }
        }
        
        // Check for force-alert requests from admin
        // Only trigger for ELIGIBLE rescuers — skip entirely if busy or restricted
        const forceAlertRequests = currentPending.filter(r => r.force_alert === true || r.force_alert === 1);
        if (forceAlertRequests.length > 0 && !isForceAlertPlaying.value && isEligibleForForceAlert.value && !isRescuerRestricted.value) {
            const req = forceAlertRequests[0];
            const name = req.firstName || 'Someone';
            const location = getLocationDisplay(req);

            // Eligible — full unstoppable force-alert
            playNotificationSound('force-alert');
            vibrate([500, 200, 500, 200, 500, 200, 500, 200, 500]);
            popupAlert.value = {
                show: true,
                title: '\uD83D\uDD14 URGENT: Admin Force Alert!',
                message: `${name} needs IMMEDIATE help at ${location}! Accept now to stop the alarm.`,
                type: 'error',
                icon: 'mdi-alarm-light',
                callback: () => {
                    showNotificationPanel.value = false;
                    selectedTab.value = 'pending';
                },
            };
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

// Soft notification for new request when rescuer has an ongoing rescue (default sound only)
const triggerSoftRequestNotification = (request, totalNew) => {
    const urgencyText = request.urgency_level || 'Unknown';
    const location = getLocationDisplay(request);
    const name = request.firstName || 'Someone';

    popupAlert.value = {
        show: true,
        title: `New Request${totalNew > 1 ? ` (${totalNew})` : ''}`,
        message: `${name} needs help at ${location}. Urgency: ${urgencyText}`,
        type: 'info',
        icon: 'mdi-bell',
        callback: null,
    };
    // Use default/message sound instead of emergency alarm
    playNotificationSound('message');
    vibrate([100, 50, 100]);
    setTimeout(() => { popupAlert.value.show = false; }, 5000);
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
    
    // Play sound based on notification type
    if (type === 'error') {
        playNotificationSound('emergency');
        vibrate([300, 100, 300, 100, 300, 100, 300]);
    } else {
        playNotificationSound('message');
        vibrate([100, 50, 100]);
    }
    
    // Auto-hide
    setTimeout(() => {
        popupAlert.value.show = false;
    }, type === 'error' ? 10000 : 5000);
};

// Handle popup click
const handlePopupClick = () => {
    if (popupAlert.value.callback) {
        popupAlert.value.callback();
    }
    popupAlert.value.show = false;
};

// Handle force-alert: go to pending tab (ringtone only stops when they actually accept)
const goToPendingAndStopAlert = () => {
    stopForceAlert();
    popupAlert.value.show = false;
    showNotificationPanel.value = false;
    selectedTab.value = 'pending';
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
        // Get status from localStorage
        if (parsed.status) {
            rescuerStatus.value = parsed.status;
        }
    }
    // Also read status from Inertia auth (most up-to-date)
    if (authUser.value?.status) {
        rescuerStatus.value = authUser.value.status;
    }
};

// Refresh rescuer status from API during each poll cycle
const refreshRescuerStatus = async () => {
    if (!rescuerId.value) return;
    try {
        const response = await apiFetch(`/api/users/${rescuerId.value}`, { method: 'GET' });
        console.log('[Dashboard] Status refresh response:', response);
        
        const user = response?.data || response;
        if (user?.status) {
            rescuerStatus.value = user.status;
            console.log('[Dashboard] Updated rescuer status to:', user.status);
            
            // Also update localStorage so other components see the change
            const storedData = localStorage.getItem('userData');
            if (storedData) {
                const parsed = JSON.parse(storedData);
                parsed.status = user.status;
                localStorage.setItem('userData', JSON.stringify(parsed));
            }
        }
    } catch (error) {
        console.error('[Dashboard] Error refreshing status:', error);
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

// Fetch unread message count is now handled by useUnreadMessages composable

const refreshData = async () => {
    await fetchRescueRequests();
    showNotification('Data refreshed', 'success');
};

const acceptRescue = async (request) => {
    // Check if rescuer status allows accepting requests
    if (rescuerStatus.value && ['off_duty', 'unavailable'].includes(rescuerStatus.value.toLowerCase())) {
        const statusText = rescuerStatus.value === 'off_duty' ? 'off duty' : 'unavailable';
        showNotification(`You cannot accept rescue requests while you are ${statusText}. Please contact an administrator to change your status.`, 'error');
        return;
    }
    
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
        
        // Handle API response errors
        if (response && !response.success) {
            showNotification(response.message || 'Failed to accept rescue', 'error');
            return;
        }
        
        // Stop force-alert ringtone if it was playing
        stopForceAlert();
        popupAlert.value.show = false;
        showNotification('Rescue accepted!', 'success');
        await fetchRescueRequests();
        router.visit(`/rescuer/active/${request.id}`);
    } catch (error) {
        console.error('Failed to accept rescue:', error);
        const errorMessage = error.data?.message || error.message || 'Failed to accept rescue';
        showNotification(errorMessage, 'error');
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
    router.visit(`/rescuer/active/${request.id}?from=pending`);
};

const viewInProgressRequest = (request) => {
    // Navigate to active rescue page to manage in-progress rescue
    router.visit(`/rescuer/active/${request.id}?from=inProgress`);
};

const viewRescuedRequest = (request) => {
    // Navigate to active rescue page to view completed rescue
    router.visit(`/rescuer/active/${request.id}?from=rescued`);
};

const openChat = (request) => {
    // Prevent opening chat if rescuer is restricted
    if (isRescuerRestricted.value) {
        showNotification('You cannot access messages while your status is restricted', 'warning');
        return;
    }
    
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
    if (diffMins < 60) {
        return `0:${String(diffMins).padStart(2, '0')}`;
    }
    if (diffHours < 24) {
        const mins = diffMins % 60;
        return `${diffHours}:${String(mins).padStart(2, '0')}`;
    }
    return `${diffDays}d ago`;
};

// Format date for rescued filter display
const formatSelectedDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

// Format rescued date for card display
const formatRescuedDate = (timestamp) => {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

// Clear rescued filters
const clearRescuedFilters = () => {
    rescuedSearchQuery.value = '';
    rescuedSelectedDate.value = null;
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
    padding: 16px 0 20px 0;
    flex-shrink: 0;
}

/* Tab Pills */
.tab-pills-container {
    display: flex;
    justify-content: center;
    gap: 12px;
    padding: 0 16px;
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

/* Enhanced tab pills with icons and counts */
.tab-pill-enhanced {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.tab-pill-enhanced .tab-icon {
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.tab-pill-enhanced .tab-text {
    font-weight: 600;
    font-size: 0.8rem;
}

.tab-pill-enhanced .tab-count {
    min-width: 20px;
    height: 20px;
    font-size: 0.7rem;
    font-weight: 700;
}

.tab-pill:hover,
.tab-pill-enhanced:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.tab-pill-enhanced:hover .tab-icon {
    opacity: 1;
}

.tab-pill.active,
.tab-pill-enhanced.active {
    color: white;
    box-shadow: 0 4px 20px rgba(0,0,0,0.25);
    transform: translateY(-1px);
}

.tab-pill-enhanced.active .tab-icon {
    opacity: 1;
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

/* Request Cards - Enhanced Design */
.request-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    margin-bottom: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-left: 5px solid transparent;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.request-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    border-color: rgba(54, 116, 181, 0.3);
}

.request-card:active {
    transform: translateY(-2px);
}

.request-card-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
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

/* Rescued Filter Section */
.rescued-filter-section {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    align-items: center;
    padding: 0 4px;
}

.rescued-search-input {
    flex: 1;
    min-width: 0;
}

.rescued-search-input :deep(.v-field) {
    border-radius: 12px;
    font-size: 0.85rem;
    background: white;
}

.rescued-date-btn {
    border-radius: 12px;
    text-transform: none;
    font-weight: 600;
    font-size: 0.75rem;
    height: 40px;
    min-width: auto;
    padding: 0 12px;
    flex-shrink: 0;
}

.date-btn-text {
    max-width: 80px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Responsive rescued filter */
@media (max-width: 400px) {
    .rescued-filter-section {
        flex-wrap: wrap;
    }
    
    .rescued-search-input {
        flex: 1 1 100%;
        margin-bottom: 8px;
    }
    
    .rescued-date-btn {
        flex: 1;
    }
    
    .date-btn-text {
        max-width: 60px;
    }
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

/* ── Force Alert Overlay ── */
.force-alert-overlay {
    z-index: 99999 !important;
    display: flex;
    align-items: center;
    justify-content: center;
}

.force-alert-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 340px;
    max-width: calc(100vw - 48px);
    background: #ffffff;
    border-radius: 24px;
    padding: 40px 28px 32px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    text-align: center;
    animation: faCardEnter 0.4s cubic-bezier(0.22, 1, 0.36, 1);
}

/* Icon area with rings */
.fa-icon-area {
    position: relative;
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
}

.fa-ring {
    position: absolute;
    border-radius: 50%;
    border: 2px solid rgba(183, 28, 28, 0.15);
    animation: faRingPulse 2s ease-out infinite;
}

.fa-ring-1 {
    width: 100px;
    height: 100px;
    animation-delay: 0s;
}
.fa-ring-2 {
    width: 100px;
    height: 100px;
    animation-delay: 0.6s;
}
.fa-ring-3 {
    width: 100px;
    height: 100px;
    animation-delay: 1.2s;
}

.fa-icon-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #EF5350, #b71c1c);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
    box-shadow: 0 4px 20px rgba(183, 28, 28, 0.35);
}

.fa-icon-shake {
    animation: faShake 0.5s ease-in-out infinite alternate;
}

.fa-label {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2.5px;
    color: #b71c1c;
    background: rgba(183, 28, 28, 0.08);
    padding: 4px 16px;
    border-radius: 20px;
    margin-bottom: 12px;
}

.fa-title {
    font-size: 20px;
    font-weight: 700;
    color: #13294B;
    margin: 0 0 8px;
    line-height: 1.3;
}

.fa-subtitle {
    font-size: 13.5px;
    color: #546E7A;
    line-height: 1.55;
    margin: 0 0 28px;
}

.fa-accept-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 14px 24px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, #3674B5, #2196F3);
    color: #ffffff;
    font-size: 14.5px;
    font-weight: 700;
    letter-spacing: 0.3px;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    box-shadow: 0 4px 16px rgba(54, 116, 181, 0.3);
}

.fa-accept-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(54, 116, 181, 0.4);
}

.fa-accept-btn:active {
    transform: translateY(0);
}

@keyframes faCardEnter {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes faRingPulse {
    0% {
        transform: scale(0.6);
        opacity: 0.6;
        border-color: rgba(183, 28, 28, 0.3);
    }
    100% {
        transform: scale(1.5);
        opacity: 0;
        border-color: rgba(183, 28, 28, 0);
    }
}

@keyframes faShake {
    0%   { transform: rotate(-6deg); }
    100% { transform: rotate(6deg); }
}
</style>
