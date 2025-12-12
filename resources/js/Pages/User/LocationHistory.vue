<template>
    <v-app>
        <v-app-bar color="primary" density="compact">
            <v-btn icon @click="drawer = true">
                <v-icon>mdi-menu</v-icon>
            </v-btn>
            <v-app-bar-title>Location History</v-app-bar-title>
            <v-spacer />
            <v-btn icon @click="toggleSelectMode">
                <v-icon>{{ selectMode ? 'mdi-close' : 'mdi-select-multiple' }}</v-icon>
            </v-btn>
        </v-app-bar>

        <!-- Navigation Drawer -->
        <UserMenu v-model="drawer" />

        <v-main class="bg-user-gradient-light">
            <v-container fluid class="pa-4">
               

                <!-- Loading State -->
                <div v-if="isLoading" class="d-flex justify-center align-center py-8">
                    <v-progress-circular indeterminate color="primary" size="48" />
                </div>

                <!-- Error State -->
                <v-alert v-else-if="error" type="error" variant="tonal" class="mb-4">
                    {{ error }}
                    <template v-slot:append>
                        <v-btn variant="text" @click="fetchLocationHistory">Retry</v-btn>
                    </template>
                </v-alert>

                <!-- Empty State -->
                <v-card v-else-if="filteredLocations.length === 0" class="text-center pa-8" elevation="2" rounded="lg">
                    <v-icon size="64" color="grey">mdi-history</v-icon>
                    <h3 class="text-h6 mt-4">No History Found</h3>
                    <p class="text-grey mt-2">Your rescue request history will appear here</p>
                </v-card>

                <!-- Location History List -->
                <template v-else>
                    <!-- Select Mode Actions -->
                    <v-card v-if="selectMode && selectedItems.length > 0" class="mb-4 pa-3" elevation="2" rounded="lg">
                        <div class="d-flex align-center justify-space-between">
                            <span>{{ selectedItems.length }} selected</span>
                            <v-btn color="error" variant="tonal" size="small" @click="showDeleteAlert = true">
                                Delete Selected
                            </v-btn>
                        </div>
                    </v-card>

                    <!-- History Cards -->
                    <v-card
                        v-for="location in filteredLocations"
                        :key="location.id"
                        class="mb-3"
                        elevation="2"
                        rounded="lg"
                        @click="handleCardClick(location)"
                        :class="{ 'border-primary': selectMode && selectedItems.includes(location.id) }"
                    >
                        <v-card-text class="d-flex align-start">
                            <!-- Checkbox for Select Mode -->
                            <v-checkbox
                                v-if="selectMode"
                                v-model="selectedItems"
                                :value="location.id"
                                hide-details
                                class="mr-2 mt-0"
                                @click.stop
                            />

                            <!-- Status Icon -->
                            <v-avatar
                                :color="getStatusColor(location.status)"
                                size="48"
                                class="mr-3"
                            >
                                <v-icon color="white">
                                    {{ location.isRescued ? 'mdi-check' : 'mdi-clock-outline' }}
                                </v-icon>
                            </v-avatar>

                            <!-- Content -->
                            <div class="flex-grow-1">
                                <div class="d-flex justify-space-between align-start">
                                    <div>
                                        <h4 class="font-weight-bold">
                                            {{ location.name || 'Rescue Request' }}
                                        </h4>
                                        <p class="text-caption text-grey mb-1">
                                            {{ location.location || formatLocation(location) }}
                                        </p>
                                    </div>
                                    <v-chip
                                        :color="getStatusColor(location.status)"
                                        variant="tonal"
                                        size="x-small"
                                    >
                                        {{ location.status || (location.isRescued ? 'Rescued' : 'Pending') }}
                                    </v-chip>
                                </div>

                                <div class="d-flex align-center mt-2">
                                    <v-icon size="14" class="mr-1" color="grey">mdi-clock-outline</v-icon>
                                    <span class="text-caption text-grey">
                                        {{ formatDate(location.timestamp || location.created_at) }}
                                    </span>
                                    <v-chip
                                        v-if="location.rescue_code"
                                        size="x-small"
                                        variant="outlined"
                                        class="ml-2"
                                    >
                                        {{ location.rescue_code }}
                                    </v-chip>
                                </div>
                            </div>

                            <!-- Arrow Icon -->
                            <v-icon v-if="!selectMode" color="grey" class="ml-2">
                                mdi-chevron-right
                            </v-icon>
                        </v-card-text>
                    </v-card>
                </template>
            </v-container>

            <!-- Delete Confirmation Dialog -->
            <v-dialog v-model="showDeleteAlert" max-width="400">
                <v-card>
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="error">mdi-delete-alert</v-icon>
                        Delete Records
                    </v-card-title>
                    <v-card-text>
                        Are you sure you want to delete {{ selectedItems.length }} selected record(s)?
                        This action cannot be undone.
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer />
                        <v-btn variant="text" @click="showDeleteAlert = false">Cancel</v-btn>
                        <v-btn color="error" @click="deleteSelected">Delete</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Toast Notification -->
            <v-snackbar v-model="showToast" :color="toastColor" location="top">
                {{ toastMessage }}
                <template v-slot:actions>
                    <v-btn variant="text" @click="showToast = false">Close</v-btn>
                </template>
            </v-snackbar>
        </v-main>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { getUserRescueHistory } from '@/Composables/useApi';
import UserMenu from '@/Components/Pages/User/Menu/UserMenu.vue';

// State
const drawer = ref(false);
const filter = ref('all');
const selectMode = ref(false);
const selectedItems = ref([]);
const showDeleteAlert = ref(false);
const locations = ref([]);
const isLoading = ref(true);
const error = ref('');

// Toast
const showToast = ref(false);
const toastMessage = ref('');
const toastColor = ref('success');

// Get current user ID
const currentUserId = computed(() => {
    const userData = localStorage.getItem('userData');
    if (userData) {
        return JSON.parse(userData).id;
    }
    return null;
});

// Filtered locations based on tab
const filteredLocations = computed(() => {
    if (filter.value === 'all') {
        return locations.value;
    } else if (filter.value === 'pending') {
        return locations.value.filter(
            (loc) => !loc.isRescued && !['rescued', 'safe', 'cancelled'].includes(loc.status)
        );
    } else if (filter.value === 'rescued') {
        return locations.value.filter(
            (loc) => loc.isRescued || ['rescued', 'safe'].includes(loc.status)
        );
    }
    return locations.value;
});

onMounted(async () => {
    await fetchLocationHistory();
});

const goBack = () => {
    // Navigate back to user scanner page
    router.visit('/user/scanner');
};

const fetchLocationHistory = async () => {
    if (!currentUserId.value) {
        error.value = 'User not logged in';
        isLoading.value = false;
        return;
    }

    isLoading.value = true;
    error.value = '';

    try {
        const data = await getUserRescueHistory(currentUserId.value);
        const records = Array.isArray(data) ? data : (data?.data || []);

        locations.value = records.map((record) => ({
            id: record.id,
            name: `${record.firstName || ''} ${record.lastName || ''}`.trim() || 'Rescue Request',
            location: formatLocationFromRecord(record),
            isRescued: ['rescued', 'safe'].includes(record.status),
            status: record.status,
            timestamp: record.created_at,
            rescue_code: record.rescue_code,
            building_id: record.building_id,
            floor_id: record.floor_id,
            room_id: record.room_id,
        }));
    } catch (err) {
        console.error('Failed to fetch location history:', err);
        error.value = err.message || 'Failed to load history';
    } finally {
        isLoading.value = false;
    }
};

const formatLocationFromRecord = (record) => {
    const parts = [];
    if (record.building_name) parts.push(record.building_name);
    if (record.floor_name) parts.push(record.floor_name);
    if (record.room_name) parts.push(record.room_name);
    return parts.join(' > ') || 'Unknown Location';
};

const formatLocation = (location) => {
    return location.location || 'Unknown Location';
};

const formatDate = (dateString) => {
    if (!dateString) return 'Unknown';

    const date = new Date(dateString);
    const now = new Date();

    // If today, show time
    if (date.toDateString() === now.toDateString()) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // If this year, show month and day
    if (date.getFullYear() === now.getFullYear()) {
        return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }

    // Otherwise show full date
    return date.toLocaleDateString();
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

const toggleSelectMode = () => {
    selectMode.value = !selectMode.value;
    selectedItems.value = [];
};

const handleCardClick = (location) => {
    if (selectMode.value) {
        toggleItemSelection(location.id);
    } else {
        // Navigate to help status page with the rescue code
        if (location.rescue_code) {
            router.visit(`/user/help-coming/${location.rescue_code}`);
        } else {
            showToast.value = true;
            toastMessage.value = 'No rescue code available';
            toastColor.value = 'warning';
        }
    }
};

const toggleItemSelection = (id) => {
    const index = selectedItems.value.indexOf(id);
    if (index > -1) {
        selectedItems.value.splice(index, 1);
    } else {
        selectedItems.value.push(id);
    }
};

const deleteSelected = async () => {
    try {
        // In a real implementation, call API to delete records
        locations.value = locations.value.filter((loc) => !selectedItems.value.includes(loc.id));
        selectedItems.value = [];
        showDeleteAlert.value = false;
        selectMode.value = false;

        toastMessage.value = 'Records deleted successfully';
        toastColor.value = 'success';
        showToast.value = true;
    } catch (err) {
        console.error('Failed to delete records:', err);
        toastMessage.value = 'Failed to delete records';
        toastColor.value = 'error';
        showToast.value = true;
    }
};
</script>

<style scoped>
/* Background is now global via bg-user-gradient-light */
.border-primary {
    border: 2px solid rgb(var(--v-theme-primary)) !important;
}
</style>
