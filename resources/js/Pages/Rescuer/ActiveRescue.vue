<template>
    <v-app class="bg-user-gradient-light">
        <!-- Custom Header -->
        <div class="rescue-header">
            <div class="header-content">
                <v-btn icon variant="text" class="back-btn" @click="goBack">
                    <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
                <div class="header-title">
                    <h1>Active Rescue</h1>
                    <p v-if="rescueRequest?.rescue_code">Code: {{ rescueRequest.rescue_code }}</p>
                </div>
                <v-chip
                    v-if="currentStatus"
                    :color="getStatusChipColor(currentStatus)"
                    variant="flat"
                    size="small"
                    class="status-chip"
                >
                    <v-icon start size="14">{{ getStatusIcon(currentStatus) }}</v-icon>
                    {{ formatStatus(currentStatus) }}
                </v-chip>
            </div>
        </div>

        <!-- Main Content -->
        <v-main class="rescue-main">
            <!-- Loading State -->
            <div v-if="loading" class="loading-container">
                <v-progress-circular indeterminate color="primary" size="64" />
                <p class="mt-4 text-grey">Loading rescue details...</p>
            </div>

            <!-- No Rescue Found -->
            <div v-else-if="!rescueRequest" class="empty-state">
                <v-icon size="64" color="grey-lighten-1">mdi-alert-circle-outline</v-icon>
                <h3>No Active Rescue</h3>
                <p>You will be redirected to the dashboard.</p>
            </div>

            <!-- Rescue Details -->
            <div v-else class="rescue-content">
                <!-- Urgency Banner -->
                <div :class="['urgency-banner', `urgency-${rescueRequest.urgency_level?.toLowerCase() || 'medium'}`]">
                    <div class="urgency-icon">
                        <v-icon size="28" color="white">{{ getUrgencyIcon(rescueRequest.urgency_level) }}</v-icon>
                    </div>
                    <div class="urgency-info">
                        <span class="urgency-label">{{ rescueRequest.urgency_level || 'Emergency' }} Priority</span>
                        <span class="urgency-time">{{ getElapsedTime(rescueRequest.created_at) }}</span>
                    </div>
                </div>

                <!-- Person Card -->
                <div class="info-card person-card">
                    <div class="card-header">
                        <v-icon size="20" color="primary">mdi-account-alert</v-icon>
                        <span>Person in Need</span>
                    </div>
                    <div class="person-info">
                        <v-avatar 
                            size="56" 
                            :color="requesterProfilePicture && !isReportingForOthers ? 'transparent' : 'primary'"
                            class="person-avatar"
                            @click="requesterProfilePicture && !isReportingForOthers && openPhotoViewer(requesterProfilePicture, getRequesterFullName())"
                        >
                            <v-img 
                                v-if="requesterProfilePicture && !isReportingForOthers" 
                                :src="requesterProfilePicture" 
                                cover 
                            />
                            <span v-else class="text-h6 text-white">{{ getPersonInNeedInitials() }}</span>
                        </v-avatar>
                        <div class="person-details">
                            <h3 class="person-name">{{ getPersonInNeedName() }}</h3>
                            <p v-if="isReportingForOthers" class="person-reporter">
                                <v-icon size="14">mdi-account</v-icon>
                                Reported by: {{ rescueRequest.requester?.first_name }} {{ rescueRequest.requester?.last_name }}
                            </p>
                            <p v-else class="person-contact">
                                <v-icon size="14">mdi-phone</v-icon>
                                {{ rescueRequest.requester?.phone || rescueRequest.contact_number || 'No contact' }}
                            </p>
                        </div>
                        <v-btn
                            v-if="rescueRequest.requester || rescueRequest.user_id"
                            icon
                            size="small"
                            variant="tonal"
                            color="primary"
                            @click="showUserProfile = true"
                        >
                            <v-icon size="20">mdi-information</v-icon>
                        </v-btn>
                    </div>
                </div>

                <!-- Location Card -->
                <div class="info-card location-card">
                    <div class="card-header">
                        <v-icon size="20" color="error">mdi-map-marker</v-icon>
                        <span>Location</span>
                    </div>
                    <div class="location-info">
                        <div class="location-primary">
                            <span class="room-name">{{ rescueRequest.room?.room_name || 'Unknown Room' }}</span>
                        </div>
                        <div class="location-secondary">
                            <span>{{ rescueRequest.floor?.floor_name || 'Unknown Floor' }}</span>
                            <span class="separator">•</span>
                            <span>{{ rescueRequest.building?.name || 'Unknown Building' }}</span>
                        </div>
                    </div>
                    <v-btn
                        variant="tonal"
                        color="primary"
                        size="small"
                        class="map-btn"
                        @click="viewMap"
                    >
                        <v-icon start size="18">mdi-map</v-icon>
                        View Map
                    </v-btn>
                </div>

                <!-- Description Card (if exists) -->
                <div v-if="rescueRequest.description" class="info-card description-card">
                    <div class="card-header">
                        <v-icon size="20" color="orange">mdi-text-box</v-icon>
                        <span>Situation Details</span>
                    </div>
                    <p class="description-text">{{ rescueRequest.description }}</p>
                </div>

                <!-- Additional Info Card -->
                <div v-if="rescueRequest.people_count || rescueRequest.mobility_status || rescueRequest.injuries || hasMediaAttachments" class="info-card additional-card">
                    <div class="card-header">
                        <v-icon size="20" color="info">mdi-clipboard-list</v-icon>
                        <span>Additional Information</span>
                    </div>
                    <div class="chips-container">
                        <v-chip v-if="rescueRequest.people_count" size="small" variant="tonal" color="info">
                            <v-icon start size="16">mdi-account-group</v-icon>
                            {{ rescueRequest.people_count }} {{ rescueRequest.people_count > 1 ? 'people' : 'person' }}
                        </v-chip>
                        <v-chip v-if="rescueRequest.mobility_status" size="small" variant="tonal" :color="getMobilityColor(rescueRequest.mobility_status)">
                            <v-icon start size="16">mdi-wheelchair-accessibility</v-icon>
                            {{ formatMobility(rescueRequest.mobility_status) }}
                        </v-chip>
                        <v-chip v-if="rescueRequest.injuries" size="small" variant="tonal" color="error">
                            <v-icon start size="16">mdi-bandage</v-icon>
                            {{ rescueRequest.injuries }}
                        </v-chip>
                    </div>
                    
                    <!-- Media Attachments Section -->
                    <div v-if="hasMediaAttachments" class="media-section">
                        <div class="media-section-header">
                            <v-icon size="16" color="purple">mdi-image-multiple</v-icon>
                            <span>Attached Media ({{ mediaAttachments.length }})</span>
                        </div>
                        <div class="media-grid">
                            <div 
                                v-for="(media, index) in mediaAttachments" 
                                :key="index" 
                                class="media-item"
                                @click="openMediaViewer(media, index)"
                            >
                                <!-- Image Thumbnail -->
                                <template v-if="media.type === 'image'">
                                    <v-img
                                        :src="media.url"
                                        :alt="media.original_name || 'Attachment'"
                                        cover
                                        class="media-thumbnail"
                                    >
                                        <template v-slot:placeholder>
                                            <div class="d-flex align-center justify-center fill-height">
                                                <v-progress-circular indeterminate color="primary" size="24" />
                                            </div>
                                        </template>
                                    </v-img>
                                    <div class="media-overlay">
                                        <v-icon color="white" size="20">mdi-magnify-expand</v-icon>
                                    </div>
                                </template>
                                <!-- Video Thumbnail -->
                                <template v-else-if="media.type === 'video'">
                                    <div class="video-thumbnail">
                                        <video :src="media.url" muted preload="metadata" />
                                        <div class="video-play-overlay">
                                            <v-icon color="white" size="32">mdi-play-circle</v-icon>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-section">
                    <!-- Pending or Assigned Status - Accept & Start in one action -->
                    <div v-if="currentStatus === 'pending' || currentStatus === 'assigned'" class="action-buttons">
                        <v-btn
                            color="success"
                            size="x-large"
                            block
                            rounded="lg"
                            @click="acceptRescue"
                            :loading="updating"
                            class="main-action-btn"
                        >
                            <v-icon start size="24">mdi-run-fast</v-icon>
                            Accept & Start Rescue
                        </v-btn>
                    </div>

                    <!-- In Progress Status -->
                    <div v-else-if="currentStatus === 'in_progress'" class="action-buttons">
                        <v-btn
                            color="success"
                            size="x-large"
                            block
                            rounded="lg"
                            @click="showCompleteDialog = true"
                            :loading="updating"
                            class="main-action-btn safe-btn"
                        >
                            <v-icon start size="24">mdi-shield-check</v-icon>
                            Mark as Safe
                        </v-btn>
                        <div class="secondary-actions">
                            <v-btn
                                variant="outlined"
                                color="primary"
                                @click="openChat"
                                class="secondary-btn"
                            >
                                <v-icon start>mdi-message-text</v-icon>
                                Message User
                            </v-btn>
                            <v-btn
                                variant="outlined"
                                color="grey"
                                @click="viewMap"
                                class="secondary-btn"
                            >
                                <v-icon start>mdi-map-marker</v-icon>
                                View Map
                            </v-btn>
                        </div>
                    </div>

                    <!-- Completed Status -->
                    <div v-else-if="currentStatus === 'rescued' || currentStatus === 'safe' || currentStatus === 'completed'" class="action-buttons completed">
                        <div class="completed-banner">
                            <v-icon size="48" color="success">mdi-check-circle</v-icon>
                            <h3>Rescue Completed</h3>
                            <p>The person has been marked as safe.</p>
                        </div>
                        <v-btn
                            color="primary"
                            size="large"
                            block
                            variant="tonal"
                            rounded="lg"
                            @click="goBack"
                        >
                            <v-icon start>mdi-arrow-left</v-icon>
                            Back to Dashboard
                        </v-btn>
                    </div>
                </div>
            </div>
        </v-main>

        <!-- Mark as Safe Dialog -->
        <v-dialog v-model="showCompleteDialog" max-width="400">
            <v-card>
                <v-card-title class="text-h6">Mark as Safe</v-card-title>
                <v-card-text>
                    <p class="mb-4">Please confirm that the person has been rescued and is now safe.</p>
                    <v-textarea
                        v-model="completionNotes"
                        label="Notes (optional)"
                        placeholder="Add any notes about the rescue..."
                        rows="3"
                        variant="outlined"
                        density="comfortable"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showCompleteDialog = false">Cancel</v-btn>
                    <v-btn
                        color="success"
                        variant="flat"
                        @click="completeRescue"
                        :loading="updating"
                    >
                        Confirm Safe
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Cancel Dialog -->
        <v-dialog v-model="showCancelDialog" max-width="400">
            <v-card>
                <v-card-title class="text-h6">Cancel Rescue</v-card-title>
                <v-card-text>
                    <p class="mb-4">Are you sure you want to cancel this rescue? The request will be made available for other rescuers.</p>
                    <v-textarea
                        v-model="cancellationReason"
                        label="Reason for cancellation"
                        placeholder="Please provide a reason..."
                        rows="3"
                        variant="outlined"
                        density="comfortable"
                        :rules="[v => !!v || 'Reason is required']"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showCancelDialog = false">Keep Rescue</v-btn>
                    <v-btn
                        color="error"
                        variant="flat"
                        @click="cancelRescue"
                        :loading="updating"
                        :disabled="!cancellationReason"
                    >
                        Cancel Rescue
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- User Profile Dialog -->
        <v-dialog v-model="showUserProfile" max-width="400">
            <v-card>
                <v-card-title class="d-flex align-center">
                    <v-icon class="mr-2">mdi-account</v-icon>
                    User Profile
                </v-card-title>
                <v-divider />
                <v-card-text class="text-center py-4">
                    <v-avatar 
                        size="80" 
                        color="primary" 
                        class="mb-3"
                        :style="requesterProfilePicture ? 'cursor: pointer' : ''"
                        @click="requesterProfilePicture && openPhotoViewer(requesterProfilePicture, getRequesterFullName())"
                    >
                        <v-img v-if="requesterProfilePicture" :src="requesterProfilePicture" cover />
                        <span v-else class="text-h5 text-white">
                            {{ getRequesterInitials() }}
                        </span>
                    </v-avatar>
                    <p v-if="requesterProfilePicture" class="text-caption text-grey mb-2">Tap photo to enlarge</p>
                    <h3 class="text-h6 mb-1">
                        {{ rescueRequest?.requester?.first_name || rescueRequest?.first_name }} {{ rescueRequest?.requester?.last_name || rescueRequest?.last_name }}
                    </h3>
                    <p class="text-grey text-body-2 mb-3">
                        {{ rescueRequest?.requester?.email || rescueRequest?.email || 'No email' }}
                    </p>
                    
                    <v-list density="compact" class="text-left">
                        <v-list-item v-if="rescueRequest?.requester?.phone || rescueRequest?.contact_number">
                            <template v-slot:prepend>
                                <v-icon color="grey" size="20">mdi-phone</v-icon>
                            </template>
                            <v-list-item-title class="text-body-2">Phone</v-list-item-title>
                            <v-list-item-subtitle>{{ rescueRequest?.requester?.phone || rescueRequest?.contact_number }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item v-if="rescueRequest?.requester?.blood_type">
                            <template v-slot:prepend>
                                <v-icon color="red" size="20">mdi-blood-bag</v-icon>
                            </template>
                            <v-list-item-title class="text-body-2">Blood Type</v-list-item-title>
                            <v-list-item-subtitle>{{ rescueRequest?.requester?.blood_type }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item v-if="rescueRequest?.requester?.allergies">
                            <template v-slot:prepend>
                                <v-icon color="orange" size="20">mdi-allergy</v-icon>
                            </template>
                            <v-list-item-title class="text-body-2">Allergies</v-list-item-title>
                            <v-list-item-subtitle>{{ rescueRequest?.requester?.allergies }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item v-if="rescueRequest?.requester?.medical_conditions">
                            <template v-slot:prepend>
                                <v-icon color="blue" size="20">mdi-pill</v-icon>
                            </template>
                            <v-list-item-title class="text-body-2">Medical Conditions</v-list-item-title>
                            <v-list-item-subtitle>{{ rescueRequest?.requester?.medical_conditions }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item v-if="rescueRequest?.requester?.emergency_contact_name">
                            <template v-slot:prepend>
                                <v-icon color="error" size="20">mdi-phone-in-talk</v-icon>
                            </template>
                            <v-list-item-title class="text-body-2">Emergency Contact</v-list-item-title>
                            <v-list-item-subtitle>
                                {{ rescueRequest?.requester?.emergency_contact_name }}
                                <span v-if="rescueRequest?.requester?.emergency_contact_relation" class="text-grey">
                                    ({{ rescueRequest?.requester?.emergency_contact_relation }})
                                </span>
                            </v-list-item-subtitle>
                            <v-list-item-subtitle v-if="rescueRequest?.requester?.emergency_contact_phone" class="mt-1">
                                <v-icon size="12" class="mr-1">mdi-phone</v-icon>
                                {{ rescueRequest?.requester?.emergency_contact_phone }}
                            </v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card-text>
                <v-divider />
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showUserProfile = false">Close</v-btn>
                    <v-btn
                        v-if="rescueRequest?.requester?.emergency_contact_phone"
                        color="error"
                        variant="tonal"
                        prepend-icon="mdi-phone-alert"
                        @click="callEmergencyContact"
                        size="small"
                    >
                        Emergency
                    </v-btn>
                    <v-btn
                        v-if="rescueRequest?.requester?.phone || rescueRequest?.contact_number"
                        color="success"
                        variant="flat"
                        prepend-icon="mdi-phone"
                        @click="callUser"
                    >
                        Call
                    </v-btn>
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

        <!-- Media Viewer Dialog (for photos/videos from rescue request) -->
        <v-dialog v-model="showMediaViewer" max-width="600" content-class="media-viewer-dialog">
            <v-card class="bg-black rounded-xl">
                <v-card-title class="d-flex align-center justify-space-between text-white pa-3">
                    <div class="d-flex align-center">
                        <v-icon :color="currentMediaItem?.type === 'video' ? 'red' : 'blue'" class="mr-2">
                            {{ currentMediaItem?.type === 'video' ? 'mdi-video' : 'mdi-image' }}
                        </v-icon>
                        <span class="text-body-1">{{ currentMediaItem?.original_name || 'Media' }}</span>
                    </div>
                    <div class="d-flex align-center">
                        <span class="text-caption text-grey mr-3">{{ currentMediaIndex + 1 }} / {{ mediaAttachments.length }}</span>
                        <v-btn icon variant="text" color="white" size="small" @click="showMediaViewer = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </div>
                </v-card-title>
                <v-divider color="grey-darken-3" />
                <v-card-text class="pa-0 d-flex justify-center align-center media-viewer-content">
                    <!-- Image Viewer -->
                    <template v-if="currentMediaItem?.type === 'image'">
                        <v-img
                            :src="currentMediaItem?.url"
                            max-height="500"
                            contain
                            class="bg-black"
                        >
                            <template v-slot:placeholder>
                                <div class="d-flex align-center justify-center fill-height">
                                    <v-progress-circular indeterminate color="primary" size="48" />
                                </div>
                            </template>
                        </v-img>
                    </template>
                    <!-- Video Viewer -->
                    <template v-else-if="currentMediaItem?.type === 'video'">
                        <video
                            ref="mediaVideoPlayer"
                            :src="currentMediaItem?.url"
                            controls
                            class="media-video-player"
                            preload="metadata"
                        />
                    </template>
                </v-card-text>
                <!-- Navigation Arrows -->
                <div v-if="mediaAttachments.length > 1" class="media-nav-arrows">
                    <v-btn 
                        icon 
                        variant="flat" 
                        color="white" 
                        size="small"
                        class="nav-arrow left"
                        :disabled="currentMediaIndex === 0"
                        @click="navigateMedia(-1)"
                    >
                        <v-icon>mdi-chevron-left</v-icon>
                    </v-btn>
                    <v-btn 
                        icon 
                        variant="flat" 
                        color="white" 
                        size="small"
                        class="nav-arrow right"
                        :disabled="currentMediaIndex === mediaAttachments.length - 1"
                        @click="navigateMedia(1)"
                    >
                        <v-icon>mdi-chevron-right</v-icon>
                    </v-btn>
                </div>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000">
            {{ snackbar.message }}
        </v-snackbar>
        
        <!-- Bottom Navigation (Mobile/Tablet only) -->
        <RescuerBottomNav :notification-count="0" :message-count="unreadMessageCount" />
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { apiFetch, getProfilePictureUrl, getUnreadMessageCount } from '@/Composables/useApi';
import RescuerBottomNav from '@/Components/Pages/Rescuer/Menu/RescuerBottomNav.vue';

const props = defineProps({
    rescueId: {
        type: [String, Number],
        default: null,
    },
});

// Using apiFetch directly instead of useApi composable

// State
const loading = ref(true);
const updating = ref(false);
const rescueRequest = ref(null);
const currentStatus = ref('');
const statusTimestamps = ref({});
const showCompleteDialog = ref(false);
const showCancelDialog = ref(false);
const showUserProfile = ref(false);
const completionNotes = ref('');
const cancellationReason = ref('');
const pollingInterval = ref(null);
const unreadMessageCount = ref(0);

const snackbar = ref({
    show: false,
    message: '',
    color: 'success',
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

// Media Viewer State (for rescue request attachments)
const showMediaViewer = ref(false);
const currentMediaIndex = ref(0);
const mediaVideoPlayer = ref(null);

// Computed property for media attachments
const mediaAttachments = computed(() => {
    const attachments = rescueRequest.value?.media_attachments;
    if (!attachments) return [];
    // Handle both array and JSON string formats
    if (typeof attachments === 'string') {
        try {
            return JSON.parse(attachments);
        } catch (e) {
            return [];
        }
    }
    return Array.isArray(attachments) ? attachments : [];
});

// Check if there are media attachments
const hasMediaAttachments = computed(() => {
    return mediaAttachments.value && mediaAttachments.value.length > 0;
});

// Get current media item being viewed
const currentMediaItem = computed(() => {
    return mediaAttachments.value[currentMediaIndex.value] || null;
});

// Open media viewer
const openMediaViewer = (media, index) => {
    currentMediaIndex.value = index;
    showMediaViewer.value = true;
};

// Navigate through media
const navigateMedia = (direction) => {
    const newIndex = currentMediaIndex.value + direction;
    if (newIndex >= 0 && newIndex < mediaAttachments.value.length) {
        // Pause video if playing
        if (mediaVideoPlayer.value) {
            mediaVideoPlayer.value.pause();
        }
        currentMediaIndex.value = newIndex;
    }
};

// Computed property for requester's profile picture
const requesterProfilePicture = computed(() => {
    const requester = rescueRequest.value?.requester;
    const picturePath = requester?.profile_picture || requester?.profile_photo;
    if (!picturePath) return null;
    return getProfilePictureUrl(picturePath);
});

// Check if there's medical information available
const hasMedicalInfo = computed(() => {
    const requester = rescueRequest.value?.requester;
    return requester?.blood_type || requester?.allergies || requester?.medical_conditions;
});

// Check if there's emergency contact information available
const hasEmergencyContact = computed(() => {
    const requester = rescueRequest.value?.requester;
    return requester?.emergency_contact_name || requester?.emergency_contact_phone;
});

// Check if the user is reporting for someone else (form has name filled in)
const isReportingForOthers = computed(() => {
    // If firstName or lastName is filled in the form, it means reporting for others
    // Check both camelCase (as stored in DB) and snake_case (for API normalization)
    const formFirstName = rescueRequest.value?.firstName || rescueRequest.value?.first_name;
    const formLastName = rescueRequest.value?.lastName || rescueRequest.value?.last_name;
    return !!(formFirstName || formLastName);
});

// Get the person in need name (form name if reporting for others, requester name if self)
const getPersonInNeedName = () => {
    if (isReportingForOthers.value) {
        // Use the name from the form (reporting for someone else)
        const firstName = rescueRequest.value?.firstName || rescueRequest.value?.first_name || '';
        const lastName = rescueRequest.value?.lastName || rescueRequest.value?.last_name || '';
        return `${firstName} ${lastName}`.trim() || 'Unknown Person';
    } else {
        // Use the requester's name (reporting for self)
        const firstName = rescueRequest.value?.requester?.first_name || '';
        const lastName = rescueRequest.value?.requester?.last_name || '';
        return `${firstName} ${lastName}`.trim() || 'Unknown User';
    }
};

// Get initials for person in need
const getPersonInNeedInitials = () => {
    if (isReportingForOthers.value) {
        const firstName = rescueRequest.value?.firstName || rescueRequest.value?.first_name || '';
        const lastName = rescueRequest.value?.lastName || rescueRequest.value?.last_name || '';
        if (firstName && lastName) {
            return `${firstName[0]}${lastName[0]}`.toUpperCase();
        }
        if (firstName) return firstName.substring(0, 2).toUpperCase();
        return '?';
    } else {
        return getRequesterInitials();
    }
};

// Get requester initials for avatar fallback
const getRequesterInitials = () => {
    const firstName = rescueRequest.value?.requester?.first_name || rescueRequest.value?.first_name || '';
    const lastName = rescueRequest.value?.requester?.last_name || rescueRequest.value?.last_name || '';
    if (firstName && lastName) {
        return `${firstName[0]}${lastName[0]}`.toUpperCase();
    }
    if (firstName) return firstName.substring(0, 2).toUpperCase();
    return '?';
};

// Get requester full name
const getRequesterFullName = () => {
    const firstName = rescueRequest.value?.requester?.first_name || rescueRequest.value?.first_name || '';
    const lastName = rescueRequest.value?.requester?.last_name || rescueRequest.value?.last_name || '';
    return `${firstName} ${lastName}`.trim() || 'User';
};

const statusSteps = [
    { value: 'pending', label: 'Request Created' },
    { value: 'assigned', label: 'Rescuer Assigned' },
    { value: 'in_progress', label: 'Rescue In Progress' },
    { value: 'rescued', label: 'Rescue Completed' },
];

const statusOrder = ['pending', 'assigned', 'in_progress', 'rescued'];

// Methods
const fetchRescueDetails = async () => {
    try {
        const id = props.rescueId || localStorage.getItem('lastRescueRequestId');
        if (!id) {
            showSnackbar('No rescue ID found', 'error');
            setTimeout(() => router.visit('/rescuer/dashboard'), 2000);
            return;
        }

        const response = await apiFetch(`/api/rescue-requests/${id}`, { method: 'GET' });
        console.log('Rescue details response:', response); // Debug log
        
        // Handle both wrapped and direct response formats
        const data = response.data || response;
        
        if (data) {
            rescueRequest.value = data;
            currentStatus.value = data.status || 'pending';
            
            // Build status timestamps
            statusTimestamps.value = {
                pending: data.created_at,
                accepted: data.accepted_at,
                en_route: data.en_route_at,
                on_scene: data.on_scene_at,
                rescued: data.rescued_at,
            };
            
            console.log('Rescue request loaded:', rescueRequest.value); // Debug log
        }
    } catch (error) {
        console.error('Error fetching rescue details:', error);
        showSnackbar('Failed to load rescue details', 'error');
    } finally {
        loading.value = false;
    }
};

const acceptRescue = async () => {
    updating.value = true;
    try {
        // Get rescuer ID from localStorage
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        const rescuerId = userData.id;
        
        if (!rescuerId) {
            showSnackbar('Rescuer ID not found', 'error');
            return;
        }

        // Check if rescuer already has an active assignment
        const checkResponse = await apiFetch(`/api/rescue-requests/rescuer/${rescuerId}`, { method: 'GET' });
        const checkData = checkResponse.data || checkResponse;
        const activeRequests = Array.isArray(checkData) ? checkData : [];
        const hasOtherActiveAssignment = activeRequests.some((r) => 
            (r.status === 'assigned' || r.status === 'in_progress') &&
            r.id !== rescueRequest.value?.id &&
            (String(r.assigned_rescuer) === String(rescuerId) || String(r.rescuer_id) === String(rescuerId))
        );

        if (hasOtherActiveAssignment) {
            showSnackbar('You are only allowed to accept requests one at a time.', 'warning');
            updating.value = false;
            return;
        }

        // Accept and start rescue in one action - go directly to in_progress
        const response = await apiFetch(`/api/rescue-requests/${rescueRequest.value.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                status: 'in_progress',
                assigned_rescuer: rescuerId 
            }),
        });

        const data = response.data || response;
        console.log('Accept rescue response:', data); // Debug log
        
        if (data) {
            // Set status to 'in_progress' - combining accept and start
            currentStatus.value = 'in_progress';
            rescueRequest.value = { ...rescueRequest.value, ...data, status: 'in_progress' };
            statusTimestamps.value['in_progress'] = new Date().toISOString();
            showSnackbar('Rescue accepted and started!', 'success');
            
            // Store the rescue ID for later reference
            localStorage.setItem('lastRescueRequestId', rescueRequest.value.id.toString());
        }
    } catch (error) {
        console.error('Error accepting rescue:', error);
        showSnackbar('Failed to accept rescue', 'error');
    } finally {
        updating.value = false;
    }
};

// Start rescue - assigns rescuer and starts in one action
const startRescue = async () => {
    updating.value = true;
    try {
        // Get rescuer ID from localStorage
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        const rescuerId = userData.id;
        
        if (!rescuerId) {
            showSnackbar('Rescuer ID not found', 'error');
            return;
        }

        const response = await apiFetch(`/api/rescue-requests/${rescueRequest.value.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                status: 'in_progress',
                assigned_rescuer: rescuerId 
            }),
        });

        const data = response.data || response;
        if (data) {
            currentStatus.value = 'in_progress';
            rescueRequest.value = data;
            statusTimestamps.value['in_progress'] = new Date().toISOString();
            
            // Store the rescue ID for later reference
            localStorage.setItem('lastRescueRequestId', rescueRequest.value.id.toString());
            
            showSnackbar('Rescue started! You can now proceed to the location.', 'success');
        }
    } catch (error) {
        console.error('Error starting rescue:', error);
        showSnackbar('Failed to start rescue', 'error');
    } finally {
        updating.value = false;
    }
};

const updateStatus = async (newStatus) => {
    updating.value = true;
    try {
        const response = await apiFetch(`/api/rescue-requests/${rescueRequest.value.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: newStatus }),
        });

        const data = response.data || response;
        if (data) {
            currentStatus.value = newStatus;
            rescueRequest.value = data;
            statusTimestamps.value[newStatus] = new Date().toISOString();
            showSnackbar(`Status updated to ${formatStatus(newStatus)}`, 'success');
        }
    } catch (error) {
        console.error('Error updating status:', error);
        showSnackbar('Failed to update status', 'error');
    } finally {
        updating.value = false;
    }
};

const completeRescue = async () => {
    updating.value = true;
    try {
        const response = await apiFetch(`/api/rescue-requests/${rescueRequest.value.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                status: 'safe',
                additional_info: completionNotes.value,
            }),
        });

        const data = response.data || response;
        if (data) {
            showSnackbar('Person marked as safe!', 'success');
            showCompleteDialog.value = false;
            localStorage.removeItem('lastRescueRequestId');
            setTimeout(() => router.visit('/rescuer/dashboard'), 1500);
        }
    } catch (error) {
        console.error('Error completing rescue:', error);
        showSnackbar('Failed to mark as safe', 'error');
    } finally {
        updating.value = false;
    }
};

const cancelRescue = async () => {
    if (!cancellationReason.value) return;

    updating.value = true;
    try {
        const response = await apiFetch(`/api/rescue-requests/${rescueRequest.value.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                status: 'cancelled',
                additional_info: cancellationReason.value,
                assigned_rescuer: null,
            }),
        });

        const data = response.data || response;
        if (data) {
            showSnackbar('Rescue cancelled', 'warning');
            showCancelDialog.value = false;
            localStorage.removeItem('lastRescueRequestId');
            setTimeout(() => router.visit('/rescuer/dashboard'), 1500);
        }
    } catch (error) {
        console.error('Error cancelling rescue:', error);
        showSnackbar('Failed to cancel rescue', 'error');
    } finally {
        updating.value = false;
    }
};

const viewMap = () => {
    if (rescueRequest.value) {
        router.visit(`/rescuer/map/${rescueRequest.value.id}`);
    }
};

const openChat = () => {
    // Use rescue-chat route which will get or create conversation
    if (rescueRequest.value?.id) {
        router.visit(`/rescuer/rescue-chat/${rescueRequest.value.id}?from=active-rescue`);
    } else {
        showSnackbar('No rescue request found', 'warning');
    }
};

const callUser = () => {
    if (rescueRequest.value?.user?.contact_number) {
        window.location.href = `tel:${rescueRequest.value.user.contact_number}`;
    } else if (rescueRequest.value?.requester?.phone) {
        window.location.href = `tel:${rescueRequest.value.requester.phone}`;
    }
};

const callEmergencyContact = () => {
    if (rescueRequest.value?.requester?.emergency_contact_phone) {
        window.location.href = `tel:${rescueRequest.value.requester.emergency_contact_phone}`;
    }
};

const goBack = () => {
    router.visit('/rescuer/dashboard');
};

// Helper methods
const getUrgencyIcon = (level) => {
    const icons = {
        'low': 'mdi-alert-circle-outline',
        'medium': 'mdi-alert',
        'high': 'mdi-alert-octagon',
        'critical': 'mdi-fire-alert',
    };
    return icons[level] || 'mdi-alert-circle';
};

const getUrgencyColor = (level) => {
    const colors = {
        'low': 'success',
        'medium': 'warning',
        'high': 'orange',
        'critical': 'error',
    };
    return colors[level] || 'grey';
};

const getStatusColor = (status) => {
    const colors = {
        'pending': 'warning',
        'assigned': 'info',
        'in_progress': 'primary', 
        'rescued': 'success',
        'safe': 'success',
        'cancelled': 'error',
    };
    return colors[status] || 'grey';
};

const getStatusChipColor = (status) => {
    const colors = {
        'pending': 'orange',
        'assigned': 'blue',
        'in_progress': 'primary', 
        'rescued': 'success',
        'safe': 'success',
        'cancelled': 'error',
    };
    return colors[status] || 'grey';
};

const getStatusIcon = (status) => {
    const icons = {
        'pending': 'mdi-clock-outline',
        'assigned': 'mdi-account-check',
        'in_progress': 'mdi-run-fast', 
        'rescued': 'mdi-check-circle',
        'safe': 'mdi-shield-check',
        'cancelled': 'mdi-close-circle',
    };
    return icons[status] || 'mdi-help-circle';
};

const formatStatus = (status) => {
    const labels = {
        'pending': 'Pending',
        'assigned': 'Assigned',
        'in_progress': 'In Progress',
        'rescued': 'Rescued',
        'safe': 'Safe',
        'cancelled': 'Cancelled',
    };
    return labels[status] || status;
};

const getMobilityColor = (status) => {
    if (status === 'immobile' || status === 'injured') return 'error';
    if (status === 'limited') return 'warning';
    return 'success';
};

const formatMobility = (status) => {
    const labels = {
        'mobile': 'Mobile',
        'limited': 'Limited Mobility',
        'immobile': 'Immobile',
        'injured': 'Injured',
    };
    return labels[status] || status;
};

const getStepColor = (step) => {
    const stepIndex = statusOrder.indexOf(step);
    const currentIndex = statusOrder.indexOf(currentStatus.value);
    
    if (stepIndex < currentIndex) return 'success';
    if (stepIndex === currentIndex) return 'primary';
    return 'grey-lighten-1';
};

const getStepIcon = (step) => {
    const stepIndex = statusOrder.indexOf(step);
    const currentIndex = statusOrder.indexOf(currentStatus.value);
    
    if (stepIndex < currentIndex) return 'mdi-check';
    if (stepIndex === currentIndex) return 'mdi-circle';
    return 'mdi-circle-outline';
};

const isCurrentOrPastStatus = (status) => {
    const stepIndex = statusOrder.indexOf(status);
    const currentIndex = statusOrder.indexOf(currentStatus.value);
    return stepIndex <= currentIndex;
};

const getElapsedTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} min ago`;
    
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    
    const diffDays = Math.floor(diffHours / 24);
    return `${diffDays}d ago`;
};

const formatTimestamp = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const showSnackbar = (message, color = 'success') => {
    snackbar.value = { show: true, message, color };
};

// Fetch unread message count
const fetchUnreadMessageCount = async () => {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    const userId = userData?.id;
    if (!userId) return;
    try {
        unreadMessageCount.value = await getUnreadMessageCount(userId);
    } catch (error) {
        console.error('Failed to fetch unread message count:', error);
    }
};

// Lifecycle
onMounted(async () => {
    fetchRescueDetails();
    await fetchUnreadMessageCount();
    
    // Poll for updates every 10 seconds
    pollingInterval.value = setInterval(async () => {
        fetchRescueDetails();
        await fetchUnreadMessageCount();
    }, 10000);
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
});
</script>

<style scoped>
/* Header */
.rescue-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: linear-gradient(135deg, #3674B5 0%, #2d5f96 100%);
    padding: env(safe-area-inset-top, 0) 0 0 0;
    box-shadow: 0 2px 12px rgba(54, 116, 181, 0.3);
}

.header-content {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    gap: 12px;
}

.back-btn {
    color: white;
    transition: transform 0.2s ease;
}

.back-btn:hover {
    transform: translateX(-2px);
}

.header-title {
    flex: 1;
}

.header-title h1 {
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
    margin: 0;
    letter-spacing: 0.3px;
}

.header-title p {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.85);
    margin: 0;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.status-chip {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.65rem;
    letter-spacing: 0.5px;
}

/* Main Content */
.rescue-main {
    padding-bottom: 100px;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    min-height: 100vh;
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    text-align: center;
    padding: 24px;
}

.empty-state h3 {
    margin-top: 16px;
    color: #64748b;
    font-weight: 600;
}

.empty-state p {
    color: #94a3b8;
}

.rescue-content {
    padding: 16px;
    max-width: 600px;
    margin: 0 auto;
}

/* Urgency Banner */
.urgency-banner {
    display: flex;
    align-items: center;
    padding: 18px 20px;
    border-radius: 20px;
    margin-bottom: 16px;
    gap: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    position: relative;
    overflow: hidden;
}

.urgency-banner::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 50%);
    pointer-events: none;
}

.urgency-banner.urgency-critical {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.urgency-banner.urgency-high {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
}

.urgency-banner.urgency-medium {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.urgency-banner.urgency-low {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

.urgency-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.urgency-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.urgency-label {
    font-size: 1.05rem;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

.urgency-time {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
}

/* Info Cards */
.info-card {
    background: white;
    border-radius: 18px;
    padding: 18px;
    margin-bottom: 14px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.04);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.info-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

/* Person Card */
.person-card {
    border-left: 4px solid #3674B5;
}

.person-info {
    display: flex;
    align-items: center;
    gap: 14px;
}

.person-avatar {
    cursor: pointer;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(54, 116, 181, 0.2);
    transition: transform 0.2s ease;
}

.person-avatar:hover {
    transform: scale(1.05);
}

.person-details {
    flex: 1;
    min-width: 0;
}

.person-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 6px 0;
}

.person-contact, .person-reporter {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: #64748b;
    margin: 0;
}

/* Location Card */
.location-card {
    border-left: 4px solid #ef4444;
}

.location-info {
    margin-bottom: 14px;
}

.location-primary {
    margin-bottom: 6px;
}

.room-name {
    font-size: 1.15rem;
    font-weight: 700;
    color: #1e293b;
}

.location-secondary {
    font-size: 0.9rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 8px;
}

.separator {
    color: #cbd5e1;
}

.map-btn {
    width: 100%;
    margin-top: 4px;
    font-weight: 600;
    letter-spacing: 0.3px;
}

/* Description Card */
.description-card {
    border-left: 4px solid #f59e0b;
}

.description-text {
    font-size: 0.95rem;
    color: #475569;
    line-height: 1.6;
    margin: 0;
}

/* Additional Info Card */
.additional-card {
    border-left: 4px solid #3b82f6;
}

.chips-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

/* Media Section within Additional Info */
.media-section {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
}

.media-section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

/* Media Attachments */
.medical-card {
    border-left: 4px solid #e53935;
}

.medical-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.medical-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.medical-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.medical-icon.blood {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.medical-icon.allergy {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
}

.medical-icon.condition {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.medical-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.medical-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.medical-value {
    font-size: 0.95rem;
    color: #1e293b;
    font-weight: 500;
}

.medical-value.warning {
    color: #ea580c;
}

/* Action Section */
.action-section {
    margin-top: 28px;
    padding-top: 8px;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.main-action-btn {
    height: 58px !important;
    font-size: 1.05rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.5px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    border-radius: 16px !important;
    transition: all 0.3s ease;
}

.main-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.safe-btn {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
}

.secondary-actions {
    display: flex;
    gap: 12px;
}

.secondary-btn {
    flex: 1;
    height: 48px !important;
    border-radius: 14px !important;
    font-weight: 600 !important;
    transition: all 0.2s ease;
}

.secondary-btn:hover {
    transform: translateY(-1px);
}

/* Completed State */
.action-buttons.completed {
    text-align: center;
}

.completed-banner {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-radius: 20px;
    padding: 36px 24px;
    margin-bottom: 18px;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.completed-banner h3 {
    margin: 14px 0 10px 0;
    font-size: 1.35rem;
    color: #166534;
    font-weight: 700;
}

.completed-banner p {
    margin: 0;
    color: #22c55e;
    font-size: 0.95rem;
    font-weight: 500;
}

/* Media Attachments */
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 10px;
}

.media-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    background: #f5f5f5;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.media-item:hover {
    transform: scale(1.03);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.media-item:active {
    transform: scale(0.98);
}

.media-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.media-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.media-item:hover .media-overlay {
    opacity: 1;
}

.video-thumbnail {
    position: relative;
    width: 100%;
    height: 100%;
    background: #1a1a1a;
}

.video-thumbnail video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.video-play-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Media Viewer Dialog */
.media-viewer-content {
    min-height: 300px;
    max-height: 500px;
    background: #000;
}

.media-video-player {
    width: 100%;
    max-height: 500px;
    background: #000;
}

.media-nav-arrows {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    transform: translateY(-50%);
    display: flex;
    justify-content: space-between;
    padding: 0 8px;
    pointer-events: none;
}

.nav-arrow {
    pointer-events: auto;
    opacity: 0.8;
    transition: opacity 0.2s ease;
}

.nav-arrow:hover {
    opacity: 1;
}

.nav-arrow:disabled {
    opacity: 0.3;
}

/* Responsive */
@media (max-width: 600px) {
    .rescue-content {
        padding: 14px;
    }
    
    .urgency-banner {
        padding: 16px;
        border-radius: 16px;
    }
    
    .urgency-label {
        font-size: 0.95rem;
    }
    
    .info-card {
        padding: 16px;
        border-radius: 16px;
    }
    
    .main-action-btn {
        height: 54px !important;
        font-size: 1rem !important;
    }
    
    .secondary-actions {
        flex-direction: column;
    }
    
    .secondary-btn {
        width: 100%;
    }
    
    .media-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
}

/* Desktop visibility */
@media (max-width: 1024px) {
    .desktop-only {
        display: none !important;
    }
}

/* Smooth scrolling */
.rescue-main {
    scroll-behavior: smooth;
}

/* Focus states for accessibility */
.main-action-btn:focus-visible,
.secondary-btn:focus-visible {
    outline: 3px solid rgba(54, 116, 181, 0.5);
    outline-offset: 2px;
}
</style>
