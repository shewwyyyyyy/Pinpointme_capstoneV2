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
                                                <v-spacer />
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
