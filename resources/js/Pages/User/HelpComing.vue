<template>
    <v-app>
        <v-app-bar color="primary" density="compact">
            <v-app-bar-nav-icon @click="drawer = !drawer" />
            <v-app-bar-title>Help Status</v-app-bar-title>
            <v-spacer />
            <v-btn icon @click="fetchRescueData">
                <v-icon>mdi-refresh</v-icon>
            </v-btn>
        </v-app-bar>

        <!-- Navigation Drawer -->
        <UserMenu v-model="drawer" />

        <v-main class="bg-user-gradient-light">
            <v-container fluid class="pa-4">
                <!-- Loading State -->
                <div v-if="loading" class="d-flex justify-center align-center" style="min-height: 60vh;">
                    <v-progress-circular indeterminate color="primary" size="64" />
                </div>

                <!-- Error State -->
                <v-alert v-else-if="error" type="error" variant="tonal" class="mb-4">
                    {{ error }}
                    <template v-slot:append>
                        <v-btn variant="text" @click="fetchRescueData">Retry</v-btn>
                    </template>
                </v-alert>

                <!-- Rescue Status Display -->
                <template v-else-if="rescue">
                    <!-- Status Header -->
                    <v-card class="mb-4 text-center" elevation="4" rounded="lg">
                        <v-card-text class="pa-6">
                            <v-avatar :color="getStatusColor(rescue.status)" size="80" class="mb-4">
                                <v-icon size="40" color="white">
                                    {{ getStatusIcon(rescue.status) }}
                                </v-icon>
                            </v-avatar>
                            <h2 class="text-h5 font-weight-bold mb-2">
                                {{ getStatusText(rescue.status) }}
                            </h2>
                            <v-chip :color="getStatusColor(rescue.status)" variant="tonal" size="large">
                                {{ rescue.status?.toUpperCase() }}
                            </v-chip>
                        </v-card-text>

                        <!-- Progress Indicator -->
                        <v-progress-linear
                            :model-value="getProgressValue(rescue.status)"
                            :color="getStatusColor(rescue.status)"
                            height="8"
                            class="mb-0"
                        />
                    </v-card>

                    <!-- Location Details -->
                    <v-card class="mb-4" elevation="2" rounded="lg">
                        <v-card-title class="d-flex align-center">
                            <v-icon class="mr-2" color="primary">mdi-map-marker</v-icon>
                            Your Location
                        </v-card-title>
                        <v-card-text>
                            <v-list density="compact">
                                <v-list-item>
                                    <template v-slot:prepend>
                                        <v-icon color="primary">mdi-office-building</v-icon>
                                    </template>
                                    <v-list-item-title>Building</v-list-item-title>
                                    <v-list-item-subtitle>
                                        {{ locationDetails.buildingName  }}
                                    </v-list-item-subtitle>
                                </v-list-item>
                                <v-list-item>
                                    <template v-slot:prepend>
                                        <v-icon color="secondary">mdi-stairs</v-icon>
                                    </template>
                                    <v-list-item-title>Floor</v-list-item-title>
                                    <v-list-item-subtitle>
                                        {{ locationDetails.floorName || 'Loading...' }}
                                    </v-list-item-subtitle>
                                </v-list-item>
                                <v-list-item>
                                    <template v-slot:prepend>
                                        <v-icon color="success">mdi-door</v-icon>
                                    </template>
                                    <v-list-item-title>Room</v-list-item-title>
                                    <v-list-item-subtitle>
                                        {{ locationDetails.roomName || 'Loading...' }}
                                    </v-list-item-subtitle>
                                </v-list-item>
                            </v-list>
                        </v-card-text>
                    </v-card>

                    <!-- Emergency Details -->
                    <v-card v-if="rescue.description || (rescue.status === 'rescued' || rescue.status === 'safe')" class="mb-4" elevation="2" rounded="lg">
                        <v-card-title class="d-flex align-center">
                            <v-icon class="mr-2" color="error">mdi-alert-circle</v-icon>
                            {{ rescue.status === 'rescued' || rescue.status === 'safe' ? 'Rescue Report Details' : 'Emergency Details' }}
                        </v-card-title>
                        <v-card-text>
                            <!-- Rescue Code -->
                            <div class="mb-3">
                                <div class="text-caption text-grey mb-1">RESCUE CODE</div>
                                <div class="text-h6 font-weight-bold text-primary">{{ rescue.rescue_code }}</div>
                            </div>

                            <!-- Person Being Reported/Rescued -->
                            <div class="mb-3">
                                <div class="text-caption text-grey mb-1">PERSON IN NEED</div>
                                <div class="text-body-2 font-weight-medium">{{ getPersonInNeedName() }}</div>
                            </div>

                            <!-- Reporter Name (if different from user) -->
                            <div v-if="getReporterName() && getReporterName() !== getUserName()" class="mb-3">
                                <div class="text-caption text-grey mb-1">REPORTED BY</div>
                                <div class="text-body-2 font-weight-medium">{{ getReporterName() }}</div>
                                <div class="text-caption text-grey">Good Samaritan Report</div>
                            </div>

                            <!-- Description -->
                            <div v-if="rescue.description" class="mb-3">
                                <div class="text-caption text-grey mb-1">DESCRIPTION</div>
                                <p class="text-body-2">{{ rescue.description }}</p>
                            </div>

                            <!-- Emergency Details Grid -->
                            <div v-if="rescue.urgency_level || rescue.mobility_status || rescue.injuries || rescue.additional_info" class="mb-3">
                                <div class="text-caption text-grey mb-2">EMERGENCY INFORMATION</div>
                                <v-row dense>
                                    <v-col cols="6" v-if="rescue.urgency_level">
                                        <div class="text-caption text-grey">Urgency Level</div>
                                        <v-chip
                                            :color="getUrgencyColor(rescue.urgency_level)"
                                            variant="tonal"
                                            size="small"
                                            class="mt-1"
                                        >
                                            {{ rescue.urgency_level }}
                                        </v-chip>
                                    </v-col>
                                    <v-col cols="6" v-if="rescue.mobility_status">
                                        <div class="text-caption text-grey">Mobility Status</div>
                                        <v-chip
                                            color="info"
                                            variant="tonal"
                                            size="small"
                                            class="mt-1"
                                        >
                                            {{ rescue.mobility_status }}
                                        </v-chip>
                                    </v-col>
                                </v-row>
                            </div>

                            <!-- Injuries -->
                            <div v-if="rescue.injuries" class="mb-3">
                                <div class="text-caption text-grey mb-1">INJURIES</div>
                                <div class="text-body-2">{{ rescue.injuries }}</div>
                            </div>

                            <!-- Additional Information -->
                            <div v-if="rescue.additional_info" class="mb-3">
                                <div class="text-caption text-grey mb-1">ADDITIONAL INFORMATION</div>
                                <div class="text-body-2">{{ rescue.additional_info }}</div>
                            </div>

                            <!-- Rescue Completion Time (for rescued status) -->
                            <div v-if="(rescue.status === 'rescued' || rescue.status === 'safe') && rescue.updated_at" class="mb-3">
                                <div class="text-caption text-grey mb-1">RESCUE COMPLETED</div>
                                <div class="d-flex align-center">
                                    <v-icon size="16" color="success" class="mr-1">mdi-clock-check</v-icon>
                                    <div class="text-body-2">
                                        {{ formatRescueDateTime(rescue.updated_at) }}
                                    </div>
                                </div>
                            </div>
                        </v-card-text>
                    </v-card>

                    <!-- Assigned Rescuer -->
                    <v-card v-if="rescue.assigned_rescuer || rescue.rescuer" class="mb-4" elevation="2" rounded="lg">
                        <v-card-title class="d-flex align-center">
                            <v-icon class="mr-2" color="success">mdi-account-check</v-icon>
                            {{ rescue.status === 'rescued' || rescue.status === 'safe' ? 'Rescuer Details' : 'Assigned Rescuer' }}
                        </v-card-title>
                        <v-card-text>
                            <div class="d-flex align-center">
                                <v-avatar 
                                    color="success" 
                                    class="mr-3" 
                                    size="48"
                                    :style="rescuerProfilePicture ? 'cursor: pointer' : ''"
                                    @click="rescuerProfilePicture && openPhotoViewer(rescuerProfilePicture, getRescuerName())"
                                >
                                    <v-img 
                                        v-if="rescuerProfilePicture" 
                                        :src="rescuerProfilePicture" 
                                        cover 
                                    />
                                    <span v-else class="text-subtitle-1 text-white">{{ getRescuerInitials() }}</span>
                                </v-avatar>
                                <div class="flex-grow-1">
                                    <div class="font-weight-bold">
                                        {{ getRescuerName() }}
                                    </div>
                                    <div class="text-caption text-grey">
                                        {{ rescue.status === 'rescued' || rescue.status === 'safe' ? 'Rescue completed' : 'Help is on the way' }}
                                    </div>
                                </div>
                                <v-btn
                                    icon
                                    size="small"
                                    variant="text"
                                    @click="showRescuerProfile = true"
                                >
                                    <v-icon>mdi-information-outline</v-icon>
                                </v-btn>
                            </div>
                        </v-card-text>
                    </v-card>

                    <!-- Action Buttons -->
                    <v-row v-if="rescue.status !== 'rescued' && rescue.status !== 'safe'" class="mt-4">
                        <v-col cols="12" sm="6">
                            <v-btn
                                color="primary"
                                block
                                size="large"
                                prepend-icon="mdi-message"
                                @click="openChat"
                            >
                                Chat with Rescuer
                            </v-btn>
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-btn
                                color="success"
                                block
                                size="large"
                                variant="tonal"
                                prepend-icon="mdi-check-circle"
                                @click="showConfirmSafe = true"
                            >
                                I'm Safe Now
                            </v-btn>
                        </v-col>
                    </v-row>


                </template>

                <!-- No Rescue Data -->
                <v-card v-else class="text-center pa-8" elevation="4" rounded="lg">
                    <v-icon size="64" color="grey">mdi-alert-circle-outline</v-icon>
                    <h3 class="text-h6 mt-4">No Active Rescue Request</h3>
                    <p class="text-grey mt-2">You don't have an active rescue request</p>
                    <v-btn color="primary" class="mt-4" @click="handleGoBack">
                        Go to Dashboard
                    </v-btn>
                </v-card>
            </v-container>

            <!-- Confirm Safe Dialog -->
            <v-dialog v-model="showConfirmSafe" max-width="400">
                <v-card>
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="success">mdi-check-circle</v-icon>
                        Confirm You're Safe
                    </v-card-title>
                    <v-card-text>
                        Are you sure you want to mark yourself as safe? This will close your rescue request.
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer />
                        <v-btn variant="text" @click="showConfirmSafe = false">Cancel</v-btn>
                        <v-btn color="success" :loading="isMarkingSafe" @click="markAsSafe">
                            Yes, I'm Safe
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Rescuer Profile Dialog -->
            <v-dialog v-model="showRescuerProfile" max-width="400">
                <v-card>
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2">mdi-account</v-icon>
                        Rescuer Profile
                    </v-card-title>
                    <v-divider />
                    <v-card-text class="text-center py-4">
                        <v-avatar 
                            size="80" 
                            color="success" 
                            class="mb-3"
                            :style="rescuerProfilePicture ? 'cursor: pointer' : ''"
                            @click="rescuerProfilePicture && openPhotoViewer(rescuerProfilePicture, getRescuerName())"
                        >
                            <v-img v-if="rescuerProfilePicture" :src="rescuerProfilePicture" cover />
                            <span v-else class="text-h5 text-white">{{ getRescuerInitials() }}</span>
                        </v-avatar>
                        <p v-if="rescuerProfilePicture" class="text-caption text-grey mb-2">Tap photo to enlarge</p>
                        <h3 class="text-h6 mb-1">{{ getRescuerName() }}</h3>
                        <p class="text-grey text-body-2 mb-3">Rescuer</p>
                        
                        <v-list density="compact" class="text-left">
                            <v-list-item v-if="getRescuerContact()">
                                <template v-slot:prepend>
                                    <v-icon color="grey" size="20">mdi-phone</v-icon>
                                </template>
                                <v-list-item-title class="text-body-2">Contact</v-list-item-title>
                                <v-list-item-subtitle>{{ getRescuerContact() }}</v-list-item-subtitle>
                            </v-list-item>
                            <v-list-item v-if="getRescuerEmail()">
                                <template v-slot:prepend>
                                    <v-icon color="grey" size="20">mdi-email</v-icon>
                                </template>
                                <v-list-item-title class="text-body-2">Email</v-list-item-title>
                                <v-list-item-subtitle>{{ getRescuerEmail() }}</v-list-item-subtitle>
                            </v-list-item>
                        </v-list>
                    </v-card-text>
                    <v-divider />
                    <v-card-actions>
                        <v-spacer />
                        <v-btn variant="text" @click="showRescuerProfile = false">Close</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Photo Viewer Dialog -->
            <v-dialog v-model="showPhotoViewer" max-width="500" content-class="photo-viewer-dialog">
                <v-card class="bg-black">
                    <v-card-title class="d-flex align-center justify-space-between text-white">
                        <span>{{ photoViewerName }}</span>
                        <v-btn icon variant="text" color="white" @click="showPhotoViewer = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>
                    <v-card-text class="pa-0 d-flex justify-center align-center" style="min-height: 300px;">
                        <v-img
                            :src="photoViewerUrl"
                            max-height="400"
                            contain
                            class="bg-black"
                        />
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
import UserMenu from '@/Components/Pages/User/Menu/UserMenu.vue';
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
const popupAlert = ref({
    show: false,
    title: '',
    message: '',
    type: 'info',
    icon: 'mdi-information',
});

// Track previous status for change detection
const previousStatus = ref(null);

// Trigger notification on status change
const triggerStatusNotification = (oldStatus, newStatus) => {
    let notificationData = null;

    switch (newStatus) {
        case 'accepted':
            notificationData = {
                title: '🚨 Rescuer Assigned!',
                message: 'A rescuer has been assigned to help you. Help is on the way!',
                type: 'success',
                icon: 'mdi-account-check',
                sound: 'notification'
            };
            break;
        case 'in_progress':
        case 'en_route':
            notificationData = {
                title: '🏃 Rescuer En Route!',
                message: 'The rescuer is now on their way to your location. Stay calm.',
                type: 'info',
                icon: 'mdi-run-fast',
                sound: 'notification'
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
                sound: 'message'
            };
            break;
    }

    if (notificationData) {
        // Play sound and vibrate
        playNotificationSound(notificationData.sound);
        vibrate('standard');

        // Show popup
        popupAlert.value = {
            show: true,
            title: notificationData.title,
            message: notificationData.message,
            type: notificationData.type,
            icon: notificationData.icon
        };

        // Also show browser notification
        notify({
            title: notificationData.title,
            body: notificationData.message,
            icon: '/icons/icon-192x192.png'
        });

        // Auto-hide after 5 seconds
        setTimeout(() => {
            popupAlert.value.show = false;
        }, 5000);
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
        pending: 15,
        open: 25,
        assigned: 40,
        en_route: 60,
        on_scene: 80,
        rescued: 100,
        safe: 100,
    };
    return progress[status] || 0;
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
        // Use rescue-chat route which will get or create conversation
        router.visit(`/user/rescue-chat/${rescue.value.id}`);
    } else {
        toastMessage.value = 'No rescuer assigned yet';
        toastColor.value = 'warning';
        showToast.value = true;
    }
};

const handleGoBack = () => {
    // Navigate to history instead of scanner
    router.visit('/user/history');
};
</script>

<style scoped>
/* Component-specific styles only - background is now global */
</style>
