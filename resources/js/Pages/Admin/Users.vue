<template>
    <v-app class="bg-grey-lighten-4">
        <!-- App Bar with Gradient -->
        <v-app-bar elevation="0" class="gradient-app-bar">
            <v-app-bar-nav-icon @click="drawer = !drawer" color="white"></v-app-bar-nav-icon>
            <v-app-bar-title class="d-flex align-center">
                <v-icon color="white" class="mr-2">mdi-map-marker-radius</v-icon>
                <span class="text-white font-weight-bold">PinPointMe Admin</span>
            </v-app-bar-title>
            <v-spacer />
            <v-chip color="rgba(255,255,255,0.2)" variant="flat" class="mr-2 text-white">
                <v-icon start color="success">mdi-circle</v-icon>
                System Online
            </v-chip>
            <v-menu>
                <template v-slot:activator="{ props }">
                    <v-btn icon v-bind="props" color="white">
                        <v-avatar color="white" size="36">
                            <span class="text-primary font-weight-bold">{{ adminInitials }}</span>
                        </v-avatar>
                    </v-btn>
                </template>
                <v-list density="compact">
                    <v-list-item prepend-icon="mdi-account" title="Profile"></v-list-item>
                    <v-divider></v-divider>
                    <v-list-item prepend-icon="mdi-logout" title="Logout" @click="logout"></v-list-item>
                </v-list>
            </v-menu>
        </v-app-bar>

        <!-- Navigation Drawer -->
        <v-navigation-drawer v-model="drawer" permanent>
            <!-- Brand Header -->
            <div class="pa-4 gradient-drawer-header">
                <div class="d-flex align-center">
                    <v-avatar color="white" size="48" class="mr-3">
                        <v-icon color="primary" size="28">mdi-map-marker-radius</v-icon>
                    </v-avatar>
                    <div>
                        <h3 class="text-white font-weight-bold text-h6">PinPointMe</h3>
                        <p class="text-white-50 text-caption mb-0">Emergency Response</p>
                    </div>
                </div>
            </div>
            <v-divider></v-divider>
            <v-list class="py-2">
                <v-list-item prepend-icon="mdi-view-dashboard" title="Dashboard" :href="'/admin/dashboard'" :active="currentPage === 'dashboard'" rounded="xl" class="mx-2 mb-1"></v-list-item>
                <v-list-item prepend-icon="mdi-account-group" title="Users" :href="'/admin/users'" :active="currentPage === 'users'" rounded="xl" class="mx-2 mb-1"></v-list-item>
                <v-list-item prepend-icon="mdi-lifebuoy" title="Rescuers" :href="'/admin/rescuers'" :active="currentPage === 'rescuers'" rounded="xl" class="mx-2 mb-1"></v-list-item>
                <v-list-item prepend-icon="mdi-office-building" title="Buildings" :href="'/admin/buildings'" :active="currentPage === 'buildings'" rounded="xl" class="mx-2 mb-1"></v-list-item>
                <v-list-item prepend-icon="mdi-file-chart" title="Reports" :href="'/admin/reports'" :active="currentPage === 'reports'" rounded="xl" class="mx-2 mb-1"></v-list-item>
                <v-list-item prepend-icon="mdi-shield-alert" title="Preventive Measures" :href="'/admin/preventive-measures'" :active="currentPage === 'preventive'" rounded="xl" class="mx-2 mb-1"></v-list-item>
            </v-list>
            <template v-slot:append>
                <div class="pa-4">
                    <v-btn block color="error" variant="tonal" @click="logout" rounded="xl">
                        <v-icon start>mdi-logout</v-icon>
                        Logout
                    </v-btn>
                </div>
            </template>
        </v-navigation-drawer>

        <!-- Main Content -->
        <v-main>
            <v-container fluid class="pa-6">
                <!-- Page Header -->
                <div class="d-flex align-center mb-6">
                    <div>
                        <h1 class="text-h4 font-weight-bold gradient-text">User Management</h1>
                        <p class="text-grey mt-1">Manage students, faculty, and staff accounts</p>
                    </div>
                    <v-spacer />
                    <!-- Add User Button with Dropdown -->
                    <v-menu>
                        <template v-slot:activator="{ props }">
                            <v-btn color="primary" v-bind="props" rounded="lg">
                                <v-icon start>mdi-plus</v-icon>
                                Add User
                                <v-icon end>mdi-chevron-down</v-icon>
                            </v-btn>
                        </template>
                        <v-list density="compact">
                            <v-list-item @click="openAddDialog" prepend-icon="mdi-account-plus">
                                <v-list-item-title>Add Single User</v-list-item-title>
                            </v-list-item>
                            <v-list-item @click="openBulkDialog" prepend-icon="mdi-account-multiple-plus">
                                <v-list-item-title>Bulk Add Users</v-list-item-title>
                            </v-list-item>
                        </v-list>
                    </v-menu>
                </div>

                <!-- Stats Cards -->
                <v-row class="mb-6">
                    <v-col cols="6" md="3">
                        <v-card class="stat-card-white" rounded="lg" elevation="2">
                            <v-card-text class="text-center py-4">
                                <v-avatar color="primary" size="48" class="mb-2">
                                    <v-icon color="white">mdi-account-group</v-icon>
                                </v-avatar>
                                <p class="text-grey text-caption mb-0">Total Users</p>
                                <h2 class="text-h4 font-weight-bold">{{ stats.total }}</h2>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="6" md="3">
                        <v-card class="stat-card-white" rounded="lg" elevation="2">
                            <v-card-text class="text-center py-4">
                                <v-avatar color="blue" size="48" class="mb-2">
                                    <v-icon color="white">mdi-school</v-icon>
                                </v-avatar>
                                <p class="text-grey text-caption mb-0">Students</p>
                                <h2 class="text-h4 font-weight-bold text-blue">{{ stats.by_role?.student || 0 }}</h2>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="6" md="3">
                        <v-card class="stat-card-white" rounded="lg" elevation="2">
                            <v-card-text class="text-center py-4">
                                <v-avatar color="purple" size="48" class="mb-2">
                                    <v-icon color="white">mdi-human-male-board</v-icon>
                                </v-avatar>
                                <p class="text-grey text-caption mb-0">Faculty</p>
                                <h2 class="text-h4 font-weight-bold text-purple">{{ stats.by_role?.faculty || 0 }}</h2>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="6" md="3">
                        <v-card class="stat-card-white" rounded="lg" elevation="2">
                            <v-card-text class="text-center py-4">
                                <v-avatar color="teal" size="48" class="mb-2">
                                    <v-icon color="white">mdi-briefcase</v-icon>
                                </v-avatar>
                                <p class="text-grey text-caption mb-0">Staff</p>
                                <h2 class="text-h4 font-weight-bold text-teal">{{ stats.by_role?.staff || 0 }}</h2>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Main Table Card -->
                <v-card rounded="lg" elevation="2">
                    <!-- Table Header -->
                    <v-card-text class="pa-4">
                        <div class="d-flex flex-wrap align-center justify-space-between gap-3">
                            <div class="d-flex align-center">
                                <span class="font-weight-medium text-subtitle-1">Users</span>
                                <v-chip size="small" color="grey-lighten-2" class="ml-2">
                                    Showing users from all roles
                                </v-chip>
                            </div>
                            <div class="d-flex flex-wrap align-center gap-2">
                                <!-- Bulk Actions -->
                                <v-btn 
                                    v-if="selectedUsers.length > 0"
                                    variant="outlined" 
                                    color="error" 
                                    size="small"
                                    @click="bulkDeleteConfirm"
                                >
                                    <v-icon start size="small">mdi-delete</v-icon>
                                    Bulk delete ({{ selectedUsers.length }})
                                </v-btn>
                                <v-btn 
                                    v-if="selectedUsers.length > 0"
                                    variant="outlined" 
                                    size="small"
                                    @click="openBulkUpdateDialog"
                                >
                                    <v-icon start size="small">mdi-pencil</v-icon>
                                    Bulk update
                                </v-btn>
                                <v-menu>
                                    <template v-slot:activator="{ props }">
                                        <v-btn variant="outlined" size="small" v-bind="props">
                                            <v-icon start size="small">mdi-filter</v-icon>
                                            Filter
                                        </v-btn>
                                    </template>
                                    <v-list density="compact" min-width="200">
                                        <v-list-subheader>Filter by Role</v-list-subheader>
                                        <v-list-item 
                                            v-for="option in roleOptions" 
                                            :key="option.value"
                                            :active="roleFilter === option.value"
                                            @click="roleFilter = option.value; fetchUsers()"
                                        >
                                            <v-list-item-title>{{ option.label }}</v-list-item-title>
                                        </v-list-item>
                                    </v-list>
                                </v-menu>
                                <v-menu>
                                    <template v-slot:activator="{ props }">
                                        <v-btn variant="outlined" size="small" v-bind="props">
                                            <v-icon start size="small">mdi-sort</v-icon>
                                            Sort
                                        </v-btn>
                                    </template>
                                    <v-list density="compact" min-width="200">
                                        <v-list-item @click="sortBy = 'name'; sortOrder = 'asc'; sortUsers()">
                                            <v-list-item-title>Name (A-Z)</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item @click="sortBy = 'name'; sortOrder = 'desc'; sortUsers()">
                                            <v-list-item-title>Name (Z-A)</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item @click="sortBy = 'created_at'; sortOrder = 'desc'; sortUsers()">
                                            <v-list-item-title>Newest First</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item @click="sortBy = 'created_at'; sortOrder = 'asc'; sortUsers()">
                                            <v-list-item-title>Oldest First</v-list-item-title>
                                        </v-list-item>
                                    </v-list>
                                </v-menu>
                            </div>
                        </div>
                    </v-card-text>
                    
                    <v-divider></v-divider>

                    <!-- Search Bar -->
                    <v-card-text class="pa-4">
                        <v-text-field
                            v-model="search"
                            prepend-inner-icon="mdi-magnify"
                            placeholder="Search users by name, email, or ID..."
                            variant="outlined"
                            density="compact"
                            hide-details
                            clearable
                            rounded="lg"
                            @input="debouncedSearch"
                        />
                    </v-card-text>

                    <!-- Data Table -->
                    <v-data-table
                        v-model="selectedUsers"
                        :headers="headers"
                        :items="usersList"
                        :loading="loading"
                        :items-per-page="itemsPerPage"
                        item-value="id"
                        show-select
                        class="elevation-0"
                        hover
                    >
                        <!-- Name Column -->
                        <template v-slot:item.name="{ item }">
                            <div class="d-flex align-center py-2">
                                <v-avatar :color="getAvatarColor(item.role)" size="36" class="mr-3">
                                    <v-img v-if="item.profile_picture" :src="getProfilePictureUrl(item.profile_picture)" cover />
                                    <span v-else class="text-white font-weight-medium text-caption">{{ getInitials(item) }}</span>
                                </v-avatar>
                                <div>
                                    <p class="font-weight-medium mb-0">{{ item.first_name }} {{ item.last_name }}</p>
                                    <p class="text-caption text-grey mb-0">{{ item.email }}</p>
                                </div>
                            </div>
                        </template>

                        <!-- Role Column -->
                        <template v-slot:item.role="{ item }">
                            <v-chip :color="getRoleColor(item.role)" size="small" variant="flat">
                                {{ formatRole(item.role) }}
                            </v-chip>
                        </template>

                        <!-- Status Column -->
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

                        <!-- ID Number Column -->
                        <template v-slot:item.id_number="{ item }">
                            <span class="text-body-2">{{ item.student_id || item.faculty_id || item.staff_id || '-' }}</span>
                        </template>

                        <!-- Date Created Column -->
                        <template v-slot:item.created_at="{ item }">
                            <span class="text-body-2 text-grey">{{ formatDateTime(item.created_at) }}</span>
                        </template>

                        <!-- Actions Column -->
                        <template v-slot:item.actions="{ item }">
                            <v-menu>
                                <template v-slot:activator="{ props }">
                                    <v-btn icon size="small" variant="text" v-bind="props">
                                        <v-icon>mdi-dots-vertical</v-icon>
                                    </v-btn>
                                </template>
                                <v-list density="compact" min-width="160">
                                    <v-list-item @click="viewProfile(item)" prepend-icon="mdi-account">
                                        <v-list-item-title>View profile</v-list-item-title>
                                    </v-list-item>
                                    <v-list-item @click="openEditDialog(item)" prepend-icon="mdi-pencil">
                                        <v-list-item-title>Edit details</v-list-item-title>
                                    </v-list-item>
                                    <v-divider class="my-1"></v-divider>
                                    <v-list-item @click="confirmDelete(item)" class="text-error">
                                        <template v-slot:prepend>
                                            <v-icon color="error" size="small">mdi-delete</v-icon>
                                        </template>
                                        <v-list-item-title>Delete user</v-list-item-title>
                                    </v-list-item>
                                </v-list>
                            </v-menu>
                        </template>

                        <!-- No Data -->
                        <template v-slot:no-data>
                            <div class="text-center py-8">
                                <v-icon size="64" color="grey-lighten-1">mdi-account-off</v-icon>
                                <p class="text-grey mt-4">No users found. Click "Add User" to create one.</p>
                            </div>
                        </template>

                        <!-- Bottom Row -->
                        <template v-slot:bottom>
                            <div class="d-flex align-center justify-space-between px-4 py-2">
                                <div class="d-flex align-center">
                                    <span class="text-body-2 text-grey mr-2">Rows per page:</span>
                                    <v-select
                                        v-model="itemsPerPage"
                                        :items="[10, 25, 50, 100]"
                                        variant="outlined"
                                        density="compact"
                                        hide-details
                                        style="width: 80px;"
                                    />
                                </div>
                                <v-pagination
                                    v-model="page"
                                    :length="Math.ceil(usersList.length / itemsPerPage)"
                                    :total-visible="5"
                                    density="compact"
                                    rounded
                                />
                            </div>
                        </template>
                    </v-data-table>
                </v-card>

                <!-- Recent Activity -->
                <v-card rounded="lg" class="mt-6" elevation="2">
                    <v-card-title class="d-flex align-center pa-4">
                        <v-icon start color="primary">mdi-history</v-icon>
                        <span class="font-weight-bold">Recent Activity</span>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text class="pa-4">
                        <v-timeline v-if="auditTrail.length > 0" density="compact" side="end">
                            <v-timeline-item
                                v-for="audit in auditTrail.slice(0, 10)"
                                :key="audit.id"
                                :dot-color="getAuditColor(audit.action)"
                                size="small"
                            >
                                <div>
                                    <p class="font-weight-medium mb-0">{{ audit.description || audit.action }}</p>
                                    <p class="text-caption text-grey">{{ formatDateTime(audit.created_at) }}</p>
                                </div>
                            </v-timeline-item>
                        </v-timeline>
                        <v-alert v-else type="info" variant="tonal" rounded="lg">
                            No recent activity
                        </v-alert>
                    </v-card-text>
                </v-card>
            </v-container>
        </v-main>

        <!-- Add/Edit Single User Dialog -->
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
                            :items="statusSelectOptions"
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

        <!-- Bulk Add Dialog -->
        <v-dialog v-model="bulkDialog" max-width="800">
            <v-card rounded="lg">
                <v-card-title class="d-flex align-center pa-4">
                    <v-icon color="primary" class="mr-2">mdi-account-multiple-plus</v-icon>
                    Bulk Add Users
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    <v-tabs v-model="bulkTab" color="primary">
                        <v-tab value="upload">Upload File</v-tab>
                        <v-tab value="manual">Manual Entry</v-tab>
                    </v-tabs>

                    <v-tabs-window v-model="bulkTab" class="mt-4">
                        <!-- Upload File Tab -->
                        <v-tabs-window-item value="upload">
                            <v-alert type="info" variant="tonal" class="mb-4" density="compact">
                                <strong>File Format:</strong> Upload an Excel (.xlsx, .xls) or CSV file with the following columns: 
                                <code>first_name, last_name, email, role, id_number, phone</code>
                                <br>
                                <small>Role options: student, faculty, staff</small>
                            </v-alert>
                            
                            <div class="d-flex gap-2 mb-4">
                                <v-btn variant="outlined" color="success" size="small" @click="downloadTemplate('excel')">
                                    <v-icon start>mdi-microsoft-excel</v-icon>
                                    Download Excel Template
                                </v-btn>
                                <v-btn variant="outlined" color="info" size="small" @click="downloadTemplate('csv')">
                                    <v-icon start>mdi-file-delimited</v-icon>
                                    Download CSV Template
                                </v-btn>
                                <v-btn variant="outlined" color="purple" size="small" @click="downloadTemplate('sql')">
                                    <v-icon start>mdi-database</v-icon>
                                    Download SQL Template
                                </v-btn>
                            </div>

                            <v-file-input
                                v-model="bulkFile"
                                label="Select file (Excel, CSV, or SQL)"
                                variant="outlined"
                                density="compact"
                                accept=".xlsx,.xls,.csv,.sql"
                                prepend-icon="mdi-file-upload"
                                :rules="[v => !v || v.size < 10000000 || 'File size should be less than 10 MB']"
                                show-size
                                @update:model-value="previewBulkFile"
                            />

                            <!-- Preview Table -->
                            <v-card v-if="bulkPreviewData.length > 0" variant="outlined" class="mt-4">
                                <v-card-title class="text-subtitle-1 d-flex align-center">
                                    <span>Preview ({{ bulkPreviewData.length }} records)</span>
                                    <v-spacer />
                                    <v-chip :color="bulkValidationErrors.length > 0 ? 'error' : 'success'" size="small">
                                        {{ bulkValidationErrors.length > 0 ? `${bulkValidationErrors.length} errors` : 'All valid' }}
                                    </v-chip>
                                </v-card-title>
                                <v-data-table
                                    :headers="bulkPreviewHeaders"
                                    :items="bulkPreviewData.slice(0, 10)"
                                    :items-per-page="10"
                                    density="compact"
                                    class="elevation-0"
                                >
                                    <template v-slot:item.email="{ item }">
                                        <span :class="{ 'text-error': !item.email.endsWith('@sdca.edu.ph') }">
                                            {{ item.email }}
                                            <v-icon v-if="!item.email.endsWith('@sdca.edu.ph')" size="x-small" color="error">mdi-alert</v-icon>
                                        </span>
                                    </template>
                                    <template v-slot:item.role="{ item }">
                                        <v-chip 
                                            :color="['student', 'faculty', 'staff'].includes(item.role?.toLowerCase()) ? getRoleColor(item.role) : 'error'" 
                                            size="x-small"
                                        >
                                            {{ item.role || 'Missing' }}
                                        </v-chip>
                                    </template>
                                </v-data-table>
                                <v-card-text v-if="bulkPreviewData.length > 10" class="text-caption text-grey">
                                    ... and {{ bulkPreviewData.length - 10 }} more records
                                </v-card-text>
                                
                                <!-- Validation Errors -->
                                <v-alert v-if="bulkValidationErrors.length > 0" type="warning" variant="tonal" class="ma-4" density="compact">
                                    <strong>Validation Warnings:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li v-for="(error, idx) in bulkValidationErrors.slice(0, 5)" :key="idx">{{ error }}</li>
                                        <li v-if="bulkValidationErrors.length > 5">... and {{ bulkValidationErrors.length - 5 }} more</li>
                                    </ul>
                                </v-alert>
                            </v-card>
                        </v-tabs-window-item>

                        <!-- Manual Entry Tab -->
                        <v-tabs-window-item value="manual">
                            <v-alert type="info" variant="tonal" class="mb-4" density="compact">
                                Enter user details below. Click "Add Row" to add more users.
                            </v-alert>

                            <div v-for="(row, index) in manualBulkData" :key="index" class="mb-3">
                                <v-row align="center" dense>
                                    <v-col cols="2">
                                        <v-text-field
                                            v-model="row.first_name"
                                            label="First Name"
                                            variant="outlined"
                                            density="compact"
                                            hide-details
                                        />
                                    </v-col>
                                    <v-col cols="2">
                                        <v-text-field
                                            v-model="row.last_name"
                                            label="Last Name"
                                            variant="outlined"
                                            density="compact"
                                            hide-details
                                        />
                                    </v-col>
                                    <v-col cols="3">
                                        <v-text-field
                                            v-model="row.email"
                                            label="Email"
                                            variant="outlined"
                                            density="compact"
                                            hide-details
                                        />
                                    </v-col>
                                    <v-col cols="2">
                                        <v-select
                                            v-model="row.role"
                                            :items="['student', 'faculty', 'staff']"
                                            label="Role"
                                            variant="outlined"
                                            density="compact"
                                            hide-details
                                        />
                                    </v-col>
                                    <v-col cols="2">
                                        <v-text-field
                                            v-model="row.id_number"
                                            label="ID Number"
                                            variant="outlined"
                                            density="compact"
                                            hide-details
                                        />
                                    </v-col>
                                    <v-col cols="1">
                                        <v-btn 
                                            icon 
                                            size="small" 
                                            color="error" 
                                            variant="text"
                                            @click="removeManualRow(index)"
                                            :disabled="manualBulkData.length === 1"
                                        >
                                            <v-icon>mdi-close</v-icon>
                                        </v-btn>
                                    </v-col>
                                </v-row>
                            </div>

                            <v-btn variant="outlined" size="small" @click="addManualRow">
                                <v-icon start>mdi-plus</v-icon>
                                Add Row
                            </v-btn>
                        </v-tabs-window-item>
                    </v-tabs-window>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="bulkDialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="bulkSaving" @click="processBulkAdd">
                        <v-icon start>mdi-upload</v-icon>
                        Import Users
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Bulk Update Dialog -->
        <v-dialog v-model="bulkUpdateDialog" max-width="400">
            <v-card rounded="lg">
                <v-card-title class="pa-4">
                    <v-icon color="info" class="mr-2">mdi-account-multiple-check</v-icon>
                    Bulk Update Status
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    <p class="mb-4">Update status for {{ selectedUsers.length }} selected user(s):</p>
                    <v-select
                        v-model="bulkUpdateStatus"
                        :items="statusSelectOptions"
                        item-title="label"
                        item-value="value"
                        label="New Status"
                        variant="outlined"
                        density="compact"
                    />
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="bulkUpdateDialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="bulkUpdating" @click="processBulkUpdate">
                        Update All
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card rounded="lg">
                <v-card-title class="text-error pa-4">
                    <v-icon start color="error">mdi-alert</v-icon>
                    Delete User
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    Are you sure you want to delete <strong>{{ selectedUser?.first_name }} {{ selectedUser?.last_name }}</strong>? This action cannot be undone.
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" :loading="deleting" @click="deleteUser">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Bulk Delete Confirmation -->
        <v-dialog v-model="bulkDeleteDialog" max-width="400">
            <v-card rounded="lg">
                <v-card-title class="text-error pa-4">
                    <v-icon start color="error">mdi-alert</v-icon>
                    Delete Multiple Users
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    Are you sure you want to delete <strong>{{ selectedUsers.length }}</strong> user(s)? This action cannot be undone.
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="bulkDeleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" :loading="bulkDeleting" @click="processBulkDelete">Delete All</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- View Profile Dialog -->
        <v-dialog v-model="profileDialog" max-width="500">
            <v-card v-if="viewingUser" rounded="lg">
                <v-card-title class="pa-4">
                    <v-icon color="primary" class="mr-2">mdi-account</v-icon>
                    User Profile
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    <div class="text-center mb-4">
                        <v-avatar :color="getAvatarColor(viewingUser.role)" size="80">
                            <v-img v-if="viewingUser.profile_picture" :src="getProfilePictureUrl(viewingUser.profile_picture)" cover />
                            <span v-else class="text-h4 text-white">{{ getInitials(viewingUser) }}</span>
                        </v-avatar>
                        <h3 class="mt-3 text-h6">{{ viewingUser.first_name }} {{ viewingUser.last_name }}</h3>
                        <v-chip :color="getRoleColor(viewingUser.role)" size="small" class="mt-2">
                            {{ formatRole(viewingUser.role) }}
                        </v-chip>
                    </div>
                    <v-list density="compact">
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-email</v-icon>
                            </template>
                            <v-list-item-title>{{ viewingUser.email }}</v-list-item-title>
                            <v-list-item-subtitle>Email</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-card-account-details</v-icon>
                            </template>
                            <v-list-item-title>{{ viewingUser.student_id || viewingUser.faculty_id || viewingUser.staff_id || 'Not provided' }}</v-list-item-title>
                            <v-list-item-subtitle>ID Number</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-phone</v-icon>
                            </template>
                            <v-list-item-title>{{ viewingUser.phone || viewingUser.phone_number || viewingUser.contact_number || 'Not provided' }}</v-list-item-title>
                            <v-list-item-subtitle>Phone</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-calendar</v-icon>
                            </template>
                            <v-list-item-title>{{ formatDateTime(viewingUser.created_at) }}</v-list-item-title>
                            <v-list-item-subtitle>Date Created</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="profileDialog = false">Close</v-btn>
                    <v-btn color="primary" @click="profileDialog = false; openEditDialog(viewingUser)">
                        <v-icon start>mdi-pencil</v-icon>
                        Edit
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000" location="bottom right">
            {{ snackbarText }}
            <template v-slot:actions>
                <v-btn variant="text" @click="snackbar = false">Close</v-btn>
            </template>
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import * as XLSX from 'xlsx';
import { getProfilePictureUrl } from '@/Composables/useApi';

const props = defineProps({
    users: { type: Object, default: () => ({ data: [] }) },
    stats: { type: Object, default: () => ({ total: 0, by_role: {} }) },
    auditTrail: { type: Array, default: () => [] }
});

// State
const drawer = ref(true);
const currentPage = ref('users');
const loading = ref(false);
const dialog = ref(false);
const bulkDialog = ref(false);
const bulkUpdateDialog = ref(false);
const deleteDialog = ref(false);
const bulkDeleteDialog = ref(false);
const profileDialog = ref(false);
const isEditing = ref(false);
const saving = ref(false);
const bulkSaving = ref(false);
const bulkUpdating = ref(false);
const deleting = ref(false);
const bulkDeleting = ref(false);
const search = ref('');
const roleFilter = ref('all');
const selectedUser = ref(null);
const viewingUser = ref(null);
const snackbar = ref(false);
const snackbarText = ref('');
const snackbarColor = ref('success');
const page = ref(1);
const itemsPerPage = ref(50);
const sortBy = ref('name');
const sortOrder = ref('asc');
const selectedUsers = ref([]);

// Bulk upload
const bulkTab = ref('upload');
const bulkFile = ref(null);
const bulkPreviewData = ref([]);
const bulkValidationErrors = ref([]);
const bulkUpdateStatus = ref('active');
const manualBulkData = ref([{ first_name: '', last_name: '', email: '', role: 'student', id_number: '', phone: '' }]);

// Data
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

// Computed
const adminInitials = computed(() => {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    if (userData.first_name && userData.last_name) {
        return `${userData.first_name[0]}${userData.last_name[0]}`.toUpperCase();
    }
    return 'AD';
});

// Table headers
const headers = [
    { title: 'User', key: 'name', sortable: true },
    { title: 'Role', key: 'role', sortable: true },
    { title: 'ID Number', key: 'id_number', sortable: false },
    { title: 'Phone', key: 'phone', sortable: false },
    { title: 'Status', key: 'status', sortable: true },
    { title: 'Created', key: 'created_at', sortable: true },
    { title: '', key: 'actions', sortable: false, align: 'end', width: '50px' }
];

const bulkPreviewHeaders = [
    { title: 'First Name', key: 'first_name' },
    { title: 'Last Name', key: 'last_name' },
    { title: 'Email', key: 'email' },
    { title: 'Role', key: 'role' },
    { title: 'ID Number', key: 'id_number' }
];

const roleOptions = [
    { label: 'All Roles', value: 'all' },
    { label: 'Students', value: 'student' },
    { label: 'Faculty', value: 'faculty' },
    { label: 'Staff', value: 'staff' }
];

const statusSelectOptions = [
    { label: 'Active', value: 'active' },
    { label: 'Pending Verification', value: 'pending' },
    { label: 'Inactive', value: 'inactive' }
];

// Methods
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
            if (data.audit_trail) {
                auditTrail.value = data.audit_trail;
            }
        }
    } catch (error) {
        console.error('Error fetching users:', error);
        showSnackbar('Error fetching users', 'error');
    } finally {
        loading.value = false;
    }
};

const sortUsers = () => {
    usersList.value.sort((a, b) => {
        let comparison = 0;
        if (sortBy.value === 'name') {
            const nameA = `${a.first_name} ${a.last_name}`.toLowerCase();
            const nameB = `${b.first_name} ${b.last_name}`.toLowerCase();
            comparison = nameA.localeCompare(nameB);
        } else if (sortBy.value === 'created_at') {
            comparison = new Date(a.created_at) - new Date(b.created_at);
        }
        return sortOrder.value === 'asc' ? comparison : -comparison;
    });
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

const openBulkDialog = () => {
    bulkTab.value = 'upload';
    bulkFile.value = null;
    bulkPreviewData.value = [];
    bulkValidationErrors.value = [];
    manualBulkData.value = [{ first_name: '', last_name: '', email: '', role: 'student', id_number: '', phone: '' }];
    bulkDialog.value = true;
};

const openBulkUpdateDialog = () => {
    bulkUpdateStatus.value = 'active';
    bulkUpdateDialog.value = true;
};

const viewProfile = (user) => {
    viewingUser.value = user;
    profileDialog.value = true;
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
            send_otp: !isEditing.value
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
            showSnackbar(isEditing.value ? 'User updated successfully' : 'User created successfully', 'success');
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

// Bulk operations
const bulkDeleteConfirm = () => {
    bulkDeleteDialog.value = true;
};

const processBulkDelete = async () => {
    bulkDeleting.value = true;
    try {
        for (const id of selectedUsers.value) {
            await fetch(`/admin/users/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });
        }
        showSnackbar(`${selectedUsers.value.length} user(s) deleted successfully`, 'success');
        bulkDeleteDialog.value = false;
        selectedUsers.value = [];
        fetchUsers();
    } catch (error) {
        console.error('Error bulk deleting:', error);
        showSnackbar('Error deleting users', 'error');
    } finally {
        bulkDeleting.value = false;
    }
};

const processBulkUpdate = async () => {
    bulkUpdating.value = true;
    try {
        for (const id of selectedUsers.value) {
            await fetch(`/admin/users/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({ status: bulkUpdateStatus.value })
            });
        }
        showSnackbar(`${selectedUsers.value.length} user(s) updated successfully`, 'success');
        bulkUpdateDialog.value = false;
        selectedUsers.value = [];
        fetchUsers();
    } catch (error) {
        console.error('Error bulk updating:', error);
        showSnackbar('Error updating users', 'error');
    } finally {
        bulkUpdating.value = false;
    }
};

const previewBulkFile = async (file) => {
    if (!file) {
        bulkPreviewData.value = [];
        bulkValidationErrors.value = [];
        return;
    }
    
    try {
        const fileName = file.name.toLowerCase();
        
        if (fileName.endsWith('.sql')) {
            // Parse SQL file
            const text = await file.text();
            const data = parseSqlInsert(text);
            bulkPreviewData.value = data;
        } else {
            // Parse Excel/CSV
            const data = await file.arrayBuffer();
            const workbook = XLSX.read(data);
            const sheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[sheetName];
            const jsonData = XLSX.utils.sheet_to_json(worksheet);
            
            bulkPreviewData.value = jsonData.map(row => ({
                first_name: row.first_name || row['First Name'] || '',
                last_name: row.last_name || row['Last Name'] || '',
                email: row.email || row['Email'] || '',
                role: (row.role || row['Role'] || 'student').toLowerCase(),
                id_number: row.id_number || row['ID Number'] || row.student_id || '',
                phone: row.phone || row['Phone'] || ''
            }));
        }
        
        // Validate data
        validateBulkData();
    } catch (error) {
        console.error('Error parsing file:', error);
        showSnackbar('Error parsing file. Please check the format.', 'error');
    }
};

const parseSqlInsert = (sqlText) => {
    const data = [];
    // Match INSERT INTO ... VALUES patterns
    const insertRegex = /INSERT\s+INTO\s+\w+\s*\([^)]+\)\s*VALUES\s*\(([^)]+)\)/gi;
    const matches = sqlText.matchAll(insertRegex);
    
    for (const match of matches) {
        const values = match[1].split(',').map(v => v.trim().replace(/^['"]|['"]$/g, ''));
        if (values.length >= 4) {
            data.push({
                first_name: values[0] || '',
                last_name: values[1] || '',
                email: values[2] || '',
                role: (values[3] || 'student').toLowerCase(),
                id_number: values[4] || '',
                phone: values[5] || ''
            });
        }
    }
    
    return data;
};

const validateBulkData = () => {
    bulkValidationErrors.value = [];
    
    bulkPreviewData.value.forEach((row, idx) => {
        if (!row.first_name || !row.last_name) {
            bulkValidationErrors.value.push(`Row ${idx + 1}: Missing first or last name`);
        }
        if (!row.email || !row.email.endsWith('@sdca.edu.ph')) {
            bulkValidationErrors.value.push(`Row ${idx + 1}: Invalid or non-SDCA email`);
        }
        if (!['student', 'faculty', 'staff'].includes(row.role?.toLowerCase())) {
            bulkValidationErrors.value.push(`Row ${idx + 1}: Invalid role "${row.role}"`);
        }
    });
};

const processBulkAdd = async () => {
    bulkSaving.value = true;
    
    let dataToProcess = [];
    if (bulkTab.value === 'upload') {
        dataToProcess = bulkPreviewData.value;
    } else {
        dataToProcess = manualBulkData.value.filter(row => row.first_name && row.last_name && row.email);
    }
    
    if (dataToProcess.length === 0) {
        showSnackbar('No valid data to import', 'warning');
        bulkSaving.value = false;
        return;
    }
    
    try {
        let successCount = 0;
        let errorCount = 0;
        const errors = [];
        
        for (const row of dataToProcess) {
            try {
                const role = (row.role || 'student').toLowerCase();
                const response = await fetch('/admin/users', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({
                        first_name: row.first_name,
                        last_name: row.last_name,
                        email: row.email,
                        role: role,
                        phone: row.phone || '',
                        student_id: role === 'student' ? row.id_number : null,
                        faculty_id: role === 'faculty' ? row.id_number : null,
                        staff_id: role === 'staff' ? row.id_number : null,
                        send_otp: true
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    successCount++;
                } else {
                    errorCount++;
                    errors.push(`${row.email}: ${data.errors ? Object.values(data.errors).flat().join(', ') : 'Unknown error'}`);
                }
            } catch {
                errorCount++;
                errors.push(`${row.email}: Request failed`);
            }
        }
        
        if (errors.length > 0) {
            console.log('Bulk import errors:', errors);
        }
        
        showSnackbar(`Imported ${successCount} user(s). ${errorCount > 0 ? `${errorCount} failed.` : ''}`, successCount > 0 ? 'success' : 'error');
        bulkDialog.value = false;
        fetchUsers();
    } catch (error) {
        console.error('Error bulk adding:', error);
        showSnackbar('Error importing users', 'error');
    } finally {
        bulkSaving.value = false;
    }
};

const downloadTemplate = (format) => {
    const templateData = [
        { first_name: 'Juan', last_name: 'Dela Cruz', email: 'juan.delacruz@sdca.edu.ph', role: 'student', id_number: '2024-00001', phone: '09171234567' },
        { first_name: 'Maria', last_name: 'Santos', email: 'maria.santos@sdca.edu.ph', role: 'faculty', id_number: 'FAC-2024-001', phone: '09181234567' },
        { first_name: 'Pedro', last_name: 'Reyes', email: 'pedro.reyes@sdca.edu.ph', role: 'staff', id_number: 'STF-2024-001', phone: '09191234567' }
    ];
    
    if (format === 'sql') {
        // Generate SQL INSERT statements
        const sqlStatements = templateData.map(row => 
            `INSERT INTO users (first_name, last_name, email, role, id_number, phone) VALUES ('${row.first_name}', '${row.last_name}', '${row.email}', '${row.role}', '${row.id_number}', '${row.phone}');`
        ).join('\n');
        
        const blob = new Blob([sqlStatements], { type: 'text/sql' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'users_template.sql';
        a.click();
        URL.revokeObjectURL(url);
    } else {
        const worksheet = XLSX.utils.json_to_sheet(templateData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Users');
        
        if (format === 'csv') {
            XLSX.writeFile(workbook, 'users_template.csv');
        } else {
            XLSX.writeFile(workbook, 'users_template.xlsx');
        }
    }
};

const addManualRow = () => {
    manualBulkData.value.push({ first_name: '', last_name: '', email: '', role: 'student', id_number: '', phone: '' });
};

const removeManualRow = (index) => {
    manualBulkData.value.splice(index, 1);
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

// Helpers
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
        'pending': 'Pending',
        'inactive': 'Inactive'
    };
    return labels[status] || status || 'Active';
};

const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
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

/* Stat Card White */
.stat-card-white {
    background: white;
    border: 1px solid #e0e0e0;
}

/* Text colors */
.text-white-50 {
    color: rgba(255, 255, 255, 0.7) !important;
}

/* Gap utility */
.gap-2 {
    gap: 8px;
}

.gap-3 {
    gap: 12px;
}
</style>
