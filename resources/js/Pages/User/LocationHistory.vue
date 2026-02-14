<template>
    <v-app class="bg-user-gradient-light">
        <!-- App Bar -->
        <UserAppBar 
            title="Location History" 
            subtitle="Your Records"
            :notification-count="0"
            @toggle-drawer="drawer = !drawer"
        >
            <template #actions>
                <v-btn icon variant="text" class="bar-btn" style="color: white;" @click="fetchLocationHistory">
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
            </template>
        </UserAppBar>

        <!-- Navigation Drawer -->
        <UserMenu v-model="drawer" />

        <v-main class="main-content">
            <v-container fluid class="pa-4">
                <!-- Hero Section -->
                <div class="hero-section mb-5">
                    <div class="hero-icon-wrap">
                        <v-icon size="32" color="white">mdi-map-clock-outline</v-icon>
                    </div>
                    <h1 class="hero-title">Rescue History</h1>
                    <p class="hero-subtitle">Track and review your past rescue requests</p>
                    <!-- Stats Row -->
                     <div class="filter-bar mb-4">
                    <v-text-field
                        v-model="searchQuery"
                        placeholder="Search code or location..."
                        prepend-inner-icon="mdi-magnify"
                        variant="outlined"
                        density="compact"
                        hide-details
                        rounded="lg"
                        clearable
                        bg-color="white"
                        class="filter-input"
                    />
                    <v-text-field
                        v-model="dateFilter"
                        type="date"
                        prepend-inner-icon="mdi-calendar-outline"
                        variant="outlined"
                        density="compact"
                        hide-details
                        rounded="lg"
                        clearable
                        bg-color="white"
                        class="filter-input date-input"
                    />
                </div>
                </div>

                <!-- Search & Date Filter -->
               

                <!-- Results Info -->
                <div v-if="!isLoading && !error && filteredLocations.length > 0" class="results-bar mb-3">
                    <span class="results-count">
                        {{ filteredLocations.length }} {{ filteredLocations.length === 1 ? 'record' : 'records' }}
                        <span v-if="searchQuery || dateFilter" class="results-filtered"> (filtered)</span>
                    </span>
                    <button v-if="searchQuery || dateFilter" class="clear-all-btn" @click="searchQuery = ''; dateFilter = ''">
                        <v-icon size="14">mdi-filter-remove-outline</v-icon>
                        Clear
                    </button>
                </div>

                <!-- Loading State -->
                <div v-if="isLoading" class="loading-state">
                    <v-progress-circular indeterminate color="primary" size="44" width="3" />
                    <span class="loading-text">Loading history...</span>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="error-card">
                    <v-icon size="48" color="error" class="mb-3">mdi-alert-circle-outline</v-icon>
                    <h3 class="text-body-1 font-weight-bold mb-2">Something went wrong</h3>
                    <p class="text-body-2 text-grey mb-4">{{ error }}</p>
                    <v-btn color="primary" variant="flat" rounded="pill" @click="fetchLocationHistory" prepend-icon="mdi-refresh" size="small">
                        Try Again
                    </v-btn>
                </div>

                <!-- Empty State -->
                <div v-else-if="filteredLocations.length === 0" class="empty-state">
                    <div class="empty-icon-wrap">
                        <v-icon size="48" color="grey-lighten-1">mdi-map-marker-off-outline</v-icon>
                    </div>
                    <h3 class="text-body-1 font-weight-bold text-grey-darken-1 mb-1">No records found</h3>
                    <p class="text-body-2 text-grey mb-4">
                        {{ (searchQuery || dateFilter) ? 'Try adjusting your filters' : 'Your rescue request history will appear here' }}
                    </p>
                    <v-btn 
                        v-if="searchQuery || dateFilter" 
                        variant="tonal" 
                        color="primary" 
                        rounded="pill"
                        size="small"
                        @click="searchQuery = ''; dateFilter = ''"
                    >
                        Clear Filters
                    </v-btn>
                </div>

                <!-- Location History List -->
                <template v-else>
                    <!-- History Cards -->
                    <div class="history-cards-container">
                        <div
                            v-for="location in paginatedLocations"
                            :key="location.id"
                            class="history-card"
                            @click="viewLocation(location)"
                        >
                            <!-- Left: Status Indicator -->
                            <div class="card-status-bar" :style="{ background: getStatusGradient(location.status) }"></div>
                            
                            <div class="card-inner">
                                <!-- Top Row: Room Name + Location -->
                                <div class="card-top">
                                    <div class="card-location-info">
                                        <div class="location-icon-wrap" :style="{ background: getStatusBg(location.status) }">
                                            <v-icon size="18" :color="getStatusHex(location.status)">mdi-map-marker</v-icon>
                                        </div>
                                        <div class="location-text">
                                            <h4 class="location-name">{{ location.room_name || 'Unknown Room' }}</h4>
                                            <span class="location-sub" v-if="location.building_name || location.floor_name">
                                                {{ [location.building_name, location.floor_name].filter(Boolean).join(', ') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bottom Row: Date + Status + Rescue Code + Arrow -->
                                <div class="card-bottom">
                                    <div class="card-date">
                                        <v-icon size="13" color="grey">mdi-clock-outline</v-icon>
                                        <span>{{ formatDateFull(location.timestamp || location.created_at) }}</span>
                                    </div>
                                    <div class="card-bottom-right">
                                        <div class="status-badge" :style="{ background: getStatusBg(location.status), color: getStatusHex(location.status) }">
                                            <span>{{ formatStatus(location.status) }}</span>
                                        </div>
                                        <span v-if="location.rescue_code" class="rescue-code-tag">{{ location.rescue_code }}</span>
                                        <v-icon size="18" color="grey-lighten-1" class="chevron-icon">mdi-chevron-right</v-icon>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="totalPages > 1" class="pagination-wrap mt-4">
                        <button 
                            class="page-btn" 
                            :disabled="currentPage <= 1" 
                            @click="currentPage--"
                        >
                            <v-icon size="18">mdi-chevron-left</v-icon>
                        </button>
                        <span class="page-info">{{ currentPage }} / {{ totalPages }}</span>
                        <button 
                            class="page-btn" 
                            :disabled="currentPage >= totalPages" 
                            @click="currentPage++"
                        >
                            <v-icon size="18">mdi-chevron-right</v-icon>
                        </button>
                    </div>
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
import UserAppBar from '@/Components/Pages/User/Menu/UserAppBar.vue';
import UserBottomNav from '@/Components/Pages/User/Menu/UserBottomNav.vue';

// State
const drawer = ref(false);
const searchQuery = ref('');
const dateFilter = ref('');
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

// Filtered locations based on search and date
const filteredLocations = computed(() => {
    let filtered = locations.value;
    
    // Filter by search query (location or rescue code)
    if (searchQuery.value?.trim()) {
        const query = searchQuery.value.toLowerCase().trim();
        filtered = filtered.filter(loc => 
            (loc.name?.toLowerCase().includes(query)) ||
            (loc.location?.toLowerCase().includes(query)) ||
            (loc.rescue_code?.toLowerCase().includes(query)) ||
            (loc.room_name?.toLowerCase().includes(query)) ||
            (loc.building_name?.toLowerCase().includes(query)) ||
            (loc.floor_name?.toLowerCase().includes(query))
        );
    }
    
    // Filter by date
    if (dateFilter.value) {
        const filterDate = new Date(dateFilter.value).toDateString();
        filtered = filtered.filter(loc => {
            const locDate = new Date(loc.timestamp || loc.created_at).toDateString();
            return locDate === filterDate;
        });
    }
    
    return filtered;
});

// Stats counts
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

// Reset pagination when filters change
watch([searchQuery, dateFilter], () => {
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

        locations.value = records.map((record) => {
            // Room model uses 'room_name', Floor uses 'floor_name', Building uses 'name'
            const roomName = (typeof record.room === 'object' && record.room !== null) ? record.room.room_name : (record.room_name || null);
            const buildingName = (typeof record.building === 'object' && record.building !== null) ? record.building.name : (record.building_name || null);
            const floorName = (typeof record.floor === 'object' && record.floor !== null) ? record.floor.floor_name : (record.floor_name || null);

            return {
            id: record.id,
            name: `${record.firstName || record.first_name || ''} ${record.lastName || record.last_name || ''}`.trim() || 'Rescue Request',
            room_name: roomName,
            building_name: buildingName,
            floor_name: floorName,
            location: formatLocationFromRecord(record),
            isRescued: ['rescued', 'safe'].includes(record.status),
            status: record.status,
            timestamp: record.created_at,
            rescue_code: record.rescue_code,
            building_id: record.building_id,
            floor_id: record.floor_id,
            room_id: record.room_id,
        };
        });
    } catch (err) {
        console.error('Failed to fetch location history:', err);
        error.value = err.message || 'Failed to load history';
    } finally {
        isLoading.value = false;
    }
};

const formatLocationFromRecord = (record) => {
    const parts = [];
    const buildingName = (typeof record.building === 'object' && record.building !== null) ? record.building.name : (record.building_name || null);
    const floorName = (typeof record.floor === 'object' && record.floor !== null) ? record.floor.floor_name : (record.floor_name || null);
    const roomName = (typeof record.room === 'object' && record.room !== null) ? record.room.room_name : (record.room_name || null);
    if (buildingName) parts.push(buildingName);
    if (floorName) parts.push(floorName);
    if (roomName) parts.push(roomName);
    return parts.join(' › ') || 'Unknown Location';
};

const formatLocation = (location) => {
    return location.location || 'Unknown Location';
};

const formatDateFull = (dateString) => {
    if (!dateString) return 'Unknown';
    const date = new Date(dateString);
    const now = new Date();

    if (date.toDateString() === now.toDateString()) {
        return 'Today, ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    const yesterday = new Date(now);
    yesterday.setDate(yesterday.getDate() - 1);
    if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday, ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
    }) + ', ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
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

const getStatusHex = (status) => {
    const hex = {
        pending: '#F57C00',
        open: '#0288D1',
        assigned: '#3674B5',
        en_route: '#7B1FA2',
        on_scene: '#2E7D32',
        rescued: '#2E7D32',
        safe: '#2E7D32',
        cancelled: '#757575',
    };
    return hex[status] || '#757575';
};

const getStatusBg = (status) => {
    const bg = {
        pending: 'rgba(245, 124, 0, 0.1)',
        open: 'rgba(2, 136, 209, 0.1)',
        assigned: 'rgba(54, 116, 181, 0.1)',
        en_route: 'rgba(123, 31, 162, 0.1)',
        on_scene: 'rgba(46, 125, 50, 0.1)',
        rescued: 'rgba(46, 125, 50, 0.1)',
        safe: 'rgba(46, 125, 50, 0.1)',
        cancelled: 'rgba(117, 117, 117, 0.1)',
    };
    return bg[status] || 'rgba(117, 117, 117, 0.1)';
};

const getStatusGradient = (status) => {
    const hex = getStatusHex(status);
    return `linear-gradient(180deg, ${hex}, ${hex}88)`;
};

const getStatusIcon = (status) => {
    const icons = {
        pending: 'mdi-clock-outline',
        open: 'mdi-alert-circle-outline',
        assigned: 'mdi-account-check',
        en_route: 'mdi-car-emergency',
        on_scene: 'mdi-map-marker-check',
        rescued: 'mdi-check-circle',
        safe: 'mdi-shield-check',
        cancelled: 'mdi-close-circle',
    };
    return icons[status] || 'mdi-help-circle-outline';
};

const formatStatus = (status) => {
    if (!status) return 'Unknown';
    return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
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
/* Main Content */
.main-content {
    height: 100%;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

/* Hero Section */
.hero-section {
    text-align: center;
    padding: 24px 16px 20px;
    background: linear-gradient(135deg, rgba(54, 116, 181, 0.08), rgba(54, 116, 181, 0.03));
    border-radius: 20px;
    border: 1px solid rgba(54, 116, 181, 0.1);
}

.hero-icon-wrap {
    width: 58px;
    height: 58px;
    border-radius: 16px;
    background: linear-gradient(135deg, #3674B5, #2196F3);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    box-shadow: 0 8px 24px rgba(54, 116, 181, 0.3);
}

.hero-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #1a1a2e;
    margin-bottom: 4px;
}

.hero-subtitle {
    font-size: 0.8rem;
    color: #777;
    margin: 0 0 16px;
    line-height: 1.4;
}

/* Stats Row */
.stats-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    padding-top: 14px;
    border-top: 1px solid rgba(54, 116, 181, 0.1);
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.stat-value {
    font-size: 1.2rem;
    font-weight: 800;
    color: #1a1a2e;
}

.stat-label {
    font-size: 0.65rem;
    font-weight: 600;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-divider {
    width: 1px;
    height: 28px;
    background: rgba(0, 0, 0, 0.08);
}

/* Filter Bar */
.filter-bar {
    display: flex;
    gap: 10px;
    align-items: stretch;
}

.filter-input {
    flex: 1;
    min-width: 0;
}

.filter-input :deep(.v-field) {
    border-radius: 14px !important;
    font-size: 0.82rem;
}

.filter-input :deep(.v-field__input) {
    font-size: 0.82rem;
    font-weight: 500;
    padding-top: 8px;
    padding-bottom: 8px;
    min-height: 40px;
}

.filter-input :deep(.v-field__prepend-inner) {
    padding-top: 8px;
}

.filter-input :deep(.v-field__append-inner) {
    padding-top: 8px;
}

.date-input {
    max-width: 180px;
    flex-shrink: 0;
}

/* Results Bar */
.results-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.results-count {
    font-size: 0.78rem;
    font-weight: 600;
    color: #888;
}

.results-filtered {
    color: #3674B5;
}

.clear-all-btn {
    display: flex;
    align-items: center;
    gap: 4px;
    border: none;
    background: none;
    font-size: 0.75rem;
    font-weight: 600;
    color: #3674B5;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: background 0.2s;
}

.clear-all-btn:hover {
    background: rgba(54, 116, 181, 0.08);
}

/* Loading State */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    gap: 16px;
}

.loading-text {
    font-size: 0.85rem;
    color: #999;
    font-weight: 500;
}

/* Error Card */
.error-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 48px 20px;
    background: #FFF5F5;
    border: 1px solid #FFCDD2;
    border-radius: 20px;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px 20px;
    text-align: center;
}

.empty-icon-wrap {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

/* History Cards */
.history-cards-container {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: 800px;
    margin: 0 auto;
}

.history-card {
    display: flex;
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.04);
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.history-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(0, 0, 0, 0.1);
}

.history-card:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.card-status-bar {
    width: 4px;
    flex-shrink: 0;
    border-radius: 4px 0 0 4px;
}

.card-inner {
    flex: 1;
    padding: 14px 16px;
    min-width: 0;
}

/* Card Top Row */
.card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}

.card-location-info {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    flex: 1;
    min-width: 0;
}

.location-icon-wrap {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.location-text {
    min-width: 0;
    flex: 1;
}

.location-name {
    font-size: 0.88rem;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex: 1;
}

.room-status-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 4px;
}

.location-sub {
    display: flex;
    align-items: center;
    font-size: 0.7rem;
    font-weight: 500;
    color: #888;
    margin-top: 2px;
    line-height: 1.3;
}

.rescue-code-tag {
    font-size: 0.65rem;
    font-weight: 700;
    color: #3674B5;
    background: rgba(54, 116, 181, 0.08);
    padding: 2px 8px;
    border-radius: 6px;
    letter-spacing: 0.3px;
}

/* Status Badge */
.status-badge {
    display: flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    white-space: nowrap;
    flex-shrink: 0;
}

/* Card Bottom Row */
.card-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-bottom-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-date {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    font-weight: 500;
    color: #999;
}

.chevron-icon {
    flex-shrink: 0;
    transition: transform 0.2s ease;
}

.history-card:hover .chevron-icon {
    transform: translateX(3px);
}

/* Pagination */
.pagination-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
}

.page-btn {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    border: 1.5px solid #e0e0e0;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.page-btn:hover:not(:disabled) {
    border-color: #3674B5;
    color: #3674B5;
    background: rgba(54, 116, 181, 0.04);
}

.page-btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

.page-info {
    font-size: 0.82rem;
    font-weight: 600;
    color: #555;
}

/* Desktop-only elements */
.desktop-only {
    display: flex;
}

/* Responsive */
@media (max-width: 1023px) {
    .desktop-only {
        display: none !important;
    }
    
    .main-content :deep(.v-container) {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 120px) !important;
    }
}

@media (max-width: 600px) {
    .main-content :deep(.v-container) {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 140px) !important;
        padding-left: 12px !important;
        padding-right: 12px !important;
    }
    
    .filter-bar {
        gap: 8px;
    }
    
    .date-input {
        max-width: none;
        flex: 0.8;
    }
    
    .hero-section {
        padding: 20px 12px 16px;
    }
    
    .hero-icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 14px;
    }
    
    .hero-icon-wrap .v-icon {
        font-size: 26px !important;
    }
    
    .hero-title {
        font-size: 1.15rem;
    }
    
    .stats-row {
        gap: 14px;
    }
    
    .stat-value {
        font-size: 1rem;
    }
    
    .card-top {
        flex-direction: column;
        gap: 8px;
    }
    
    .status-badge {
        align-self: flex-start;
    }
    
    .card-inner {
        padding: 12px 14px;
    }
}

@media (max-width: 359px) {
    .hero-title {
        font-size: 1.05rem;
    }
    
    .hero-subtitle {
        font-size: 0.72rem;
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
    
    .history-cards-container {
        gap: 12px;
    }
    
    .card-inner {
        padding: 16px 20px;
    }
}
</style>
