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
                        <h1 class="text-h4 font-weight-bold gradient-text">Rescuer Management</h1>
                        <p class="text-grey mt-1">To maintain security and compliance.</p>
                    </div>
                    <v-spacer />
                    <!-- Add Rescuer Button with Dropdown -->
                    <v-menu>
                        <template v-slot:activator="{ props }">
                            <v-btn color="primary" v-bind="props" rounded="lg">
                                <v-icon start>mdi-plus</v-icon>
                                Add Rescuer
                                <v-icon end>mdi-chevron-down</v-icon>
                            </v-btn>
                        </template>
                        <v-list density="compact">
                            <v-list-item @click="openAddDialog" prepend-icon="mdi-account-plus">
                                <v-list-item-title>Add Single Rescuer</v-list-item-title>
                            </v-list-item>
                            <v-list-item @click="openBulkDialog" prepend-icon="mdi-account-multiple-plus">
                                <v-list-item-title>Bulk Add Rescuers</v-list-item-title>
                            </v-list-item>
                        </v-list>
                    </v-menu>
                </div>

                <!-- Status Cards -->
                <v-row class="mb-6">
                    <v-col cols="6" sm="6" md="2.4">
                        <v-card class="stat-card-white" rounded="lg" elevation="2">
                            <v-card-text class="text-center py-4">
                                <p class="text-grey text-caption mb-1">Total Rescuers</p>
                                <h2 class="text-h4 font-weight-bold">{{ counts.total }}</h2>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="6" md="2.4">
                        <v-card class="stat-card-white" rounded="lg" elevation="2">
                            <v-card-text class="text-center py-4">
                                <p class="text-grey text-caption mb-1">Available</p>
                                <h2 class="text-h4 font-weight-bold text-success">{{ counts.available }}</h2>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="6" md="2.4">
                        <v-card class="stat-card-white" rounded="lg" elevation="2">
                            <v-card-text class="text-center py-4">
                                <p class="text-grey text-caption mb-1">On a rescue</p>
                                <h2 class="text-h4 font-weight-bold text-warning">{{ counts.on_rescue }}</h2>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="6" md="2.4">
                        <v-card class="stat-card-white" rounded="lg" elevation="2">
                            <v-card-text class="text-center py-4">
                                <p class="text-grey text-caption mb-1">Off Duty</p>
                                <h2 class="text-h4 font-weight-bold text-grey">{{ counts.off_duty }}</h2>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="6" md="2.4">
                        <v-card class="stat-card-white" rounded="lg" elevation="2">
                            <v-card-text class="text-center py-4">
                                <p class="text-grey text-caption mb-1">Unavailable</p>
                                <h2 class="text-h4 font-weight-bold text-error">{{ counts.unavailable }}</h2>
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
                                <span class="font-weight-medium text-subtitle-1">Rescuers</span>
                                <v-chip size="small" color="grey-lighten-2" class="ml-2">
                                    Showing rescuers from all organizational units
                                </v-chip>
                            </div>
                            <div class="d-flex flex-wrap align-center gap-2">
                                <!-- Bulk Actions -->
                                <v-btn 
                                    v-if="selectedRescuers.length > 0"
                                    variant="outlined" 
                                    color="error" 
                                    size="small"
                                    @click="bulkDeleteConfirm"
                                >
                                    <v-icon start size="small">mdi-delete</v-icon>
                                    Bulk delete rescuers ({{ selectedRescuers.length }})
                                </v-btn>
                                <v-btn 
                                    v-if="selectedRescuers.length > 0"
                                    variant="outlined" 
                                    size="small"
                                    @click="openBulkUpdateDialog"
                                >
                                    <v-icon start size="small">mdi-pencil</v-icon>
                                    Bulk update rescuers
                                </v-btn>
                                <v-menu>
                                    <template v-slot:activator="{ props }">
                                        <v-btn variant="outlined" size="small" v-bind="props">
                                            <v-icon start size="small">mdi-filter</v-icon>
                                            Filter
                                        </v-btn>
                                    </template>
                                    <v-list density="compact" min-width="200">
                                        <v-list-subheader>Filter by Status</v-list-subheader>
                                        <v-list-item 
                                            v-for="option in statusOptions" 
                                            :key="option.value"
                                            :active="statusFilter === option.value"
                                            @click="statusFilter = option.value; fetchRescuers()"
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
                                        <v-list-item @click="sortBy = 'name'; sortOrder = 'asc'; sortRescuers()">
                                            <v-list-item-title>Name (A-Z)</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item @click="sortBy = 'name'; sortOrder = 'desc'; sortRescuers()">
                                            <v-list-item-title>Name (Z-A)</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item @click="sortBy = 'created_at'; sortOrder = 'desc'; sortRescuers()">
                                            <v-list-item-title>Newest First</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item @click="sortBy = 'created_at'; sortOrder = 'asc'; sortRescuers()">
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
                            placeholder="Search rescuers by name, email, or ID..."
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
                        v-model="selectedRescuers"
                        :headers="headers"
                        :items="rescuersList"
                        :loading="loading"
                        :items-per-page="itemsPerPage"
                        item-value="id"
                        show-select
                        class="elevation-0"
                        hover
                    >
                        <!-- Checkbox header -->
                        <template v-slot:header.data-table-select="{ allSelected, selectAll, someSelected }">
                            <v-checkbox-btn
                                :model-value="allSelected"
                                :indeterminate="someSelected && !allSelected"
                                color="primary"
                                @click="selectAll(!allSelected)"
                            />
                        </template>

                        <!-- Name Column -->
                        <template v-slot:item.name="{ item }">
                            <div class="d-flex align-center py-2">
                                <v-avatar :color="getStatusColor(item.status)" size="36" class="mr-3">
                                    <v-img v-if="item.profile_picture" :src="getProfilePictureUrl(item.profile_picture)" cover />
                                    <span v-else class="text-white font-weight-medium text-caption">{{ getInitials(item) }}</span>
                                </v-avatar>
                                <span class="font-weight-medium">{{ item.first_name }} {{ item.last_name }}</span>
                            </div>
                        </template>

                        <!-- Email Column -->
                        <template v-slot:item.email="{ item }">
                            <span class="text-body-2">{{ item.email }}</span>
                        </template>

                        <!-- ID Column -->
                        <template v-slot:item.rescuer_id="{ item }">
                            <span class="text-body-2 font-weight-medium">{{ item.rescuer_id || item.id }}</span>
                        </template>

                        <!-- Status Column -->
                        <template v-slot:item.status="{ item }">
                            <v-chip 
                                :color="getStatusColor(item.status)" 
                                size="small" 
                                variant="flat"
                            >
                                {{ formatStatus(item.status) }}
                            </v-chip>
                        </template>

                        <!-- Date Created Column -->
                        <template v-slot:item.created_at="{ item }">
                            <span class="text-body-2 text-grey">{{ formatDateTime(item.created_at) }}</span>
                        </template>

                        <!-- 2FA Auth Column -->
                        <template v-slot:item.two_factor="{ item }">
                            <span :class="item.two_factor_enabled ? 'text-success' : 'text-grey'">
                                {{ item.two_factor_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
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
                                    <v-list-subheader>Change Status</v-list-subheader>
                                    <v-list-item @click="updateStatus(item, 'available')">
                                        <template v-slot:prepend>
                                            <v-icon color="success" size="small">mdi-check-circle</v-icon>
                                        </template>
                                        <v-list-item-title>Set Available</v-list-item-title>
                                    </v-list-item>
                                    <v-list-item @click="updateStatus(item, 'off_duty')">
                                        <template v-slot:prepend>
                                            <v-icon color="grey" size="small">mdi-sleep</v-icon>
                                        </template>
                                        <v-list-item-title>Set Off Duty</v-list-item-title>
                                    </v-list-item>
                                    <v-list-item @click="updateStatus(item, 'unavailable')">
                                        <template v-slot:prepend>
                                            <v-icon color="error" size="small">mdi-close-circle</v-icon>
                                        </template>
                                        <v-list-item-title>Set Unavailable</v-list-item-title>
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
                                <p class="text-grey mt-4">No rescuers found. Click "Add Rescuer" to create one.</p>
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
                                    :length="Math.ceil(rescuersList.length / itemsPerPage)"
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

        <!-- Add/Edit Single Rescuer Dialog -->
        <v-dialog v-model="dialog" max-width="550">
            <v-card rounded="lg">
                <v-card-title class="d-flex align-center pa-4">
                    <v-icon :color="isEditing ? 'info' : 'primary'" class="mr-2">
                        {{ isEditing ? 'mdi-account-edit' : 'mdi-account-plus' }}
                    </v-icon>
                    {{ isEditing ? 'Edit Rescuer' : 'Add New Rescuer' }}
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
                        <v-text-field
                            v-model="formData.phone"
                            label="Phone Number"
                            variant="outlined"
                            density="compact"
                            class="mb-3"
                        />
                        
                        <!-- Status selector for editing -->
                        <v-select
                            v-if="isEditing"
                            v-model="formData.status"
                            :items="['available', 'on_rescue', 'off_duty', 'unavailable']"
                            label="Status"
                            variant="outlined"
                            density="compact"
                            class="mb-3"
                        />
                        
                        <!-- OTP Activation Notice for new rescuers -->
                        <v-alert 
                            v-if="!isEditing" 
                            type="info" 
                            variant="tonal" 
                            class="mb-3"
                            density="compact"
                        >
                            <v-icon start size="small">mdi-information</v-icon>
                            <span class="text-body-2">
                                An email with OTP verification will be sent to the rescuer. 
                                Account will be <strong>pending</strong> until email is verified and password is changed.
                            </span>
                        </v-alert>
                    </v-form>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="saving" @click="saveRescuer">
                        {{ isEditing ? 'Update' : 'Create & Send OTP' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Bulk Add Dialog -->
        <v-dialog v-model="bulkDialog" max-width="700">
            <v-card rounded="lg">
                <v-card-title class="d-flex align-center pa-4">
                    <v-icon color="primary" class="mr-2">mdi-account-multiple-plus</v-icon>
                    Bulk Add Rescuers
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
                                <code>first_name, last_name, email, phone</code>
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
                            </div>

                            <v-file-input
                                v-model="bulkFile"
                                label="Select file"
                                variant="outlined"
                                density="compact"
                                accept=".xlsx,.xls,.csv"
                                prepend-icon="mdi-file-upload"
                                :rules="[v => !v || v.size < 5000000 || 'File size should be less than 5 MB']"
                                show-size
                                @update:model-value="previewBulkFile"
                            />

                            <!-- Preview Table -->
                            <v-card v-if="bulkPreviewData.length > 0" variant="outlined" class="mt-4">
                                <v-card-title class="text-subtitle-1">
                                    Preview ({{ bulkPreviewData.length }} records)
                                </v-card-title>
                                <v-data-table
                                    :headers="bulkPreviewHeaders"
                                    :items="bulkPreviewData.slice(0, 5)"
                                    :items-per-page="5"
                                    density="compact"
                                    class="elevation-0"
                                />
                                <v-card-text v-if="bulkPreviewData.length > 5" class="text-caption text-grey">
                                    ... and {{ bulkPreviewData.length - 5 }} more records
                                </v-card-text>
                            </v-card>
                        </v-tabs-window-item>

                        <!-- Manual Entry Tab -->
                        <v-tabs-window-item value="manual">
                            <v-alert type="info" variant="tonal" class="mb-4" density="compact">
                                Enter rescuer details below. Click "Add Row" to add more rescuers.
                            </v-alert>

                            <div v-for="(row, index) in manualBulkData" :key="index" class="mb-3">
                                <v-row align="center" dense>
                                    <v-col cols="3">
                                        <v-text-field
                                            v-model="row.first_name"
                                            label="First Name"
                                            variant="outlined"
                                            density="compact"
                                            hide-details
                                        />
                                    </v-col>
                                    <v-col cols="3">
                                        <v-text-field
                                            v-model="row.last_name"
                                            label="Last Name"
                                            variant="outlined"
                                            density="compact"
                                            hide-details
                                        />
                                    </v-col>
                                    <v-col cols="4">
                                        <v-text-field
                                            v-model="row.email"
                                            label="Email"
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
                        Import Rescuers
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
                    <p class="mb-4">Update status for {{ selectedRescuers.length }} selected rescuer(s):</p>
                    <v-select
                        v-model="bulkUpdateStatus"
                        :items="['available', 'off_duty', 'unavailable']"
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
                    Delete Rescuer
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    Are you sure you want to delete <strong>{{ selectedRescuer?.first_name }} {{ selectedRescuer?.last_name }}</strong>? This action cannot be undone.
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" :loading="deleting" @click="deleteRescuer">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Bulk Delete Confirmation -->
        <v-dialog v-model="bulkDeleteDialog" max-width="400">
            <v-card rounded="lg">
                <v-card-title class="text-error pa-4">
                    <v-icon start color="error">mdi-alert</v-icon>
                    Delete Multiple Rescuers
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    Are you sure you want to delete <strong>{{ selectedRescuers.length }}</strong> rescuer(s)? This action cannot be undone.
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
            <v-card v-if="viewingRescuer" rounded="lg">
                <v-card-title class="pa-4">
                    <v-icon color="primary" class="mr-2">mdi-account</v-icon>
                    Rescuer Profile
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    <div class="text-center mb-4">
                        <v-avatar :color="getStatusColor(viewingRescuer.status)" size="80">
                            <v-img v-if="viewingRescuer.profile_picture" :src="getProfilePictureUrl(viewingRescuer.profile_picture)" cover />
                            <span v-else class="text-h4 text-white">{{ getInitials(viewingRescuer) }}</span>
                        </v-avatar>
                        <h3 class="mt-3 text-h6">{{ viewingRescuer.first_name }} {{ viewingRescuer.last_name }}</h3>
                        <v-chip :color="getStatusColor(viewingRescuer.status)" size="small" class="mt-2">
                            {{ formatStatus(viewingRescuer.status) }}
                        </v-chip>
                    </div>
                    <v-list density="compact">
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-email</v-icon>
                            </template>
                            <v-list-item-title>{{ viewingRescuer.email }}</v-list-item-title>
                            <v-list-item-subtitle>Email</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-phone</v-icon>
                            </template>
                            <v-list-item-title>{{ viewingRescuer.phone || viewingRescuer.phone_number || viewingRescuer.contact_number || 'Not provided' }}</v-list-item-title>
                            <v-list-item-subtitle>Phone</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-calendar</v-icon>
                            </template>
                            <v-list-item-title>{{ formatDateTime(viewingRescuer.created_at) }}</v-list-item-title>
                            <v-list-item-subtitle>Date Created</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="profileDialog = false">Close</v-btn>
                    <v-btn color="primary" @click="profileDialog = false; openEditDialog(viewingRescuer)">
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
    rescuers: { type: Object, default: () => ({ data: [] }) },
    counts: { type: Object, default: () => ({ total: 0, available: 0, on_rescue: 0, off_duty: 0, unavailable: 0 }) },
    auditTrail: { type: Array, default: () => [] }
});

// State
const drawer = ref(true);
const currentPage = ref('rescuers');
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
const statusFilter = ref('all');
const selectedRescuer = ref(null);
const viewingRescuer = ref(null);
const snackbar = ref(false);
const snackbarText = ref('');
const snackbarColor = ref('success');
const page = ref(1);
const itemsPerPage = ref(50);
const sortBy = ref('name');
const sortOrder = ref('asc');
const selectedRescuers = ref([]);

// Bulk upload
const bulkTab = ref('upload');
const bulkFile = ref(null);
const bulkPreviewData = ref([]);
const bulkUpdateStatus = ref('available');
const manualBulkData = ref([{ first_name: '', last_name: '', email: '', phone: '' }]);

// Data
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

// Computed
const adminInitials = computed(() => {
    // Get from localStorage or default
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    if (userData.first_name && userData.last_name) {
        return `${userData.first_name[0]}${userData.last_name[0]}`.toUpperCase();
    }
    return 'AD';
});

// Table headers
const headers = [
    { title: 'Name', key: 'name', sortable: true },
    { title: 'Email', key: 'email', sortable: true },
    { title: 'ID', key: 'rescuer_id', sortable: false, width: '100px' },
    { title: 'Status', key: 'status', sortable: true },
    { title: 'Date created', key: 'created_at', sortable: true },
    { title: '2F Auth', key: 'two_factor', sortable: false },
    { title: '', key: 'actions', sortable: false, align: 'end', width: '50px' }
];

const bulkPreviewHeaders = [
    { title: 'First Name', key: 'first_name' },
    { title: 'Last Name', key: 'last_name' },
    { title: 'Email', key: 'email' },
    { title: 'Phone', key: 'phone' }
];

const statusOptions = [
    { label: 'All Status', value: 'all' },
    { label: 'Available', value: 'available' },
    { label: 'On Rescue', value: 'on_rescue' },
    { label: 'Off Duty', value: 'off_duty' },
    { label: 'Unavailable', value: 'unavailable' }
];

// Methods
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
            if (data.audit_trail) {
                auditTrail.value = data.audit_trail;
            }
        }
    } catch (error) {
        console.error('Error fetching rescuers:', error);
        showSnackbar('Error fetching rescuers', 'error');
    } finally {
        loading.value = false;
    }
};

const sortRescuers = () => {
    rescuersList.value.sort((a, b) => {
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

const openBulkDialog = () => {
    bulkTab.value = 'upload';
    bulkFile.value = null;
    bulkPreviewData.value = [];
    manualBulkData.value = [{ first_name: '', last_name: '', email: '', phone: '' }];
    bulkDialog.value = true;
};

const openBulkUpdateDialog = () => {
    bulkUpdateStatus.value = 'available';
    bulkUpdateDialog.value = true;
};

const viewProfile = (rescuer) => {
    viewingRescuer.value = rescuer;
    profileDialog.value = true;
};

const saveRescuer = async () => {
    saving.value = true;
    try {
        const url = isEditing.value ? `/admin/rescuers/${selectedRescuer.value.id}` : '/admin/rescuers';
        const method = isEditing.value ? 'PUT' : 'POST';
        
        const submitData = {
            ...formData.value,
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
            showSnackbar(`Status updated to ${formatStatus(status)}`, 'success');
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

// Bulk operations
const bulkDeleteConfirm = () => {
    bulkDeleteDialog.value = true;
};

const processBulkDelete = async () => {
    bulkDeleting.value = true;
    try {
        for (const id of selectedRescuers.value) {
            await fetch(`/admin/rescuers/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });
        }
        showSnackbar(`${selectedRescuers.value.length} rescuer(s) deleted successfully`, 'success');
        bulkDeleteDialog.value = false;
        selectedRescuers.value = [];
        fetchRescuers();
    } catch (error) {
        console.error('Error bulk deleting:', error);
        showSnackbar('Error deleting rescuers', 'error');
    } finally {
        bulkDeleting.value = false;
    }
};

const processBulkUpdate = async () => {
    bulkUpdating.value = true;
    try {
        for (const id of selectedRescuers.value) {
            await fetch(`/admin/rescuers/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({ status: bulkUpdateStatus.value })
            });
        }
        showSnackbar(`${selectedRescuers.value.length} rescuer(s) updated successfully`, 'success');
        bulkUpdateDialog.value = false;
        selectedRescuers.value = [];
        fetchRescuers();
    } catch (error) {
        console.error('Error bulk updating:', error);
        showSnackbar('Error updating rescuers', 'error');
    } finally {
        bulkUpdating.value = false;
    }
};

const previewBulkFile = async (file) => {
    if (!file) {
        bulkPreviewData.value = [];
        return;
    }
    
    try {
        const data = await file.arrayBuffer();
        const workbook = XLSX.read(data);
        const sheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[sheetName];
        const jsonData = XLSX.utils.sheet_to_json(worksheet);
        
        bulkPreviewData.value = jsonData.map(row => ({
            first_name: row.first_name || row['First Name'] || '',
            last_name: row.last_name || row['Last Name'] || '',
            email: row.email || row['Email'] || '',
            phone: row.phone || row['Phone'] || ''
        }));
    } catch (error) {
        console.error('Error parsing file:', error);
        showSnackbar('Error parsing file. Please check the format.', 'error');
    }
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
        
        for (const row of dataToProcess) {
            try {
                const response = await fetch('/admin/rescuers', {
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
                        phone: row.phone || '',
                        send_otp: true
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    successCount++;
                } else {
                    errorCount++;
                }
            } catch {
                errorCount++;
            }
        }
        
        showSnackbar(`Imported ${successCount} rescuer(s). ${errorCount > 0 ? `${errorCount} failed.` : ''}`, successCount > 0 ? 'success' : 'error');
        bulkDialog.value = false;
        fetchRescuers();
    } catch (error) {
        console.error('Error bulk adding:', error);
        showSnackbar('Error importing rescuers', 'error');
    } finally {
        bulkSaving.value = false;
    }
};

const downloadTemplate = (format) => {
    const templateData = [
        { first_name: 'John', last_name: 'Doe', email: 'john.doe@sdca.edu.ph', phone: '09171234567' },
        { first_name: 'Jane', last_name: 'Smith', email: 'jane.smith@sdca.edu.ph', phone: '09181234567' }
    ];
    
    const worksheet = XLSX.utils.json_to_sheet(templateData);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Rescuers');
    
    if (format === 'csv') {
        XLSX.writeFile(workbook, 'rescuers_template.csv');
    } else {
        XLSX.writeFile(workbook, 'rescuers_template.xlsx');
    }
};

const addManualRow = () => {
    manualBulkData.value.push({ first_name: '', last_name: '', email: '', phone: '' });
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
    const labels = {
        available: 'Available',
        on_rescue: 'On rescue',
        off_duty: 'Off duty',
        unavailable: 'Unavailable'
    };
    return labels[status] || status?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || 'Unknown';
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

/* Card hover effect */
.v-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

/* Gap utility */
.gap-2 {
    gap: 8px;
}

.gap-3 {
    gap: 12px;
}
</style>
