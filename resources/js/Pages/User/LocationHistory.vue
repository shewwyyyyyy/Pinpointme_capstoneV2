<template>
    <v-app class="bg-user-gradient-light">
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
                <!-- Filter Tabs -->
                <v-card class="mb-4" elevation="0" rounded="lg">
                    <v-card-text class="pa-2">
                        <v-tabs v-model="filter" bg-color="transparent" color="primary" density="compact">
                            <v-tab value="all">
                                <v-icon start size="16">mdi-history</v-icon>
                                All
                                <v-chip v-if="allCount > 0" size="x-small" class="ml-1">{{ allCount }}</v-chip>
                            </v-tab>
                            <v-tab value="pending">
                                <v-icon start size="16">mdi-clock-outline</v-icon>
                                Pending
                                <v-chip v-if="pendingCount > 0" size="x-small" color="warning" class="ml-1">{{ pendingCount }}</v-chip>
                            </v-tab>
                            <v-tab value="rescued">
                                <v-icon start size="16">mdi-check-circle</v-icon>
                                Rescued
                                <v-chip v-if="rescuedCount > 0" size="x-small" color="success" class="ml-1">{{ rescuedCount }}</v-chip>
                            </v-tab>
                        </v-tabs>
                    </v-card-text>
                </v-card>

                <!-- Search Bar -->
                <v-card class="mb-4" elevation="0" rounded="lg">
                    <v-card-text class="pa-3">
                        <v-text-field
                            v-model="searchQuery"
                            placeholder="Search by rescue code or location..."
                            prepend-inner-icon="mdi-magnify"
                            variant="outlined"
                            density="compact"
                            hide-details
                            rounded
                            clearable
                        />
                    </v-card-text>
                </v-card>
               

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
                    <!-- Results Info -->
                    <div class="d-flex justify-space-between align-center mb-3">
                        <p class="text-body-2 text-grey ma-0">
                            Showing {{ Math.min((currentPage - 1) * itemsPerPage + 1, filteredLocations.length) }}-{{ Math.min(currentPage * itemsPerPage, filteredLocations.length) }} of {{ filteredLocations.length }} results
                        </p>
                        <v-select
                            v-model="itemsPerPage"
                            :items="[5, 10, 15, 20]"
                            density="compact"
                            variant="outlined"
                            hide-details
                            style="max-width: 80px;"
                        />
                    </div>

                    <!-- History Cards -->
                    <div class="history-cards-container">
                        <v-card
                            v-for="location in paginatedLocations"
                            :key="location.id"
                            class="location-card mb-3"
                            elevation="2"
                            rounded="lg"
                            @click="viewLocation(location)"
                        >
                            <v-card-text class="card-content">
                                <!-- Status Icon -->
                                <v-avatar
                                    :color="getStatusColor(location.status)"
                                    size="48"
                                    class="status-avatar"
                                >
                                    <v-icon color="white" size="20">
                                        {{ location.isRescued ? 'mdi-check-circle' : 'mdi-clock-outline' }}
                                    </v-icon>
                                </v-avatar>

                                <!-- Content -->
                                <div class="card-details">
                                    <div class="card-header">
                                        <div class="card-title-section">
                                            <h4 class="location-title">
                                                {{ location.name || 'Rescue Request' }}
                                            </h4>
                                            <p class="location-subtitle">
                                                {{ location.location || formatLocation(location) }}
                                            </p>
                                        </div>
                                        <v-chip
                                            :color="getStatusColor(location.status)"
                                            variant="flat"
                                            size="small"
                                            class="status-chip"
                                        >
                                            {{ location.status || (location.isRescued ? 'safe' : 'pending') }}
                                        </v-chip>
                                    </div>

                                    <div class="card-footer">
                                        <div class="time-info">
                                            <v-icon size="14" class="mr-1" color="grey">mdi-clock-outline</v-icon>
                                            <span class="time-text">
                                                {{ formatDate(location.timestamp || location.created_at) }}
                                            </span>
                                        </div>
                                        <v-chip
                                            v-if="location.rescue_code"
                                            size="small"
                                            variant="outlined"
                                            color="primary"
                                            class="rescue-code-chip"
                                        >
                                            {{ location.rescue_code }}
                                        </v-chip>
                                    </div>
                                </div>

                                <!-- Arrow Icon -->
                                <v-icon color="grey-darken-1" class="chevron-icon">
                                    mdi-chevron-right
                                </v-icon>
                            </v-card-text>
                        </v-card>
                    </div>

                    <!-- Pagination -->
                    <v-card v-if="totalPages > 1" class="mt-4" elevation="0" rounded="lg">
                        <v-card-text class="pa-2">
                            <v-pagination
                                v-model="currentPage"
                                :length="totalPages"
                                :total-visible="5"
                                color="primary"
                                class="pagination-component"
                            />
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
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { getUserRescueHistory } from '@/Composables/useApi';
import { useUnreadMessages } from '@/Composables/useUnreadMessages';
import UserMenu from '@/Components/Pages/User/Menu/UserMenu.vue';
import UserBottomNav from '@/Components/Pages/User/Menu/UserBottomNav.vue';

// State
const drawer = ref(false);
const filter = ref('all');
const searchQuery = ref('');
const locations = ref([]);
const isLoading = ref(true);
const error = ref('');

// Pagination state
const currentPage = ref(1);
const itemsPerPage = ref(10);

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

// Filtered locations based on tab and search
const filteredLocations = computed(() => {
    let filtered = locations.value;
    
    // Filter by tab
    if (filter.value === 'pending') {
        filtered = filtered.filter(
            (loc) => !loc.isRescued && !['rescued', 'safe', 'cancelled'].includes(loc.status)
        );
    } else if (filter.value === 'rescued') {
        filtered = filtered.filter(
            (loc) => loc.isRescued || ['rescued', 'safe'].includes(loc.status)
        );
    }
    
    // Filter by search query
    if (searchQuery.value?.trim()) {
        const query = searchQuery.value.toLowerCase().trim();
        filtered = filtered.filter(loc => 
            (loc.name?.toLowerCase().includes(query)) ||
            (loc.location?.toLowerCase().includes(query)) ||
            (loc.rescue_code?.toLowerCase().includes(query))
        );
    }
    
    return filtered;
});

// Count badges for tabs
const allCount = computed(() => locations.value.length);
const pendingCount = computed(() => 
    locations.value.filter(loc => 
        !loc.isRescued && !['rescued', 'safe', 'cancelled'].includes(loc.status)
    ).length
);
const rescuedCount = computed(() => 
    locations.value.filter(loc => 
        loc.isRescued || ['rescued', 'safe'].includes(loc.status)
    ).length
);

// Pagination
const totalPages = computed(() => Math.ceil(filteredLocations.value.length / itemsPerPage.value));

const paginatedLocations = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredLocations.value.slice(start, end);
});

// Reset pagination when filter or search changes
watch([filter, searchQuery], () => {
    currentPage.value = 1;
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
    height: 100%;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

/* History Cards Container */
.history-cards-container {
    max-width: 800px;
    margin: 0 auto;
}

/* Location Card */
.location-card {
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.location-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
    border-color: rgba(54, 116, 181, 0.2);
}

.card-content {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px !important;
}

.status-avatar {
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.card-details {
    flex: 1;
    min-width: 0;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    gap: 12px;
}

.card-title-section {
    flex: 1;
    min-width: 0;
}

.location-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1a365d;
    margin: 0 0 4px 0;
    line-height: 1.3;
}

.location-subtitle {
    font-size: 0.85rem;
    color: #64748b;
    margin: 0;
    line-height: 1.4;
}

.status-chip {
    font-weight: 600;
    flex-shrink: 0;
    text-transform: capitalize;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.time-info {
    display: flex;
    align-items: center;
    color: #64748b;
}

.time-text {
    font-size: 0.8rem;
    font-weight: 500;
}

.rescue-code-chip {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    font-size: 0.75rem;
}

.chevron-icon {
    flex-shrink: 0;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.location-card:hover .chevron-icon {
    opacity: 1;
}

/* Pagination */
.pagination-component {
    margin: 0;
}

.pagination-component :deep(.v-pagination__item) {
    min-width: 40px;
    height: 40px;
}

.pagination-component :deep(.v-pagination__prev),
.pagination-component :deep(.v-pagination__next) {
    min-width: 40px;
    height: 40px;
}

/* Desktop-only elements */
.desktop-only {
    display: flex;
}

/* Responsive visibility and layout */
@media (max-width: 1023px) {
    .desktop-only {
        display: none !important;
    }
    
    .main-content :deep(.v-container) {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 120px) !important;
    }
    
    .card-content {
        padding: 12px !important;
        gap: 12px;
    }
    
    .location-title {
        font-size: 0.95rem;
    }
    
    .location-subtitle {
        font-size: 0.8rem;
    }
}

@media (max-width: 600px) {
    .main-content :deep(.v-container) {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 140px) !important;
        padding-left: 12px !important;
        padding-right: 12px !important;
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .card-title-section {
        width: 100%;
    }
    
    .status-chip {
        align-self: flex-start;
    }
    
    .card-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .rescue-code-chip {
        align-self: flex-end;
    }
    
    .pagination-component :deep(.v-pagination__item) {
        min-width: 36px;
        height: 36px;
    }
    
    .pagination-component :deep(.v-pagination__prev),
    .pagination-component :deep(.v-pagination__next) {
        min-width: 36px;
        height: 36px;
    }
}

@media (min-width: 1024px) {
    .desktop-only {
        display: flex;
    }
    
    .main-content :deep(.v-container) {
        max-width: 1200px;
        margin: 0 auto;
        padding-bottom: 40px !important;
    }
    
    .location-card {
        margin-bottom: 12px;
    }
    
    .card-content {
        padding: 20px !important;
    }
    
    .location-title {
        font-size: 1.1rem;
    }
}
</style>
