<template>
    <v-app class="app-container">
        <!-- Header - matches Dashboard style -->
        <div class="page-header">
            <div class="header-content">
                <v-btn icon variant="text" @click="drawer = !drawer" class="menu-btn desktop-only">
                    <v-icon>mdi-menu</v-icon>
                </v-btn>
                <div class="header-title">
                    <h1>Location History</h1>
                    <p>Your Records</p>
                </div>
                <v-btn icon variant="text" @click="fetchLocationHistory" class="action-btn">
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
            </div>
        </div>

        <!-- Navigation Drawer -->
        <UserMenu v-model="drawer" />

        <v-main class="main-content">
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
                    <!-- History Cards -->
                    <v-card
                        v-for="location in filteredLocations"
                        :key="location.id"
                        class="mb-3"
                        elevation="2"
                        rounded="lg"
                        @click="viewLocation(location)"
                    >
                        <v-card-text class="d-flex align-start">
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
                            <v-icon color="grey" class="ml-2">
                                mdi-chevron-right
                            </v-icon>
                        </v-card-text>
                    </v-card>
                </template>
            </v-container>

            <!-- Toast Notification -->
            <v-snackbar v-model="showToast" :color="toastColor" location="top">
                {{ toastMessage }}
                <template v-slot:actions>
                    <v-btn variant="text" @click="showToast = false">Close</v-btn>
                </template>
            </v-snackbar>
        </v-main>
        
        <!-- Bottom Navigation for Mobile -->
        <UserBottomNav :notification-count="0" :message-count="unreadCount" />
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { getUserRescueHistory } from '@/Composables/useApi';
import { useUnreadMessages } from '@/Composables/useUnreadMessages';
import UserMenu from '@/Components/Pages/User/Menu/UserMenu.vue';
import UserBottomNav from '@/Components/Pages/User/Menu/UserBottomNav.vue';

// State
const drawer = ref(false);
const filter = ref('all');
const locations = ref([]);
const isLoading = ref(true);
const error = ref('');

// Unread messages count for bottom nav
const { unreadCount } = useUnreadMessages();

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

const viewLocation = (location) => {
    // Navigate to help status page with the rescue code
    if (location.rescue_code) {
        router.visit(`/user/help-coming/${location.rescue_code}`);
    } else {
        showToast.value = true;
        toastMessage.value = 'No rescue code available';
        toastColor.value = 'warning';
    }
};
</script>

<style scoped>
/* App Container */
.app-container {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    overflow: hidden !important;
}

/* Header - matches Dashboard style */
.page-header {
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

.menu-btn, .action-btn {
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

/* Main Content */
.main-content {
    background: linear-gradient(180deg, #e8f5f3 0%, #f5f9f8 50%, #ffffff 100%);
    height: 100%;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

/* Desktop-only elements */
.desktop-only {
    display: flex;
}

/* Responsive visibility */
@media (max-width: 1023px) {
    .desktop-only {
        display: none !important;
    }
    
    .main-content :deep(.v-container) {
        padding-bottom: 80px !important;
    }
}

@media (min-width: 1024px) {
    .desktop-only {
        display: flex;
    }
}
</style>
