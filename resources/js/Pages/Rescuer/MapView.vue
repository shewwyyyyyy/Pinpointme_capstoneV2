<template>
    <v-app class="bg-user-gradient-light">
        <!-- Modern Header -->
        <div class="map-header">
            <div class="header-content">
                <v-btn icon variant="text" class="back-btn" @click="goBack">
                    <v-icon color="white">mdi-arrow-left</v-icon>
                </v-btn>
                <div class="header-info">
                    <h1>Floor Map</h1>
                    <p v-if="rescueRequest?.building?.name">{{ rescueRequest.building.name }}</p>
                </div>
                <v-btn icon variant="text" @click="resetZoom" class="header-action-btn">
                    <v-icon color="white">mdi-crosshairs-gps</v-icon>
                </v-btn>
            </div>
        </div>

        <!-- Main Content -->
        <v-main class="map-main">
            <!-- Loading State -->
            <div v-if="loading" class="loading-container">
                <div class="loading-content">
                    <v-progress-circular indeterminate color="primary" size="64" width="4" />
                    <p class="mt-4 text-grey-darken-1">Loading floor map...</p>
                </div>
            </div>

            <div v-else class="map-container">
                <!-- Target Location Banner -->
                <div class="target-banner">
                    <div class="target-banner-content">
                        <div class="target-icon">
                            <v-icon color="white" size="24">mdi-map-marker-alert</v-icon>
                        </div>
                        <div class="target-info">
                            <span class="target-label">Target Location</span>
                            <span class="target-name">{{ rescueRequest?.room?.room_name || rescueRequest?.room?.name || 'Unknown Room' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Map Area -->
                <div 
                    ref="mapContainer"
                    class="map-area"
                    @touchstart="handleTouchStart"
                    @touchmove="handleTouchMove"
                    @touchend="handleTouchEnd"
                    @wheel="handleWheel"
                    @mousedown="handleMouseDown"
                    @mousemove="handleMouseMove"
                    @mouseup="handleMouseUp"
                    @mouseleave="handleMouseUp"
                >
                    <div 
                        class="map-content"
                        :style="{
                            transform: `scale(${scale}) translate(${translateX}px, ${translateY}px)`,
                            transformOrigin: 'center center',
                        }"
                    >
                        <!-- Floor Plan Image with Annotations -->
                        <div v-if="selectedFloor?.floor_plan_url" class="floor-plan-container">
                            <img 
                                ref="floorPlanImage"
                                :src="selectedFloor.floor_plan_url" 
                                class="floor-plan-image"
                                @load="onImageLoad"
                                @error="onImageError"
                                draggable="false"
                            />
                            
                            <!-- Show loading while image loads -->
                            <div v-if="!imageLoaded" class="image-loading">
                                <v-progress-circular indeterminate color="primary" size="40" />
                                <p>Loading floor plan...</p>
                            </div>
                            
                            <!-- Only show target location -->
                            <template v-if="imageLoaded && targetRoomAnnotation">
                                <div
                                    class="room-annotation target-room"
                                    :style="getRoomAnnotationStyle(targetRoomAnnotation)"
                                >
                                    <div class="room-annotation-label">
                                        {{ targetRoomAnnotation.room_name }}
                                    </div>
                                    <div class="target-indicator">
                                        <v-icon color="white" size="16">mdi-alert</v-icon>
                                    </div>
                                    <div class="pulse-ring"></div>
                                </div>
                            </template>
                            
                            <!-- Evacuation Path from target location only -->
                            <svg v-if="imageLoaded && targetEvacuationPath" class="evacuation-svg" :viewBox="svgViewBox">
                                <path
                                    :d="getPathD(targetEvacuationPath)"
                                    :stroke="targetEvacuationPath.color || '#4CAF50'"
                                    stroke-width="5"
                                    fill="none"
                                    stroke-dasharray="15,8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="evacuation-path"
                                />
                                <!-- Exit marker at the end of path -->
                                <circle 
                                    v-if="targetEvacuationPath.points && targetEvacuationPath.points.length > 0"
                                    :cx="targetEvacuationPath.points[targetEvacuationPath.points.length - 1].x"
                                    :cy="targetEvacuationPath.points[targetEvacuationPath.points.length - 1].y"
                                    r="12"
                                    fill="#4CAF50"
                                    stroke="white"
                                    stroke-width="3"
                                />
                            </svg>
                        </div>

                        <!-- Grid Layout (fallback if no image) -->
                        <div v-else class="grid-layout">
                            <div class="grid-header">
                                <v-icon color="grey">mdi-grid</v-icon>
                                <span>{{ selectedFloor?.floor_name || selectedFloor?.name || 'Floor' }} - Room Layout</span>
                            </div>
                            <div class="grid-rooms">
                                <div 
                                    v-for="room in currentFloorRooms"
                                    :key="room.id"
                                    class="room-cell"
                                    :class="{
                                        'target-room': room.id === rescueRequest?.room_id,
                                        'selected': selectedRoom?.id === room.id,
                                    }"
                                    @click="selectRoom(room)"
                                >
                                    <div class="room-icon">
                                        <v-icon v-if="room.id === rescueRequest?.room_id" color="error" size="24">mdi-alert-circle</v-icon>
                                        <v-icon v-else color="grey" size="20">mdi-door</v-icon>
                                    </div>
                                    <div class="room-label">{{ room.room_name || room.name }}</div>
                                    <div v-if="room.id === rescueRequest?.room_id" class="target-badge">
                                        <v-icon size="10" color="white">mdi-alert</v-icon>
                                        Target
                                    </div>
                                </div>
                            </div>
                            <div v-if="currentFloorRooms.length === 0" class="no-rooms">
                                <v-icon size="48" color="grey-lighten-1">mdi-door-closed</v-icon>
                                <p>No rooms found on this floor</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Zoom Controls -->
                    <div class="zoom-controls">
                        <v-btn icon size="small" variant="elevated" color="white" @click="zoomIn" class="zoom-btn">
                            <v-icon>mdi-plus</v-icon>
                        </v-btn>
                        <div class="zoom-level">{{ Math.round(scale * 100) }}%</div>
                        <v-btn icon size="small" variant="elevated" color="white" @click="zoomOut" class="zoom-btn">
                            <v-icon>mdi-minus</v-icon>
                        </v-btn>
                    </div>
                    
                    <!-- Map Instructions -->
                    <div v-if="!hasInteracted" class="map-instructions">
                        <v-icon size="20">mdi-gesture-pinch</v-icon>
                        <span>Pinch to zoom • Drag to pan</span>
                    </div>
                </div>

                <!-- Selected Room Info Panel -->
                <v-slide-y-reverse-transition>
                    <div v-if="selectedRoom" class="room-info-panel">
                        <div class="room-info-header">
                            <div class="room-info-icon" :class="{ 'target': selectedRoom.id === rescueRequest?.room_id }">
                                <v-icon color="white" size="20">
                                    {{ selectedRoom.id === rescueRequest?.room_id ? 'mdi-alert' : 'mdi-door' }}
                                </v-icon>
                            </div>
                            <div class="room-info-details">
                                <h3>{{ selectedRoom.room_name || selectedRoom.name }}</h3>
                                <p>{{ selectedRoom.room_type || 'Room' }}</p>
                            </div>
                            <v-btn icon size="small" variant="text" @click="selectedRoom = null">
                                <v-icon>mdi-close</v-icon>
                            </v-btn>
                        </div>
                        <div v-if="selectedRoom.description" class="room-info-description">
                            {{ selectedRoom.description }}
                        </div>
                        <div v-if="selectedRoom.id === rescueRequest?.room_id" class="room-info-actions">
                            <v-btn color="error" variant="flat" block size="large" rounded="lg" @click="navigateToRoom">
                                <v-icon start>mdi-navigation</v-icon>
                                Navigate to Target
                            </v-btn>
                        </div>
                    </div>
                </v-slide-y-reverse-transition>

                <!-- Legend Card -->
                <div class="legend-card">
                    <div class="legend-header">
                        <v-icon size="16" color="grey-darken-1">mdi-map-legend</v-icon>
                        <span>Legend</span>
                    </div>
                    <div class="legend-items">
                        <div class="legend-item">
                            <div class="legend-dot error"></div>
                            <span>Target Location</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-line success"></div>
                            <span>Evacuation Path</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-dot exit"></div>
                            <span>Exit Point</span>
                        </div>
                    </div>
                </div>

                <!-- Directions Card -->
                <div v-if="directions.length" class="directions-card">
                    <div class="directions-header">
                        <v-icon size="20" color="primary">mdi-directions</v-icon>
                        <span>Directions</span>
                    </div>
                    <div class="directions-steps">
                        <div v-for="(step, index) in directions" :key="index" class="direction-step">
                            <div class="step-number">{{ index + 1 }}</div>
                            <v-icon :icon="step.icon" size="18" color="grey-darken-1" />
                            <span>{{ step.text }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </v-main>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000" location="top">
            {{ snackbar.message }}
        </v-snackbar>
        
        <!-- Bottom Navigation (Mobile/Tablet only) -->
        <RescuerBottomNav :notification-count="0" :message-count="unreadMessageCount" />
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { getUnreadMessageCount } from '@/Composables/useApi';
import RescuerBottomNav from '@/Components/Pages/Rescuer/Menu/RescuerBottomNav.vue';

const props = defineProps({
    rescueId: {
        type: [String, Number],
        default: null,
    },
});

// State
const loading = ref(true);
const rescueRequest = ref(null);
const floors = ref([]);
const rooms = ref([]);
const selectedFloorIndex = ref(0);
const selectedRoom = ref(null);
const mapContainer = ref(null);
const floorPlanImage = ref(null);
const imageLoaded = ref(false);
const imageError = ref(false);
const unreadMessageCount = ref(0);
const hasInteracted = ref(false);
const imageNaturalWidth = ref(0);
const imageNaturalHeight = ref(0);

// Zoom and pan
const scale = ref(1);
const translateX = ref(0);
const translateY = ref(0);
const lastTouchDistance = ref(0);
const lastTouchCenter = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const isMouseDragging = ref(false);
const lastMousePos = ref({ x: 0, y: 0 });

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

// Floor plan data computations
const floorPlanRooms = computed(() => {
    if (!selectedFloor.value?.floor_plan_data) return [];
    return selectedFloor.value.floor_plan_data.rooms || [];
});

// Only get the target room annotation
const targetRoomAnnotation = computed(() => {
    if (!floorPlanRooms.value.length) return null;
    const targetRoomName = rescueRequest.value?.room?.room_name || rescueRequest.value?.room?.name;
    const targetRoomId = rescueRequest.value?.room_id;
    
    return floorPlanRooms.value.find(room => 
        room.room_id === targetRoomId || room.room_name === targetRoomName
    );
});

const evacuationPaths = computed(() => {
    if (!selectedFloor.value?.floor_plan_data) return [];
    return selectedFloor.value.floor_plan_data.evacuation_paths || [];
});

// Only get evacuation path from target location
const targetEvacuationPath = computed(() => {
    if (!evacuationPaths.value.length || !targetRoomAnnotation.value) return null;
    
    // Find path that starts from or near target's room
    const targetRoom = targetRoomAnnotation.value;
    const targetCenterX = targetRoom.x + (targetRoom.width / 2);
    const targetCenterY = targetRoom.y + (targetRoom.height / 2);
    
    // Find the path closest to target's room or the first path if only one exists
    let closestPath = evacuationPaths.value[0];
    let minDistance = Infinity;
    
    for (const path of evacuationPaths.value) {
        if (path.points && path.points.length > 0) {
            const startPoint = path.points[0];
            const distance = Math.sqrt(
                Math.pow(startPoint.x - targetCenterX, 2) + 
                Math.pow(startPoint.y - targetCenterY, 2)
            );
            if (distance < minDistance) {
                minDistance = distance;
                closestPath = path;
            }
        }
    }
    
    return closestPath;
});

const svgViewBox = computed(() => {
    if (!imageNaturalWidth.value || !imageNaturalHeight.value) return '0 0 100 100';
    return `0 0 ${imageNaturalWidth.value} ${imageNaturalHeight.value}`;
});

const directions = computed(() => {
    if (!rescueRequest.value) return [];
    
    const steps = [];
    
    if (rescueRequest.value.building) {
        steps.push({
            icon: 'mdi-office-building',
            text: `Go to ${rescueRequest.value.building.name}`,
        });
    }
    
    if (rescueRequest.value.floor) {
        const floorName = rescueRequest.value.floor.floor_name || rescueRequest.value.floor.name;
        steps.push({
            icon: 'mdi-stairs',
            text: `Navigate to ${floorName}`,
        });
    }
    
    if (rescueRequest.value.room) {
        const roomName = rescueRequest.value.room.room_name || rescueRequest.value.room.name;
        steps.push({
            icon: 'mdi-door',
            text: `Find ${roomName}`,
        });
    }
    
    return steps;
});

// Helper functions for room annotations
const isTargetRoom = (room) => {
    return room.room_id === rescueRequest.value?.room_id || 
           room.room_name === (rescueRequest.value?.room?.room_name || rescueRequest.value?.room?.name);
};

const getRoomAnnotationStyle = (room) => {
    if (!imageNaturalWidth.value || !imageNaturalHeight.value) return {};
    
    return {
        left: `${(room.x / imageNaturalWidth.value) * 100}%`,
        top: `${(room.y / imageNaturalHeight.value) * 100}%`,
        width: `${(room.width / imageNaturalWidth.value) * 100}%`,
        height: `${(room.height / imageNaturalHeight.value) * 100}%`,
        backgroundColor: 'rgba(244, 67, 54, 0.35)',
        borderColor: '#f44336',
    };
};

const getPathD = (path) => {
    if (!path.points || path.points.length < 2) return '';
    return path.points.map((point, i) => 
        `${i === 0 ? 'M' : 'L'} ${point.x} ${point.y}`
    ).join(' ');
};

const selectRoomAnnotation = (room) => {
    hasInteracted.value = true;
    const fullRoom = rooms.value.find(r => r.id === room.room_id) || {
        id: room.room_id,
        room_name: room.room_name,
        name: room.room_name,
    };
    selectedRoom.value = fullRoom;
};

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
        const rescueResponse = await fetch(`/api/rescue-requests/${id}`);
        const rescueData = await rescueResponse.json();
        
        if (rescueData.data) {
            rescueRequest.value = rescueData.data;
            
            // Fetch building floors with floor_plan_data
            if (rescueRequest.value.building_id) {
                try {
                    const buildingResponse = await fetch('/api/buildings');
                    const buildings = await buildingResponse.json();
                    const building = buildings?.find(b => b.id === rescueRequest.value.building_id);
                    
                    if (building?.floors) {
                        const floorsData = [];
                        for (const floor of building.floors) {
                            // Fetch full floor data including floor_plan_data
                            try {
                                const floorResponse = await fetch(`/floor-plans/${floor.id}`);
                                const floorData = await floorResponse.json();
                                if (floorData.success && floorData.data) {
                                    floorsData.push({
                                        ...floor,
                                        floor_plan_url: floorData.data.floor_plan_url,
                                        floor_plan_data: floorData.data.floor_plan_data,
                                    });
                                } else {
                                    floorsData.push(floor);
                                }
                            } catch {
                                floorsData.push(floor);
                            }
                        }
                        floors.value = floorsData;
                        
                        // Also get rooms from the building
                        rooms.value = building.floors.flatMap(f => f.rooms || []);
                    }
                    
                    // Set initial floor to target floor
                    const targetFloorIndex = floors.value.findIndex(
                        f => f.id === rescueRequest.value.floor_id
                    );
                    if (targetFloorIndex !== -1) {
                        selectedFloorIndex.value = targetFloorIndex;
                    }
                } catch (error) {
                    console.error('Error fetching floor data:', error);
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

const zoomIn = () => {
    hasInteracted.value = true;
    scale.value = Math.min(scale.value + 0.25, 3);
};

const zoomOut = () => {
    hasInteracted.value = true;
    scale.value = Math.max(scale.value - 0.25, 0.5);
};

const resetZoom = () => {
    scale.value = 1;
    translateX.value = 0;
    translateY.value = 0;
};

const handleWheel = (e) => {
    e.preventDefault();
    hasInteracted.value = true;
    const delta = e.deltaY > 0 ? -0.1 : 0.1;
    scale.value = Math.max(0.5, Math.min(3, scale.value + delta));
};

const handleTouchStart = (e) => {
    hasInteracted.value = true;
    if (e.touches.length === 2) {
        lastTouchDistance.value = getTouchDistance(e.touches);
        lastTouchCenter.value = getTouchCenter(e.touches);
    } else if (e.touches.length === 1) {
        isDragging.value = true;
        lastTouchCenter.value = { x: e.touches[0].clientX, y: e.touches[0].clientY };
    }
};

const handleTouchMove = (e) => {
    e.preventDefault();
    
    if (e.touches.length === 2) {
        const currentDistance = getTouchDistance(e.touches);
        const scaleChange = currentDistance / lastTouchDistance.value;
        scale.value = Math.max(0.5, Math.min(3, scale.value * scaleChange));
        lastTouchDistance.value = currentDistance;
    } else if (e.touches.length === 1 && isDragging.value) {
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

// Mouse handlers for desktop
const handleMouseDown = (e) => {
    if (e.button === 0) {
        hasInteracted.value = true;
        isMouseDragging.value = true;
        lastMousePos.value = { x: e.clientX, y: e.clientY };
    }
};

const handleMouseMove = (e) => {
    if (isMouseDragging.value) {
        translateX.value += (e.clientX - lastMousePos.value.x) / scale.value;
        translateY.value += (e.clientY - lastMousePos.value.y) / scale.value;
        lastMousePos.value = { x: e.clientX, y: e.clientY };
    }
};

const handleMouseUp = () => {
    isMouseDragging.value = false;
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
    hasInteracted.value = true;
    selectedRoom.value = room;
};

const navigateToRoom = () => {
    showSnackbar('Navigation started - Follow the directions above', 'info');
};

const onImageLoad = () => {
    imageLoaded.value = true;
    imageError.value = false;
    if (floorPlanImage.value) {
        imageNaturalWidth.value = floorPlanImage.value.naturalWidth;
        imageNaturalHeight.value = floorPlanImage.value.naturalHeight;
    }
};

const onImageError = () => {
    imageLoaded.value = false;
    imageError.value = true;
    console.error('Failed to load floor plan image:', selectedFloor.value?.floor_plan_url);
};

const showSnackbar = (message, color = 'success') => {
    snackbar.value = { show: true, message, color };
};

// Watch for floor changes
watch(selectedFloorIndex, () => {
    selectedRoom.value = null;
    imageLoaded.value = false;
    imageError.value = false;
    resetZoom();
});

// Fetch unread message count
const fetchUnreadMessageCount = async () => {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    const userId = userData?.id;
    if (!userId) return;
    try {
        unreadMessageCount.value = await getUnreadMessageCount(userId);
    } catch (error) {
        console.error('Failed to fetch unread message count:', error);
    }
};

// Lifecycle
onMounted(async () => {
    await fetchData();
    await fetchUnreadMessageCount();
});
</script>

<style scoped>
/* App Layout */
.map-app {
    background: #f5f7fa;
}

/* Header Styling */
.map-header {
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 100%);
    padding: 12px 16px;
    padding-top: calc(env(safe-area-inset-top) + 12px);
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 800px;
    margin: 0 auto;
}

.back-btn {
    background: rgba(255, 255, 255, 0.1) !important;
}

.header-info {
    flex: 1;
    text-align: center;
    margin: 0 8px;
}

.header-info h1 {
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
}

.header-info p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.7rem;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 4px;
}

.header-action-btn {
    background: rgba(255, 255, 255, 0.1) !important;
}

/* Main Content */
.map-main {
    min-height: 100vh;
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
}

.loading-content {
    text-align: center;
}

/* Map Container */
.map-container {
    padding: 0;
}

/* Target Banner */
.target-banner {
    background: linear-gradient(135deg, #f44336 0%, #e91e63 100%);
    margin: 0;
    padding: 10px 12px;
}

.target-banner-content {
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 800px;
    margin: 0 auto;
}

.target-icon {
    width: 36px;
    height: 36px;
    min-width: 36px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse-target 2s infinite;
}

@keyframes pulse-target {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.target-info {
    flex: 1;
    min-width: 0;
}

.target-label {
    display: block;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.target-name {
    display: block;
    color: white;
    font-size: 0.9rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Map Area */
.map-area {
    position: relative;
    margin: 12px;
    border-radius: 12px;
    overflow: hidden;
    background: #e8ecef;
    min-height: 250px;
    max-height: 50vh;
    touch-action: none;
    cursor: grab;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.map-area:active {
    cursor: grabbing;
}

.map-content {
    width: 100%;
    min-height: 250px;
    position: relative;
    transition: transform 0.1s ease-out;
}

/* Floor Plan Container */
.floor-plan-container {
    position: relative;
    width: 100%;
    min-height: 250px;
}

.floor-plan-image {
    width: 100%;
    height: auto;
    display: block;
    user-select: none;
}

.image-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    color: #666;
    font-size: 0.85rem;
}

/* Room Annotations */
.room-annotation {
    position: absolute;
    border: 3px solid #f44336;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: visible;
    box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
}

.room-annotation.target-room {
    z-index: 5;
    animation: glow-target 2s ease-in-out infinite;
}

@keyframes glow-target {
    0%, 100% { box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4); }
    50% { box-shadow: 0 4px 20px rgba(244, 67, 54, 0.7); }
}

.room-annotation-label {
    font-size: 10px;
    font-weight: 700;
    color: #b71c1c;
    background: rgba(255, 255, 255, 0.95);
    padding: 4px 8px;
    border-radius: 4px;
    text-align: center;
    word-break: break-word;
    line-height: 1.2;
    max-width: 95%;
    max-height: 90%;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.target-indicator {
    position: absolute;
    top: -10px;
    right: -10px;
    width: 24px;
    height: 24px;
    background: #f44336;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    z-index: 10;
}

.pulse-ring {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100%;
    height: 100%;
    border: 3px solid #f44336;
    border-radius: 6px;
    animation: pulse 2s ease-out infinite;
    pointer-events: none;
}

@keyframes pulse {
    0% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 0.8;
    }
    100% {
        transform: translate(-50%, -50%) scale(1.3);
        opacity: 0;
    }
}

/* Evacuation SVG */
.evacuation-svg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.evacuation-path {
    opacity: 0.85;
}

/* Grid Layout (fallback) */
.grid-layout {
    padding: 16px;
    min-height: 350px;
}

.grid-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    color: #666;
    font-size: 0.9rem;
}

.grid-rooms {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 12px;
}

.room-cell {
    position: relative;
    padding: 16px 12px;
    background: white;
    border-radius: 12px;
    border: 2px solid #e0e0e0;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.room-cell:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.room-cell.target-room {
    border-color: #f44336;
    background: rgba(244, 67, 54, 0.05);
}

.room-cell.selected {
    border-color: #1976d2;
    background: rgba(25, 118, 210, 0.05);
}

.room-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
}

.room-cell.target-room .room-icon {
    background: rgba(244, 67, 54, 0.1);
}

.room-label {
    font-size: 0.75rem;
    font-weight: 500;
    text-align: center;
    color: #333;
    word-break: break-word;
}

.target-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #f44336;
    color: white;
    font-size: 0.6rem;
    padding: 2px 6px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 2px;
}

.no-rooms {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    color: #999;
}

.no-rooms p {
    margin-top: 12px;
}

/* Zoom Controls */
.zoom-controls {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    z-index: 10;
}

.zoom-btn {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.zoom-level {
    background: rgba(0, 0, 0, 0.6);
    color: white;
    font-size: 0.7rem;
    padding: 4px 8px;
    border-radius: 4px;
}

/* Map Instructions */
.map-instructions {
    position: absolute;
    bottom: 12px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 8px;
    animation: fade-out 5s forwards;
}

@keyframes fade-out {
    0%, 80% { opacity: 1; }
    100% { opacity: 0; pointer-events: none; }
}

/* Room Info Panel */
.room-info-panel {
    position: fixed;
    bottom: 80px;
    left: 16px;
    right: 16px;
    background: white;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
    z-index: 50;
    max-width: 500px;
    margin: 0 auto;
}

.room-info-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.room-info-icon {
    width: 40px;
    height: 40px;
    background: #1976d2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.room-info-icon.target {
    background: #f44336;
}

.room-info-details {
    flex: 1;
}

.room-info-details h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: #333;
}

.room-info-details p {
    font-size: 0.8rem;
    color: #666;
    margin: 0;
}

.room-info-description {
    margin-top: 12px;
    padding: 12px;
    background: #f5f5f5;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #555;
}

.room-info-actions {
    margin-top: 16px;
}

/* Legend Card */
.legend-card {
    background: white;
    margin: 16px;
    padding: 12px 16px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.legend-header {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}

.legend-items {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: #555;
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.legend-dot.error { background-color: #f44336; }
.legend-dot.primary { background-color: #1976d2; }
.legend-dot.success { background-color: #4caf50; }
.legend-dot.exit { background-color: #4caf50; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }

.legend-line {
    width: 20px;
    height: 3px;
    border-radius: 2px;
}

.legend-line.success {
    background: repeating-linear-gradient(
        90deg,
        #4caf50,
        #4caf50 5px,
        transparent 5px,
        transparent 8px
    );
}

/* Directions Card */
.directions-card {
    background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
    margin: 0 16px 16px;
    padding: 16px;
    border-radius: 12px;
    border-left: 4px solid #1976d2;
}

.directions-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #1976d2;
    margin-bottom: 12px;
}

.directions-steps {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.direction-step {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.85rem;
    color: #333;
}

.step-number {
    width: 22px;
    height: 22px;
    background: #1976d2;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 600;
}

/* Responsive */

/* Mobile Small (< 360px) */
@media (max-width: 359px) {
    .map-header {
        padding: 10px 12px;
    }
    
    .header-info h1 {
        font-size: 0.95rem;
    }
    
    .header-info p {
        font-size: 0.6rem;
    }
    
    .map-area {
        margin: 8px;
        min-height: 200px;
        max-height: 45vh;
    }
    
    .target-banner {
        padding: 8px 10px;
    }
    
    .target-icon {
        width: 32px;
        height: 32px;
        min-width: 32px;
    }
    
    .target-label {
        font-size: 0.55rem;
    }
    
    .target-name {
        font-size: 0.75rem;
    }
    
    .room-annotation-label {
        font-size: 8px;
        padding: 2px 4px;
    }
    
    .legend-card,
    .directions-card {
        margin: 8px;
        padding: 10px;
    }
    
    .legend-header {
        font-size: 0.65rem;
    }
    
    .legend-items {
        gap: 8px;
    }
    
    .legend-item {
        font-size: 0.65rem;
    }
    
    .directions-header {
        font-size: 0.8rem;
    }
    
    .direction-step {
        font-size: 0.75rem;
    }
    
    .step-number {
        width: 18px;
        height: 18px;
        font-size: 0.6rem;
    }
    
    .room-info-panel {
        left: 8px;
        right: 8px;
        bottom: 72px;
        padding: 12px;
    }
    
    .room-info-details h3 {
        font-size: 0.85rem;
    }
    
    .room-info-details p {
        font-size: 0.65rem;
    }
    
    .zoom-level {
        font-size: 0.6rem;
    }
    
    .map-instructions {
        font-size: 0.65rem;
        padding: 6px 12px;
    }
}

/* Mobile (360px - 599px) */
@media (min-width: 360px) and (max-width: 599px) {
    .header-info h1 {
        font-size: 1rem;
    }
    
    .header-info p {
        font-size: 0.65rem;
    }
    
    .map-area {
        min-height: 250px;
        max-height: 50vh;
    }
    
    .target-label {
        font-size: 0.6rem;
    }
    
    .target-name {
        font-size: 0.85rem;
    }
    
    .room-annotation-label {
        font-size: 9px;
    }
    
    .legend-item {
        font-size: 0.7rem;
    }
    
    .directions-header {
        font-size: 0.85rem;
    }
    
    .direction-step {
        font-size: 0.8rem;
    }
}

/* Tablet (600px - 1023px) */
@media (min-width: 600px) and (max-width: 1023px) {
    .header-info h1 {
        font-size: 1.15rem;
    }
    
    .header-info p {
        font-size: 0.75rem;
    }
    
    .map-area {
        margin: 16px 24px;
        min-height: 350px;
        max-height: 55vh;
    }
    
    .target-label {
        font-size: 0.7rem;
    }
    
    .target-name {
        font-size: 1rem;
    }
    
    .room-annotation-label {
        font-size: 11px;
        padding: 5px 10px;
    }
    
    .legend-card,
    .directions-card {
        margin-left: 24px;
        margin-right: 24px;
    }
    
    .legend-header {
        font-size: 0.75rem;
    }
    
    .legend-item {
        font-size: 0.8rem;
    }
    
    .directions-header {
        font-size: 0.9rem;
    }
    
    .direction-step {
        font-size: 0.85rem;
    }
    
    .room-info-panel {
        left: 24px;
        right: 24px;
        bottom: 90px;
    }
    
    .room-info-details h3 {
        font-size: 1rem;
    }
    
    .room-info-details p {
        font-size: 0.8rem;
    }
    
    .map-main {
        padding-bottom: 90px;
    }
}

/* Desktop (1024px+) */
@media (min-width: 1024px) {
    .map-header {
        padding: 14px 20px;
    }
    
    .header-info h1 {
        font-size: 1.25rem;
    }
    
    .header-info p {
        font-size: 0.8rem;
    }
    
    .map-main {
        padding-bottom: 40px;
    }
    
    .map-container {
        max-width: 900px;
        margin: 0 auto;
        padding-bottom: 24px;
    }
    
    .map-area {
        margin: 20px 24px;
        min-height: 400px;
        max-height: 60vh;
    }
    
    .target-label {
        font-size: 0.7rem;
    }
    
    .target-name {
        font-size: 1.05rem;
    }
    
    .room-annotation-label {
        font-size: 12px;
        padding: 6px 12px;
    }
    
    .legend-card,
    .directions-card {
        margin-left: 24px;
        margin-right: 24px;
    }
    
    .legend-header {
        font-size: 0.8rem;
    }
    
    .legend-item {
        font-size: 0.85rem;
    }
    
    .directions-header {
        font-size: 1rem;
    }
    
    .direction-step {
        font-size: 0.9rem;
    }
    
    .room-info-panel {
        bottom: 24px;
        left: 50%;
        right: auto;
        transform: translateX(-50%);
        width: 400px;
    }
    
    .room-info-details h3 {
        font-size: 1.1rem;
    }
    
    .room-info-details p {
        font-size: 0.85rem;
    }
    
    .target-banner-content {
        max-width: 850px;
    }
    
    .zoom-level {
        font-size: 0.75rem;
    }
    
    .map-instructions {
        font-size: 0.8rem;
    }
}

/* Bottom nav padding for mobile */
@media (max-width: 1023px) {
    .map-main {
        padding-bottom: 80px;
    }
    
    .map-container {
        padding-bottom: 20px;
    }
}
</style>
