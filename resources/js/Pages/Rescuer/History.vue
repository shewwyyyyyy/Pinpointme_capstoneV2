<template>
    <v-app class="bg-user-gradient-light">
        <!-- App Bar -->
        <v-app-bar color="primary" density="comfortable">
            <v-btn icon @click="drawer = true">
                <v-icon>mdi-menu</v-icon>
            </v-btn>
            <v-app-bar-title>Rescue History</v-app-bar-title>
            <v-spacer />
            <v-btn icon @click="showFilters = !showFilters">
                <v-badge v-if="hasActiveFilters" dot color="error">
                    <v-icon>mdi-filter</v-icon>
                </v-badge>
                <v-icon v-else>mdi-filter-outline</v-icon>
            </v-btn>
        </v-app-bar>

        <!-- Navigation Drawer -->
        <RescuerMenu v-model="drawer" />

        <!-- Main Content -->
        <v-main>
            <!-- Filters -->
            <v-expand-transition>
                <v-sheet v-if="showFilters" class="pa-4" color="grey-lighten-4">
                    <div class="text-subtitle-2 mb-3">Filter Results</div>
                    <v-row dense>
                        <v-col cols="6">
                            <v-select
                                v-model="filters.status"
                                :items="statusOptions"
                                item-title="label"
                                item-value="value"
                                label="Status"
                                density="compact"
                                variant="outlined"
                                clearable
                                hide-details
                            />
                        </v-col>
                        <v-col cols="6">
                            <v-select
                                v-model="filters.emergencyType"
                                :items="emergencyTypes"
                                label="Emergency Type"
                                density="compact"
                                variant="outlined"
                                clearable
                                hide-details
                            />
                        </v-col>
                        <v-col cols="12">
                            <v-text-field
                                v-model="filters.dateRange"
                                label="Date Range"
                                density="compact"
                                variant="outlined"
                                type="date"
                                clearable
                                hide-details
                            />
                        </v-col>
                    </v-row>
                    <v-btn
                        v-if="hasActiveFilters"
                        variant="text"
                        color="primary"
                        size="small"
                        class="mt-2"
                        @click="clearFilters"
                    >
                        Clear All Filters
                    </v-btn>
                </v-sheet>
            </v-expand-transition>

            <!-- Stats Cards -->
            <div class="pa-4">
                <v-row dense>
                    <v-col cols="4">
                        <v-card variant="tonal" color="success" class="text-center pa-3">
                            <div class="text-h5 font-weight-bold">{{ stats.completed }}</div>
                            <div class="text-caption">Completed</div>
                        </v-card>
                    </v-col>
                    <v-col cols="4">
                        <v-card variant="tonal" color="error" class="text-center pa-3">
                            <div class="text-h5 font-weight-bold">{{ stats.cancelled }}</div>
                            <div class="text-caption">Cancelled</div>
                        </v-card>
                    </v-col>
                    <v-col cols="4">
                        <v-card variant="tonal" color="info" class="text-center pa-3">
                            <div class="text-h5 font-weight-bold">{{ stats.avgTime }}</div>
                            <div class="text-caption">Avg Time</div>
                        </v-card>
                    </v-col>
                </v-row>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="d-flex justify-center py-8">
                <v-progress-circular indeterminate color="primary" size="48" />
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredHistory.length === 0" class="pa-4">
                <v-card variant="tonal" color="grey-lighten-3" class="text-center pa-8">
                    <v-icon size="64" color="grey">mdi-history</v-icon>
                    <div class="text-h6 mt-4 text-grey-darken-1">No Rescue History</div>
                    <div class="text-body-2 text-grey mt-2">
                        {{ hasActiveFilters ? 'No rescues match your filters' : 'Your completed rescues will appear here' }}
                    </div>
                    <v-btn
                        v-if="hasActiveFilters"
                        variant="text"
                        color="primary"
                        class="mt-4"
                        @click="clearFilters"
                    >
                        Clear Filters
                    </v-btn>
                </v-card>
            </div>

            <!-- History List -->
            <div v-else class="px-4 pb-4">
                <div class="text-caption text-grey mb-2">
                    {{ filteredHistory.length }} {{ filteredHistory.length === 1 ? 'rescue' : 'rescues' }} found
                </div>

                <v-card
                    v-for="rescue in filteredHistory"
                    :key="rescue.id"
                    class="mb-3"
                    elevation="1"
                    @click="viewDetails(rescue)"
                >
                    <v-card-item>
                        <template v-slot:prepend>
                            <v-avatar :color="getEmergencyColor(rescue.emergency_type)" size="44">
                                <v-icon color="white">{{ getEmergencyIcon(rescue.emergency_type) }}</v-icon>
                            </v-avatar>
                        </template>
                        <v-card-title class="text-subtitle-1">
                            {{ rescue.emergency_type || 'Emergency' }}
                        </v-card-title>
                        <v-card-subtitle class="d-flex align-center">
                            <v-icon size="14" class="mr-1">mdi-map-marker</v-icon>
                            {{ rescue.room?.name || 'Unknown' }}
                        </v-card-subtitle>
                        <template v-slot:append>
                            <v-chip
                                :color="getStatusColor(rescue.status)"
                                size="x-small"
                                variant="flat"
                            >
                                {{ formatStatus(rescue.status) }}
                            </v-chip>
                        </template>
                    </v-card-item>

                    <v-divider />

                    <v-card-text class="py-2">
                        <v-row dense>
                            <v-col cols="6">
                                <div class="d-flex align-center text-caption">
                                    <v-icon size="14" class="mr-1" color="grey">mdi-calendar</v-icon>
                                    {{ formatDate(rescue.created_at) }}
                                </div>
                            </v-col>
                            <v-col cols="6">
                                <div class="d-flex align-center text-caption">
                                    <v-icon size="14" class="mr-1" color="grey">mdi-clock-outline</v-icon>
                                    {{ calculateDuration(rescue) }}
                                </div>
                            </v-col>
                        </v-row>

                        <!-- User Info -->
                        <div class="d-flex align-center mt-2 pt-2 border-t">
                            <v-avatar size="24" color="grey-lighten-2" class="mr-2">
                                <v-icon size="14">mdi-account</v-icon>
                            </v-avatar>
                            <span class="text-caption">
                                {{ rescue.user?.first_name }} {{ rescue.user?.last_name }}
                            </span>
                        </div>
                    </v-card-text>
                </v-card>

                <!-- Load More -->
                <v-btn
                    v-if="hasMore"
                    block
                    variant="outlined"
                    color="primary"
                    :loading="loadingMore"
                    @click="loadMore"
                >
                    Load More
                </v-btn>
            </div>
        </v-main>

        <!-- Detail Dialog -->
        <v-dialog v-model="showDetailDialog" max-width="500" scrollable>
            <v-card v-if="selectedRescue">
                <v-card-title class="d-flex align-center">
                    <v-avatar :color="getEmergencyColor(selectedRescue.emergency_type)" size="36" class="mr-3">
                        <v-icon size="20" color="white">{{ getEmergencyIcon(selectedRescue.emergency_type) }}</v-icon>
                    </v-avatar>
                    {{ selectedRescue.emergency_type || 'Emergency' }}
                    <v-spacer />
                    <v-btn icon size="small" variant="text" @click="showDetailDialog = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>

                <v-divider />

                <v-card-text>
                    <!-- Status Badge -->
                    <v-chip
                        :color="getStatusColor(selectedRescue.status)"
                        variant="flat"
                        size="small"
                        class="mb-4"
                    >
                        {{ formatStatus(selectedRescue.status) }}
                    </v-chip>

                    <!-- Location -->
                    <div class="mb-4">
                        <div class="text-caption text-grey mb-1">LOCATION</div>
                        <div class="text-body-2">
                            <strong>{{ selectedRescue.room?.name }}</strong>
                        </div>
                        <div class="text-caption text-grey-darken-1">
                            {{ selectedRescue.floor?.name }}, {{ selectedRescue.building?.name }}
                        </div>
                    </div>

                    <!-- Person Rescued -->
                    <div class="mb-4">
                        <div class="text-caption text-grey mb-1">PERSON RESCUED</div>
                        <div class="d-flex align-center">
                            <v-avatar size="32" color="grey-lighten-2" class="mr-2">
                                <v-icon size="18">mdi-account</v-icon>
                            </v-avatar>
                            <div>
                                <div class="text-body-2">
                                    {{ selectedRescue.user?.first_name }} {{ selectedRescue.user?.last_name }}
                                </div>
                                <div class="text-caption text-grey">
                                    {{ selectedRescue.user?.contact_number || 'No contact' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="mb-4">
                        <div class="text-caption text-grey mb-2">TIMELINE</div>
                        <v-timeline side="end" density="compact" truncate-line="both">
                            <v-timeline-item
                                v-if="selectedRescue.created_at"
                                dot-color="grey"
                                size="x-small"
                            >
                                <div class="text-caption">
                                    <strong>Created:</strong> {{ formatDateTime(selectedRescue.created_at) }}
                                </div>
                            </v-timeline-item>
                            <v-timeline-item
                                v-if="selectedRescue.accepted_at"
                                dot-color="info"
                                size="x-small"
                            >
                                <div class="text-caption">
                                    <strong>Accepted:</strong> {{ formatDateTime(selectedRescue.accepted_at) }}
                                </div>
                            </v-timeline-item>
                            <v-timeline-item
                                v-if="selectedRescue.en_route_at"
                                dot-color="primary"
                                size="x-small"
                            >
                                <div class="text-caption">
                                    <strong>En Route:</strong> {{ formatDateTime(selectedRescue.en_route_at) }}
                                </div>
                            </v-timeline-item>
                            <v-timeline-item
                                v-if="selectedRescue.on_scene_at"
                                dot-color="warning"
                                size="x-small"
                            >
                                <div class="text-caption">
                                    <strong>On Scene:</strong> {{ formatDateTime(selectedRescue.on_scene_at) }}
                                </div>
                            </v-timeline-item>
                            <v-timeline-item
                                v-if="selectedRescue.rescued_at"
                                dot-color="success"
                                size="x-small"
                            >
                                <div class="text-caption">
                                    <strong>Completed:</strong> {{ formatDateTime(selectedRescue.rescued_at) }}
                                </div>
                            </v-timeline-item>
                            <v-timeline-item
                                v-if="selectedRescue.cancelled_at"
                                dot-color="error"
                                size="x-small"
                            >
                                <div class="text-caption">
                                    <strong>Cancelled:</strong> {{ formatDateTime(selectedRescue.cancelled_at) }}
                                </div>
                            </v-timeline-item>
                        </v-timeline>
                    </div>

                    <!-- Notes -->
                    <div v-if="selectedRescue.notes">
                        <div class="text-caption text-grey mb-1">NOTES</div>
                        <div class="text-body-2">{{ selectedRescue.notes }}</div>
                    </div>
                </v-card-text>

                <v-divider />

                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showDetailDialog = false">Close</v-btn>
                </v-card-actions>
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
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useApi, getUnreadMessageCount } from '@/Composables/useApi';
import RescuerMenu from '@/Components/Pages/Rescuer/Menu/RescuerMenu.vue';
import RescuerBottomNav from '@/Components/Pages/Rescuer/Menu/RescuerBottomNav.vue';

const { get } = useApi();

// State
const drawer = ref(false);
const loading = ref(true);
const loadingMore = ref(false);
const showFilters = ref(false);
const showDetailDialog = ref(false);
const history = ref([]);
const selectedRescue = ref(null);
const page = ref(1);
const hasMore = ref(false);
const unreadMessageCount = ref(0);

const filters = ref({
    status: null,
    emergencyType: null,
    dateRange: null,
});

const stats = ref({
    completed: 0,
    cancelled: 0,
    avgTime: '0m',
});

const snackbar = ref({
    show: false,
    message: '',
    color: 'success',
});

const statusOptions = [
    { value: 'rescued', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

const emergencyTypes = ['Fire', 'Medical', 'Earthquake', 'Flood', 'Violence', 'Other'];

// Computed
const hasActiveFilters = computed(() => {
    return filters.value.status || filters.value.emergencyType || filters.value.dateRange;
});

const filteredHistory = computed(() => {
    let result = [...history.value];

    if (filters.value.status) {
        result = result.filter(r => r.status === filters.value.status);
    }

    if (filters.value.emergencyType) {
        result = result.filter(r => r.emergency_type === filters.value.emergencyType);
    }

    if (filters.value.dateRange) {
        const filterDate = new Date(filters.value.dateRange);
        result = result.filter(r => {
            const rescueDate = new Date(r.created_at);
            return rescueDate.toDateString() === filterDate.toDateString();
        });
    }

    return result;
});

// Methods
const fetchHistory = async (append = false) => {
    try {
        if (!append) {
            loading.value = true;
            page.value = 1;
        } else {
            loadingMore.value = true;
        }

        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        const response = await get('/api/rescue-requests', {
            rescuer_id: userData.id,
            status: 'rescued,cancelled',
            page: page.value,
            per_page: 10,
        });

        if (response.data) {
            const newItems = Array.isArray(response.data) ? response.data : response.data.data || [];
            
            if (append) {
                history.value = [...history.value, ...newItems];
            } else {
                history.value = newItems;
            }

            hasMore.value = response.data.next_page_url != null || 
                           (response.data.meta && response.data.meta.current_page < response.data.meta.last_page);

            calculateStats();
        }
    } catch (error) {
        console.error('Error fetching history:', error);
        showSnackbar('Failed to load history', 'error');
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
};

const loadMore = () => {
    page.value++;
    fetchHistory(true);
};

const calculateStats = () => {
    const completed = history.value.filter(r => r.status === 'rescued').length;
    const cancelled = history.value.filter(r => r.status === 'cancelled').length;
    
    // Calculate average rescue time
    const completedRescues = history.value.filter(r => r.status === 'rescued' && r.accepted_at && r.rescued_at);
    let avgMinutes = 0;
    
    if (completedRescues.length > 0) {
        const totalMinutes = completedRescues.reduce((acc, r) => {
            const start = new Date(r.accepted_at);
            const end = new Date(r.rescued_at);
            return acc + (end - start) / 60000;
        }, 0);
        avgMinutes = Math.round(totalMinutes / completedRescues.length);
    }
    
    stats.value = {
        completed,
        cancelled,
        avgTime: avgMinutes > 60 ? `${Math.round(avgMinutes / 60)}h` : `${avgMinutes}m`,
    };
};

const clearFilters = () => {
    filters.value = {
        status: null,
        emergencyType: null,
        dateRange: null,
    };
};

const viewDetails = (rescue) => {
    selectedRescue.value = rescue;
    showDetailDialog.value = true;
};

// Helper methods
const getEmergencyIcon = (type) => {
    const icons = {
        'Fire': 'mdi-fire',
        'Medical': 'mdi-hospital-box',
        'Earthquake': 'mdi-home-alert',
        'Flood': 'mdi-waves',
        'Violence': 'mdi-alert-octagon',
        'Other': 'mdi-alert-circle',
    };
    return icons[type] || 'mdi-alert-circle';
};

const getEmergencyColor = (type) => {
    const colors = {
        'Fire': 'deep-orange',
        'Medical': 'red',
        'Earthquake': 'brown',
        'Flood': 'blue',
        'Violence': 'purple',
        'Other': 'grey',
    };
    return colors[type] || 'grey';
};

const getStatusColor = (status) => {
    const colors = {
        'rescued': 'success',
        'cancelled': 'error',
    };
    return colors[status] || 'grey';
};

const formatStatus = (status) => {
    const labels = {
        'rescued': 'Completed',
        'cancelled': 'Cancelled',
    };
    return labels[status] || status;
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString([], { 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit',
    });
};

const calculateDuration = (rescue) => {
    if (!rescue.accepted_at) return 'N/A';
    
    const start = new Date(rescue.accepted_at);
    const end = rescue.rescued_at ? new Date(rescue.rescued_at) : 
                rescue.cancelled_at ? new Date(rescue.cancelled_at) : new Date();
    
    const diffMs = end - start;
    const diffMins = Math.floor(diffMs / 60000);
    
    if (diffMins < 60) return `${diffMins}m`;
    const hours = Math.floor(diffMins / 60);
    const mins = diffMins % 60;
    return `${hours}h ${mins}m`;
};

const showSnackbar = (message, color = 'success') => {
    snackbar.value = { show: true, message, color };
};

// Watch filters
watch(filters, () => {
    // Filters are applied via computed property
}, { deep: true });

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
    fetchHistory();
    await fetchUnreadMessageCount();
});
</script>

<style scoped>
.border-t {
    border-top: 1px solid rgba(0, 0, 0, 0.08);
}
</style>
