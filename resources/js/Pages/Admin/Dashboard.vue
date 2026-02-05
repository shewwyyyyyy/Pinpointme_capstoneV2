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
                <v-list-item prepend-icon="mdi-view-dashboard" title="Dashboard" href="/admin/dashboard" active @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-account-group" title="Users" href="/admin/users" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-lifebuoy" title="Rescuers" href="/admin/rescuers" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-office-building" title="Buildings" href="/admin/buildings" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-file-chart" title="Reports" href="/admin/reports" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-shield-alert" title="Preventive Measures" href="/admin/preventive-measures" @click="closeDrawerOnMobile"></v-list-item>
            </v-list>
        </v-navigation-drawer>

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
                        <v-card class="stat-card stat-card-primary" rounded="xl" elevation="4">
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
                        <v-card class="stat-card stat-card-warning" rounded="xl" elevation="4">
                            <div class="stat-card-overlay"></div>
                            <v-card-text class="position-relative">
                                <div class="d-flex align-center justify-space-between">
                                    <div>
                                        <p class="text-white text-caption mb-1 opacity-80">Pending</p>
                                        <h2 class="text-h3 font-weight-bold text-white">{{ statusCounts.pending }}</h2>
                                        <v-chip color="rgba(255,255,255,0.2)" size="x-small" class="mt-2 text-white">
                                            <v-icon start size="12">mdi-clock-outline</v-icon>
                                            Awaiting
                                        </v-chip>
                                    </div>
                                    <v-avatar size="64" color="rgba(255,255,255,0.2)">
                                        <v-icon size="36" color="white">mdi-clock-alert-outline</v-icon>
                                    </v-avatar>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="6" lg="3">
                        <v-card class="stat-card stat-card-info" rounded="xl" elevation="4">
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
                        <v-card class="stat-card stat-card-success" rounded="xl" elevation="4">
                            <div class="stat-card-overlay"></div>
                            <v-card-text class="position-relative">
                                <div class="d-flex align-center justify-space-between">
                                    <div>
                                        <p class="text-white text-caption mb-1 opacity-80">Completed</p>
                                        <h2 class="text-h3 font-weight-bold text-white">{{ statusCounts.completed }}</h2>
                                        <v-chip color="rgba(255,255,255,0.2)" size="x-small" class="mt-2 text-white">
                                            <v-icon start size="12">mdi-check-all</v-icon>
                                            Resolved
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
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useDisplay } from 'vuetify';

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

const page = usePage();

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

const drawer = ref(!mobile.value);
const currentPage = ref('dashboard');
const timeFilter = ref('week');

// Admin Profile
const adminProfile = ref({
    full_name: 'Administrator',
    email: '',
    profile_picture: null
});

const adminInitials = computed(() => {
    if (adminProfile.value.full_name) {
        const names = adminProfile.value.full_name.split(' ');
        if (names.length >= 2) {
            return `${names[0][0]}${names[names.length - 1][0]}`.toUpperCase();
        }
        return names[0][0]?.toUpperCase() || 'AD';
    }
    return 'AD';
});

// Load admin profile from localStorage or page props
const loadAdminProfile = () => {
    try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        if (userData.first_name || userData.last_name) {
            adminProfile.value = {
                full_name: `${userData.first_name || ''} ${userData.last_name || ''}`.trim(),
                email: userData.email || '',
                profile_picture: userData.profile_picture || null
            };
        } else if (page.props.auth?.user) {
            const user = page.props.auth.user;
            adminProfile.value = {
                full_name: `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.name || 'Administrator',
                email: user.email || '',
                profile_picture: user.profile_picture || null
            };
        }
    } catch (e) {
        console.error('Error loading admin profile:', e);
    }
};

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

const logout = async () => {
    // Clear local storage
    localStorage.removeItem('userData');
    localStorage.removeItem('authToken');
    localStorage.removeItem('token');
    
    // Post logout using fetch and redirect
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        await fetch('/logout', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            credentials: 'include'
        });
    } catch (e) {
        console.error('Logout error:', e);
    }
    
    // Force redirect to login
    window.location.href = '/login';
};

const getStatusColor = (status) => {
    const colors = {
        'pending': 'warning',
        'accepted': 'info',
        'in_progress': 'info',
        'en_route': 'info',
        'rescued': 'success',
        'completed': 'success',
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

onMounted(() => {
    // Load admin profile data
    loadAdminProfile();
});
</script>

<style scoped>
/* Gradient App Bar */
.gradient-app-bar {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 50%, #0D47A1 100%) !important;
}

/* Gradient Drawer Header */
.gradient-drawer-header {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%);
}

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
</style>
