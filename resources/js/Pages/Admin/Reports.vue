<template>
    <v-app class="bg-grey-lighten-4">

        <!-- App Bar (Unified) -->
        <v-app-bar color="primary" elevation="2">
            <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
            <v-app-bar-title>
                <v-icon class="mr-2" color="white">mdi-shield-check</v-icon>
                <span class="text-white font-weight-bold">PinPointMe Admin</span>
            </v-app-bar-title>
            <v-spacer />
            <!-- Profile Avatar Menu -->
            <v-menu offset-y>
                <template v-slot:activator="{ props }">
                    <v-btn icon v-bind="props">
                        <v-avatar color="white" size="36">
                            <span class="text-primary font-weight-bold">{{ adminInitials }}</span>
                        </v-avatar>
                    </v-btn>
                </template>
                <v-list>
                    <v-list-item @click="goToProfile" prepend-icon="mdi-account">
                        <v-list-item-title>Profile</v-list-item-title>
                    </v-list-item>
                    <v-list-item @click="toggleDarkMode" prepend-icon="mdi-theme-light-dark">
                        <v-list-item-title>{{ isDark ? 'Light Mode' : 'Dark Mode' }}</v-list-item-title>
                    </v-list-item>
                    <v-list-item @click="logout" prepend-icon="mdi-logout">
                        <v-list-item-title>Logout</v-list-item-title>
                    </v-list-item>
                </v-list>
            </v-menu>
        </v-app-bar>

        <!-- Navigation Drawer (Unified) -->
        <v-navigation-drawer
            v-model="drawer"
            :permanent="!isMobile"
            :temporary="isMobile"
            app
        >
            <v-list>
                <v-list-item prepend-icon="mdi-view-dashboard" title="Dashboard" href="/admin/dashboard" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-account-group" title="Users" href="/admin/users" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-lifebuoy" title="Rescuers" href="/admin/rescuers" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-office-building" title="Buildings" href="/admin/buildings" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-file-chart" title="Reports" href="/admin/reports" active @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-shield-alert" title="Preventive Measures" href="/admin/preventive-measures" @click="closeDrawerOnMobile"></v-list-item>
            </v-list>
        </v-navigation-drawer>

        <!-- Main Content -->
        <v-main>
            <v-container fluid :class="isMobile ? 'pa-3' : 'pa-6'">
                <!-- Page Header -->
                <div class="page-header mb-4 mb-md-6">
                    <div class="page-header-content">
                        <h1 :class="isMobile ? 'text-h5' : 'text-h4'" class="font-weight-bold">Rescue Reports</h1>
                        <p class="text-grey mt-1 text-body-2">View and analyze rescue request data</p>
                    </div>
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
                                <v-btn color="error" @click="exportToPDF" :loading="exporting">
                                    <v-icon start>mdi-file-pdf-box</v-icon>
                                    Export PDF
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <!-- Enhanced Stats Cards -->
                <v-row class="mb-6">
                    <v-col cols="12" sm="6" md="3">
                        <v-card class="stat-card stat-card-primary" rounded="xl" elevation="4">
                            <div class="stat-card-overlay"></div>
                            <v-card-text class="position-relative pa-4">
                                <div class="d-flex align-center justify-space-between">
                                    <div>
                                        <p class="text-white text-caption mb-1 opacity-80">Total Requests</p>
                                        <h2 class="text-h3 font-weight-bold text-white">{{ counts.total }}</h2>
                                        <v-chip color="rgba(255,255,255,0.2)" size="x-small" class="mt-2 text-white">
                                            <v-icon start size="12">mdi-trending-up</v-icon>
                                            {{ timeFilter === 'day' ? 'Today' : timeFilter === 'week' ? 'This Week' : timeFilter === 'month' ? 'This Month' : 'This Year' }}
                                        </v-chip>
                                    </div>
                                    <v-avatar size="56" color="rgba(255,255,255,0.2)">
                                        <v-icon size="32" color="white">mdi-alert-circle-outline</v-icon>
                                    </v-avatar>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="6" md="3">
                        <v-card class="stat-card stat-card-warning" rounded="xl" elevation="4">
                            <div class="stat-card-overlay"></div>
                            <v-card-text class="position-relative pa-4">
                                <div class="d-flex align-center justify-space-between">
                                    <div>
                                        <p class="text-white text-caption mb-1 opacity-80">Pending</p>
                                        <h2 class="text-h3 font-weight-bold text-white">{{ counts.pending }}</h2>
                                        <v-chip color="rgba(255,255,255,0.2)" size="x-small" class="mt-2 text-white">
                                            <v-icon start size="12">mdi-clock-outline</v-icon>
                                            {{ getPercentage(counts.pending) }}% of total
                                        </v-chip>
                                    </div>
                                    <v-avatar size="56" color="rgba(255,255,255,0.2)">
                                        <v-icon size="32" color="white">mdi-clock-alert-outline</v-icon>
                                    </v-avatar>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="6" md="3">
                        <v-card class="stat-card stat-card-info" rounded="xl" elevation="4">
                            <div class="stat-card-overlay"></div>
                            <v-card-text class="position-relative pa-4">
                                <div class="d-flex align-center justify-space-between">
                                    <div>
                                        <p class="text-white text-caption mb-1 opacity-80">In Progress</p>
                                        <h2 class="text-h3 font-weight-bold text-white">{{ counts.in_progress }}</h2>
                                        <v-chip color="rgba(255,255,255,0.2)" size="x-small" class="mt-2 text-white">
                                            <v-icon start size="12">mdi-run-fast</v-icon>
                                            {{ getPercentage(counts.in_progress) }}% of total
                                        </v-chip>
                                    </div>
                                    <v-avatar size="56" color="rgba(255,255,255,0.2)">
                                        <v-icon size="32" color="white">mdi-progress-clock</v-icon>
                                    </v-avatar>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="6" md="3">
                        <v-card class="stat-card stat-card-success" rounded="xl" elevation="4">
                            <div class="stat-card-overlay"></div>
                            <v-card-text class="position-relative pa-4">
                                <div class="d-flex align-center justify-space-between">
                                    <div>
                                        <p class="text-white text-caption mb-1 opacity-80">Completed</p>
                                        <h2 class="text-h3 font-weight-bold text-white">{{ counts.completed }}</h2>
                                        <v-chip color="rgba(255,255,255,0.2)" size="x-small" class="mt-2 text-white">
                                            <v-icon start size="12">mdi-check-all</v-icon>
                                            {{ getPercentage(counts.completed) }}% success rate
                                        </v-chip>
                                    </div>
                                    <v-avatar size="56" color="rgba(255,255,255,0.2)">
                                        <v-icon size="32" color="white">mdi-check-circle-outline</v-icon>
                                    </v-avatar>
                                </div>
                            </v-card-text>
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
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import { useDisplay } from 'vuetify';
import { setUserActiveStatus } from '@/Utilities/firebase';

const { mobile } = useDisplay();
const isMobile = computed(() => mobile.value);

const isDark = ref(false);
const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('v-theme--dark', isDark.value);
};
const goToProfile = () => {
    window.location.href = '/admin/profile';
};
const closeDrawerOnMobile = () => {
    if (isMobile.value) {
        drawer.value = false;
    }
};

const props = defineProps({
    reportData: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({ total: 0, pending: 0, in_progress: 0, completed: 0 }) }
});

const drawer = ref(!mobile.value);
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

// Admin initials for profile
const adminInitials = computed(() => {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    if (userData.first_name && userData.last_name) {
        return `${userData.first_name[0]}${userData.last_name[0]}`.toUpperCase();
    }
    return 'AD';
});

// Calculate percentage helper
const getPercentage = (value) => {
    if (!counts.value.total || counts.value.total === 0) return 0;
    return Math.round((value / counts.value.total) * 100);
};

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
    { title: 'Reported By', key: 'name' },
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

const exportToPDF = async () => {
    exporting.value = true;
    try {
        const doc = new jsPDF('p', 'mm', 'a4');
        const pageWidth = doc.internal.pageSize.getWidth();
        
        // Header with gradient effect simulation
        doc.setFillColor(25, 118, 210);
        doc.rect(0, 0, pageWidth, 35, 'F');
        
        // Logo placeholder and title
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(22);
        doc.setFont('helvetica', 'bold');
        doc.text('PinPointMe - Rescue Reports', 14, 18);
        
        // Date and time
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        const reportDate = new Date().toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        doc.text(`Generated: ${reportDate}`, 14, 28);
        
        // Time period filter
        const periodText = timeFilter.value === 'day' ? 'Today' : 
                          timeFilter.value === 'week' ? 'This Week' : 
                          timeFilter.value === 'month' ? 'This Month' : 'This Year';
        doc.text(`Period: ${periodText}`, pageWidth - 60, 28);
        
        // Table headers and data
        const tableColumn = ['Code', 'Reported By', 'Location', 'Time', 'Date', 'Status', 'Urgency', 'Rescuer'];
        const tableRows = filteredData.value.map(r => [
            r.rescue_code || '',
            r.name || '',
            r.location || '',
            r.time || '',
            r.date || '',
            formatStatus(r.status),
            r.urgency_level || 'Medium',
            r.rescuer_name || 'Unassigned'
        ]);
        
        autoTable(doc, {
            head: [tableColumn],
            body: tableRows,
            startY: 40,
            theme: 'striped',
            headStyles: {
                fillColor: [25, 118, 210],
                textColor: [255, 255, 255],
                fontStyle: 'bold',
                fontSize: 9
            },
            bodyStyles: {
                fontSize: 8
            },
            alternateRowStyles: {
                fillColor: [245, 245, 245]
            },
            // Remove columnStyles for portrait, let autoTable auto-fit columns
            didDrawPage: function(data) {
                // Footer
                doc.setFontSize(8);
                doc.setTextColor(128, 128, 128);
                doc.text(
                    `Page ${doc.internal.getNumberOfPages()}`,
                    pageWidth / 2,
                    doc.internal.pageSize.getHeight() - 10,
                    { align: 'center' }
                );
                doc.text(
                    'PinPointMe Emergency Response System',
                    14,
                    doc.internal.pageSize.getHeight() - 10
                );
            }
        });
        
        // Save the PDF
        const fileName = `rescue_report_${new Date().toISOString().split('T')[0]}.pdf`;
        doc.save(fileName);
        
        showSnackbar('PDF report exported successfully!', 'success');
    } catch (error) {
        console.error('Error exporting PDF:', error);
        showSnackbar('Error exporting PDF. Please try again.', 'error');
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
    // Set user as inactive in Firebase (keep FCM token for offline notifications)
    try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        if (userData.id) {
            await setUserActiveStatus(userData.id, false);
            console.log('[Logout] User marked as inactive in Firebase');
        }
    } catch (e) {
        console.error('[Logout] Error setting user inactive:', e);
    }

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

<style scoped>
/* Gradient App Bar */
.gradient-app-bar {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 50%, #0D47A1 100%) !important;
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

.opacity-80 {
    opacity: 0.8;
}

.gap-2 {
    gap: 8px;
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

/* Mobile Specific Styles */
@media (max-width: 600px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .page-header-content {
        width: 100%;
    }
}
</style>
