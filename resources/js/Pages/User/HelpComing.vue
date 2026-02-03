<template>
    <v-app>
        <!-- Clean Header -->
        <div class="help-page-header">
            <div class="header-content">
                <v-btn icon variant="text" class="back-btn" @click="handleGoBack">
                    <v-icon color="white">mdi-arrow-left</v-icon>
                </v-btn>
                <div class="header-title">
                    <h1>Help Status</h1>
                    <p v-if="rescue">{{ rescue.rescue_code }}</p>
                </div>
                <v-btn icon variant="text" @click="fetchRescueData" class="refresh-btn">
                    <v-icon color="white">mdi-refresh</v-icon>
                </v-btn>
            </div>
        </div>

        <!-- Navigation Drawer - handles its own visibility -->
        <UserMenu v-model="drawer" />

        <v-main class="bg-grey-lighten-4">
            <!-- Notification Alert Banner -->
            <v-slide-y-transition>
                <v-alert
                    v-if="notificationBanner.show"
                    :type="notificationBanner.type"
                    :icon="notificationBanner.icon"
                    variant="tonal"
                    class="notification-banner ma-3 rounded-xl"
                    closable
                    @click:close="notificationBanner.show = false"
                >
                    <div class="d-flex align-center">
                        <div class="flex-grow-1">
                            <div class="text-subtitle-2 font-weight-bold">{{ notificationBanner.title }}</div>
                            <div class="text-caption">{{ notificationBanner.message }}</div>
                        </div>
                    </div>
                </v-alert>
            </v-slide-y-transition>

            <v-container fluid class="pa-0">
                <!-- Loading State -->
                <div v-if="loading" class="d-flex flex-column justify-center align-center" style="min-height: 70vh;">
                    <v-progress-circular indeterminate color="primary" size="64" width="5" />
                    <p class="text-grey mt-4">Loading rescue status...</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="pa-4">
                    <v-alert type="error" variant="tonal" class="mb-4 rounded-xl">
                        {{ error }}
                        <template v-slot:append>
                            <v-btn variant="text" @click="fetchRescueData">Retry</v-btn>
                        </template>
                    </v-alert>
                </div>

                <!-- Rescue Status Display -->
                <template v-else-if="rescue">
                    <!-- Status Hero Card -->
                    <div class="status-hero" :class="'status-' + rescue.status">
                        <div class="status-hero-content">
                            <div class="status-icon-wrapper">
                                <v-avatar :color="getStatusColor(rescue.status)" size="100" class="status-avatar">
                                    <v-icon size="50" color="white">
                                        {{ getStatusIcon(rescue.status) }}
                                    </v-icon>
                                </v-avatar>
                                <div v-if="rescue.status !== 'rescued' && rescue.status !== 'safe' && rescue.status !== 'cancelled'" class="pulse-ring"></div>
                            </div>
                            <h2 class="status-title">{{ getStatusText(rescue.status) }}</h2>
                            <v-chip :color="getStatusColor(rescue.status)" variant="flat" size="large" class="status-chip">
                                {{ rescue.status?.replace('_', ' ').toUpperCase() }}
                            </v-chip>
                        </div>
                        
                        <!-- Progress Steps -->
                        <div class="progress-steps-container">
                            <div class="progress-track">
                                <div class="progress-fill" :style="{ width: getProgressWidth(rescue.status) }"></div>
                            </div>
                            <div class="progress-steps">
                                <div class="progress-step" :class="{ active: isStepActive(rescue.status, 'pending'), completed: isStepCompleted(rescue.status, 'pending') }">
                                    <div class="step-dot">
                                        <v-icon v-if="isStepCompleted(rescue.status, 'pending')" size="14" color="white">mdi-check</v-icon>
                                        <v-icon v-else-if="isStepActive(rescue.status, 'pending')" size="12" color="white">mdi-clock-outline</v-icon>
                                    </div>
                                    <span>Pending</span>
                                </div>
                                <div class="progress-step" :class="{ active: isStepActive(rescue.status, 'in_progress'), completed: isStepCompleted(rescue.status, 'in_progress') }">
                                    <div class="step-dot">
                                        <v-icon v-if="isStepCompleted(rescue.status, 'in_progress')" size="14" color="white">mdi-check</v-icon>
                                        <v-icon v-else-if="isStepActive(rescue.status, 'in_progress')" size="12" color="white">mdi-run-fast</v-icon>
                                    </div>
                                    <span>In Progress</span>
                                </div>
                                <div class="progress-step" :class="{ active: isStepActive(rescue.status, 'rescued'), completed: isStepCompleted(rescue.status, 'rescued') }">
                                    <div class="step-dot">
                                        <v-icon v-if="isStepCompleted(rescue.status, 'rescued') || isStepActive(rescue.status, 'rescued')" size="14" color="white">mdi-check-circle</v-icon>
                                    </div>
                                    <span>Rescued</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Area -->
                    <div class="content-area pa-4">
                        <!-- Location Card -->
                        <v-card class="mb-3 rounded-xl" elevation="0">
                            <div class="card-header-icon">
                                <v-avatar color="primary" size="40">
                                    <v-icon color="white" size="20">mdi-map-marker</v-icon>
                                </v-avatar>
                                <div class="card-header-text">
                                    <h3>Your Location</h3>
                                    <p>Where help is coming</p>
                                </div>
                            </div>
                            <v-card-text class="pt-0">
                                <div class="location-details">
                                    <div class="location-item">
                                        <v-icon color="primary" size="18">mdi-office-building</v-icon>
                                        <div>
                                            <span class="label">Building</span>
                                            <span class="value">{{ locationDetails.buildingName }}</span>
                                        </div>
                                    </div>
                                    <div class="location-item">
                                        <v-icon color="secondary" size="18">mdi-stairs</v-icon>
                                        <div>
                                            <span class="label">Floor</span>
                                            <span class="value">{{ locationDetails.floorName || 'Loading...' }}</span>
                                        </div>
                                    </div>
                                    <div class="location-item">
                                        <v-icon color="success" size="18">mdi-door</v-icon>
                                        <div>
                                            <span class="label">Room</span>
                                            <span class="value">{{ locationDetails.roomName || 'Loading...' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- View Map Button -->
                                <v-btn
                                    variant="tonal"
                                    color="primary"
                                    size="small"
                                    class="mt-3 view-map-btn"
                                    block
                                    @click="viewMap"
                                >
                                    <v-icon start size="18">mdi-map</v-icon>
                                    View Floor Map
                                </v-btn>
                            </v-card-text>
                        </v-card>

                        <!-- Rescuer Card (if assigned) -->
                        <v-card v-if="rescue.assigned_rescuer || rescue.rescuer" class="mb-3 rounded-xl rescuer-card" elevation="0">
                            <div class="card-header-icon">
                                <v-avatar color="success" size="40">
                                    <v-icon color="white" size="20">mdi-account-check</v-icon>
                                </v-avatar>
                                <div class="card-header-text">
                                    <h3>{{ rescue.status === 'rescued' || rescue.status === 'safe' ? 'Your Rescuer' : 'Help is Coming' }}</h3>
                                    <p>{{ rescue.status === 'rescued' || rescue.status === 'safe' ? 'Rescue completed' : 'Rescuer assigned to help you' }}</p>
                                </div>
                            </div>
                            <v-card-text class="pt-0">
                                <div class="rescuer-info" @click="showRescuerProfile = true">
                                    <v-avatar 
                                        size="60" 
                                        color="success"
                                        :style="rescuerProfilePicture ? 'cursor: pointer' : ''"
                                    >
                                        <v-img v-if="rescuerProfilePicture" :src="rescuerProfilePicture" cover />
                                        <span v-else class="text-h6 text-white">{{ getRescuerInitials() }}</span>
                                    </v-avatar>
                                    <div class="rescuer-details">
                                        <h4>{{ getRescuerName() }}</h4>
                                        <p>Certified Rescuer</p>
                                        <v-chip color="success" variant="tonal" size="x-small" v-if="rescue.status === 'en_route'">
                                            <v-icon start size="12">mdi-run-fast</v-icon>
                                            On the way
                                        </v-chip>
                                    </div>
                                    <v-icon color="grey" size="20">mdi-chevron-right</v-icon>
                                </div>
                            </v-card-text>
                        </v-card>

                        <!-- Emergency Details (Collapsible) -->
                        <v-expansion-panels v-if="rescue.description || rescue.urgency_level" class="mb-3" variant="accordion">
                            <v-expansion-panel elevation="0" class="rounded-xl">
                                <v-expansion-panel-title class="py-3">
                                    <div class="d-flex align-center">
                                        <v-avatar color="error" size="40" class="mr-3">
                                            <v-icon color="white" size="20">mdi-alert-circle</v-icon>
                                        </v-avatar>
                                        <div>
                                            <h3 class="text-subtitle-1 font-weight-bold">Emergency Details</h3>
                                            <p class="text-caption text-grey mb-0">View request information</p>
                                        </div>
                                    </div>
                                </v-expansion-panel-title>
                                <v-expansion-panel-text>
                                    <div class="emergency-details">
                                        <div v-if="rescue.description" class="detail-item">
                                            <span class="detail-label">Description</span>
                                            <p class="detail-value">{{ rescue.description }}</p>
                                        </div>
                                        <div class="detail-row" v-if="rescue.urgency_level || rescue.mobility_status">
                                            <div v-if="rescue.urgency_level" class="detail-item half">
                                                <span class="detail-label">Urgency</span>
                                                <v-chip :color="getUrgencyColor(rescue.urgency_level)" variant="tonal" size="small">
                                                    {{ rescue.urgency_level }}
                                                </v-chip>
                                            </div>
                                            <div v-if="rescue.mobility_status" class="detail-item half">
                                                <span class="detail-label">Mobility</span>
                                                <v-chip color="info" variant="tonal" size="small">
                                                    {{ rescue.mobility_status }}
                                                </v-chip>
                                            </div>
                                        </div>
                                        <div v-if="rescue.injuries" class="detail-item">
                                            <span class="detail-label">Injuries</span>
                                            <p class="detail-value">{{ rescue.injuries }}</p>
                                        </div>
                                        <div v-if="(rescue.status === 'rescued' || rescue.status === 'safe') && rescue.updated_at" class="detail-item">
                                            <span class="detail-label">Completed At</span>
                                            <p class="detail-value">{{ formatRescueDateTime(rescue.updated_at) }}</p>
                                        </div>
                                    </div>
                                </v-expansion-panel-text>
                            </v-expansion-panel>
                        </v-expansion-panels>

                        <!-- Action Buttons -->
                        <div v-if="rescue.status !== 'rescued' && rescue.status !== 'safe'" class="action-buttons pb-safe">
                            <v-btn
                                color="primary"
                                size="large"
                                block
                                class="mb-2 rounded-xl"
                                elevation="2"
                                @click="openChat"
                            >
                                <v-icon start>mdi-message-text</v-icon>
                                Chat with Rescuer
                            </v-btn>
                            <v-btn
                                color="success"
                                size="large"
                                block
                                variant="flat"
                                class="rounded-xl"
                                elevation="2"
                                @click="showConfirmSafe = true"
                            >
                                <v-icon start>mdi-check-circle</v-icon>
                                I'm Safe Now
                            </v-btn>
                        </div>
                        
                        <!-- Completed State Actions -->
                        <div v-else class="text-center py-4 pb-safe">
                            <v-icon size="56" color="success" class="mb-2">mdi-check-circle</v-icon>
                            <h3 class="text-h6 mb-1">Rescue Complete</h3>
                            <p class="text-grey mb-3">Thank you for using PinPointMe. Stay safe!</p>
                            <v-btn color="primary" variant="tonal" class="rounded-xl" @click="handleGoBack">
                                Return to Dashboard
                            </v-btn>
                        </div>
                    </div>
                </template>

                <!-- No Rescue Data -->
                <div v-else class="no-rescue-state">
                    <v-icon size="80" color="grey-lighten-1">mdi-alert-circle-outline</v-icon>
                    <h3>No Active Rescue Request</h3>
                    <p>You don't have an active rescue request</p>
                    <v-btn color="primary" class="mt-4 rounded-xl" @click="handleGoBack">
                        Go to Dashboard
                    </v-btn>
                </div>
            </v-container>

            <!-- Confirm Safe Dialog -->
            <v-dialog v-model="showConfirmSafe" max-width="400">
                <v-card class="rounded-xl">
                    <v-card-text class="text-center pt-6">
                        <v-avatar color="success" size="64" class="mb-4">
                            <v-icon size="32" color="white">mdi-check-circle</v-icon>
                        </v-avatar>
                        <h3 class="text-h6 mb-2">Confirm You're Safe</h3>
                        <p class="text-grey">This will close your rescue request. Are you sure?</p>
                    </v-card-text>
                    <v-card-actions class="pa-4 pt-0">
                        <v-btn variant="text" class="flex-grow-1" @click="showConfirmSafe = false">Cancel</v-btn>
                        <v-btn color="success" variant="flat" class="flex-grow-1 rounded-lg" :loading="isMarkingSafe" @click="markAsSafe">
                            Yes, I'm Safe
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Rescuer Profile Dialog -->
            <v-dialog v-model="showRescuerProfile" max-width="400">
                <v-card class="rounded-xl">
                    <v-card-text class="text-center py-6">
                        <v-avatar 
                            size="100" 
                            color="success" 
                            class="mb-4"
                            :style="rescuerProfilePicture ? 'cursor: pointer' : ''"
                            @click="rescuerProfilePicture && openPhotoViewer(rescuerProfilePicture, getRescuerName())"
                        >
                            <v-img v-if="rescuerProfilePicture" :src="rescuerProfilePicture" cover />
                            <span v-else class="text-h4 text-white">{{ getRescuerInitials() }}</span>
                        </v-avatar>
                        <h3 class="text-h5 font-weight-bold mb-1">{{ getRescuerName() }}</h3>
                        <p class="text-grey mb-4">Certified Rescuer</p>
                        
                        <v-divider class="mb-4" />
                        
                        <div v-if="getRescuerContact()" class="d-flex align-center justify-center mb-3">
                            <v-icon color="primary" class="mr-2">mdi-phone</v-icon>
                            <span>{{ getRescuerContact() }}</span>
                        </div>
                        <div v-if="getRescuerEmail()" class="d-flex align-center justify-center">
                            <v-icon color="primary" class="mr-2">mdi-email</v-icon>
                            <span>{{ getRescuerEmail() }}</span>
                        </div>
                    </v-card-text>
                    <v-card-actions class="pa-4 pt-0">
                        <v-btn variant="text" block @click="showRescuerProfile = false">Close</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Photo Viewer Dialog -->
            <v-dialog v-model="showPhotoViewer" max-width="500">
                <v-card class="bg-black rounded-xl">
                    <v-card-title class="d-flex align-center justify-space-between text-white">
                        <span>{{ photoViewerName }}</span>
                        <v-btn icon variant="text" color="white" @click="showPhotoViewer = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>
                    <v-card-text class="pa-0 d-flex justify-center align-center" style="min-height: 300px;">
                        <v-img :src="photoViewerUrl" max-height="400" contain class="bg-black" />
                    </v-card-text>
                </v-card>
            </v-dialog>

            <!-- Toast Notification -->
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
                @click="popupAlert.show = false"
            />
        </v-main>
        
        <!-- Bottom Navigation for Mobile -->
        <UserBottomNav :notification-count="1" :message-count="unreadCount" />
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    getRescueRequestByCode,
    getRescueRequestById,
    markRescueSafe,
    getLocationDetails,
    getProfilePictureUrl,
} from '@/Composables/useApi';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import { useUnreadMessages } from '@/Composables/useUnreadMessages';
import UserMenu from '@/Components/Pages/User/Menu/UserMenu.vue';
import UserBottomNav from '@/Components/Pages/User/Menu/UserBottomNav.vue';
import NotificationPopup from '@/Components/NotificationPopup.vue';

// Props from Inertia
const props = defineProps({
    code: {
        type: String,
        default: null,
    },
});

// Navigation drawer
const drawer = ref(false);

// Unread messages count for bottom nav
const { unreadCount } = useUnreadMessages();

// State
const rescue = ref(null);
const loading = ref(true);
const error = ref(null);
const showConfirmSafe = ref(false);
const showRescuerProfile = ref(false);
const isMarkingSafe = ref(false);
const locationDetails = ref({
    buildingName: '',
    floorName: '',
    roomName: '',
});

// Photo Viewer State
const showPhotoViewer = ref(false);
const photoViewerUrl = ref('');
const photoViewerName = ref('');

const openPhotoViewer = (url, name) => {
    photoViewerUrl.value = url;
    photoViewerName.value = name || 'Profile Photo';
    showPhotoViewer.value = true;
};

// Computed property for rescuer's profile picture
const rescuerProfilePicture = computed(() => {
    const rescuer = rescue.value?.rescuer || rescue.value?.assigned_rescuer;
    const picturePath = rescuer?.profile_picture || rescuer?.avatar;
    if (!picturePath) return null;
    if (picturePath.startsWith('http') || picturePath.startsWith('data:')) return picturePath;
    return getProfilePictureUrl(picturePath);
});

// Helper functions for rescuer info
const getRescuerInitials = () => {
    const rescuer = rescue.value?.rescuer || rescue.value?.assigned_rescuer;
    const firstName = rescuer?.first_name || '';
    const lastName = rescuer?.last_name || '';
    if (firstName && lastName) {
        return `${firstName[0]}${lastName[0]}`.toUpperCase();
    }
    if (firstName) return firstName.substring(0, 2).toUpperCase();
    return 'R';
};

const getRescuerContact = () => {
    const rescuer = rescue.value?.rescuer || rescue.value?.assigned_rescuer;
    return rescuer?.phone || rescuer?.contact_number || null;
};

const getRescuerEmail = () => {
    const rescuer = rescue.value?.rescuer || rescue.value?.assigned_rescuer;
    return rescuer?.email || null;
};

// Toast
const showToast = ref(false);
const toastMessage = ref('');
const toastColor = ref('success');

// Notification Alert
const { playNotificationSound, vibrate, notify } = useNotificationAlert();

// Notification Banner State
const notificationBanner = ref({
    show: false,
    title: '',
    message: '',
    type: 'info',
    icon: 'mdi-information',
});

const popupAlert = ref({
    show: false,
    title: '',
    message: '',
    type: 'info',
    icon: 'mdi-information',
});

// Track previous status for change detection
const previousStatus = ref(null);

// Show notification with sound and vibration
const showNotification = (options) => {
    const { title, message, type, icon, sound, vibratePattern } = options;
    
    // Play sound
    if (sound) {
        playNotificationSound(sound);
    }
    
    // Vibrate
    if (vibratePattern) {
        vibrate(vibratePattern);
    }
    
    // Show banner notification
    notificationBanner.value = {
        show: true,
        title,
        message,
        type,
        icon,
    };
    
    // Show popup alert
    popupAlert.value = {
        show: true,
        title,
        message,
        type,
        icon,
    };
    
    // Show browser notification
    notify({
        title,
        body: message,
        icon: '/images/logo.png'
    });
    
    // Auto-hide banner after 5 seconds
    setTimeout(() => {
        notificationBanner.value.show = false;
    }, 5000);
    
    // Auto-hide popup after 5 seconds
    setTimeout(() => {
        popupAlert.value.show = false;
    }, 5000);
};

// Trigger notification on status change
const triggerStatusNotification = (oldStatus, newStatus) => {
    let notificationData = null;

    switch (newStatus) {
        case 'accepted':
        case 'assigned':
            notificationData = {
                title: '🚨 Rescuer Assigned!',
                message: 'A rescuer has been assigned to help you. Help is on the way!',
                type: 'success',
                icon: 'mdi-account-check',
                sound: 'notification',
                vibratePattern: 'urgent'
            };
            break;
        case 'in_progress':
        case 'en_route':
            notificationData = {
                title: '🏃 Rescuer En Route!',
                message: 'The rescuer is now on their way to your location. Stay calm.',
                type: 'info',
                icon: 'mdi-run-fast',
                sound: 'notification',
                vibratePattern: 'standard'
            };
            break;
        case 'on_scene':
            notificationData = {
                title: '📍 Rescuer Arrived!',
                message: 'The rescuer has arrived at your location. Look for them nearby.',
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
                message: 'You have been marked as safe. Thank you for using PinPointMe.',
                type: 'success',
                icon: 'mdi-check-circle',
                sound: 'success',
                vibratePattern: 'success'
            };
            break;
        case 'cancelled':
            notificationData = {
                title: '❌ Request Cancelled',
                message: 'Your rescue request has been cancelled.',
                type: 'warning',
                icon: 'mdi-close-circle',
                sound: 'notification',
                vibratePattern: 'standard'
            };
            break;
    }

    if (notificationData) {
        showNotification(notificationData);
    }
};

// Get rescue code from URL or localStorage
const rescueCode = ref('');
const rescueId = ref('');

onMounted(async () => {
    // Get from props (Inertia) first, then localStorage
    rescueCode.value = props.code || localStorage.getItem('lastRescueCode') || '';
    rescueId.value = localStorage.getItem('lastRescueRequestId') || '';

    await fetchRescueData();

    // Set up polling for status updates
    const pollInterval = setInterval(async () => {
        if (rescue.value && !['rescued', 'safe', 'cancelled', 'completed'].includes(rescue.value.status)) {
            try {
                await fetchRescueData(true);
            } catch (err) {
                console.warn('Polling error (will retry):', err);
            }
        }
    }, 5000); // Poll every 5 seconds for faster updates

    // Cleanup on unmount
    return () => clearInterval(pollInterval);
});

const fetchRescueData = async (silent = false) => {
    if (!silent) loading.value = true;
    error.value = null;

    try {
        let data;
        if (rescueCode.value) {
            data = await getRescueRequestByCode(rescueCode.value);
        } else if (rescueId.value) {
            data = await getRescueRequestById(rescueId.value);
        } else {
            throw new Error('No rescue code or ID provided');
        }

        // Handle both wrapped and direct response formats
        const newRescueData = data.data || data;
        
        // Detect status change and trigger notification
        const oldStatus = rescue.value?.status;
        const newStatus = newRescueData.status;
        
        if (oldStatus && oldStatus !== newStatus) {
            console.log('Status changed:', oldStatus, '->', newStatus);
            triggerStatusNotification(oldStatus, newStatus);
        }
        
        // Initialize previous status on first load
        if (!previousStatus.value && newStatus) {
            previousStatus.value = newStatus;
        }
        
        rescue.value = newRescueData;
        previousStatus.value = newStatus;
        console.log('Current rescue status:', rescue.value.status); // Debug log

        // Check if location is already in the rescue data
        if (rescue.value.building && rescue.value.floor && rescue.value.room) {
            locationDetails.value = {
                buildingName: rescue.value.building.name,
                floorName: rescue.value.floor.floor_name,
                roomName: rescue.value.room.room_name,
            };
        }
        // Fetch location details if not already available
        else if (rescue.value.building_id && rescue.value.floor_id && rescue.value.room_id) {
            await fetchLocationDetails(rescue.value.building_id, rescue.value.floor_id, rescue.value.room_id);
        }
    } catch (err) {
        console.error('Failed to fetch rescue data:', err);
        if (!silent) {
            error.value = err.message || 'Failed to load rescue request';
        } else {
            // For polling errors, just log but don't show error to user
            console.warn('Polling fetch failed (will retry):', err.message);
        }
    } finally {
        if (!silent) loading.value = false;
    }
};

const fetchLocationDetails = async (buildingId, floorId, roomId) => {
    try {
        const response = await getLocationDetails(buildingId, floorId, roomId);
        console.log('Location details response:', response);
        
        // Handle both nested and flat response structures
        const data = response.data || response;
        
        locationDetails.value = {
            buildingName: data.building?.name || data.building_name || data.buildingName || 'Unknown Building',
            floorName: data.floor?.floor_name || data.floor_name || data.floorName || 'Unknown Floor',
            roomName: data.room?.room_name || data.room_name || data.roomName || 'Unknown Room',
        };
    } catch (err) {
        console.error('Failed to fetch location details:', err);
        // If location fetch fails, try to get from rescue data or set defaults
        if (rescue.value) {
            locationDetails.value = {
                buildingName: rescue.value.building?.name || 'Building ' + (rescue.value.building_id || '?'),
                floorName: rescue.value.floor?.floor_name || 'Floor ' + (rescue.value.floor_id || '?'),
                roomName: rescue.value.room?.room_name || 'Room ' + (rescue.value.room_id || '?'),
            };
        } else {
            locationDetails.value = {
                buildingName: 'Location unavailable',
                floorName: 'Location unavailable', 
                roomName: 'Location unavailable',
            };
        }
    }
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'warning',
        open: 'info',
        assigned: 'primary',
        en_route: 'secondary',
        on_scene: 'success',
        rescued: 'success',
        safe: 'success',
        cancelled: 'grey',
    };
    return colors[status] || 'grey';
};

const getStatusIcon = (status) => {
    const icons = {
        pending: 'mdi-clock-outline',
        open: 'mdi-alert-circle-outline',
        assigned: 'mdi-account-check',
        en_route: 'mdi-run-fast',
        on_scene: 'mdi-map-marker-check',
        rescued: 'mdi-check-circle',
        safe: 'mdi-shield-check',
        cancelled: 'mdi-close-circle',
    };
    return icons[status] || 'mdi-help-circle-outline';
};

const getStatusText = (status) => {
    const texts = {
        pending: 'Your request is pending',
        open: 'Help request is open',
        assigned: 'A rescuer has been assigned',
        en_route: 'Rescuer is on the way',
        on_scene: 'Rescuer has arrived',
        rescued: 'You have been rescued',
        safe: 'Marked as safe',
        cancelled: 'Request cancelled',
    };
    return texts[status] || 'Unknown status';
};

const getProgressValue = (status) => {
    const progress = {
        pending: 0,
        open: 0,
        assigned: 50,
        accepted: 50,
        in_progress: 50,
        en_route: 50,
        on_scene: 50,
        rescued: 100,
        safe: 100,
        completed: 100,
    };
    return progress[status] || 0;
};

// Get progress bar width percentage
const getProgressWidth = (status) => {
    const widths = {
        pending: '0%',
        open: '0%',
        assigned: '50%',
        accepted: '50%',
        in_progress: '50%',
        en_route: '50%',
        on_scene: '50%',
        rescued: '100%',
        safe: '100%',
        completed: '100%',
    };
    return widths[status] || '0%';
};

// Check if a step is active
const isStepActive = (status, step) => {
    const stepStatuses = {
        pending: ['pending', 'open'],
        in_progress: ['assigned', 'accepted', 'in_progress', 'en_route', 'on_scene'],
        rescued: ['rescued', 'safe', 'completed'],
    };
    return stepStatuses[step]?.includes(status) || false;
};

// Check if a step is completed
const isStepCompleted = (status, step) => {
    const completedOrder = ['pending', 'in_progress', 'rescued'];
    const currentStepIndex = completedOrder.findIndex(s => {
        if (s === 'pending') return ['pending', 'open'].includes(status);
        if (s === 'in_progress') return ['assigned', 'accepted', 'in_progress', 'en_route', 'on_scene'].includes(status);
        if (s === 'rescued') return ['rescued', 'safe', 'completed'].includes(status);
        return false;
    });
    const targetStepIndex = completedOrder.indexOf(step);
    return currentStepIndex > targetStepIndex;
};

const getUrgencyColor = (urgency) => {
    const colors = {
        low: 'success',
        medium: 'warning',
        high: 'orange',
        critical: 'error',
    };
    return colors[urgency] || 'grey';
};

const getRescuerName = () => {
    if (rescue.value?.rescuer) {
        const rescuer = rescue.value.rescuer;
        return `${rescuer.first_name || ''} ${rescuer.last_name || ''}`.trim() || 'Rescuer Assigned';
    }
    return 'Rescuer Assigned';
};

const getPersonInNeedName = () => {
    // Check if there's a requester (the person who needs help)
    if (rescue.value?.requester) {
        const requester = rescue.value.requester;
        return `${requester.first_name || ''} ${requester.last_name || ''}`.trim() || 'Unknown Person';
    }
    // Fall back to the user data from the rescue request
    if (rescue.value?.first_name || rescue.value?.last_name) {
        return `${rescue.value.first_name || ''} ${rescue.value.last_name || ''}`.trim();
    }
    // Get current user name as last resort
    return getUserName() || 'Unknown Person';
};

const getReporterName = () => {
    if (rescue.value?.first_name || rescue.value?.last_name) {
        return `${rescue.value.first_name || ''} ${rescue.value.last_name || ''}`.trim();
    }
    return null;
};

const getUserName = () => {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    return `${userData.firstName || userData.first_name || ''} ${userData.lastName || userData.last_name || ''}`.trim();
};

const formatRescueDateTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString([], {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const markAsSafe = async () => {
    isMarkingSafe.value = true;
    try {
        await markRescueSafe(rescue.value.id);
        rescue.value.status = 'safe';
        showConfirmSafe.value = false;

        // Show notification with sound and vibration
        showNotification({
            title: '✅ Marked as Safe!',
            message: 'You have been marked as safe. Stay safe!',
            type: 'success',
            icon: 'mdi-shield-check',
            sound: 'success',
            vibratePattern: 'success'
        });

        toastMessage.value = 'You have been marked as safe!';
        toastColor.value = 'success';
        showToast.value = true;

        // Clear localStorage
        localStorage.removeItem('lastRescueCode');
        localStorage.removeItem('lastRescueRequestId');

        setTimeout(() => {
            router.visit('/user/scanner');
        }, 2000);
    } catch (err) {
        console.error('Failed to mark as safe:', err);
        toastMessage.value = 'Failed to update status';
        toastColor.value = 'error';
        showToast.value = true;
    } finally {
        isMarkingSafe.value = false;
    }
};

const openChat = () => {
    if (rescue.value?.assigned_rescuer || rescue.value?.rescuer_id) {
        // Play sound when opening chat
        playNotificationSound('message');
        // Use rescue-chat route which will get or create conversation
        router.visit(`/user/rescue-chat/${rescue.value.id}`);
    } else {
        toastMessage.value = 'No rescuer assigned yet';
        toastColor.value = 'warning';
        showToast.value = true;
    }
};

const viewMap = () => {
    // Navigate to the map view with the rescue code
    const code = rescue.value?.rescue_code || rescueCode.value;
    if (code) {
        router.visit(`/user/map/${code}`);
    } else {
        router.visit('/user/map');
    }
};

const handleGoBack = () => {
    // Navigate to history instead of scanner
    router.visit('/user/history');
};
</script>

<style scoped>
/* Header Styling */
.help-page-header {
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 100%);
    padding: 16px;
    padding-top: calc(env(safe-area-inset-top) + 16px);
    position: sticky;
    top: 0;
    z-index: 10;
}

/* Notification Banner */
.notification-banner {
    position: sticky;
    top: 70px;
    z-index: 100;
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

.notification-banner.v-alert--type-success {
    background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%) !important;
    border-left: 4px solid #4CAF50;
}

.notification-banner.v-alert--type-info {
    background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%) !important;
    border-left: 4px solid #2196F3;
}

.notification-banner.v-alert--type-warning {
    background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%) !important;
    border-left: 4px solid #FF9800;
}

.notification-banner.v-alert--type-error {
    background: linear-gradient(135deg, #FFEBEE 0%, #FFCDD2 100%) !important;
    border-left: 4px solid #F44336;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 800px;
    margin: 0 auto;
}

.header-title {
    text-align: center;
    flex: 1;
}

.header-title h1 {
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.header-title p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.75rem;
    margin: 0;
}

.back-btn, .refresh-btn {
    background: rgba(255, 255, 255, 0.1) !important;
}

/* Status Hero */
.status-hero {
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 100%);
    padding: 24px 16px 36px;
    position: relative;
    overflow: hidden;
}

.status-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.3;
}

.status-hero.status-pending {
    background: linear-gradient(135deg, #FF9800 0%, #FFC107 100%);
}

.status-hero.status-rescued,
.status-hero.status-safe {
    background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
}

.status-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
}

.status-icon-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 12px;
}

.status-avatar {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    width: 88px !important;
    height: 88px !important;
}

.status-avatar .v-icon {
    font-size: 44px !important;
}

.pulse-ring {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 88px;
    height: 88px;
    border-radius: 50%;
    border: 3px solid currentColor;
    animation: pulse 2s ease-out infinite;
}

@keyframes pulse {
    0% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 0.5;
        border-color: rgba(255, 255, 255, 0.5);
    }
    100% {
        transform: translate(-50%, -50%) scale(1.5);
        opacity: 0;
        border-color: rgba(255, 255, 255, 0);
    }
}

.status-title {
    color: white;
    font-size: 1.35rem;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.status-chip {
    font-weight: 600;
}

/* Progress Steps Container */
.progress-steps-container {
    position: relative;
    margin-top: 24px;
    padding: 0 20px;
    z-index: 1;
}

/* Progress Track */
.progress-track {
    position: absolute;
    top: 18px;
    left: 60px;
    right: 60px;
    height: 4px;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.9) 0%, white 100%);
    border-radius: 4px;
    transition: width 0.5s ease-in-out;
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

/* Progress Steps */
.progress-steps {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    position: relative;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    flex: 1;
    max-width: 100px;
}

.progress-step span {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    text-align: center;
    transition: all 0.3s ease;
}

.progress-step.active span {
    color: white;
    text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

.progress-step.completed span {
    color: rgba(255, 255, 255, 0.85);
}

.step-dot {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: 3px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.progress-step.active .step-dot {
    background: white;
    border-color: white;
    box-shadow: 0 0 0 6px rgba(255, 255, 255, 0.25), 0 4px 15px rgba(0, 0, 0, 0.2);
    transform: scale(1.1);
    animation: pulse-step 2s infinite;
}

.progress-step.completed .step-dot {
    background: rgba(255, 255, 255, 0.9);
    border-color: rgba(255, 255, 255, 0.9);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.progress-step.active .step-dot .v-icon,
.progress-step.completed .step-dot .v-icon {
    color: #3674B5 !important;
}

@keyframes pulse-step {
    0%, 100% {
        box-shadow: 0 0 0 6px rgba(255, 255, 255, 0.25), 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(255, 255, 255, 0.15), 0 4px 20px rgba(0, 0, 0, 0.25);
    }
}

/* Content Area */
.content-area {
    margin-top: -20px;
    border-radius: 24px 24px 0 0;
    background: #f5f5f5;
    position: relative;
    z-index: 2;
    padding-bottom: 100px !important; /* Space for bottom nav */
}

/* Safe area bottom padding for buttons */
.pb-safe {
    padding-bottom: calc(env(safe-area-inset-bottom) + 90px) !important;
}

/* Card Header with Icon */
.card-header-icon {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 16px 8px;
}

.card-header-text h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.card-header-text p {
    font-size: 0.75rem;
    color: #888;
    margin: 0;
}

/* Location Details */
.location-details {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.location-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 12px;
}

.location-item > div {
    display: flex;
    flex-direction: column;
}

.location-item .label {
    font-size: 0.7rem;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.location-item .value {
    font-size: 0.9rem;
    font-weight: 500;
    color: #333;
}

/* View Map Button */
.view-map-btn {
    border-radius: 12px !important;
    text-transform: none;
    font-weight: 500;
}

/* Rescuer Card */
.rescuer-card .rescuer-info {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px;
    background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
    border-radius: 16px;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.rescuer-card .rescuer-info:active {
    transform: scale(0.98);
}

.rescuer-details {
    flex: 1;
}

.rescuer-details h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #333;
    margin: 0 0 2px;
}

.rescuer-details p {
    font-size: 0.8rem;
    color: #666;
    margin: 0 0 4px;
}

/* Emergency Details */
.emergency-details {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-item.half {
    flex: 1;
}

.detail-row {
    display: flex;
    gap: 16px;
}

.detail-label {
    font-size: 0.7rem;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    font-size: 0.9rem;
    color: #333;
    margin: 0;
}

/* Action Buttons */
.action-buttons {
    margin-top: 8px;
    position: relative;
}

.action-buttons .v-btn {
    min-height: 48px;
}

/* No Rescue State */
.no-rescue-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    padding: 24px;
    padding-bottom: 100px;
    text-align: center;
}

.no-rescue-state h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
    margin: 16px 0 8px;
}

.no-rescue-state p {
    color: #888;
    margin: 0;
}

/* Mobile-responsive layout */
@media (max-width: 1024px) {
    .v-main :deep(.v-container) {
        padding-bottom: 0 !important;
    }
    
    .content-area {
        padding-bottom: 100px !important;
    }
}

/* Small mobile screens */
@media (max-height: 700px) {
    .status-hero {
        padding: 20px 16px 32px;
    }
    
    .status-avatar {
        width: 80px !important;
        height: 80px !important;
    }
    
    .status-avatar .v-icon {
        font-size: 40px !important;
    }
    
    .status-title {
        font-size: 1.25rem;
    }
    
    .progress-steps-container {
        margin-top: 16px;
        padding: 0 12px;
    }
    
    .progress-track {
        left: 45px;
        right: 45px;
    }
    
    .progress-step span {
        font-size: 0.6rem;
    }
    
    .step-dot {
        width: 32px;
        height: 32px;
    }
    
    .content-area {
        margin-top: -16px;
        padding-bottom: 100px !important;
    }
    
    .card-header-icon {
        padding: 12px 12px 6px;
    }
    
    .location-item {
        padding: 6px 10px;
    }
}

/* Very small screens (iPhone SE, etc.) */
@media (max-height: 600px) {
    .status-hero {
        padding: 16px 12px 24px;
    }
    
    .status-avatar {
        width: 64px !important;
        height: 64px !important;
    }
    
    .status-avatar .v-icon {
        font-size: 32px !important;
    }
    
    .status-title {
        font-size: 1.1rem;
        margin-bottom: 8px;
    }
    
    .status-chip {
        height: 28px !important;
    }
    
    .progress-steps-container {
        margin-top: 12px;
        padding: 0 8px;
    }
    
    .progress-track {
        left: 35px;
        right: 35px;
        top: 14px;
    }
    
    .step-dot {
        width: 28px;
        height: 28px;
        border-width: 2px;
    }
    
    .step-dot .v-icon {
        font-size: 12px !important;
    }
    
    .progress-step span {
        font-size: 0.55rem;
    }
    
    .pulse-ring {
        width: 64px;
        height: 64px;
    }
}

/* Show side menu on laptop and up (1024px+) */
@media (min-width: 1024px) {
    .v-main {
        margin-left: 256px !important;
    }
    
    .content-area {
        padding-bottom: 40px !important;
    }
    
    .pb-safe {
        padding-bottom: 20px !important;
    }
}

@media (min-width: 600px) {
    .content-area {
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .status-hero-content {
        max-width: 600px;
        margin: 0 auto;
    }
}

/* Expansion Panel Customization */
:deep(.v-expansion-panel-title) {
    padding: 12px 16px !important;
}

:deep(.v-expansion-panel-text__wrapper) {
    padding: 0 16px 16px !important;
}
</style>
