<template>
    <v-app class="bg-user-gradient-light">
        <!-- App Bar -->
        <v-app-bar color="primary" density="comfortable">
            <v-btn icon @click="goBack">
                <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            <v-app-bar-title>Active Rescue</v-app-bar-title>
            <v-spacer />
            <v-chip
                v-if="currentStatus"
                :color="getStatusColor(currentStatus)"
                variant="flat"
                size="small"
            >
                {{ formatStatus(currentStatus) }}
            </v-chip>
        </v-app-bar>

        <!-- Main Content -->
        <v-main>
            <div v-if="loading" class="d-flex justify-center align-center" style="min-height: 60vh;">
                <v-progress-circular indeterminate color="primary" size="64" />
            </div>

            <div v-else-if="!rescueRequest" class="pa-4">
                <v-alert type="info" variant="tonal">
                    No active rescue found. You will be redirected to the dashboard.
                </v-alert>
            </div>

            <div v-else class="pa-4">
                <!-- Emergency Info Card -->
                <v-card class="mb-4" elevation="2">
                    <v-card-item>
                        <template v-slot:prepend>
                            <v-avatar :color="getUrgencyColor(rescueRequest.urgency_level)" size="56">
                                <v-icon size="28" color="white">{{ getUrgencyIcon(rescueRequest.urgency_level) }}</v-icon>
                            </v-avatar>
                        </template>
                        <v-card-title class="text-h6">
                            {{ rescueRequest.urgency_level || 'Emergency Request' }}
                        </v-card-title>
                        <v-card-subtitle>
                            Code: {{ rescueRequest.rescue_code }} • {{ getElapsedTime(rescueRequest.created_at) }}
                        </v-card-subtitle>
                    </v-card-item>

                    <v-divider />

                    <v-card-text>
                        <!-- Location Section -->
                        <div class="mb-4">
                            <div class="text-caption text-grey mb-1">LOCATION</div>
                            <div class="d-flex align-center mb-2">
                                <v-icon size="20" color="primary" class="mr-2">mdi-map-marker</v-icon>
                                <span class="text-body-1 font-weight-medium">
                                    {{ rescueRequest.room?.room_name || 'Unknown Room' }}
                                </span>
                            </div>
                            <div class="text-body-2 text-grey-darken-1 ml-7">
                                {{ rescueRequest.floor?.floor_name || 'Unknown Floor' }}, 
                                {{ rescueRequest.building?.name || 'Unknown Building' }}
                            </div>
                        </div>

                        <!-- Person In Need -->
                        <div class="mb-4">
                            <div class="text-caption text-grey mb-1">PERSON IN NEED</div>
                            <div class="d-flex align-center">
                                <v-avatar 
                                    size="40" 
                                    color="grey-lighten-2" 
                                    class="mr-3"
                                    :style="requesterProfilePicture && !isReportingForOthers ? 'cursor: pointer' : ''"
                                    @click="requesterProfilePicture && !isReportingForOthers && openPhotoViewer(requesterProfilePicture, getRequesterFullName())"
                                >
                                    <v-img 
                                        v-if="requesterProfilePicture && !isReportingForOthers" 
                                        :src="requesterProfilePicture" 
                                        cover 
                                    />
                                    <span v-else class="text-subtitle-1 text-grey-darken-1">
                                        {{ getPersonInNeedInitials() }}
                                    </span>
                                </v-avatar>
                                <div>
                                    <div class="text-body-1 font-weight-medium">
                                        {{ getPersonInNeedName() }}
                                    </div>
                                    <div v-if="isReportingForOthers" class="text-body-2 text-grey">
                                        Reported by: {{ rescueRequest.requester?.first_name }} {{ rescueRequest.requester?.last_name }}
                                    </div>
                                    <div v-else class="text-body-2 text-grey">
                                        {{ rescueRequest.requester?.phone || rescueRequest.contact_number || 'No contact' }}
                                    </div>
                                </div>
                                <v-spacer />
                                <v-btn
                                    v-if="rescueRequest.requester || rescueRequest.user_id"
                                    icon
                                    size="small"
                                    variant="text"
                                    @click="showUserProfile = true"
                                >
                                    <v-icon>mdi-information-outline</v-icon>
                                </v-btn>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="rescueRequest.description" class="mb-4">
                            <div class="text-caption text-grey mb-1">DESCRIPTION</div>
                            <div class="text-body-2">{{ rescueRequest.description }}</div>
                        </div>

                        <!-- Additional Details -->
                        <div v-if="rescueRequest.people_count || rescueRequest.mobility_status">
                            <div class="text-caption text-grey mb-2">ADDITIONAL INFO</div>
                            <div class="d-flex flex-wrap ga-2">
                                <v-chip v-if="rescueRequest.people_count" size="small" variant="tonal" color="info">
                                    <v-icon start size="16">mdi-account-group</v-icon>
                                    {{ rescueRequest.people_count }} {{ rescueRequest.people_count > 1 ? 'people' : 'person' }}
                                </v-chip>
                                <v-chip v-if="rescueRequest.mobility_status" size="small" variant="tonal" :color="getMobilityColor(rescueRequest.mobility_status)">
                                    <v-icon start size="16">mdi-wheelchair-accessibility</v-icon>
                                    {{ formatMobility(rescueRequest.mobility_status) }}
                                </v-chip>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>

                <!-- Status Progress -->
                <!-- <v-card class="mb-4" elevation="2">
                    <v-card-title class="text-subtitle-1">
                        <v-icon start>mdi-progress-check</v-icon>
                        Rescue Progress
                    </v-card-title>
                    <v-card-text>
                        <v-timeline side="end" density="compact">
                            <v-timeline-item
                                v-for="status in statusSteps"
                                :key="status.value"
                                :dot-color="getStepColor(status.value)"
                                :icon="getStepIcon(status.value)"
                                size="small"
                            >
                                <div class="d-flex align-center justify-space-between">
                                    <div>
                                        <div :class="['text-body-2', isCurrentOrPastStatus(status.value) ? 'font-weight-medium' : 'text-grey']">
                                            {{ status.label }}
                                        </div>
                                        <div v-if="statusTimestamps[status.value]" class="text-caption text-grey">
                                            {{ formatTimestamp(statusTimestamps[status.value]) }}
                                        </div>
                                    </div>
                                    <v-icon v-if="isCurrentOrPastStatus(status.value)" color="success" size="16">
                                        mdi-check-circle
                                    </v-icon>
                                </div>
                            </v-timeline-item>
                        </v-timeline>
                    </v-card-text>
                </v-card> -->

                <!-- Status Update Buttons -->
                <div class="d-flex flex-column ga-3">
                    <v-btn
                        v-if="currentStatus === 'pending' || currentStatus === 'assigned'"
                        color="primary"
                        size="large"
                        block
                        @click="startRescue"
                        :loading="updating"
                    >
                        <v-icon start>mdi-run</v-icon>
                        Start Rescue
                    </v-btn>

                    <v-btn
                        v-if="currentStatus === 'in_progress'"
                        color="success"
                        size="large"
                        block
                        @click="showCompleteDialog = true"
                        :loading="updating"
                    >
                        <v-icon start>mdi-check-circle</v-icon>
                        Complete Rescue
                    </v-btn>
                </div>
            </div>
        </v-main>

        <!-- Complete Rescue Dialog -->
        <v-dialog v-model="showCompleteDialog" max-width="400">
            <v-card>
                <v-card-title class="text-h6">Complete Rescue</v-card-title>
                <v-card-text>
                    <p class="mb-4">Please confirm that the rescue operation has been completed successfully.</p>
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
                        Confirm Complete
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
                                <span v-if="rescueRequest?.requester?.emergency_contact_phone">
                                    ({{ rescueRequest?.requester?.emergency_contact_phone }})
                                </span>
                            </v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card-text>
                <v-divider />
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showUserProfile = false">Close</v-btn>
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

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000">
            {{ snackbar.message }}
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { apiFetch, getProfilePictureUrl } from '@/Composables/useApi';

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

// Computed property for requester's profile picture
const requesterProfilePicture = computed(() => {
    const requester = rescueRequest.value?.requester;
    const picturePath = requester?.profile_picture || requester?.profile_photo;
    if (!picturePath) return null;
    return getProfilePictureUrl(picturePath);
});

// Check if the user is reporting for someone else (form has name filled in)
const isReportingForOthers = computed(() => {
    // If first_name or last_name is filled in the form, it means reporting for others
    const formFirstName = rescueRequest.value?.first_name;
    const formLastName = rescueRequest.value?.last_name;
    return !!(formFirstName || formLastName);
});

// Get the person in need name (form name if reporting for others, requester name if self)
const getPersonInNeedName = () => {
    if (isReportingForOthers.value) {
        // Use the name from the form (reporting for someone else)
        const firstName = rescueRequest.value?.first_name || '';
        const lastName = rescueRequest.value?.last_name || '';
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
        const firstName = rescueRequest.value?.first_name || '';
        const lastName = rescueRequest.value?.last_name || '';
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

        const response = await apiFetch(`/api/rescue-requests/${rescueRequest.value.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                status: 'assigned',
                assigned_rescuer: rescuerId 
            }),
        });

        const data = response.data || response;
        if (data) {
            currentStatus.value = 'assigned';
            rescueRequest.value = data;
            statusTimestamps.value['assigned'] = new Date().toISOString();
            showSnackbar('Rescue accepted successfully!', 'success');
            
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
            
            showSnackbar('Rescue started! Redirecting to dashboard...', 'success');
            
            // Redirect to dashboard after a short delay - rescue can be managed in "In Progress" tab
            setTimeout(() => {
                router.visit('/rescuer/dashboard');
            }, 1500);
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
                status: 'rescued',
                additional_info: completionNotes.value,
            }),
        });

        const data = response.data || response;
        if (data) {
            showSnackbar('Rescue completed successfully!', 'success');
            showCompleteDialog.value = false;
            localStorage.removeItem('lastRescueRequestId');
            setTimeout(() => router.visit('/rescuer/dashboard'), 1500);
        }
    } catch (error) {
        console.error('Error completing rescue:', error);
        showSnackbar('Failed to complete rescue', 'error');
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
        router.visit(`/rescuer/rescue-chat/${rescueRequest.value.id}`);
    } else {
        showSnackbar('No rescue request found', 'warning');
    }
};

const callUser = () => {
    if (rescueRequest.value?.user?.contact_number) {
        window.location.href = `tel:${rescueRequest.value.user.contact_number}`;
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

// Lifecycle
onMounted(() => {
    fetchRescueDetails();
    
    // Poll for updates every 10 seconds
    pollingInterval.value = setInterval(fetchRescueDetails, 10000);
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
});
</script>

<style scoped>
.v-timeline-item {
    padding-bottom: 16px;
}
</style>
