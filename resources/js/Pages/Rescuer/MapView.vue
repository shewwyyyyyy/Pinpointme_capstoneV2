<template>
    <v-app class="bg-user-gradient-light">
        <!-- App Bar -->
        <v-app-bar color="primary" density="comfortable">
            <v-btn icon @click="goBack">
                <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            <v-app-bar-title>Floor Map</v-app-bar-title>
            <v-spacer />
            <v-btn icon @click="toggleFullscreen">
                <v-icon>{{ isFullscreen ? 'mdi-fullscreen-exit' : 'mdi-fullscreen' }}</v-icon>
            </v-btn>
        </v-app-bar>

        <!-- Main Content -->
        <v-main>
            <div v-if="loading" class="d-flex justify-center align-center" style="min-height: 60vh;">
                <v-progress-circular indeterminate color="primary" size="64" />
            </div>

            <div v-else class="map-container">
                <!-- Location Info Bar -->
                <v-sheet class="pa-3 mb-2" color="primary" dark>
                    <div class="d-flex align-center justify-space-between">
                        <div>
                            <div class="text-subtitle-2">{{ rescueRequest?.building?.name || 'Building' }}</div>
                            <div class="text-caption">{{ rescueRequest?.floor?.name || 'Floor' }}</div>
                        </div>
                        <v-chip color="white" text-color="primary" size="small">
                            <v-icon start size="16">mdi-map-marker</v-icon>
                            {{ rescueRequest?.room?.name || 'Room' }}
                        </v-chip>
                    </div>
                </v-sheet>

                <!-- Floor Selector -->
                <div class="px-4 mb-2">
                    <v-chip-group v-model="selectedFloorIndex" mandatory>
                        <v-chip
                            v-for="(floor, index) in floors"
                            :key="floor.id"
                            :value="index"
                            filter
                            variant="elevated"
                            :color="floor.id === rescueRequest?.floor?.id ? 'primary' : 'default'"
                        >
                            {{ floor.name }}
                        </v-chip>
                    </v-chip-group>
                </div>

                <!-- Map Area -->
                <div 
                    ref="mapContainer"
                    class="map-area mx-4"
                    :style="{ height: mapHeight }"
                    @touchstart="handleTouchStart"
                    @touchmove="handleTouchMove"
                    @touchend="handleTouchEnd"
                    @wheel="handleWheel"
                >
                    <div 
                        class="map-content"
                        :style="{
                            transform: `scale(${scale}) translate(${translateX}px, ${translateY}px)`,
                            transformOrigin: 'center center',
                        }"
                    >
                        <!-- Floor Plan Image -->
                        <v-img
                            v-if="selectedFloor?.floor_plan_url"
                            :src="selectedFloor.floor_plan_url"
                            class="floor-plan"
                            contain
                            @load="onImageLoad"
                        >
                            <template v-slot:placeholder>
                                <div class="d-flex justify-center align-center" style="height: 100%;">
                                    <v-progress-circular indeterminate color="primary" />
                                </div>
                            </template>
                        </v-img>

                        <!-- Grid Layout (fallback if no image) -->
                        <div v-else class="grid-layout">
                            <div 
                                v-for="room in currentFloorRooms"
                                :key="room.id"
                                class="room-cell"
                                :class="{
                                    'target-room': room.id === rescueRequest?.room?.id,
                                    'occupied': room.has_occupants,
                                }"
                                :style="getRoomStyle(room)"
                                @click="selectRoom(room)"
                            >
                                <div class="room-label">{{ room.name }}</div>
                                <v-icon 
                                    v-if="room.id === rescueRequest?.room?.id"
                                    class="target-marker"
                                    color="error"
                                    size="32"
                                >
                                    mdi-map-marker
                                </v-icon>
                            </div>
                        </div>

                        <!-- Target Room Marker (for image maps) -->
                        <div 
                            v-if="selectedFloor?.floor_plan_url && targetRoomPosition"
                            class="target-marker-overlay"
                            :style="targetRoomPosition"
                        >
                            <div class="pulse-ring"></div>
                            <v-icon color="error" size="40">mdi-map-marker</v-icon>
                            <div class="marker-label">{{ rescueRequest?.room?.name }}</div>
                        </div>
                    </div>
                </div>

                <!-- Zoom Controls -->
                <div class="zoom-controls">
                    <v-btn icon size="small" variant="elevated" @click="zoomIn">
                        <v-icon>mdi-plus</v-icon>
                    </v-btn>
                    <v-btn icon size="small" variant="elevated" @click="zoomOut">
                        <v-icon>mdi-minus</v-icon>
                    </v-btn>
                    <v-btn icon size="small" variant="elevated" @click="resetZoom">
                        <v-icon>mdi-crosshairs-gps</v-icon>
                    </v-btn>
                </div>

                <!-- Room Info Panel -->
                <v-expand-transition>
                    <v-card v-if="selectedRoom" class="room-info-card ma-4" elevation="4">
                        <v-card-item>
                            <template v-slot:prepend>
                                <v-avatar :color="selectedRoom.id === rescueRequest?.room?.id ? 'error' : 'primary'" size="40">
                                    <v-icon color="white">
                                        {{ selectedRoom.id === rescueRequest?.room?.id ? 'mdi-alert' : 'mdi-door' }}
                                    </v-icon>
                                </v-avatar>
                            </template>
                            <v-card-title>{{ selectedRoom.name }}</v-card-title>
                            <v-card-subtitle>{{ selectedRoom.room_type || 'Room' }}</v-card-subtitle>
                            <template v-slot:append>
                                <v-btn icon size="small" variant="text" @click="selectedRoom = null">
                                    <v-icon>mdi-close</v-icon>
                                </v-btn>
                            </template>
                        </v-card-item>
                        <v-card-text v-if="selectedRoom.description">
                            {{ selectedRoom.description }}
                        </v-card-text>
                        <v-card-actions v-if="selectedRoom.id === rescueRequest?.room?.id">
                            <v-btn color="primary" variant="flat" block @click="navigateToRoom">
                                <v-icon start>mdi-navigation</v-icon>
                                Navigate Here
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-expand-transition>

                <!-- Legend -->
                <div class="legend pa-3 mx-4 mb-4">
                    <div class="text-caption text-grey mb-2">LEGEND</div>
                    <div class="d-flex flex-wrap ga-3">
                        <div class="d-flex align-center">
                            <div class="legend-dot error"></div>
                            <span class="text-caption ml-2">Target Location</span>
                        </div>
                        <div class="d-flex align-center">
                            <div class="legend-dot primary"></div>
                            <span class="text-caption ml-2">Current Floor</span>
                        </div>
                        <div class="d-flex align-center">
                            <div class="legend-dot success"></div>
                            <span class="text-caption ml-2">Exit</span>
                        </div>
                    </div>
                </div>

                <!-- Direction Instructions -->
                <v-card v-if="directions.length" class="mx-4 mb-4" variant="tonal" color="primary">
                    <v-card-title class="text-subtitle-1">
                        <v-icon start>mdi-directions</v-icon>
                        Directions
                    </v-card-title>
                    <v-card-text>
                        <v-list density="compact" bg-color="transparent">
                            <v-list-item
                                v-for="(step, index) in directions"
                                :key="index"
                                :prepend-icon="step.icon"
                            >
                                <v-list-item-title class="text-body-2">{{ step.text }}</v-list-item-title>
                            </v-list-item>
                        </v-list>
                    </v-card-text>
                </v-card>
            </div>
        </v-main>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000">
            {{ snackbar.message }}
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useApi } from '@/Composables/useApi';

const props = defineProps({
    rescueId: {
        type: [String, Number],
        default: null,
    },
});

const { get } = useApi();

// State
const loading = ref(true);
const rescueRequest = ref(null);
const floors = ref([]);
const rooms = ref([]);
const selectedFloorIndex = ref(0);
const selectedRoom = ref(null);
const isFullscreen = ref(false);
const mapContainer = ref(null);
const imageLoaded = ref(false);

// Zoom and pan
const scale = ref(1);
const translateX = ref(0);
const translateY = ref(0);
const lastTouchDistance = ref(0);
const lastTouchCenter = ref({ x: 0, y: 0 });
const isDragging = ref(false);

const snackbar = ref({
    show: false,
    message: '',
    color: 'success',
});

// Computed
const selectedFloor = computed(() => floors.value[selectedFloorIndex.value]);

const currentFloorRooms = computed(() => {
    if (!selectedFloor.value) return [];
    return rooms.value.filter(room => room.floor_id === selectedFloor.value.id);
});

const mapHeight = computed(() => isFullscreen.value ? 'calc(100vh - 200px)' : '400px');

const targetRoomPosition = computed(() => {
    if (!rescueRequest.value?.room?.map_position) return null;
    const pos = rescueRequest.value.room.map_position;
    return {
        left: `${pos.x}%`,
        top: `${pos.y}%`,
    };
});

const directions = computed(() => {
    if (!rescueRequest.value) return [];
    
    // Generate simple directions based on room info
    const steps = [];
    
    if (rescueRequest.value.building) {
        steps.push({
            icon: 'mdi-office-building',
            text: `Go to ${rescueRequest.value.building.name}`,
        });
    }
    
    if (rescueRequest.value.floor) {
        const floorNum = rescueRequest.value.floor.floor_number || 1;
        if (floorNum > 1) {
            steps.push({
                icon: 'mdi-stairs',
                text: `Take stairs/elevator to ${rescueRequest.value.floor.name}`,
            });
        }
    }
    
    if (rescueRequest.value.room) {
        steps.push({
            icon: 'mdi-door',
            text: `Find ${rescueRequest.value.room.name}`,
        });
    }
    
    return steps;
});

// Methods
const fetchData = async () => {
    try {
        const id = props.rescueId || localStorage.getItem('lastRescueRequestId');
        if (!id) {
            showSnackbar('No rescue ID found', 'error');
            setTimeout(() => router.visit('/rescuer/dashboard'), 2000);
            return;
        }

        // Fetch rescue request details
        const rescueResponse = await get(`/api/rescue-requests/${id}`);
        if (rescueResponse.data) {
            rescueRequest.value = rescueResponse.data;
            
            // Fetch building floors
            if (rescueRequest.value.building_id) {
                const floorsResponse = await get(`/api/buildings/${rescueRequest.value.building_id}/floors`);
                if (floorsResponse.data) {
                    floors.value = floorsResponse.data;
                    
                    // Set initial floor to target floor
                    const targetFloorIndex = floors.value.findIndex(
                        f => f.id === rescueRequest.value.floor_id
                    );
                    if (targetFloorIndex !== -1) {
                        selectedFloorIndex.value = targetFloorIndex;
                    }
                }
                
                // Fetch all rooms for the building
                const roomsResponse = await get(`/api/buildings/${rescueRequest.value.building_id}/rooms`);
                if (roomsResponse.data) {
                    rooms.value = roomsResponse.data;
                }
            }
        }
    } catch (error) {
        console.error('Error fetching map data:', error);
        showSnackbar('Failed to load map data', 'error');
    } finally {
        loading.value = false;
    }
};

const goBack = () => {
    const rescueId = props.rescueId || localStorage.getItem('lastRescueRequestId');
    if (rescueId) {
        router.visit(`/rescuer/active/${rescueId}`);
    } else {
        router.visit('/rescuer/dashboard');
    }
};

const toggleFullscreen = () => {
    isFullscreen.value = !isFullscreen.value;
};

const zoomIn = () => {
    scale.value = Math.min(scale.value + 0.25, 3);
};

const zoomOut = () => {
    scale.value = Math.max(scale.value - 0.25, 0.5);
};

const resetZoom = () => {
    scale.value = 1;
    translateX.value = 0;
    translateY.value = 0;
};

const handleWheel = (e) => {
    e.preventDefault();
    const delta = e.deltaY > 0 ? -0.1 : 0.1;
    scale.value = Math.max(0.5, Math.min(3, scale.value + delta));
};

const handleTouchStart = (e) => {
    if (e.touches.length === 2) {
        // Pinch zoom start
        lastTouchDistance.value = getTouchDistance(e.touches);
        lastTouchCenter.value = getTouchCenter(e.touches);
    } else if (e.touches.length === 1) {
        // Pan start
        isDragging.value = true;
        lastTouchCenter.value = { x: e.touches[0].clientX, y: e.touches[0].clientY };
    }
};

const handleTouchMove = (e) => {
    e.preventDefault();
    
    if (e.touches.length === 2) {
        // Pinch zoom
        const currentDistance = getTouchDistance(e.touches);
        const scaleChange = currentDistance / lastTouchDistance.value;
        scale.value = Math.max(0.5, Math.min(3, scale.value * scaleChange));
        lastTouchDistance.value = currentDistance;
    } else if (e.touches.length === 1 && isDragging.value) {
        // Pan
        const currentX = e.touches[0].clientX;
        const currentY = e.touches[0].clientY;
        translateX.value += (currentX - lastTouchCenter.value.x) / scale.value;
        translateY.value += (currentY - lastTouchCenter.value.y) / scale.value;
        lastTouchCenter.value = { x: currentX, y: currentY };
    }
};

const handleTouchEnd = () => {
    isDragging.value = false;
};

const getTouchDistance = (touches) => {
    const dx = touches[0].clientX - touches[1].clientX;
    const dy = touches[0].clientY - touches[1].clientY;
    return Math.sqrt(dx * dx + dy * dy);
};

const getTouchCenter = (touches) => {
    return {
        x: (touches[0].clientX + touches[1].clientX) / 2,
        y: (touches[0].clientY + touches[1].clientY) / 2,
    };
};

const selectRoom = (room) => {
    selectedRoom.value = room;
};

const navigateToRoom = () => {
    // Could integrate with external navigation or show detailed directions
    showSnackbar('Navigation started', 'info');
};

const getRoomStyle = (room) => {
    // Default grid positioning if no specific coordinates
    const baseStyle = {
        backgroundColor: room.id === rescueRequest.value?.room?.id 
            ? 'rgba(244, 67, 54, 0.2)' 
            : 'rgba(33, 150, 243, 0.1)',
        border: room.id === rescueRequest.value?.room?.id 
            ? '2px solid #f44336' 
            : '1px solid #e0e0e0',
    };
    
    if (room.map_position) {
        return {
            ...baseStyle,
            position: 'absolute',
            left: `${room.map_position.x}%`,
            top: `${room.map_position.y}%`,
            width: `${room.map_position.width || 10}%`,
            height: `${room.map_position.height || 10}%`,
        };
    }
    
    return baseStyle;
};

const onImageLoad = () => {
    imageLoaded.value = true;
};

const showSnackbar = (message, color = 'success') => {
    snackbar.value = { show: true, message, color };
};

// Watch for floor changes
watch(selectedFloorIndex, () => {
    selectedRoom.value = null;
    resetZoom();
});

// Lifecycle
onMounted(() => {
    fetchData();
});
</script>

<style scoped>
.map-container {
    position: relative;
}

.map-area {
    position: relative;
    overflow: hidden;
    background: #f5f5f5;
    border-radius: 8px;
    touch-action: none;
}

.map-content {
    width: 100%;
    height: 100%;
    position: relative;
    transition: transform 0.1s ease-out;
}

.floor-plan {
    width: 100%;
    height: 100%;
}

.grid-layout {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    padding: 16px;
    position: relative;
    min-height: 300px;
}

.room-cell {
    position: relative;
    padding: 12px;
    border-radius: 8px;
    min-height: 80px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.room-cell:hover {
    transform: scale(1.02);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.room-cell.target-room {
    animation: pulse 2s infinite;
}

.room-label {
    font-size: 12px;
    font-weight: 500;
    text-align: center;
    word-break: break-word;
}

.target-marker {
    position: absolute;
    top: -16px;
    animation: bounce 1s infinite;
}

.target-marker-overlay {
    position: absolute;
    transform: translate(-50%, -100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    z-index: 10;
}

.marker-label {
    background: rgba(244, 67, 54, 0.9);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    margin-top: 4px;
    white-space: nowrap;
}

.pulse-ring {
    position: absolute;
    width: 60px;
    height: 60px;
    border: 3px solid #f44336;
    border-radius: 50%;
    animation: pulse-ring 1.5s infinite;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.zoom-controls {
    position: absolute;
    right: 24px;
    top: 180px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 10;
}

.room-info-card {
    position: relative;
    z-index: 5;
}

.legend {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.legend-dot.error {
    background-color: #f44336;
}

.legend-dot.primary {
    background-color: #1976d2;
}

.legend-dot.success {
    background-color: #4caf50;
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(244, 67, 54, 0.4);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(244, 67, 54, 0);
    }
}

@keyframes pulse-ring {
    0% {
        transform: translate(-50%, -50%) scale(0.5);
        opacity: 1;
    }
    100% {
        transform: translate(-50%, -50%) scale(1.5);
        opacity: 0;
    }
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}
</style>
