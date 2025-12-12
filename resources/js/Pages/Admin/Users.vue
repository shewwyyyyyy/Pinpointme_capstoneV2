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
                <v-list-item prepend-icon="mdi-account-group" title="Users" href="/admin/users" active></v-list-item>
                <v-list-item prepend-icon="mdi-lifebuoy" title="Rescuers" href="/admin/rescuers"></v-list-item>
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
                        <h1 class="text-h4 font-weight-bold">User Management</h1>
                        <p class="text-grey mt-1">Manage students, faculty, and staff accounts</p>
                    </div>
                    <v-spacer />
                    <v-btn color="primary" @click="openAddDialog">
                        <v-icon start>mdi-plus</v-icon>
                        Add User
                    </v-btn>
                </div>

                <!-- Stats Cards -->
                <v-row class="mb-6">
                    <v-col cols="6" md="3">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="primary" size="48">
                                    <v-icon color="white">mdi-account-group</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Total Users</p>
                                    <h4 class="text-h5 font-weight-bold">{{ stats.total }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" md="3">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="blue" size="48">
                                    <v-icon color="white">mdi-school</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Students</p>
                                    <h4 class="text-h5 font-weight-bold">{{ stats.by_role?.student || 0 }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" md="3">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="purple" size="48">
                                    <v-icon color="white">mdi-human-male-board</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Faculty</p>
                                    <h4 class="text-h5 font-weight-bold">{{ stats.by_role?.faculty || 0 }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" md="3">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="teal" size="48">
                                    <v-icon color="white">mdi-briefcase</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Staff</p>
                                    <h4 class="text-h5 font-weight-bold">{{ stats.by_role?.staff || 0 }}</h4>
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
                                    label="Search users..."
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    clearable
                                    @input="debouncedSearch"
                                />
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-select
                                    v-model="roleFilter"
                                    :items="roleOptions"
                                    item-title="label"
                                    item-value="value"
                                    label="Filter by Role"
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    @update:model-value="fetchUsers"
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

                <!-- Users Table -->
                <v-card rounded="lg">
                    <v-data-table
                        :headers="headers"
                        :items="usersList"
                        :loading="loading"
                        :items-per-page="10"
                        class="elevation-0"
                    >
                        <template v-slot:item.name="{ item }">
                            <div class="d-flex align-center py-2">
                                <v-avatar :color="getAvatarColor(item.role)" size="36" class="mr-3">
                                    <span class="text-white font-weight-medium">{{ getInitials(item) }}</span>
                                </v-avatar>
                                <div>
                                    <p class="font-weight-medium mb-0">{{ item.first_name }} {{ item.last_name }}</p>
                                    <p class="text-caption text-grey mb-0">{{ item.email }}</p>
                                </div>
                            </div>
                        </template>
                        <template v-slot:item.role="{ item }">
                            <v-chip :color="getRoleColor(item.role)" size="small" variant="flat">
                                {{ formatRole(item.role) }}
                            </v-chip>
                        </template>
                        <template v-slot:item.status="{ item }">
                            <v-chip 
                                :color="getStatusColor(item.status)" 
                                size="small" 
                                :variant="item.status === 'pending' ? 'flat' : 'outlined'"
                            >
                                <v-icon v-if="item.status === 'pending'" start size="small">mdi-clock-outline</v-icon>
                                {{ formatStatus(item.status) }}
                            </v-chip>
                        </template>
                        <template v-slot:item.created_at="{ item }">
                            {{ formatDate(item.created_at) }}
                        </template>
                        <template v-slot:item.actions="{ item }">
                            <v-btn icon size="small" variant="text" @click="openEditDialog(item)">
                                <v-icon size="small">mdi-pencil</v-icon>
                            </v-btn>
                            <v-btn icon size="small" variant="text" color="error" @click="confirmDelete(item)">
                                <v-icon size="small">mdi-delete</v-icon>
                            </v-btn>
                        </template>
                    </v-data-table>
                </v-card>

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
        <v-dialog v-model="dialog" max-width="550">
            <v-card rounded="lg">
                <v-card-title class="d-flex align-center pa-4">
                    <v-icon :color="isEditing ? 'info' : 'primary'" class="mr-2">
                        {{ isEditing ? 'mdi-account-edit' : 'mdi-account-plus' }}
                    </v-icon>
                    {{ isEditing ? 'Edit User' : 'Add New User' }}
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    <v-form ref="form">
                        <v-row>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="formData.first_name"
                                    label="First Name"
                                    variant="outlined"
                                    density="compact"
                                    :rules="[v => !!v || 'First name is required']"
                                />
                            </v-col>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="formData.last_name"
                                    label="Last Name"
                                    variant="outlined"
                                    density="compact"
                                    :rules="[v => !!v || 'Last name is required']"
                                />
                            </v-col>
                        </v-row>
                        <v-text-field
                            v-model="formData.email"
                            label="Email (@sdca.edu.ph)"
                            type="email"
                            variant="outlined"
                            density="compact"
                            :rules="[
                                v => !!v || 'Email is required', 
                                v => /.+@.+\..+/.test(v) || 'Invalid email',
                                v => v.endsWith('@sdca.edu.ph') || 'Must be an SDCA email (@sdca.edu.ph)'
                            ]"
                            class="mb-3"
                            hint="Must be an SDCA email address"
                            persistent-hint
                        />
                        <v-select
                            v-model="formData.role"
                            :items="['student', 'faculty', 'staff']"
                            label="Role"
                            variant="outlined"
                            density="compact"
                            :rules="[v => !!v || 'Role is required']"
                            class="mb-3"
                        />
                        
                        <!-- ID Number Field (shown for all roles) -->
                        <v-text-field
                            v-model="formData.id_number"
                            :label="getIdLabel(formData.role)"
                            variant="outlined"
                            density="compact"
                            :rules="[v => !!v || 'ID number is required']"
                            :hint="getIdHint(formData.role)"
                            persistent-hint
                            class="mb-3"
                        />
                        
                        <v-text-field
                            v-model="formData.phone"
                            label="Phone Number"
                            variant="outlined"
                            density="compact"
                            class="mb-3"
                        />
                        
                        <!-- OTP Activation Notice for new users -->
                        <v-alert 
                            v-if="!isEditing" 
                            type="info" 
                            variant="tonal" 
                            class="mb-3"
                            density="compact"
                        >
                            <v-icon start size="small">mdi-information</v-icon>
                            <span class="text-body-2">
                                An email with OTP verification will be sent to the user. 
                                Account will be <strong>pending</strong> until email is verified and password is changed.
                            </span>
                        </v-alert>
                        
                        <!-- Status selector for editing -->
                        <v-select
                            v-if="isEditing"
                            v-model="formData.status"
                            :items="statusOptions"
                            item-title="label"
                            item-value="value"
                            label="Account Status"
                            variant="outlined"
                            density="compact"
                            class="mb-3"
                        />
                    </v-form>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="saving" @click="saveUser">
                        {{ isEditing ? 'Update' : 'Create & Send OTP' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title class="text-error">
                    <v-icon start color="error">mdi-alert</v-icon>
                    Delete User
                </v-card-title>
                <v-card-text>
                    Are you sure you want to delete <strong>{{ selectedUser?.first_name }} {{ selectedUser?.last_name }}</strong>? This action cannot be undone.
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" :loading="deleting" @click="deleteUser">Delete</v-btn>
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
    users: { type: Object, default: () => ({ data: [] }) },
    stats: { type: Object, default: () => ({ total: 0, by_role: {} }) },
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
const roleFilter = ref('all');
const selectedUser = ref(null);
const snackbar = ref(false);
const snackbarText = ref('');
const snackbarColor = ref('success');

const usersList = ref(props.users?.data || []);
const stats = ref(props.stats);
const auditTrail = ref(props.auditTrail || []);

const formData = ref({
    first_name: '',
    last_name: '',
    email: '',
    role: 'student',
    phone: '',
    id_number: '',
    status: 'pending'
});

const statusOptions = [
    { label: 'Active', value: 'active' },
    { label: 'Pending Verification', value: 'pending' },
    { label: 'Inactive', value: 'inactive' }
];

const headers = [
    { title: 'User', key: 'name', sortable: false },
    { title: 'Role', key: 'role' },
    { title: 'Phone', key: 'phone' },
    { title: 'Status', key: 'status' },
    { title: 'Created', key: 'created_at' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' }
];

const roleOptions = [
    { label: 'All Roles', value: 'all' },
    { label: 'Students', value: 'student' },
    { label: 'Faculty', value: 'faculty' },
    { label: 'Staff', value: 'staff' }
];

let searchTimeout = null;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(fetchUsers, 500);
};

const fetchUsers = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (search.value) params.append('search', search.value);
        if (roleFilter.value !== 'all') params.append('role', roleFilter.value);
        
        const response = await fetch(`/admin/users?${params}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success) {
            usersList.value = data.data.data || data.data;
            stats.value = data.stats;
        }
    } catch (error) {
        console.error('Error fetching users:', error);
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    search.value = '';
    roleFilter.value = 'all';
    fetchUsers();
};

const openAddDialog = () => {
    isEditing.value = false;
    formData.value = {
        first_name: '',
        last_name: '',
        email: '',
        role: 'student',
        phone: '',
        id_number: '',
        status: 'pending'
    };
    dialog.value = true;
};

const openEditDialog = (user) => {
    isEditing.value = true;
    selectedUser.value = user;
    formData.value = { 
        ...user,
        id_number: user.student_id || user.faculty_id || user.staff_id || user.id_number || ''
    };
    dialog.value = true;
};

// Helper functions for ID field
const getIdLabel = (role) => {
    const labels = {
        'student': 'Student ID',
        'faculty': 'Faculty ID',
        'staff': 'Staff ID'
    };
    return labels[role] || 'ID Number';
};

const getIdHint = (role) => {
    const hints = {
        'student': 'e.g., 2024-00001',
        'faculty': 'e.g., FAC-2024-001',
        'staff': 'e.g., STF-2024-001'
    };
    return hints[role] || 'Enter ID number';
};

const saveUser = async () => {
    saving.value = true;
    try {
        const url = isEditing.value ? `/admin/users/${selectedUser.value.id}` : '/admin/users';
        const method = isEditing.value ? 'PUT' : 'POST';
        
        // Prepare data with proper ID field based on role
        const submitData = {
            ...formData.value,
            student_id: formData.value.role === 'student' ? formData.value.id_number : null,
            faculty_id: formData.value.role === 'faculty' ? formData.value.id_number : null,
            staff_id: formData.value.role === 'staff' ? formData.value.id_number : null,
            send_otp: !isEditing.value // Send OTP only for new users
        };
        
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(submitData)
        });
        
        const data = await response.json();
        if (data.success) {
            // Show message with temp password if available (local dev)
            const message = data.message || (isEditing.value ? 'User updated successfully' : 'User created successfully');
            showSnackbar(message, 'success');
            
            // Log temp password for dev purposes
            if (data.temp_password) {
                console.log('New user temp password:', data.temp_password);
            }
            
            dialog.value = false;
            fetchUsers();
        } else {
            showSnackbar(data.errors ? Object.values(data.errors).flat().join(', ') : 'Error saving user', 'error');
        }
    } catch (error) {
        console.error('Error saving user:', error);
        showSnackbar('Error saving user', 'error');
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (user) => {
    selectedUser.value = user;
    deleteDialog.value = true;
};

const deleteUser = async () => {
    deleting.value = true;
    try {
        const response = await fetch(`/admin/users/${selectedUser.value.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        
        const data = await response.json();
        if (data.success) {
            showSnackbar('User deleted successfully', 'success');
            deleteDialog.value = false;
            fetchUsers();
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        showSnackbar('Error deleting user', 'error');
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

const getInitials = (user) => {
    return `${user.first_name?.[0] || ''}${user.last_name?.[0] || ''}`.toUpperCase();
};

const getAvatarColor = (role) => {
    const colors = { student: 'blue', faculty: 'purple', staff: 'teal' };
    return colors[role] || 'grey';
};

const getRoleColor = (role) => {
    const colors = { student: 'blue', faculty: 'purple', staff: 'teal' };
    return colors[role] || 'grey';
};

const formatRole = (role) => {
    return role?.charAt(0).toUpperCase() + role?.slice(1) || '';
};

const getStatusColor = (status) => {
    const colors = {
        'active': 'success',
        'pending': 'warning',
        'inactive': 'grey'
    };
    return colors[status] || 'grey';
};

const formatStatus = (status) => {
    const labels = {
        'active': 'Active',
        'pending': 'Pending Verification',
        'inactive': 'Inactive'
    };
    return labels[status] || status || 'Active';
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
