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
                <v-list-item prepend-icon="mdi-lifebuoy" title="Rescuers" href="/admin/rescuers"></v-list-item>
                <v-list-item prepend-icon="mdi-office-building" title="Buildings" href="/admin/buildings" active></v-list-item>
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
                        <h1 class="text-h4 font-weight-bold">Building Management</h1>
                        <p class="text-grey mt-1">Manage buildings, floors, and rooms</p>
                    </div>
                    <v-spacer />
                    <v-btn color="primary" @click="openAddBuildingDialog">
                        <v-icon start>mdi-plus</v-icon>
                        Add Building
                    </v-btn>
                </div>

                <!-- Stats Cards -->
                <v-row class="mb-6">
                    <v-col cols="12" sm="4">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="primary" size="48">
                                    <v-icon color="white">mdi-office-building</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Total Buildings</p>
                                    <h4 class="text-h5 font-weight-bold">{{ buildingsList.length }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="4">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="info" size="48">
                                    <v-icon color="white">mdi-layers</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Total Floors</p>
                                    <h4 class="text-h5 font-weight-bold">{{ totalFloors }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="4">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="success" size="48">
                                    <v-icon color="white">mdi-door</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Total Rooms</p>
                                    <h4 class="text-h5 font-weight-bold">{{ totalRooms }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Buildings List -->
                <v-row>
                    <v-col v-for="building in buildingsList" :key="building.id" cols="12">
                        <v-card rounded="lg">
                            <v-card-title class="d-flex align-center">
                                <v-avatar color="primary" size="40" class="mr-3">
                                    <v-icon color="white">mdi-domain</v-icon>
                                </v-avatar>
                                <div>
                                    <span class="font-weight-bold">{{ building.name }}</span>
                                    <div class="text-caption text-grey">{{ building.floors?.length || 0 }} floors</div>
                                </div>
                                <v-spacer />
                                <v-btn variant="text" size="small" @click="toggleBuilding(building.id)">
                                    <v-icon>{{ expandedBuildings.includes(building.id) ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
                                </v-btn>
                                <v-menu>
                                    <template v-slot:activator="{ props }">
                                        <v-btn icon size="small" v-bind="props">
                                            <v-icon>mdi-dots-vertical</v-icon>
                                        </v-btn>
                                    </template>
                                    <v-list density="compact">
                                        <v-list-item @click="openAddFloorDialog(building)">
                                            <v-list-item-title>
                                                <v-icon start size="small">mdi-layers</v-icon>
                                                Add Floor
                                            </v-list-item-title>
                                        </v-list-item>
                                        <v-list-item @click="openBulkQrDialog(building)">
                                            <v-list-item-title>
                                                <v-icon start size="small" color="primary">mdi-qrcode</v-icon>
                                                Generate All QR Codes
                                            </v-list-item-title>
                                        </v-list-item>
                                        <v-divider />
                                        <v-list-item @click="openEditBuildingDialog(building)">
                                            <v-list-item-title>
                                                <v-icon start size="small">mdi-pencil</v-icon>
                                                Edit Building
                                            </v-list-item-title>
                                        </v-list-item>
                                        <v-list-item @click="confirmDeleteBuilding(building)">
                                            <v-list-item-title class="text-error">
                                                <v-icon start size="small" color="error">mdi-delete</v-icon>
                                                Delete Building
                                            </v-list-item-title>
                                        </v-list-item>
                                    </v-list>
                                </v-menu>
                            </v-card-title>

                            <v-expand-transition>
                                <div v-show="expandedBuildings.includes(building.id)">
                                    <v-divider />
                                    <v-card-text>
                                        <!-- Floors -->
                                        <div v-for="floor in building.floors" :key="floor.id" class="mb-4">
                                            <div class="d-flex align-center pa-3 bg-grey-lighten-4 rounded mb-2">
                                                <v-icon class="mr-2" color="info">mdi-layers</v-icon>
                                                <span class="font-weight-medium">{{ floor.floor_name }}</span>
                                                <v-chip size="x-small" class="ml-2" color="grey">
                                                    {{ floor.rooms?.length || 0 }} rooms
                                                </v-chip>
                                                <v-chip 
                                                    v-if="floor.floor_plan_url" 
                                                    size="x-small" 
                                                    class="ml-2" 
                                                    color="success"
                                                >
                                                    <v-icon start size="x-small">mdi-floor-plan</v-icon>
                                                    Floor Plan
                                                </v-chip>
                                                <v-spacer />
                                                <v-btn 
                                                    v-if="floor.floor_plan_url && floor.floor_plan_data?.evacuation_paths?.length"
                                                    variant="text" 
                                                    size="x-small" 
                                                    color="warning"
                                                    @click="openEvacuationPathDialog(building, floor)"
                                                    title="View Evacuation Paths"
                                                >
                                                    <v-icon start size="small">mdi-routes</v-icon>
                                                    Evacuation
                                                </v-btn>
                                                <v-btn 
                                                    variant="text" 
                                                    size="x-small" 
                                                    color="primary"
                                                    @click="openFloorPlanEditor(floor)"
                                                    title="Edit Floor Plan"
                                                >
                                                    <v-icon start size="small">mdi-floor-plan</v-icon>
                                                    Floor Plan
                                                </v-btn>
                                                <v-btn variant="text" size="x-small" @click="openAddRoomDialog(building, floor)">
                                                    <v-icon start size="small">mdi-plus</v-icon>
                                                    Add Room
                                                </v-btn>
                                                <v-btn icon size="x-small" @click="openEditFloorDialog(building, floor)">
                                                    <v-icon size="small">mdi-pencil</v-icon>
                                                </v-btn>
                                                <v-btn icon size="x-small" color="error" @click="confirmDeleteFloor(building, floor)">
                                                    <v-icon size="small">mdi-delete</v-icon>
                                                </v-btn>
                                            </div>

                                            <!-- Rooms -->
                                            <v-row class="ml-6">
                                                <v-col v-for="room in floor.rooms" :key="room.id" cols="6" sm="4" md="3" lg="2">
                                                    <v-card variant="outlined" class="pa-2 text-center">
                                                        <v-icon color="success" size="small">mdi-door</v-icon>
                                                        <div class="text-caption font-weight-medium">{{ room.room_name }}</div>
                                                        <div class="d-flex justify-center mt-1">
                                                            <v-btn icon size="x-small" color="primary" @click="openQrDialog(building, floor, room)" title="Generate QR Code">
                                                                <v-icon size="x-small">mdi-qrcode</v-icon>
                                                            </v-btn>
                                                            <v-btn icon size="x-small" @click="openEditRoomDialog(building, floor, room)">
                                                                <v-icon size="x-small">mdi-pencil</v-icon>
                                                            </v-btn>
                                                            <v-btn icon size="x-small" color="error" @click="confirmDeleteRoom(building, floor, room)">
                                                                <v-icon size="x-small">mdi-delete</v-icon>
                                                            </v-btn>
                                                        </div>
                                                    </v-card>
                                                </v-col>
                                            </v-row>
                                        </div>

                                        <v-alert v-if="!building.floors?.length" type="info" variant="tonal" density="compact">
                                            No floors added yet. Click the menu to add a floor.
                                        </v-alert>
                                    </v-card-text>
                                </div>
                            </v-expand-transition>
                        </v-card>
                    </v-col>
                </v-row>

                <v-alert v-if="buildingsList.length === 0" type="info" variant="tonal" class="mt-4">
                    No buildings found. Click "Add Building" to create one.
                </v-alert>
            </v-container>
        </v-main>

        <!-- Add/Edit Building Dialog -->
        <v-dialog v-model="buildingDialog" max-width="500">
            <v-card>
                <v-card-title>{{ isEditingBuilding ? 'Edit Building' : 'Add New Building' }}</v-card-title>
                <v-card-text>
                    <v-text-field
                        v-model="buildingForm.name"
                        label="Building Name"
                        variant="outlined"
                        density="compact"
                        :rules="[v => !!v || 'Building name is required']"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="buildingDialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="saving" @click="saveBuilding">
                        {{ isEditingBuilding ? 'Update' : 'Create' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Add/Edit Floor Dialog -->
        <v-dialog v-model="floorDialog" max-width="500">
            <v-card>
                <v-card-title>{{ isEditingFloor ? 'Edit Floor' : 'Add New Floor' }}</v-card-title>
                <v-card-text>
                    <p class="text-caption text-grey mb-3">Building: {{ selectedBuilding?.name }}</p>
                    <v-text-field
                        v-model="floorForm.floor_name"
                        label="Floor Name"
                        variant="outlined"
                        density="compact"
                        placeholder="e.g., 1st Floor, Ground Floor"
                        :rules="[v => !!v || 'Floor name is required']"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="floorDialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="saving" @click="saveFloor">
                        {{ isEditingFloor ? 'Update' : 'Create' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Add/Edit Room Dialog -->
        <v-dialog v-model="roomDialog" max-width="500">
            <v-card>
                <v-card-title>{{ isEditingRoom ? 'Edit Room' : 'Add New Room' }}</v-card-title>
                <v-card-text>
                    <p class="text-caption text-grey mb-3">
                        Building: {{ selectedBuilding?.name }} / Floor: {{ selectedFloor?.floor_name }}
                    </p>
                    <v-text-field
                        v-model="roomForm.room_name"
                        label="Room Name"
                        variant="outlined"
                        density="compact"
                        placeholder="e.g., Room 101, Laboratory A"
                        :rules="[v => !!v || 'Room name is required']"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="roomDialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="saving" @click="saveRoom">
                        {{ isEditingRoom ? 'Update' : 'Create' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title class="text-error">
                    <v-icon start color="error">mdi-alert</v-icon>
                    Delete {{ deleteType }}
                </v-card-title>
                <v-card-text>
                    Are you sure you want to delete <strong>{{ deleteItemName }}</strong>?
                    <span v-if="deleteType === 'Building'">All floors and rooms in this building will also be deleted.</span>
                    <span v-if="deleteType === 'Floor'">All rooms on this floor will also be deleted.</span>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" :loading="deleting" @click="executeDelete">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- QR Code Dialog -->
        <v-dialog v-model="qrDialog" max-width="500">
            <v-card>
                <v-card-title class="d-flex align-center">
                    <v-icon start color="primary">mdi-qrcode</v-icon>
                    Room QR Code
                </v-card-title>
                <v-card-text class="text-center">
                    <div class="mb-4">
                        <p class="text-subtitle-1 font-weight-bold">{{ qrRoomInfo.building }}</p>
                        <p class="text-body-2 text-grey">{{ qrRoomInfo.floor }} → {{ qrRoomInfo.room }}</p>
                    </div>
                    
                    <!-- QR Code Canvas -->
                    <div class="d-flex justify-center mb-4">
                        <canvas ref="qrCanvas" id="qr-canvas"></canvas>
                    </div>
                    
                    <!-- QR Data Preview -->
                    <v-expansion-panels variant="accordion" class="mb-4">
                        <v-expansion-panel>
                            <v-expansion-panel-title>
                                <v-icon start size="small">mdi-code-json</v-icon>
                                View QR Data
                            </v-expansion-panel-title>
                            <v-expansion-panel-text>
                                <pre class="text-left text-caption bg-grey-lighten-4 pa-2 rounded">{{ qrDataPreview }}</pre>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card-text>
                <v-card-actions>
                    <v-btn variant="text" @click="printQrCode">
                        <v-icon start>mdi-printer</v-icon>
                        Print
                    </v-btn>
                    <v-spacer />
                    <v-btn variant="text" @click="qrDialog = false">Close</v-btn>
                    <v-btn color="primary" @click="downloadQrCode">
                        <v-icon start>mdi-download</v-icon>
                        Download
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Bulk QR Code Generation Dialog -->
        <v-dialog v-model="bulkQrDialog" max-width="600" scrollable>
            <v-card>
                <v-card-title class="d-flex align-center">
                    <v-icon start color="primary">mdi-qrcode</v-icon>
                    Bulk QR Code Generation
                </v-card-title>
                <v-card-text>
                    <p class="text-body-2 mb-4">Generate QR codes for all rooms in this building</p>
                    <div id="bulk-qr-container" class="d-flex flex-wrap justify-center gap-4">
                        <div v-for="qrItem in bulkQrItems" :key="qrItem.id" class="text-center pa-2 border rounded">
                            <canvas :id="'bulk-qr-' + qrItem.id" width="150" height="150"></canvas>
                            <p class="text-caption mt-1">{{ qrItem.floor }}</p>
                            <p class="text-caption font-weight-bold">{{ qrItem.room }}</p>
                        </div>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-btn variant="text" @click="printAllQrCodes">
                        <v-icon start>mdi-printer</v-icon>
                        Print All
                    </v-btn>
                    <v-spacer />
                    <v-btn variant="text" @click="bulkQrDialog = false">Close</v-btn>
                    <v-btn color="primary" @click="downloadAllQrCodes">
                        <v-icon start>mdi-download</v-icon>
                        Download All
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Evacuation Path Preview Dialog -->
        <v-dialog v-model="evacuationDialog" max-width="900" scrollable>
            <v-card>
                <v-card-title class="d-flex align-center">
                    <v-icon start color="warning">mdi-routes</v-icon>
                    Evacuation Paths - {{ evacuationFloorInfo.building }} / {{ evacuationFloorInfo.floor }}
                </v-card-title>
                <v-card-subtitle class="px-6 pt-3">
                    <v-select
                        v-model="selectedEvacuationRoom"
                        :items="evacuationRoomOptions"
                        item-title="text"
                        item-value="value"
                        label="Select Room to View Evacuation Paths"
                        variant="outlined"
                        density="compact"
                        @update:model-value="onEvacuationRoomChange"
                    >
                        <template v-slot:prepend-inner>
                            <v-icon>mdi-door</v-icon>
                        </template>
                    </v-select>
                    <v-alert
                        v-if="selectedEvacuationRoom && filteredEvacuationPaths.length === 0"
                        type="info"
                        variant="tonal"
                        density="compact"
                        class="mt-2"
                    >
                        No evacuation paths assigned to this room yet.
                    </v-alert>
                </v-card-subtitle>
                <v-card-text class="pa-0">
                    <div class="evacuation-canvas-container">
                        <div class="evacuation-canvas-wrapper" :style="evacuationWrapperStyle">
                            <img 
                                v-if="evacuationFloorInfo.imageUrl" 
                                :src="evacuationFloorInfo.imageUrl" 
                                class="evacuation-image"
                                ref="evacuationImage"
                                @load="onEvacuationImageLoad"
                            />
                            <canvas 
                                ref="evacuationCanvas" 
                                class="evacuation-overlay"
                            ></canvas>
                        </div>
                    </div>
                    <!-- Legend -->
                    <div class="pa-4 bg-grey-lighten-4">
                        <div class="text-subtitle-2 mb-2">Legend</div>
                        <div class="d-flex flex-wrap gap-4">
                            <div class="d-flex align-center">
                                <v-avatar color="success" size="16" class="mr-2"></v-avatar>
                                <span class="text-caption">Start Point</span>
                            </div>
                            <div class="d-flex align-center">
                                <v-avatar color="error" size="16" class="mr-2"></v-avatar>
                                <span class="text-caption">Exit Point</span>
                            </div>
                            <div class="d-flex align-center">
                                <div class="legend-line mr-2"></div>
                                <span class="text-caption">Evacuation Path</span>
                            </div>
                            <div class="d-flex align-center">
                                <div class="legend-room mr-2"></div>
                                <span class="text-caption">Room</span>
                            </div>
                        </div>
                        <div v-if="filteredEvacuationPaths.length" class="mt-3">
                            <div class="text-subtitle-2 mb-1">Paths ({{ filteredEvacuationPaths.length }})</div>
                            <v-chip 
                                v-for="(path, idx) in filteredEvacuationPaths" 
                                :key="idx" 
                                size="small" 
                                class="mr-1 mb-1"
                                :color="path.color || '#FF5722'"
                            >
                                {{ path.name || 'Path ' + (idx + 1) }}
                            </v-chip>
                        </div>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-btn 
                        variant="text" 
                        @click="printEvacuationPlan"
                        :disabled="!selectedEvacuationRoom || filteredEvacuationPaths.length === 0"
                    >
                        <v-icon start>mdi-printer</v-icon>
                        Print
                    </v-btn>
                    <v-spacer />
                    <v-btn variant="text" @click="evacuationDialog = false">Close</v-btn>
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
import { ref, computed, onMounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import QRCode from 'qrcode';

const props = defineProps({
    buildings: { type: Array, default: () => [] }
});

const drawer = ref(true);
const saving = ref(false);
const deleting = ref(false);
const snackbar = ref(false);
const snackbarText = ref('');
const snackbarColor = ref('success');

const buildingsList = ref(props.buildings || []);
const expandedBuildings = ref([]);

// Building dialog
const buildingDialog = ref(false);
const isEditingBuilding = ref(false);
const buildingForm = ref({ name: '' });
const selectedBuilding = ref(null);

// Floor dialog
const floorDialog = ref(false);
const isEditingFloor = ref(false);
const floorForm = ref({ floor_name: '' });
const selectedFloor = ref(null);

// Room dialog
const roomDialog = ref(false);
const isEditingRoom = ref(false);
const roomForm = ref({ room_name: '' });
const selectedRoom = ref(null);

// Delete dialog
const deleteDialog = ref(false);
const deleteType = ref('');
const deleteItemName = ref('');
const deleteCallback = ref(null);

// QR Code dialog
const qrDialog = ref(false);
const qrCanvas = ref(null);
const qrRoomInfo = ref({ building: '', floor: '', room: '' });
const qrData = ref(null);
const qrDataPreview = computed(() => JSON.stringify(qrData.value, null, 2));

// Bulk QR dialog
const bulkQrDialog = ref(false);
const bulkQrItems = ref([]);

// Evacuation path dialog
const evacuationDialog = ref(false);
const evacuationImage = ref(null);
const evacuationCanvas = ref(null);
const selectedEvacuationRoom = ref(null);
const evacuationFloorInfo = ref({
    building: '',
    floor: '',
    imageUrl: '',
    paths: [],
    rooms: [],
    floorRooms: []
});
const evacuationZoom = ref(1);
const evacuationWrapperStyle = computed(() => ({
    transform: `scale(${evacuationZoom.value})`,
    transformOrigin: 'top left'
}));

// Computed: Room options for evacuation path selector
const evacuationRoomOptions = computed(() => {
    const rooms = evacuationFloorInfo.value.floorRooms || [];
    return rooms.map(room => ({
        value: room.id,
        text: room.room_name
    }));
});

// Computed: Filter evacuation paths based on selected room
const filteredEvacuationPaths = computed(() => {
    if (!selectedEvacuationRoom.value) return [];
    return evacuationFloorInfo.value.paths.filter(
        path => path.room_id === selectedEvacuationRoom.value
    );
});

const totalFloors = computed(() => {
    return buildingsList.value.reduce((sum, b) => sum + (b.floors?.length || 0), 0);
});

const totalRooms = computed(() => {
    return buildingsList.value.reduce((sum, b) => {
        return sum + (b.floors?.reduce((fSum, f) => fSum + (f.rooms?.length || 0), 0) || 0);
    }, 0);
});

const toggleBuilding = (id) => {
    const index = expandedBuildings.value.indexOf(id);
    if (index === -1) {
        expandedBuildings.value.push(id);
    } else {
        expandedBuildings.value.splice(index, 1);
    }
};

const fetchBuildings = async () => {
    try {
        const response = await fetch('/admin/buildings', {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success) {
            buildingsList.value = data.data;
        }
    } catch (error) {
        console.error('Error fetching buildings:', error);
    }
};

// Building operations
const openAddBuildingDialog = () => {
    isEditingBuilding.value = false;
    buildingForm.value = { name: '' };
    buildingDialog.value = true;
};

const openEditBuildingDialog = (building) => {
    isEditingBuilding.value = true;
    selectedBuilding.value = building;
    buildingForm.value = { name: building.name };
    buildingDialog.value = true;
};

const saveBuilding = async () => {
    saving.value = true;
    try {
        const url = isEditingBuilding.value ? `/buildings/${selectedBuilding.value.id}` : '/buildings';
        const method = isEditingBuilding.value ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(buildingForm.value)
        });
        
        const data = await response.json();
        if (data) {
            showSnackbar(isEditingBuilding.value ? 'Building updated' : 'Building created', 'success');
            buildingDialog.value = false;
            fetchBuildings();
        }
    } catch (error) {
        console.error('Error saving building:', error);
        showSnackbar('Error saving building', 'error');
    } finally {
        saving.value = false;
    }
};

const confirmDeleteBuilding = (building) => {
    deleteType.value = 'Building';
    deleteItemName.value = building.name;
    deleteCallback.value = () => deleteBuilding(building);
    deleteDialog.value = true;
};

const deleteBuilding = async (building) => {
    deleting.value = true;
    try {
        const response = await fetch(`/buildings/${building.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        
        showSnackbar('Building deleted', 'success');
        deleteDialog.value = false;
        fetchBuildings();
    } catch (error) {
        console.error('Error deleting building:', error);
        showSnackbar('Error deleting building', 'error');
    } finally {
        deleting.value = false;
    }
};

// Floor operations
const openAddFloorDialog = (building) => {
    isEditingFloor.value = false;
    selectedBuilding.value = building;
    floorForm.value = { floor_name: '' };
    floorDialog.value = true;
};

const openEditFloorDialog = (building, floor) => {
    isEditingFloor.value = true;
    selectedBuilding.value = building;
    selectedFloor.value = floor;
    floorForm.value = { floor_name: floor.floor_name };
    floorDialog.value = true;
};

const saveFloor = async () => {
    saving.value = true;
    try {
        const url = isEditingFloor.value 
            ? `/floors/${selectedFloor.value.id}` 
            : `/buildings/${selectedBuilding.value.id}/floors`;
        const method = isEditingFloor.value ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                ...floorForm.value,
                building_id: selectedBuilding.value.id
            })
        });
        
        showSnackbar(isEditingFloor.value ? 'Floor updated' : 'Floor created', 'success');
        floorDialog.value = false;
        fetchBuildings();
    } catch (error) {
        console.error('Error saving floor:', error);
        showSnackbar('Error saving floor', 'error');
    } finally {
        saving.value = false;
    }
};

const confirmDeleteFloor = (building, floor) => {
    deleteType.value = 'Floor';
    deleteItemName.value = floor.floor_name;
    deleteCallback.value = () => deleteFloor(floor);
    deleteDialog.value = true;
};

const deleteFloor = async (floor) => {
    deleting.value = true;
    try {
        await fetch(`/floors/${floor.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        
        showSnackbar('Floor deleted', 'success');
        deleteDialog.value = false;
        fetchBuildings();
    } catch (error) {
        console.error('Error deleting floor:', error);
        showSnackbar('Error deleting floor', 'error');
    } finally {
        deleting.value = false;
    }
};

// Room operations
const openAddRoomDialog = (building, floor) => {
    isEditingRoom.value = false;
    selectedBuilding.value = building;
    selectedFloor.value = floor;
    roomForm.value = { room_name: '' };
    roomDialog.value = true;
};

const openEditRoomDialog = (building, floor, room) => {
    isEditingRoom.value = true;
    selectedBuilding.value = building;
    selectedFloor.value = floor;
    selectedRoom.value = room;
    roomForm.value = { room_name: room.room_name };
    roomDialog.value = true;
};

const saveRoom = async () => {
    saving.value = true;
    try {
        const url = isEditingRoom.value 
            ? `/rooms/${selectedRoom.value.id}` 
            : `/buildings/${selectedBuilding.value.id}/rooms`;
        const method = isEditingRoom.value ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                ...roomForm.value,
                floor_id: selectedFloor.value.id,
                building_id: selectedBuilding.value.id
            })
        });
        
        showSnackbar(isEditingRoom.value ? 'Room updated' : 'Room created', 'success');
        roomDialog.value = false;
        fetchBuildings();
    } catch (error) {
        console.error('Error saving room:', error);
        showSnackbar('Error saving room', 'error');
    } finally {
        saving.value = false;
    }
};

const confirmDeleteRoom = (building, floor, room) => {
    deleteType.value = 'Room';
    deleteItemName.value = room.room_name;
    deleteCallback.value = () => deleteRoom(room);
    deleteDialog.value = true;
};

const deleteRoom = async (room) => {
    deleting.value = true;
    try {
        await fetch(`/rooms/${room.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        
        showSnackbar('Room deleted', 'success');
        deleteDialog.value = false;
        fetchBuildings();
    } catch (error) {
        console.error('Error deleting room:', error);
        showSnackbar('Error deleting room', 'error');
    } finally {
        deleting.value = false;
    }
};

const executeDelete = () => {
    if (deleteCallback.value) {
        deleteCallback.value();
    }
};

const showSnackbar = (text, color) => {
    snackbarText.value = text;
    snackbarColor.value = color;
    snackbar.value = true;
};

// ============================================================
// QR Code Generation Functions
// ============================================================

const openQrDialog = async (building, floor, room) => {
    qrRoomInfo.value = {
        building: building.name,
        floor: floor.floor_name,
        room: room.room_name
    };
    
    // Create QR data with both IDs and names for maximum compatibility
    qrData.value = {
        building_id: building.id,
        building_name: building.name,
        floor_id: floor.id,
        floor_name: floor.floor_name,
        room_id: room.id,
        room_name: room.room_name
    };
    
    qrDialog.value = true;
    
    // Wait for dialog to open and canvas to be available
    await nextTick();
    setTimeout(() => generateQrCode(), 100);
};

const generateQrCode = async () => {
    const canvas = document.getElementById('qr-canvas');
    if (!canvas) {
        console.error('QR Canvas not found');
        return;
    }
    
    try {
        await QRCode.toCanvas(canvas, JSON.stringify(qrData.value), {
            width: 250,
            margin: 2,
            color: {
                dark: '#000000',
                light: '#ffffff'
            },
            errorCorrectionLevel: 'M'
        });
    } catch (error) {
        console.error('Error generating QR code:', error);
        showSnackbar('Error generating QR code', 'error');
    }
};

const downloadQrCode = () => {
    const canvas = document.getElementById('qr-canvas');
    if (!canvas) return;
    
    const link = document.createElement('a');
    link.download = `QR-${qrRoomInfo.value.building}-${qrRoomInfo.value.floor}-${qrRoomInfo.value.room}.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
    
    showSnackbar('QR code downloaded', 'success');
};

const printQrCode = () => {
    const canvas = document.getElementById('qr-canvas');
    if (!canvas) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>QR Code - ${qrRoomInfo.value.room}</title>
            <style>
                body { 
                    display: flex; 
                    flex-direction: column; 
                    align-items: center; 
                    justify-content: center; 
                    min-height: 100vh; 
                    margin: 0;
                    font-family: Arial, sans-serif;
                }
                .qr-container {
                    text-align: center;
                    padding: 20px;
                    border: 2px solid #333;
                    border-radius: 10px;
                }
                h2 { margin: 0 0 5px 0; }
                p { margin: 5px 0; color: #666; }
                img { margin: 15px 0; }
                @media print {
                    body { padding: 0; }
                    .qr-container { border: 1px solid #333; }
                }
            </style>
        </head>
        <body>
            <div class="qr-container">
                <h2>${qrRoomInfo.value.building}</h2>
                <p>${qrRoomInfo.value.floor} → ${qrRoomInfo.value.room}</p>
                <img src="${canvas.toDataURL('image/png')}" alt="QR Code" />
                <p style="font-size: 12px; color: #999;">Scan this QR code in emergency</p>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
};

const openBulkQrDialog = async (building) => {
    bulkQrItems.value = [];
    
    // Collect all rooms from all floors
    building.floors?.forEach(floor => {
        floor.rooms?.forEach(room => {
            bulkQrItems.value.push({
                id: room.id,
                building: building.name,
                floor: floor.floor_name,
                room: room.room_name,
                data: {
                    building_id: building.id,
                    building_name: building.name,
                    floor_id: floor.id,
                    floor_name: floor.floor_name,
                    room_id: room.id,
                    room_name: room.room_name
                }
            });
        });
    });
    
    if (bulkQrItems.value.length === 0) {
        showSnackbar('No rooms found in this building', 'warning');
        return;
    }
    
    bulkQrDialog.value = true;
    
    // Generate all QR codes
    await nextTick();
    setTimeout(() => generateBulkQrCodes(), 100);
};

const generateBulkQrCodes = async () => {
    for (const item of bulkQrItems.value) {
        const canvas = document.getElementById(`bulk-qr-${item.id}`);
        if (canvas) {
            try {
                await QRCode.toCanvas(canvas, JSON.stringify(item.data), {
                    width: 150,
                    margin: 1,
                    errorCorrectionLevel: 'M'
                });
            } catch (error) {
                console.error(`Error generating QR for room ${item.id}:`, error);
            }
        }
    }
};

const downloadAllQrCodes = async () => {
    for (const item of bulkQrItems.value) {
        const canvas = document.getElementById(`bulk-qr-${item.id}`);
        if (canvas) {
            const link = document.createElement('a');
            link.download = `QR-${item.building}-${item.floor}-${item.room}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
            // Small delay between downloads
            await new Promise(resolve => setTimeout(resolve, 100));
        }
    }
    showSnackbar('All QR codes downloaded', 'success');
};

const printAllQrCodes = () => {
    const printWindow = window.open('', '_blank');
    
    let qrHtml = '';
    bulkQrItems.value.forEach(item => {
        const canvas = document.getElementById(`bulk-qr-${item.id}`);
        if (canvas) {
            qrHtml += `
                <div class="qr-item">
                    <img src="${canvas.toDataURL('image/png')}" alt="QR Code" />
                    <p class="floor">${item.floor}</p>
                    <p class="room">${item.room}</p>
                </div>
            `;
        }
    });
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>QR Codes - ${bulkQrItems.value[0]?.building || 'Building'}</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0; 
                    padding: 20px;
                }
                h1 { text-align: center; margin-bottom: 20px; }
                .qr-grid { 
                    display: flex; 
                    flex-wrap: wrap; 
                    justify-content: center; 
                    gap: 20px; 
                }
                .qr-item { 
                    text-align: center; 
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    page-break-inside: avoid;
                }
                .qr-item img { width: 150px; height: 150px; }
                .qr-item .floor { margin: 5px 0 0 0; font-size: 12px; color: #666; }
                .qr-item .room { margin: 2px 0 0 0; font-weight: bold; }
                @media print {
                    .qr-item { border: 1px solid #333; }
                }
            </style>
        </head>
        <body>
            <h1>${bulkQrItems.value[0]?.building || 'Building'} - Room QR Codes</h1>
            <div class="qr-grid">${qrHtml}</div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
};

// ============================================================
// Floor Plan Editor Functions
// ============================================================

const openFloorPlanEditor = (floor) => {
    window.location.href = `/admin/floor-plan/${floor.id}`;
};

// ============================================================
// Evacuation Path Preview Functions
// ============================================================

const openEvacuationPathDialog = async (building, floor) => {
    evacuationFloorInfo.value = {
        building: building.name,
        floor: floor.floor_name,
        imageUrl: floor.floor_plan_url,
        paths: floor.floor_plan_data?.evacuation_paths || [],
        rooms: floor.floor_plan_data?.rooms || [],
        floorRooms: floor.rooms || []
    };
    
    // Select first room by default if available
    if (evacuationFloorInfo.value.floorRooms.length > 0) {
        selectedEvacuationRoom.value = evacuationFloorInfo.value.floorRooms[0].id;
    } else {
        selectedEvacuationRoom.value = null;
    }
    
    evacuationDialog.value = true;
    
    // Wait for dialog to open and draw paths
    await nextTick();
    setTimeout(() => drawEvacuationPaths(), 100);
};

const onEvacuationRoomChange = () => {
    drawEvacuationPaths();
};

const onEvacuationImageLoad = () => {
    drawEvacuationPaths();
};

const drawEvacuationPaths = () => {
    const canvas = evacuationCanvas.value;
    const image = evacuationImage.value;
    
    if (!canvas || !image) return;
    
    // Set canvas size to match image
    canvas.width = image.naturalWidth;
    canvas.height = image.naturalHeight;
    
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Draw all room boxes (faded)
    evacuationFloorInfo.value.rooms.forEach(room => {
        const isSelectedRoom = room.room_id === selectedEvacuationRoom.value;
        const opacity = isSelectedRoom ? 0.5 : 0.1;
        
        // Fill
        ctx.fillStyle = hexToRgba(room.color || '#4CAF50', opacity);
        ctx.fillRect(room.x, room.y, room.width, room.height);
        
        // Border
        ctx.strokeStyle = room.color || '#4CAF50';
        ctx.lineWidth = isSelectedRoom ? 3 : 1;
        ctx.strokeRect(room.x, room.y, room.width, room.height);
        
        // Label
        if (room.room_name) {
            ctx.fillStyle = isSelectedRoom ? '#000' : '#666';
            ctx.font = isSelectedRoom ? 'bold 14px Arial' : '12px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(room.room_name, room.x + room.width / 2, room.y + room.height / 2);
        }
    });
    
    // Draw only evacuation paths for the selected room
    filteredEvacuationPaths.value.forEach(path => {
        if (path.points.length < 2) return;
        
        // Draw path line
        ctx.beginPath();
        ctx.moveTo(path.points[0].x, path.points[0].y);
        for (let i = 1; i < path.points.length; i++) {
            ctx.lineTo(path.points[i].x, path.points[i].y);
        }
        ctx.strokeStyle = path.color || '#FF5722';
        ctx.lineWidth = 4;
        ctx.stroke();
        
        // Draw arrow heads along the path
        for (let i = 1; i < path.points.length; i++) {
            drawArrowHead(ctx, path.points[i - 1], path.points[i], path.color || '#FF5722');
        }
        
        // Draw points
        path.points.forEach((point, i) => {
            ctx.beginPath();
            ctx.arc(point.x, point.y, 8, 0, Math.PI * 2);
            ctx.fillStyle = i === 0 ? '#4CAF50' : (i === path.points.length - 1 ? '#f44336' : path.color || '#FF5722');
            ctx.fill();
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 2;
            ctx.stroke();
        });
        
        // Draw path name at first point
        if (path.name) {
            ctx.fillStyle = '#000';
            ctx.font = 'bold 12px Arial';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'bottom';
            ctx.fillText(path.name, path.points[0].x + 12, path.points[0].y - 5);
        }
    });
};

const drawArrowHead = (ctx, from, to, color) => {
    const headLength = 15;
    const angle = Math.atan2(to.y - from.y, to.x - from.x);
    
    // Position arrow head at 70% of the line
    const midX = from.x + (to.x - from.x) * 0.7;
    const midY = from.y + (to.y - from.y) * 0.7;
    
    ctx.beginPath();
    ctx.moveTo(midX, midY);
    ctx.lineTo(
        midX - headLength * Math.cos(angle - Math.PI / 6),
        midY - headLength * Math.sin(angle - Math.PI / 6)
    );
    ctx.moveTo(midX, midY);
    ctx.lineTo(
        midX - headLength * Math.cos(angle + Math.PI / 6),
        midY - headLength * Math.sin(angle + Math.PI / 6)
    );
    ctx.strokeStyle = color;
    ctx.lineWidth = 4;
    ctx.stroke();
};

const hexToRgba = (hex, alpha) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    if (result) {
        return `rgba(${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}, ${alpha})`;
    }
    return `rgba(76, 175, 80, ${alpha})`;
};

const printEvacuationPlan = () => {
    const canvas = evacuationCanvas.value;
    const image = evacuationImage.value;
    
    if (!canvas || !image || !selectedEvacuationRoom.value) return;
    
    // Get selected room name
    const selectedRoom = evacuationFloorInfo.value.floorRooms.find(
        r => r.id === selectedEvacuationRoom.value
    );
    const roomName = selectedRoom ? selectedRoom.room_name : 'Unknown Room';
    
    // Create a combined canvas with image and annotations
    const combinedCanvas = document.createElement('canvas');
    combinedCanvas.width = image.naturalWidth;
    combinedCanvas.height = image.naturalHeight;
    const combinedCtx = combinedCanvas.getContext('2d');
    
    // Draw background image
    combinedCtx.drawImage(image, 0, 0);
    
    // Draw annotations on top
    combinedCtx.drawImage(canvas, 0, 0);
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Evacuation Plan - ${evacuationFloorInfo.value.building} / ${evacuationFloorInfo.value.floor} / ${roomName}</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
                h1 { margin-bottom: 5px; }
                h2 { margin-top: 0; color: #666; font-weight: normal; }
                .room-name { color: #1976D2; font-weight: bold; margin: 10px 0; }
                img { max-width: 100%; height: auto; border: 1px solid #ccc; }
                .legend { margin-top: 20px; display: flex; justify-content: center; gap: 30px; }
                .legend-item { display: flex; align-items: center; gap: 8px; }
                .legend-dot { width: 16px; height: 16px; border-radius: 50%; }
                .legend-line { width: 30px; height: 4px; background: #FF5722; }
                .path-count { margin-top: 10px; color: #666; }
                @media print { body { padding: 0; } }
            </style>
        </head>
        <body>
            <h1>${evacuationFloorInfo.value.building}</h1>
            <h2>${evacuationFloorInfo.value.floor} - Evacuation Plan</h2>
            <div class="room-name">Room: ${roomName}</div>
            <div class="path-count">${filteredEvacuationPaths.value.length} evacuation path(s)</div>
            <img src="${combinedCanvas.toDataURL('image/png')}" alt="Evacuation Plan" />
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-dot" style="background: #4CAF50;"></div>
                    <span>Start Point</span>
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background: #f44336;"></div>
                    <span>Exit Point</span>
                </div>
                <div class="legend-item">
                    <div class="legend-line"></div>
                    <span>Evacuation Path</span>
                </div>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
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

onMounted(() => {
    // Expand first building by default
    if (buildingsList.value.length > 0) {
        expandedBuildings.value.push(buildingsList.value[0].id);
    }
});
</script>

<style scoped>
.evacuation-canvas-container {
    overflow: auto;
    background-color: #e0e0e0;
    background-image: 
        linear-gradient(45deg, #ccc 25%, transparent 25%),
        linear-gradient(-45deg, #ccc 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #ccc 75%),
        linear-gradient(-45deg, transparent 75%, #ccc 75%);
    background-size: 20px 20px;
    background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
    max-height: 60vh;
    min-height: 300px;
}

.evacuation-canvas-wrapper {
    position: relative;
    display: inline-block;
}

.evacuation-image {
    display: block;
    max-width: none;
}

.evacuation-overlay {
    position: absolute;
    top: 0;
    left: 0;
    pointer-events: none;
}

.legend-line {
    width: 30px;
    height: 4px;
    background: #FF5722;
    border-radius: 2px;
}

.legend-room {
    width: 24px;
    height: 16px;
    background: rgba(76, 175, 80, 0.3);
    border: 2px solid #4CAF50;
    border-radius: 2px;
}

.gap-4 {
    gap: 16px;
}
</style>
