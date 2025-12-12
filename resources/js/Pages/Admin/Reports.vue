<template>
    <v-app class="bg-grey-lighten-4">
        <!-- App Bar -->
        <v-app-bar color="primary" elevation="2">
            <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
            <v-app-bar-title>
                <v-icon class="mr-2">mdi-shield-check</v-icon>
                PinPointMe Admin
            </v-app-bar-title>
            <v-spacer />
            <v-btn icon @click="exportReport" :loading="exporting">
                <v-icon>mdi-download</v-icon>
            </v-btn>
            <v-btn icon @click="logout">
                <v-icon>mdi-logout</v-icon>
            </v-btn>
        </v-app-bar>

        <!-- Navigation Drawer -->
        <v-navigation-drawer v-model="drawer" permanent>
            <v-list>
                <v-list-item prepend-icon="mdi-view-dashboard" title="Dashboard" href="/admin/dashboard"></v-list-item>
                <v-list-item prepend-icon="mdi-account-group" title="Users" href="/admin/users"></v-list-item>
                <v-list-item prepend-icon="mdi-lifebuoy" title="Rescuers" href="/admin/rescuers"></v-list-item>
                <v-list-item prepend-icon="mdi-office-building" title="Buildings" href="/admin/buildings"></v-list-item>
                <v-list-item prepend-icon="mdi-file-chart" title="Reports" href="/admin/reports" active></v-list-item>
                <v-list-item prepend-icon="mdi-shield-alert" title="Preventive Measures" href="/admin/preventive-measures"></v-list-item>
            </v-list>
        </v-navigation-drawer>

        <!-- Main Content -->
        <v-main>
            <v-container fluid class="pa-6">
                <!-- Page Header -->
                <div class="d-flex align-center mb-6">
                    <div>
                        <h1 class="text-h4 font-weight-bold">Rescue Reports</h1>
                        <p class="text-grey mt-1">View and analyze rescue request data</p>
                    </div>
                    <v-spacer />
                </div>

                <!-- Filters -->
                <v-card rounded="lg" class="mb-6">
                    <v-card-text>
                        <v-row align="center">
                            <v-col cols="12" md="3">
                                <v-select
                                    v-model="timeFilter"
                                    :items="timeFilters"
                                    item-title="label"
                                    item-value="value"
                                    label="Time Period"
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    @update:model-value="fetchReports"
                                />
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-select
                                    v-model="statusFilter"
                                    :items="statusFilters"
                                    item-title="label"
                                    item-value="value"
                                    label="Status"
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    @update:model-value="fetchReports"
                                />
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-text-field
                                    v-model="search"
                                    prepend-inner-icon="mdi-magnify"
                                    label="Search..."
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    clearable
                                />
                            </v-col>
                            <v-col cols="12" md="3" class="d-flex gap-2">
                                <v-btn variant="outlined" @click="resetFilters">
                                    <v-icon start>mdi-filter-off</v-icon>
                                    Reset
                                </v-btn>
                                <v-btn color="primary" @click="exportReport" :loading="exporting">
                                    <v-icon start>mdi-download</v-icon>
                                    Export
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <!-- Stats Cards -->
                <v-row class="mb-6">
                    <v-col cols="6" md="3">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="primary" size="48">
                                    <v-icon color="white">mdi-alert-circle</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Total Requests</p>
                                    <h4 class="text-h5 font-weight-bold">{{ counts.total }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" md="3">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="warning" size="48">
                                    <v-icon color="white">mdi-clock</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Pending</p>
                                    <h4 class="text-h5 font-weight-bold text-warning">{{ counts.pending }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" md="3">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="info" size="48">
                                    <v-icon color="white">mdi-progress-clock</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">In Progress</p>
                                    <h4 class="text-h5 font-weight-bold text-info">{{ counts.in_progress }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" md="3">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="success" size="48">
                                    <v-icon color="white">mdi-check-circle</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Completed</p>
                                    <h4 class="text-h5 font-weight-bold text-success">{{ counts.completed }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Reports Table -->
                <v-card rounded="lg">
                    <v-card-title class="d-flex align-center">
                        <v-icon start>mdi-table</v-icon>
                        Rescue Requests
                        <v-spacer />
                        <v-chip size="small" color="primary">{{ filteredData.length }} records</v-chip>
                    </v-card-title>
                    <v-card-text>
                        <v-data-table
                            :headers="headers"
                            :items="filteredData"
                            :search="search"
                            :loading="loading"
                            :items-per-page="10"
                            class="elevation-0"
                        >
                            <template v-slot:item.name="{ item }">
                                <div class="d-flex align-center py-2">
                                    <v-avatar color="primary" size="32" class="mr-2">
                                        <span class="text-white text-caption">{{ getInitials(item.name) }}</span>
                                    </v-avatar>
                                    {{ item.name }}
                                </div>
                            </template>
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
                            <template v-slot:item.actions="{ item }">
                                <v-btn icon size="small" variant="text" @click="viewDetails(item)">
                                    <v-icon size="small">mdi-eye</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-card-text>
                </v-card>

                <!-- Summary Charts (simplified for Vue) -->
                <v-row class="mt-6">
                    <v-col cols="12" md="6">
                        <v-card rounded="lg">
                            <v-card-title>
                                <v-icon start>mdi-chart-pie</v-icon>
                                Status Distribution
                            </v-card-title>
                            <v-card-text>
                                <div class="d-flex flex-column gap-3">
                                    <div v-for="status in statusDistribution" :key="status.name">
                                        <div class="d-flex justify-space-between mb-1">
                                            <span class="text-caption">{{ status.name }}</span>
                                            <span class="text-caption font-weight-medium">{{ status.count }} ({{ status.percentage }}%)</span>
                                        </div>
                                        <v-progress-linear
                                            :model-value="status.percentage"
                                            :color="status.color"
                                            height="8"
                                            rounded
                                        />
                                    </div>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-card rounded="lg">
                            <v-card-title>
                                <v-icon start>mdi-office-building</v-icon>
                                Requests by Building
                            </v-card-title>
                            <v-card-text>
                                <v-list density="compact">
                                    <v-list-item v-for="building in buildingDistribution" :key="building.name">
                                        <template v-slot:prepend>
                                            <v-avatar color="primary" size="32">
                                                <v-icon size="small">mdi-domain</v-icon>
                                            </v-avatar>
                                        </template>
                                        <v-list-item-title>{{ building.name }}</v-list-item-title>
                                        <template v-slot:append>
                                            <v-chip color="primary" size="small">{{ building.count }}</v-chip>
                                        </template>
                                    </v-list-item>
                                </v-list>
                                <v-alert v-if="buildingDistribution.length === 0" type="info" variant="tonal" density="compact">
                                    No data available
                                </v-alert>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>
        </v-main>

        <!-- Details Dialog -->
        <v-dialog v-model="detailsDialog" max-width="600">
            <v-card v-if="selectedReport">
                <v-card-title class="d-flex align-center">
                    <v-icon start color="primary">mdi-file-document</v-icon>
                    Rescue Request Details
                    <v-spacer />
                    <v-chip :color="getStatusColor(selectedReport.status)" size="small">
                        {{ formatStatus(selectedReport.status) }}
                    </v-chip>
                </v-card-title>
                <v-card-text>
                    <v-list density="compact">
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-identifier</v-icon>
                            </template>
                            <v-list-item-title>Rescue Code</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedReport.rescue_code }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-account</v-icon>
                            </template>
                            <v-list-item-title>Requester</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedReport.name }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-map-marker</v-icon>
                            </template>
                            <v-list-item-title>Location</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedReport.location }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-clock</v-icon>
                            </template>
                            <v-list-item-title>Date & Time</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedReport.date }} at {{ selectedReport.time }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item v-if="selectedReport.rescuer_name">
                            <template v-slot:prepend>
                                <v-icon>mdi-lifebuoy</v-icon>
                            </template>
                            <v-list-item-title>Assigned Rescuer</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedReport.rescuer_name }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-alert</v-icon>
                            </template>
                            <v-list-item-title>Urgency Level</v-list-item-title>
                            <v-list-item-subtitle>
                                <v-chip :color="getUrgencyColor(selectedReport.urgency_level)" size="small">
                                    {{ selectedReport.urgency_level || 'Medium' }}
                                </v-chip>
                            </v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="detailsDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000">
            {{ snackbarText }}
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    reportData: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({ total: 0, pending: 0, in_progress: 0, completed: 0 }) }
});

const drawer = ref(true);
const loading = ref(false);
const exporting = ref(false);
const search = ref('');
const timeFilter = ref('day');
const statusFilter = ref('all');
const detailsDialog = ref(false);
const selectedReport = ref(null);
const snackbar = ref(false);
const snackbarText = ref('');
const snackbarColor = ref('success');

const reportsList = ref(props.reportData || []);
const counts = ref(props.counts);

const timeFilters = [
    { label: 'Today', value: 'day' },
    { label: 'This Week', value: 'week' },
    { label: 'This Month', value: 'month' },
    { label: 'This Year', value: 'year' }
];

const statusFilters = [
    { label: 'All Status', value: 'all' },
    { label: 'Pending', value: 'pending' },
    { label: 'In Progress', value: 'in_progress' },
    { label: 'Completed', value: 'completed' },
    { label: 'Rescued', value: 'rescued' }
];

const headers = [
    { title: 'Code', key: 'rescue_code', width: '100px' },
    { title: 'Name', key: 'name' },
    { title: 'Location', key: 'location' },
    { title: 'Time', key: 'time', width: '100px' },
    { title: 'Date', key: 'date', width: '120px' },
    { title: 'Status', key: 'status', width: '120px' },
    { title: 'Urgency', key: 'urgency_level', width: '100px' },
    { title: 'Actions', key: 'actions', sortable: false, width: '80px' }
];

const filteredData = computed(() => {
    let data = reportsList.value;
    if (statusFilter.value !== 'all') {
        if (statusFilter.value === 'in_progress') {
            data = data.filter(r => ['accepted', 'in_progress', 'en_route'].includes(r.status));
        } else {
            data = data.filter(r => r.status === statusFilter.value);
        }
    }
    return data;
});

const statusDistribution = computed(() => {
    const total = reportsList.value.length || 1;
    return [
        { name: 'Pending', count: counts.value.pending, percentage: Math.round((counts.value.pending / total) * 100), color: 'warning' },
        { name: 'In Progress', count: counts.value.in_progress, percentage: Math.round((counts.value.in_progress / total) * 100), color: 'info' },
        { name: 'Completed', count: counts.value.completed, percentage: Math.round((counts.value.completed / total) * 100), color: 'success' }
    ];
});

const buildingDistribution = computed(() => {
    const buildings = {};
    reportsList.value.forEach(r => {
        const name = r.building || 'Unknown';
        buildings[name] = (buildings[name] || 0) + 1;
    });
    return Object.entries(buildings)
        .map(([name, count]) => ({ name, count }))
        .sort((a, b) => b.count - a.count)
        .slice(0, 5);
});

const fetchReports = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('time_filter', timeFilter.value);
        if (statusFilter.value !== 'all') params.append('status_filter', statusFilter.value);
        
        const response = await fetch(`/admin/reports?${params}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success) {
            reportsList.value = data.data;
            counts.value = data.counts;
        }
    } catch (error) {
        console.error('Error fetching reports:', error);
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    timeFilter.value = 'day';
    statusFilter.value = 'all';
    search.value = '';
    fetchReports();
};

const viewDetails = (item) => {
    selectedReport.value = item;
    detailsDialog.value = true;
};

const exportReport = async () => {
    exporting.value = true;
    try {
        // Generate CSV
        const csvContent = generateCSV(filteredData.value);
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `rescue_report_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showSnackbar('Report exported successfully', 'success');
    } catch (error) {
        console.error('Error exporting report:', error);
        showSnackbar('Error exporting report', 'error');
    } finally {
        exporting.value = false;
    }
};

const generateCSV = (data) => {
    const headers = ['Rescue Code', 'Name', 'Location', 'Time', 'Date', 'Status', 'Urgency', 'Rescuer'];
    const rows = data.map(r => [
        r.rescue_code,
        r.name,
        r.location,
        r.time,
        r.date,
        r.status,
        r.urgency_level || 'Medium',
        r.rescuer_name || ''
    ]);
    return [headers, ...rows].map(row => row.map(cell => `"${cell || ''}"`).join(',')).join('\n');
};

const showSnackbar = (text, color) => {
    snackbarText.value = text;
    snackbarColor.value = color;
    snackbar.value = true;
};

const logout = async () => {
    localStorage.removeItem('userData');
    localStorage.removeItem('authToken');
    localStorage.removeItem('token');
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        await fetch('/logout', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            credentials: 'include'
        });
    } catch (e) { console.error('Logout error:', e); }
    
    window.location.href = '/login';
};

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'warning',
        accepted: 'info',
        in_progress: 'info',
        en_route: 'info',
        rescued: 'success',
        completed: 'success',
        cancelled: 'error'
    };
    return colors[status] || 'grey';
};

const formatStatus = (status) => {
    return status?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || 'Unknown';
};

const getUrgencyColor = (urgency) => {
    const colors = {
        Critical: 'error',
        High: 'orange',
        Medium: 'warning',
        Low: 'success'
    };
    return colors[urgency] || 'grey';
};

onMounted(() => {
    // Data comes from Inertia props
});
</script>
