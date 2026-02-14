<template>
    <v-app class="bg-grey-lighten-4">
        <AdminAppBar activePage="dashboard" />

        <!-- Main Content -->
        <v-main>
            <v-container fluid :class="isMobile ? 'pa-3' : 'pa-6'">
                <!-- Page Header with Welcome Message -->
                <div class="page-header mb-4 mb-md-6">
                    <div class="page-header-content">
                        <h1 :class="isMobile ? 'text-h5' : 'text-h4'" class="font-weight-bold gradient-text">Dashboard Overview</h1>
                        <p class="text-grey mt-1 text-body-2">Welcome back! Here's what's happening with rescue operations.</p>
                    </div>
                    <v-select
                        v-model="timeFilter"
                        :items="timeFilters"
                        item-title="label"
                        item-value="value"
                        variant="outlined"
                        density="compact"
                        rounded="xl"
                        class="time-filter-select"
                        hide-details
                        @update:model-value="refreshData"
                    />
                </div>

                <!-- Stats Cards with Gradient Backgrounds -->
                <v-row>
                    <v-col cols="12" sm="6" lg="3">
                        <v-card class="stat-card stat-card-primary stat-card-clickable" rounded="xl" elevation="4" @click="openStatDialog('total')">
                            <div class="stat-card-overlay"></div>
                            <v-card-text class="position-relative">
                                <div class="d-flex align-center justify-space-between">
                                    <div>
                                        <p class="text-white text-caption mb-1 opacity-80">Total Requests</p>
                                        <h2 class="text-h3 font-weight-bold text-white">{{ statusCounts.total }}</h2>
                                        <v-chip color="rgba(255,255,255,0.2)" size="x-small" class="mt-2 text-white">
                                            <v-icon start size="12">mdi-trending-up</v-icon>
                                            All Time
                                        </v-chip>
                                    </div>
                                    <v-avatar size="64" color="rgba(255,255,255,0.2)">
                                        <v-icon size="36" color="white">mdi-alert-circle-outline</v-icon>
                                    </v-avatar>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="6" lg="3">
                        <v-card class="stat-card stat-card-warning stat-card-clickable" rounded="xl" elevation="4" @click="openStatDialog('pending')">
                            <div class="stat-card-overlay"></div>
                            <v-card-text class="position-relative">
                                <div class="d-flex align-center justify-space-between">
                                    <div>
                                        <p class="text-white text-caption mb-1 opacity-80">Need Help</p>
                                        <h2 class="text-h3 font-weight-bold text-white">{{ statusCounts.pending || 0 }}</h2>
                                        <v-chip color="rgba(255,255,255,0.2)" size="x-small" class="mt-2 text-white">
                                            <v-icon start size="12">mdi-hand-heart</v-icon>
                                            Need Help
                                        </v-chip>
                                    </div>
                                    <v-avatar size="64" color="rgba(255,255,255,0.2)">
                                        <v-icon size="36" color="white">mdi-alert-circle-outline</v-icon>
                                    </v-avatar>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="6" lg="3">
                        <v-card class="stat-card stat-card-info stat-card-clickable" rounded="xl" elevation="4" @click="openStatDialog('in_progress')">
                            <div class="stat-card-overlay"></div>
                            <v-card-text class="position-relative">
                                <div class="d-flex align-center justify-space-between">
                                    <div>
                                        <p class="text-white text-caption mb-1 opacity-80">In Progress</p>
                                        <h2 class="text-h3 font-weight-bold text-white">{{ statusCounts.in_progress }}</h2>
                                        <v-chip color="rgba(255,255,255,0.2)" size="x-small" class="mt-2 text-white">
                                            <v-icon start size="12">mdi-run-fast</v-icon>
                                            Active
                                        </v-chip>
                                    </div>
                                    <v-avatar size="64" color="rgba(255,255,255,0.2)">
                                        <v-icon size="36" color="white">mdi-progress-clock</v-icon>
                                    </v-avatar>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="6" lg="3">
                        <v-card class="stat-card stat-card-success stat-card-clickable" rounded="xl" elevation="4" @click="openStatDialog('completed')">
                            <div class="stat-card-overlay"></div>
                            <v-card-text class="position-relative">
                                <div class="d-flex align-center justify-space-between">
                                    <div>
                                        <p class="text-white text-caption mb-1 opacity-80">Marked as Safe</p>
                                        <h2 class="text-h3 font-weight-bold text-white">{{ statusCounts.completed || 0 }}</h2>
                                        <v-chip color="rgba(255,255,255,0.2)" size="x-small" class="mt-2 text-white">
                                            <v-icon start size="12">mdi-shield-check</v-icon>
                                            Safe
                                        </v-chip>
                                    </div>
                                    <v-avatar size="64" color="rgba(255,255,255,0.2)">
                                        <v-icon size="36" color="white">mdi-check-circle-outline</v-icon>
                                    </v-avatar>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Rescuer Status Cards -->
                <v-row class="mt-4">
                    <v-col cols="12">
                        <v-card rounded="xl" elevation="2">
                            <v-card-title class="d-flex align-center pa-4">
                                <v-avatar color="primary" size="40" class="mr-3">
                                    <v-icon color="white">mdi-lifebuoy</v-icon>
                                </v-avatar>
                                <span class="font-weight-bold">Rescuer Status Overview</span>
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text class="pa-4">
                                <v-row>
                                    <v-col cols="6" sm="3">
                                        <div class="text-center pa-4 rounded-xl bg-grey-lighten-4">
                                            <v-avatar color="primary" size="56" class="mb-3 elevation-2">
                                                <v-icon color="white" size="28">mdi-account-group</v-icon>
                                            </v-avatar>
                                            <h3 class="text-h4 font-weight-bold text-primary">{{ rescuerStats.total }}</h3>
                                            <p class="text-grey text-body-2 mb-0">Total Rescuers</p>
                                        </div>
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <div class="text-center pa-4 rounded-xl bg-success-lighten-5">
                                            <v-avatar color="success" size="56" class="mb-3 elevation-2">
                                                <v-icon color="white" size="28">mdi-check-circle</v-icon>
                                            </v-avatar>
                                            <h3 class="text-h4 font-weight-bold text-success">{{ rescuerStats.available }}</h3>
                                            <p class="text-grey text-body-2 mb-0">Available</p>
                                        </div>
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <div class="text-center pa-4 rounded-xl bg-warning-lighten-5">
                                            <v-avatar color="warning" size="56" class="mb-3 elevation-2">
                                                <v-icon color="white" size="28">mdi-run-fast</v-icon>
                                            </v-avatar>
                                            <h3 class="text-h4 font-weight-bold text-warning">{{ rescuerStats.on_rescue }}</h3>
                                            <p class="text-grey text-body-2 mb-0">On Rescue</p>
                                        </div>
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <div class="text-center pa-4 rounded-xl bg-grey-lighten-3">
                                            <v-avatar color="grey" size="56" class="mb-3 elevation-2">
                                                <v-icon color="white" size="28">mdi-power-sleep</v-icon>
                                            </v-avatar>
                                            <h3 class="text-h4 font-weight-bold text-grey-darken-1">{{ rescuerStats.off_duty }}</h3>
                                            <p class="text-grey text-body-2 mb-0">Off Duty</p>
                                        </div>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <v-row class="mt-4">
                    <!-- Rescues by Building -->
                    <v-col cols="12" md="6">
                        <v-card rounded="xl" height="100%" elevation="2">
                            <v-card-title class="d-flex align-center pa-4">
                                <v-avatar color="info" size="40" class="mr-3">
                                    <v-icon color="white">mdi-office-building</v-icon>
                                </v-avatar>
                                <span class="font-weight-bold">Rescues by Building</span>
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text class="pa-4">
                                <v-list v-if="rescuesByBuilding.length > 0" class="bg-transparent">
                                    <v-list-item 
                                        v-for="(item, index) in rescuesByBuilding" 
                                        :key="item.name"
                                        class="px-0 mb-2"
                                    >
                                        <template v-slot:prepend>
                                            <v-avatar :color="getBuildingColor(index)" size="42" class="mr-3">
                                                <v-icon size="small" color="white">mdi-domain</v-icon>
                                            </v-avatar>
                                        </template>
                                        <v-list-item-title class="font-weight-medium">{{ item.name }}</v-list-item-title>
                                        <v-list-item-subtitle>
                                            <v-progress-linear 
                                                :model-value="(item.count / Math.max(...rescuesByBuilding.map(b => b.count))) * 100" 
                                                :color="getBuildingColor(index)"
                                                height="6"
                                                rounded
                                                class="mt-1"
                                            ></v-progress-linear>
                                        </v-list-item-subtitle>
                                        <template v-slot:append>
                                            <v-chip :color="getBuildingColor(index)" size="small" variant="flat" class="font-weight-bold">
                                                {{ item.count }}
                                            </v-chip>
                                        </template>
                                    </v-list-item>
                                </v-list>
                                <v-alert v-else type="info" variant="tonal" rounded="xl">
                                    <v-icon start>mdi-information</v-icon>
                                    No rescue data available for this period.
                                </v-alert>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- User Statistics -->
                    <v-col cols="12" md="6">
                        <v-card rounded="xl" height="100%" elevation="2">
                            <v-card-title class="d-flex align-center pa-4">
                                <v-avatar color="purple" size="40" class="mr-3">
                                    <v-icon color="white">mdi-account-multiple</v-icon>
                                </v-avatar>
                                <span class="font-weight-bold">User Statistics</span>
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text class="pa-4">
                                <div class="text-center mb-6">
                                    <div class="d-inline-flex align-center justify-center rounded-circle pa-6 bg-primary-lighten-5">
                                        <div>
                                            <h2 class="text-h2 font-weight-bold text-primary">{{ userStats.total }}</h2>
                                            <p class="text-grey mb-0">Total Users</p>
                                        </div>
                                    </div>
                                </div>
                                <v-row class="text-center">
                                    <v-col cols="4">
                                        <v-card class="pa-3 bg-blue-lighten-5" rounded="xl" flat>
                                            <v-avatar color="blue" size="36" class="mb-2">
                                                <v-icon color="white" size="20">mdi-school</v-icon>
                                            </v-avatar>
                                            <h4 class="text-h5 font-weight-bold text-blue">{{ userStats.by_role?.student || 0 }}</h4>
                                            <p class="text-caption text-grey mb-0">Students</p>
                                        </v-card>
                                    </v-col>
                                    <v-col cols="4">
                                        <v-card class="pa-3 bg-purple-lighten-5" rounded="xl" flat>
                                            <v-avatar color="purple" size="36" class="mb-2">
                                                <v-icon color="white" size="20">mdi-human-male-board</v-icon>
                                            </v-avatar>
                                            <h4 class="text-h5 font-weight-bold text-purple">{{ userStats.by_role?.faculty || 0 }}</h4>
                                            <p class="text-caption text-grey mb-0">Faculty</p>
                                        </v-card>
                                    </v-col>
                                    <v-col cols="4">
                                        <v-card class="pa-3 bg-teal-lighten-5" rounded="xl" flat>
                                            <v-avatar color="teal" size="36" class="mb-2">
                                                <v-icon color="white" size="20">mdi-briefcase</v-icon>
                                            </v-avatar>
                                            <h4 class="text-h5 font-weight-bold text-teal">{{ userStats.by_role?.staff || 0 }}</h4>
                                            <p class="text-caption text-grey mb-0">Staff</p>
                                        </v-card>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Recent Alerts -->
                <v-row class="mt-4">
                    <v-col cols="12">
                        <v-card rounded="xl" elevation="2">
                            <v-card-title class="d-flex align-center pa-4">
                                <v-avatar color="error" size="40" class="mr-3">
                                    <v-icon color="white">mdi-bell-alert</v-icon>
                                </v-avatar>
                                <span class="font-weight-bold">Recent Rescue Alerts</span>
                                <v-spacer />
                                <v-btn variant="tonal" color="primary" href="/admin/reports" rounded="xl" size="small">
                                    <v-icon start size="18">mdi-arrow-right</v-icon>
                                    View All
                                </v-btn>
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text class="pa-0">
                                <v-data-table
                                    :headers="alertHeaders"
                                    :items="recentAlerts"
                                    :items-per-page="5"
                                    class="elevation-0"
                                >
                                    <template v-slot:item.status="{ item }">
                                        <v-chip :color="getStatusColor(item.status)" size="small" variant="flat">
                                            {{ formatStatus(item.status) }}
                                        </v-chip>
                                    </template>
                                    <template v-slot:item.urgency_level="{ item }">
                                        <v-chip :color="getUrgencyColor(item.urgency_level)" size="small" variant="outlined">
                                            {{ item.urgency_level || 'Medium' }}
                                        </v-chip>
                                    </template>
                                    <template v-slot:item.created_at="{ item }">
                                        {{ formatDate(item.created_at) }}
                                    </template>
                                </v-data-table>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>
        </v-main>

        <!-- Stat Detail Dialog -->
        <v-dialog v-model="statDialog.show" :max-width="isMobile ? '100%' : 640" scrollable>
            <v-card rounded="xl" class="stat-dialog-card">
                <!-- Dialog Header -->
                <div class="stat-dialog-header" :class="`stat-dialog-header-${statDialog.type}`">
                    <div class="d-flex align-center">
                        <v-avatar size="40" color="rgba(255,255,255,0.2)" class="mr-3">
                            <v-icon color="white">{{ statDialogConfig.icon }}</v-icon>
                        </v-avatar>
                        <div>
                            <h3 class="text-white font-weight-bold text-body-1">{{ statDialogConfig.title }}</h3>
                            <p class="text-white-50 text-caption mb-0">{{ statDialogItems.length }} request{{ statDialogItems.length !== 1 ? 's' : '' }}</p>
                        </div>
                    </div>
                    <v-btn icon variant="text" size="small" @click="statDialog.show = false">
                        <v-icon color="white">mdi-close</v-icon>
                    </v-btn>
                </div>

                <!-- Loading -->
                <v-card-text v-if="statDialog.loading" class="d-flex justify-center py-12">
                    <v-progress-circular indeterminate color="primary" size="36" width="3"></v-progress-circular>
                </v-card-text>

                <!-- Empty State -->
                <v-card-text v-else-if="statDialogItems.length === 0" class="text-center py-12">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-clipboard-text-off-outline</v-icon>
                    <p class="text-grey text-body-2 mb-0">No requests found for this category.</p>
                </v-card-text>

                <!-- Request List -->
                <v-card-text v-else class="pa-0 stat-dialog-body">
                    <div
                        v-for="(item, idx) in statDialogItems"
                        :key="item.id"
                        class="stat-dialog-item"
                        :class="{ 'stat-dialog-item-alt': idx % 2 === 1 }"
                    >
                        <div class="d-flex align-center justify-space-between mb-2">
                            <div class="d-flex align-center">
                                <v-chip :color="getUrgencyColor(item.urgency_level)" size="x-small" variant="flat" class="mr-2 font-weight-bold">
                                    {{ item.urgency_level || 'Medium' }}
                                </v-chip>
                                <span class="text-body-2 font-weight-bold text-grey-darken-3">{{ item.rescue_code }}</span>
                            </div>
                            <v-chip :color="getStatusColor(item.status)" size="x-small" variant="flat">
                                {{ formatStatus(item.status) }}
                            </v-chip>
                        </div>
                        <div class="d-flex align-center text-body-2 text-grey-darken-1 mb-1">
                            <v-icon size="14" class="mr-1">mdi-account</v-icon>
                            {{ item.requester_name || 'Anonymous' }}
                        </div>
                        <div class="d-flex align-center text-caption text-grey mb-1">
                            <v-icon size="13" class="mr-1">mdi-map-marker</v-icon>
                            {{ item.location || 'Unknown Location' }}
                        </div>
                        <div class="d-flex align-center justify-space-between">
                            <span class="text-caption text-grey">
                                <v-icon size="12" class="mr-1">mdi-clock-outline</v-icon>
                                {{ formatDate(item.created_at) }}
                            </span>
                            <span v-if="item.rescuer_name" class="text-caption text-grey">
                                <v-icon size="12" class="mr-1">mdi-lifebuoy</v-icon>
                                {{ item.rescuer_name }}
                            </span>
                        </div>
                    </div>
                </v-card-text>

                <!-- Footer -->
                <v-divider></v-divider>
                <v-card-actions class="pa-3 justify-end">
                    <v-btn variant="tonal" color="primary" size="small" rounded="xl" href="/admin/reports">
                        <v-icon start size="16">mdi-file-chart</v-icon>
                        View Full Report
                    </v-btn>
                    <v-btn variant="text" size="small" rounded="xl" @click="statDialog.show = false">
                        Close
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useDisplay } from 'vuetify';
import AdminAppBar from '@/Components/AdminAppBar.vue';
import { getAllRescueRequests } from '@/Composables/useApi';

const { mobile } = useDisplay();
const isMobile = computed(() => mobile.value);

// Props from Inertia
const props = defineProps({
    statusCounts: {
        type: Object,
        default: () => ({ total: 0, pending: 0, in_progress: 0, completed: 0 })
    },
    rescuesByBuilding: {
        type: Array,
        default: () => []
    },
    rescuerStats: {
        type: Object,
        default: () => ({ total: 0, available: 0, on_rescue: 0, off_duty: 0 })
    },
    recentAlerts: {
        type: Array,
        default: () => []
    },
    userStats: {
        type: Object,
        default: () => ({ total: 0, by_role: { student: 0, faculty: 0, staff: 0 } })
    }
});

const timeFilter = ref('week');

const timeFilters = [
    { label: 'Today', value: 'day' },
    { label: 'This Week', value: 'week' },
    { label: 'This Month', value: 'month' },
    { label: 'This Year', value: 'year' },
];

const alertHeaders = [
    { title: 'Code', key: 'rescue_code' },
    { title: 'Requester', key: 'requester_name' },
    { title: 'Location', key: 'location' },
    { title: 'Status', key: 'status' },
    { title: 'Urgency', key: 'urgency_level' },
    { title: 'Date', key: 'created_at' },
];

const statusCounts = ref(props.statusCounts);
const rescuesByBuilding = ref(props.rescuesByBuilding);
const rescuerStats = ref(props.rescuerStats);
const recentAlerts = ref(props.recentAlerts);
const userStats = ref(props.userStats);

// Refresh data on mount to ensure fresh stats
onMounted(() => {
    refreshData();
});

const refreshData = async () => {
    try {
        const response = await fetch(`/admin/dashboard?time_filter=${timeFilter.value}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success) {
            statusCounts.value = data.data.statusCounts;
            rescuesByBuilding.value = data.data.rescuesByBuilding;
            rescuerStats.value = data.data.rescuerStats;
            recentAlerts.value = data.data.recentAlerts;
            userStats.value = data.data.userStats;
        }
    } catch (error) {
        console.error('Error refreshing data:', error);
    }
};

// ── Stat Card Detail Dialog ──
const statDialog = ref({
    show: false,
    type: 'total',
    loading: false,
});
const statDialogItems = ref([]);

const STAT_CONFIG = {
    total: {
        title: 'All Rescue Requests',
        icon: 'mdi-alert-circle-outline',
        statuses: null, // all
    },
    pending: {
        title: 'People Who Need Help',
        icon: 'mdi-hand-heart',
        statuses: ['pending'],
    },
    in_progress: {
        title: 'Rescues In Progress',
        icon: 'mdi-progress-clock',
        statuses: ['accepted', 'in_progress', 'en_route'],
    },
    completed: {
        title: 'Marked as Safe',
        icon: 'mdi-shield-check',
        statuses: ['completed', 'rescued', 'safe'],
    },
};

const statDialogConfig = computed(() => STAT_CONFIG[statDialog.value.type] || STAT_CONFIG.total);

const openStatDialog = async (type) => {
    statDialog.value = { show: true, type, loading: true };
    statDialogItems.value = [];

    try {
        const response = await getAllRescueRequests();
        const data = response?.data || response;
        let all = Array.isArray(data) ? data : (data?.data || []);

        // Filter by status category
        const config = STAT_CONFIG[type];
        if (config.statuses) {
            all = all.filter(r => config.statuses.includes(r.status));
        }

        // Map to display format
        statDialogItems.value = all
            .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
            .map(r => ({
                id: r.id,
                rescue_code: r.rescue_code || '—',
                status: r.status,
                urgency_level: r.urgency_level,
                requester_name: r.firstName ? `${r.firstName} ${r.lastName || ''}`.trim() : 'Anonymous',
                location: [r.building?.name, r.floor?.floor_name, r.room?.room_name].filter(Boolean).join(' › ') || 'Unknown',
                rescuer_name: r.rescuer ? `${r.rescuer.first_name || ''} ${r.rescuer.last_name || ''}`.trim() : null,
                created_at: r.created_at,
            }));
    } catch (err) {
        console.error('Error fetching stat details:', err);
    } finally {
        statDialog.value.loading = false;
    }
};

const getStatusColor = (status) => {
    const colors = {
        'pending': 'warning',
        'accepted': 'info',
        'in_progress': 'info',
        'en_route': 'info',
        'rescued': 'success',
        'completed': 'success',
        'safe': 'success',
        'cancelled': 'error'
    };
    return colors[status] || 'grey';
};

const formatStatus = (status) => {
    return status?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || 'Unknown';
};

const getUrgencyColor = (urgency) => {
    const colors = {
        'Critical': 'error',
        'High': 'orange',
        'Medium': 'warning',
        'Low': 'success'
    };
    return colors[urgency] || 'grey';
};

const getBuildingColor = (index) => {
    const colors = ['primary', 'info', 'success', 'warning', 'purple', 'teal', 'orange', 'pink'];
    return colors[index % colors.length];
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<style scoped>

/* Gradient Text */
.gradient-text {
    background: linear-gradient(135deg, #1976D2, #0D47A1);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Stat Cards with Gradient Backgrounds */
.stat-card {
    position: relative;
    overflow: hidden;
}

.stat-card-overlay {
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    pointer-events: none;
}

.stat-card-primary {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 50%, #0D47A1 100%) !important;
}

.stat-card-warning {
    background: linear-gradient(135deg, #FB8C00 0%, #F57C00 50%, #EF6C00 100%) !important;
}

.stat-card-info {
    background: linear-gradient(135deg, #00ACC1 0%, #0097A7 50%, #00838F 100%) !important;
}

.stat-card-success {
    background: linear-gradient(135deg, #43A047 0%, #388E3C 50%, #2E7D32 100%) !important;
}

/* Clickable stat cards */
.stat-card-clickable {
    cursor: pointer;
}
.stat-card-clickable:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.18) !important;
}
.stat-card-clickable:active {
    transform: translateY(-2px) !important;
}

/* ── Stat Dialog ── */
.stat-dialog-card {
    overflow: hidden;
}

.stat-dialog-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px;
}

.stat-dialog-header-total {
    background: linear-gradient(135deg, #1976D2 0%, #0D47A1 100%);
}
.stat-dialog-header-pending {
    background: linear-gradient(135deg, #FB8C00 0%, #EF6C00 100%);
}
.stat-dialog-header-in_progress {
    background: linear-gradient(135deg, #00ACC1 0%, #00838F 100%);
}
.stat-dialog-header-completed {
    background: linear-gradient(135deg, #43A047 0%, #2E7D32 100%);
}

.text-white-50 {
    color: rgba(255, 255, 255, 0.7) !important;
}

.stat-dialog-body {
    max-height: 420px;
    overflow-y: auto;
}

.stat-dialog-item {
    padding: 14px 20px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.12s ease;
}
.stat-dialog-item:last-child {
    border-bottom: none;
}
.stat-dialog-item:hover {
    background: #fafbfd;
}
.stat-dialog-item-alt {
    background: #fafafa;
}
.stat-dialog-item-alt:hover {
    background: #f0f4f8;
}

/* Background colors for stats */
.bg-success-lighten-5 {
    background-color: rgba(76, 175, 80, 0.08) !important;
}

.bg-warning-lighten-5 {
    background-color: rgba(255, 152, 0, 0.08) !important;
}

.bg-primary-lighten-5 {
    background-color: rgba(25, 118, 210, 0.08) !important;
}

.bg-blue-lighten-5 {
    background-color: rgba(33, 150, 243, 0.1) !important;
}

.bg-purple-lighten-5 {
    background-color: rgba(156, 39, 176, 0.1) !important;
}

.bg-teal-lighten-5 {
    background-color: rgba(0, 150, 136, 0.1) !important;
}

/* Text colors */
.text-white-50 {
    color: rgba(255, 255, 255, 0.7) !important;
}

.opacity-80 {
    opacity: 0.8;
}

/* Card hover effect */
.v-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.v-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

/* Stat cards don't need hover effect */
.stat-card:hover {
    transform: translateY(-4px);
}

/* Page Header Responsive Styles */
.page-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.page-header-content {
    flex: 1;
    min-width: 200px;
}

.time-filter-select {
    max-width: 150px;
    flex-shrink: 0;
}

/* Mobile Specific Styles */
@media (max-width: 600px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .page-header-content {
        width: 100%;
    }
    
    .time-filter-select {
        width: 100%;
        max-width: 100%;
    }
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   DARK MODE STYLES
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

.dark-mode {
    --bg-primary: #121212;
    --bg-secondary: #1E1E1E;
    --bg-tertiary: #2D2D2D;
    --text-primary: #E0E0E0;
    --text-secondary: #B0B0B0;
    --text-tertiary: #808080;
    --border-color: #3A3A3A;
    --shadow-color: rgba(0, 0, 0, 0.5);
}

/* Background colors */
.dark-mode .v-application,
.dark-mode .v-main,
.dark-mode .dashboard-container {
    background: var(--bg-primary) !important;
    color: var(--text-primary) !important;
}

.dark-mode .v-card {
    background: var(--bg-secondary) !important;
    color: var(--text-primary) !important;
    border-color: var(--border-color) !important;
}

.dark-mode .stat-card {
    background: linear-gradient(135deg, #1E1E1E, #2D2D2D) !important;
    box-shadow: 0 4px 12px var(--shadow-color) !important;
}

.dark-mode .stat-icon {
    filter: brightness(1.2);
}

/* Notification panel dark mode */
.dark-mode .nc-panel {
    background: var(--bg-secondary) !important;
    border-left-color: var(--border-color) !important;
}

.dark-mode .nc-header {
    background: linear-gradient(135deg, #1A365D, #2C4F7C) !important;
}

.dark-mode .nc-tab {
    color: var(--text-secondary) !important;
}

.dark-mode .nc-tab-active {
    color: #ffffff !important;
}

.dark-mode .nc-body {
    background: var(--bg-primary) !important;
}

.dark-mode .nc-item {
    background: var(--bg-secondary) !important;
    border-color: var(--border-color) !important;
}

.dark-mode .nc-item:hover {
    background: var(--bg-tertiary) !important;
}

.dark-mode .nc-item-unread {
    background: #1A2332 !important;
    border-color: #2C4F7C !important;
}

.dark-mode .nc-item-title {
    color: var(--text-primary) !important;
}

.dark-mode .nc-item-msg {
    color: var(--text-secondary) !important;
}

.dark-mode .nc-empty {
    color: var(--text-secondary) !important;
}

/* Conversation panel dark mode */
.dark-mode .conv-panel {
    background: var(--bg-secondary) !important;
    border-left-color: var(--border-color) !important;
}

.dark-mode .conv-header {
    background: linear-gradient(135deg, #1A365D, #2C4F7C) !important;
}

.dark-mode .conv-item {
    background: var(--bg-secondary) !important;
    border-color: var(--border-color) !important;
}

.dark-mode .conv-item:hover {
    background: var(--bg-tertiary) !important;
}

.dark-mode .conv-name {
    color: var(--text-primary) !important;
}

.dark-mode .conv-preview {
    color: var(--text-secondary) !important;
}

.dark-mode .msg-bubble {
    background: var(--bg-tertiary) !important;
    color: var(--text-primary) !important;
}

/* Vuetify component dark mode adjustments */
.dark-mode .v-navigation-drawer {
    background: var(--bg-secondary) !important;
}

.dark-mode .v-list-item {
    color: var(--text-primary) !important;
}

.dark-mode .v-list-item:hover {
    background: var(--bg-tertiary) !important;
}

.dark-mode .v-app-bar {
    background: var(--bg-secondary) !important;
    border-bottom: 1px solid var(--border-color) !important;
}

.dark-mode .v-btn {
    color: var(--text-primary) !important;
}

.dark-mode .v-select,
.dark-mode .v-text-field {
    background: var(--bg-tertiary) !important;
    color: var(--text-primary) !important;
}

.dark-mode .v-chip {
    background: var(--bg-tertiary) !important;
    color: var(--text-primary) !important;
}

.dark-mode .v-table {
    background: var(--bg-secondary) !important;
    color: var(--text-primary) !important;
}

.dark-mode .v-table th,
.dark-mode .v-table td {
    border-color: var(--border-color) !important;
    color: var(--text-primary) !important;
}

.dark-mode .v-divider {
    border-color: var(--border-color) !important;
}

/* Chart dark mode (if applicable) */
.dark-mode canvas {
    filter: brightness(0.9) contrast(1.1);
}

/* Ensure gradients remain vibrant in dark mode */
.dark-mode .gradient-purple,
.dark-mode .gradient-teal,
.dark-mode .gradient-orange,
.dark-mode .gradient-pink {
    filter: brightness(0.85);
}
</style>
