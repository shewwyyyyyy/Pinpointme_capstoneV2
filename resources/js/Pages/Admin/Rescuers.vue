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
            <v-btn icon @click="logout">
                <v-icon>mdi-logout</v-icon>
            </v-btn>
        </v-app-bar>

        <!-- Navigation Drawer -->
        <v-navigation-drawer v-model="drawer" permanent>
            <v-list>
                <v-list-item prepend-icon="mdi-view-dashboard" title="Dashboard" href="/admin/dashboard"></v-list-item>
                <v-list-item prepend-icon="mdi-account-group" title="Users" href="/admin/users"></v-list-item>
                <v-list-item prepend-icon="mdi-lifebuoy" title="Rescuers" href="/admin/rescuers" active></v-list-item>
                <v-list-item prepend-icon="mdi-office-building" title="Buildings" href="/admin/buildings"></v-list-item>
                <v-list-item prepend-icon="mdi-file-chart" title="Reports" href="/admin/reports"></v-list-item>
                <v-list-item prepend-icon="mdi-shield-alert" title="Preventive Measures" href="/admin/preventive-measures"></v-list-item>
            </v-list>
        </v-navigation-drawer>

        <!-- Main Content -->
        <v-main>
            <v-container fluid class="pa-6">
                <!-- Page Header -->
                <div class="d-flex align-center mb-6">
                    <div>
                        <h1 class="text-h4 font-weight-bold">Rescuer Management</h1>
                        <p class="text-grey mt-1">Manage rescue team members and their status</p>
                    </div>
                    <v-spacer />
                    <v-btn color="primary" @click="openAddDialog">
                        <v-icon start>mdi-plus</v-icon>
                        Add Rescuer
                    </v-btn>
                </div>

                <!-- Status Cards -->
                <v-row class="mb-6">
                    <v-col cols="6" sm="3">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="primary" size="48">
                                    <v-icon color="white">mdi-account-group</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Total Rescuers</p>
                                    <h4 class="text-h5 font-weight-bold">{{ counts.total }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="3">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="success" size="48">
                                    <v-icon color="white">mdi-check-circle</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Available</p>
                                    <h4 class="text-h5 font-weight-bold text-success">{{ counts.available }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="3">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="warning" size="48">
                                    <v-icon color="white">mdi-run-fast</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">On Rescue</p>
                                    <h4 class="text-h5 font-weight-bold text-warning">{{ counts.on_rescue }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="3">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="grey" size="48">
                                    <v-icon color="white">mdi-sleep</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Off Duty</p>
                                    <h4 class="text-h5 font-weight-bold text-grey">{{ counts.off_duty }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Filters -->
                <v-card rounded="lg" class="mb-6">
                    <v-card-text>
                        <v-row align="center">
                            <v-col cols="12" md="4">
                                <v-text-field
                                    v-model="search"
                                    prepend-inner-icon="mdi-magnify"
                                    label="Search rescuers..."
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    clearable
                                    @input="debouncedSearch"
                                />
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-select
                                    v-model="statusFilter"
                                    :items="statusOptions"
                                    item-title="label"
                                    item-value="value"
                                    label="Filter by Status"
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    @update:model-value="fetchRescuers"
                                />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-btn variant="outlined" block @click="resetFilters">
                                    <v-icon start>mdi-filter-off</v-icon>
                                    Reset
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <!-- Rescuers Grid -->
                <v-row>
                    <v-col v-for="rescuer in rescuersList" :key="rescuer.id" cols="12" sm="6" md="4" lg="3">
                        <v-card rounded="lg" class="h-100">
                            <v-card-text class="text-center">
                                <v-avatar :color="getStatusColor(rescuer.status)" size="80" class="mb-3">
                                    <v-img v-if="rescuer.profile_picture" :src="rescuer.profile_picture" />
                                    <span v-else class="text-h4 text-white">{{ getInitials(rescuer) }}</span>
                                </v-avatar>
                                <h3 class="text-h6 font-weight-medium">{{ rescuer.first_name }} {{ rescuer.last_name }}</h3>
                                <p class="text-grey text-caption mb-2">{{ rescuer.email }}</p>
                                <v-chip :color="getStatusColor(rescuer.status)" size="small" variant="flat" class="mb-3">
                                    {{ formatStatus(rescuer.status) }}
                                </v-chip>
                                <div class="text-caption text-grey mb-3">
                                    <v-icon size="small" class="mr-1">mdi-phone</v-icon>
                                    {{ rescuer.phone || 'No phone' }}
                                </div>
                            </v-card-text>
                            <v-divider />
                            <v-card-actions>
                                <v-btn variant="text" size="small" color="primary" @click="openEditDialog(rescuer)">
                                    <v-icon start size="small">mdi-pencil</v-icon>
                                    Edit
                                </v-btn>
                                <v-spacer />
                                <v-menu>
                                    <template v-slot:activator="{ props }">
                                        <v-btn icon size="small" v-bind="props">
                                            <v-icon>mdi-dots-vertical</v-icon>
                                        </v-btn>
                                    </template>
                                    <v-list density="compact">
                                        <v-list-item @click="updateStatus(rescuer, 'available')">
                                            <v-list-item-title>
                                                <v-icon start color="success" size="small">mdi-check</v-icon>
                                                Set Available
                                            </v-list-item-title>
                                        </v-list-item>
                                        <v-list-item @click="updateStatus(rescuer, 'off_duty')">
                                            <v-list-item-title>
                                                <v-icon start color="grey" size="small">mdi-sleep</v-icon>
                                                Set Off Duty
                                            </v-list-item-title>
                                        </v-list-item>
                                        <v-list-item @click="updateStatus(rescuer, 'unavailable')">
                                            <v-list-item-title>
                                                <v-icon start color="error" size="small">mdi-close</v-icon>
                                                Set Unavailable
                                            </v-list-item-title>
                                        </v-list-item>
                                        <v-divider />
                                        <v-list-item @click="confirmDelete(rescuer)">
                                            <v-list-item-title class="text-error">
                                                <v-icon start color="error" size="small">mdi-delete</v-icon>
                                                Delete
                                            </v-list-item-title>
                                        </v-list-item>
                                    </v-list>
                                </v-menu>
                            </v-card-actions>
                        </v-card>
                    </v-col>
                </v-row>

                <v-alert v-if="rescuersList.length === 0 && !loading" type="info" variant="tonal" class="mt-4">
                    No rescuers found. Click "Add Rescuer" to create one.
                </v-alert>

                <!-- Audit Trail -->
                <v-card rounded="lg" class="mt-6">
                    <v-card-title>
                        <v-icon start>mdi-history</v-icon>
                        Recent Activity
                    </v-card-title>
                    <v-card-text>
                        <v-timeline density="compact" side="end">
                            <v-timeline-item
                                v-for="audit in auditTrail"
                                :key="audit.id"
                                :dot-color="getAuditColor(audit.action)"
                                size="small"
                            >
                                <div>
                                    <p class="font-weight-medium mb-0">{{ audit.description || audit.action }}</p>
                                    <p class="text-caption text-grey">{{ formatDate(audit.created_at) }}</p>
                                </div>
                            </v-timeline-item>
                        </v-timeline>
                        <v-alert v-if="auditTrail.length === 0" type="info" variant="tonal">
                            No recent activity
                        </v-alert>
                    </v-card-text>
                </v-card>
            </v-container>
        </v-main>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="dialog" max-width="500">
            <v-card>
                <v-card-title>{{ isEditing ? 'Edit Rescuer' : 'Add New Rescuer' }}</v-card-title>
                <v-card-text>
                    <v-form ref="form">
                        <v-text-field
                            v-model="formData.first_name"
                            label="First Name"
                            variant="outlined"
                            density="compact"
                            :rules="[v => !!v || 'First name is required']"
                            class="mb-3"
                        />
                        <v-text-field
                            v-model="formData.last_name"
                            label="Last Name"
                            variant="outlined"
                            density="compact"
                            :rules="[v => !!v || 'Last name is required']"
                            class="mb-3"
                        />
                        <v-text-field
                            v-model="formData.email"
                            label="Email"
                            type="email"
                            variant="outlined"
                            density="compact"
                            :rules="[v => !!v || 'Email is required', v => /.+@.+\..+/.test(v) || 'Invalid email']"
                            class="mb-3"
                        />
                        <v-text-field
                            v-model="formData.phone"
                            label="Phone"
                            variant="outlined"
                            density="compact"
                            class="mb-3"
                        />
                        <v-select
                            v-if="isEditing"
                            v-model="formData.status"
                            :items="['available', 'on_rescue', 'off_duty', 'unavailable']"
                            label="Status"
                            variant="outlined"
                            density="compact"
                        />
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="saving" @click="saveRescuer">
                        {{ isEditing ? 'Update' : 'Create' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title class="text-error">
                    <v-icon start color="error">mdi-alert</v-icon>
                    Delete Rescuer
                </v-card-title>
                <v-card-text>
                    Are you sure you want to delete <strong>{{ selectedRescuer?.first_name }} {{ selectedRescuer?.last_name }}</strong>? This action cannot be undone.
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" :loading="deleting" @click="deleteRescuer">Delete</v-btn>
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
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    rescuers: { type: Object, default: () => ({ data: [] }) },
    counts: { type: Object, default: () => ({ total: 0, available: 0, on_rescue: 0, off_duty: 0, unavailable: 0 }) },
    auditTrail: { type: Array, default: () => [] }
});

const drawer = ref(true);
const loading = ref(false);
const dialog = ref(false);
const deleteDialog = ref(false);
const isEditing = ref(false);
const saving = ref(false);
const deleting = ref(false);
const search = ref('');
const statusFilter = ref('all');
const selectedRescuer = ref(null);
const snackbar = ref(false);
const snackbarText = ref('');
const snackbarColor = ref('success');

const rescuersList = ref(props.rescuers?.data || []);
const counts = ref(props.counts);
const auditTrail = ref(props.auditTrail || []);

const formData = ref({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    status: 'available'
});

const statusOptions = [
    { label: 'All Status', value: 'all' },
    { label: 'Available', value: 'available' },
    { label: 'On Rescue', value: 'on_rescue' },
    { label: 'Off Duty', value: 'off_duty' },
    { label: 'Unavailable', value: 'unavailable' }
];

let searchTimeout = null;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(fetchRescuers, 500);
};

const fetchRescuers = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (search.value) params.append('search', search.value);
        if (statusFilter.value !== 'all') params.append('status', statusFilter.value);
        
        const response = await fetch(`/admin/rescuers?${params}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success) {
            rescuersList.value = data.data.data || data.data;
            counts.value = data.counts;
        }
    } catch (error) {
        console.error('Error fetching rescuers:', error);
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    search.value = '';
    statusFilter.value = 'all';
    fetchRescuers();
};

const openAddDialog = () => {
    isEditing.value = false;
    formData.value = {
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        status: 'available'
    };
    dialog.value = true;
};

const openEditDialog = (rescuer) => {
    isEditing.value = true;
    selectedRescuer.value = rescuer;
    formData.value = { ...rescuer };
    dialog.value = true;
};

const saveRescuer = async () => {
    saving.value = true;
    try {
        const url = isEditing.value ? `/admin/rescuers/${selectedRescuer.value.id}` : '/admin/rescuers';
        const method = isEditing.value ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(formData.value)
        });
        
        const data = await response.json();
        if (data.success) {
            showSnackbar(isEditing.value ? 'Rescuer updated successfully' : 'Rescuer created successfully', 'success');
            dialog.value = false;
            fetchRescuers();
        } else {
            showSnackbar(data.errors ? Object.values(data.errors).flat().join(', ') : 'Error saving rescuer', 'error');
        }
    } catch (error) {
        console.error('Error saving rescuer:', error);
        showSnackbar('Error saving rescuer', 'error');
    } finally {
        saving.value = false;
    }
};

const updateStatus = async (rescuer, status) => {
    try {
        const response = await fetch(`/admin/rescuers/${rescuer.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ status })
        });
        
        const data = await response.json();
        if (data.success) {
            showSnackbar(`Status updated to ${status.replace('_', ' ')}`, 'success');
            fetchRescuers();
        }
    } catch (error) {
        console.error('Error updating status:', error);
        showSnackbar('Error updating status', 'error');
    }
};

const confirmDelete = (rescuer) => {
    selectedRescuer.value = rescuer;
    deleteDialog.value = true;
};

const deleteRescuer = async () => {
    deleting.value = true;
    try {
        const response = await fetch(`/admin/rescuers/${selectedRescuer.value.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        
        const data = await response.json();
        if (data.success) {
            showSnackbar('Rescuer deleted successfully', 'success');
            deleteDialog.value = false;
            fetchRescuers();
        }
    } catch (error) {
        console.error('Error deleting rescuer:', error);
        showSnackbar('Error deleting rescuer', 'error');
    } finally {
        deleting.value = false;
    }
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

const getInitials = (rescuer) => {
    return `${rescuer.first_name?.[0] || ''}${rescuer.last_name?.[0] || ''}`.toUpperCase();
};

const getStatusColor = (status) => {
    const colors = {
        available: 'success',
        on_rescue: 'warning',
        off_duty: 'grey',
        unavailable: 'error'
    };
    return colors[status] || 'grey';
};

const formatStatus = (status) => {
    return status?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || 'Unknown';
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

const getAuditColor = (action) => {
    const colors = { create: 'success', update: 'info', delete: 'error' };
    return colors[action] || 'grey';
};

onMounted(() => {
    // Data comes from Inertia props
});
</script>
