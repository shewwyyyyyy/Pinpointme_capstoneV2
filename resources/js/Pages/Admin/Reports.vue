<template>
    <v-app class="bg-grey-lighten-4">

        <!-- Admin App Bar -->
        <AdminAppBar activePage="reports" />

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
                            <v-col cols="12" sm="6" md="3">
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
                            <v-col cols="12" sm="6" md="3">
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
                            <v-col cols="12" md="6">
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
                        </v-row>
                        <v-row class="mt-2">
                            <v-col cols="12" class="d-flex gap-2 justify-end">
                                <v-btn variant="outlined" @click="resetFilters" size="default">
                                    <v-icon start>mdi-filter-off</v-icon>
                                    Reset
                                </v-btn>
                                <v-btn color="error" @click="openExportDialog" :loading="exporting" size="default">
                                    <v-icon start>mdi-file-pdf-box</v-icon>
                                    Export PDF
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                 

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

        <!-- Export Options Dialog -->
        <v-dialog v-model="exportDialog" max-width="420" persistent>
            <v-card rounded="xl">
                <v-card-title class="d-flex align-center bg-error pa-4">
                    <v-icon color="white" class="mr-2">mdi-file-pdf-box</v-icon>
                    <span class="text-white font-weight-bold">Export to PDF</span>
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-5">
                    <p class="text-body-2 text-grey-darken-1 mb-4">Select the time period for the report export:</p>
                    <v-select
                        v-model="exportTimeFilter"
                        :items="timeFilters"
                        item-title="label"
                        item-value="value"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        label="Time Period"
                        prepend-inner-icon="mdi-calendar-range"
                    ></v-select>
                     
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="exportDialog = false" :disabled="exporting">
                        Cancel
                    </v-btn>
                    <v-btn color="error" variant="flat" @click="confirmExport" :loading="exporting" rounded="lg">
                        <v-icon start>mdi-download</v-icon>
                        Export
                    </v-btn>
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
import { ref, computed } from 'vue';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import { useDisplay } from 'vuetify';
import AdminAppBar from '@/Components/AdminAppBar.vue';

const { mobile } = useDisplay();
const isMobile = computed(() => mobile.value);

const props = defineProps({
    reportData: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({ total: 0, pending: 0, in_progress: 0, completed: 0 }) }
});

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
const exportDialog = ref(false);
const exportTimeFilter = ref('day');
const exportReportData = ref([]);

const reportsList = ref(props.reportData || []);
const counts = ref(props.counts);

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
    { label: 'Need Help', value: 'need_help' },
    { label: 'In Progress', value: 'in_progress' },
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
        if (statusFilter.value === 'need_help') {
            data = data.filter(r => r.status === 'pending');
        } else if (statusFilter.value === 'in_progress') {
            data = data.filter(r => ['accepted', 'in_progress', 'en_route'].includes(r.status));
        } else if (statusFilter.value === 'rescued') {
            data = data.filter(r => ['rescued', 'completed', 'safe'].includes(r.status));
        }
    }
    return data;
});

const statusDistribution = computed(() => {
    const total = reportsList.value.length || 1;
    return [
        { name: 'Need Help', count: counts.value.pending, percentage: Math.round((counts.value.pending / total) * 100), color: 'warning' },
        { name: 'In Progress', count: counts.value.in_progress, percentage: Math.round((counts.value.in_progress / total) * 100), color: 'info' },
        { name: 'Rescued', count: counts.value.completed, percentage: Math.round((counts.value.completed / total) * 100), color: 'success' }
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

const getExportRecordCount = computed(() => {
    return exportReportData.value.length;
});

const openExportDialog = () => {
    exportTimeFilter.value = timeFilter.value;
    exportReportData.value = filteredData.value;
    exportDialog.value = true;
};

const confirmExport = async () => {
    // Fetch data for selected time period if different from current
    if (exportTimeFilter.value !== timeFilter.value) {
        try {
            exporting.value = true;
            const params = new URLSearchParams();
            params.append('time_filter', exportTimeFilter.value);
            if (statusFilter.value !== 'all') params.append('status_filter', statusFilter.value);
            
            const response = await fetch(`/admin/reports?${params}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (data.success) {
                exportReportData.value = data.data;
                await exportToPDF(exportReportData.value, exportTimeFilter.value);
            }
        } catch (error) {
            console.error('Error fetching export data:', error);
            showSnackbar('Export failed', 'error');
        } finally {
            exporting.value = false;
            exportDialog.value = false;
        }
    } else {
        await exportToPDF(filteredData.value, exportTimeFilter.value);
        exportDialog.value = false;
    }
};

const exportToPDF = async (data, timePeriod) => {
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
        const periodText = timePeriod === 'day' ? 'Today' : 
                          timePeriod === 'week' ? 'This Week' : 
                          timePeriod === 'month' ? 'This Month' : 'This Year';
        doc.text(`Period: ${periodText}`, pageWidth - 60, 28);
        
        // Table headers and data
        const tableColumn = ['Code', 'Reported By', 'Location', 'Time', 'Date', 'Status', 'Urgency', 'Rescuer'];
        const tableRows = data.map(r => [
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
        safe: 'success',
        cancelled: 'error'
    };
    return colors[status] || 'grey';
};

const formatStatus = (status) => {
    const labels = {
        pending: 'Need Help',
        accepted: 'In Progress',
        in_progress: 'In Progress',
        en_route: 'In Progress',
        rescued: 'Rescued',
        completed: 'Rescued',
        safe: 'Rescued',
        cancelled: 'Cancelled'
    };
    return labels[status] || status?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || 'Unknown';
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
