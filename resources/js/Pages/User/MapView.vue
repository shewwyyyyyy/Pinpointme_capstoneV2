<template>
    <v-app class="bg-user-gradient-light">
        <!-- App Bar -->
        <UserAppBar 
            title="Your Location Map" 
            :subtitle="rescueRequest?.building?.name || ''"
            :show-back="true"
            :notification-count="0"
            @go-back="goBack"
        >
            <template #actions>
                <v-btn icon variant="text" class="bar-btn" style="color: white;" @click="resetZoom">
                    <v-icon>mdi-crosshairs-gps</v-icon>
                </v-btn>
            </template>
        </UserAppBar>

        <!-- Navigation Drawer -->
        <UserMenu v-model="drawer" />

        <!-- Main Content -->
        <v-main class="map-main">
            <!-- Loading State -->
            <div v-if="loading" class="loading-container">
                <div class="loading-content">
                    <v-progress-circular indeterminate color="primary" size="64" width="4" />
                    <p class="mt-4 text-grey-darken-1">Loading floor map...</p>
                </div>
            </div>

            <div v-else-if="!rescueRequest" class="empty-state">
                <v-icon size="64" color="grey-lighten-1">mdi-map-marker-off</v-icon>
                <h3>No Active Rescue</h3>
                <p>Unable to load map data</p>
                <v-btn color="primary" class="mt-4" @click="goBack">Go Back</v-btn>
            </div>

            <div v-else class="map-container">
                <!-- Your Location Banner -->
                <div class="location-banner">
                    <div class="location-banner-content">
                        <div class="location-icon">
                            <v-icon color="white" size="24">mdi-map-marker-radius</v-icon>
                        </div>
                        <div class="location-info">
                            <span class="location-label">Your Location</span>
                            <span class="location-name">{{ rescueRequest?.room?.room_name || rescueRequest?.room?.name || 'Unknown Room' }}</span>
                        </div>
                        <v-chip 
                            v-if="rescueRequest?.status" 
                            :color="getStatusColor(rescueRequest.status)"
                            variant="flat" 
                            size="small"
                            class="status-chip"
                        >
                            {{ formatStatus(rescueRequest.status) }}
                        </v-chip>
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
                            
                            <!-- Only show current user's location -->
                            <template v-if="imageLoaded && userRoomAnnotation">
                                <div
                                    class="room-annotation your-room"
                                    :style="getRoomAnnotationStyle(userRoomAnnotation)"
                                >
                                    <div class="room-annotation-label">
                                        {{ userRoomAnnotation.room_name }}
                                    </div>
                                    <div class="your-location-indicator">
                                        <v-icon color="white" size="14">mdi-account</v-icon>
                                    </div>
                                    <div class="pulse-ring"></div>
                                </div>
                            </template>
                            
                            <!-- Evacuation Path from user's location only -->
                            <svg v-if="imageLoaded && userEvacuationPath" class="evacuation-svg" :viewBox="svgViewBox">
                                <path
                                    :d="getPathD(userEvacuationPath)"
                                    :stroke="userEvacuationPath.color || '#4CAF50'"
                                    stroke-width="5"
                                    fill="none"
                                    stroke-dasharray="15,8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="evacuation-path"
                                />
                                <!-- Exit marker at the end of path -->
                                <circle 
                                    v-if="userEvacuationPath.points && userEvacuationPath.points.length > 0"
                                    :cx="userEvacuationPath.points[userEvacuationPath.points.length - 1].x"
                                    :cy="userEvacuationPath.points[userEvacuationPath.points.length - 1].y"
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
                                        'your-room': room.id === rescueRequest?.room_id,
                                        'selected': selectedRoom?.id === room.id,
                                    }"
                                    @click="selectRoom(room)"
                                >
                                    <div class="room-icon">
                                        <v-icon v-if="room.id === rescueRequest?.room_id" color="primary" size="24">mdi-account-circle</v-icon>
                                        <v-icon v-else color="grey" size="20">mdi-door</v-icon>
                                    </div>
                                    <div class="room-label">{{ room.room_name || room.name }}</div>
                                    <div v-if="room.id === rescueRequest?.room_id" class="your-badge">
                                        <v-icon size="10" color="white">mdi-account</v-icon>
                                        You
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
                            <div class="room-info-icon" :class="{ 'yours': selectedRoom.id === rescueRequest?.room_id || selectedRoom.room_name === (rescueRequest?.room?.room_name || rescueRequest?.room?.name) }">
                                <v-icon color="white" size="20">
                                    {{ (selectedRoom.id === rescueRequest?.room_id || selectedRoom.room_name === (rescueRequest?.room?.room_name || rescueRequest?.room?.name)) ? 'mdi-account' : 'mdi-door' }}
                                </v-icon>
                            </div>
                            <div class="room-info-details">
                                <h3>{{ selectedRoom.room_name || selectedRoom.name }}</h3>
                                <p v-if="selectedRoom.id === rescueRequest?.room_id || selectedRoom.room_name === (rescueRequest?.room?.room_name || rescueRequest?.room?.name)">
                                    <v-icon size="14" color="primary">mdi-map-marker</v-icon>
                                    Your current location
                                </p>
                                <p v-else>{{ selectedRoom.room_type || 'Room' }}</p>
                            </div>
                            <v-btn icon size="small" variant="text" @click="selectedRoom = null">
                                <v-icon>mdi-close</v-icon>
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
                            <div class="legend-dot primary"></div>
                            <span>Your Location</span>
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

                <!-- Help Info Card -->
                <div class="help-card">
                    <div class="help-icon">
                        <v-icon color="white" size="20">mdi-information</v-icon>
                    </div>
                    <div class="help-content">
                        <h4>Help is on the way!</h4>
                        <p>Stay at your location. The rescuer will find you using this map.</p>
                    </div>
                </div>
            </div>
        </v-main>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000" location="top">
            {{ snackbar.message }}
        </v-snackbar>
        
        <!-- Bottom Navigation (Mobile/Tablet only) -->
        <UserBottomNav :notification-count="1" :message-count="unreadCount" />
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { getRescueRequestByCode, getRescueRequestById } from '@/Composables/useApi';
import { useUnreadMessages } from '@/Composables/useUnreadMessages';
import UserMenu from '@/Components/Pages/User/Menu/UserMenu.vue';
import UserAppBar from '@/Components/Pages/User/Menu/UserAppBar.vue';
import UserBottomNav from '@/Components/Pages/User/Menu/UserBottomNav.vue';

const props = defineProps({
    code: {
        type: String,
        default: null,
    },
    rescueId: {
        type: [String, Number],
        default: null,
    },
});

// Navigation drawer
const drawer = ref(false);

// Unread messages count for bottom nav
const { unreadCount } = useUnreadMessages();

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

// Only get the user's current room annotation
const userRoomAnnotation = computed(() => {
    if (!floorPlanRooms.value.length) return null;
    const userRoomName = rescueRequest.value?.room?.room_name || rescueRequest.value?.room?.name;
    const userRoomId = rescueRequest.value?.room_id;
    
    return floorPlanRooms.value.find(room => 
        room.room_id === userRoomId || room.room_name === userRoomName
    );
});

const evacuationPaths = computed(() => {
    if (!selectedFloor.value?.floor_plan_data) return [];
    return selectedFloor.value.floor_plan_data.evacuation_paths || [];
});

// Only get evacuation path from user's location
const userEvacuationPath = computed(() => {
    if (!evacuationPaths.value.length || !userRoomAnnotation.value) return null;
    
    // Find path that starts from or near user's room
    const userRoom = userRoomAnnotation.value;
    const userCenterX = userRoom.x + (userRoom.width / 2);
    const userCenterY = userRoom.y + (userRoom.height / 2);
    
    // Find the path closest to user's room or the first path if only one exists
    let closestPath = evacuationPaths.value[0];
    let minDistance = Infinity;
    
    for (const path of evacuationPaths.value) {
        if (path.points && path.points.length > 0) {
            const startPoint = path.points[0];
            const distance = Math.sqrt(
                Math.pow(startPoint.x - userCenterX, 2) + 
                Math.pow(startPoint.y - userCenterY, 2)
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

// Helper functions
const isYourRoom = (room) => {
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
        backgroundColor: 'rgba(54, 116, 181, 0.35)',
        borderColor: '#3674B5',
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
    selectedRoom.value = {
        room_name: room.room_name,
        name: room.room_name,
        id: room.room_id,
    };
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'warning',
        open: 'warning',
        assigned: 'info',
        accepted: 'info',
        in_progress: 'primary',
        en_route: 'primary',
        on_scene: 'primary',
        rescued: 'success',
        safe: 'success',
        completed: 'success',
        cancelled: 'error',
    };
    return colors[status] || 'grey';
};

const formatStatus = (status) => {
    const texts = {
        pending: 'Pending',
        open: 'Pending',
        assigned: 'Rescuer Assigned',
        accepted: 'Help Coming',
        in_progress: 'In Progress',
        en_route: 'Rescuer En Route',
        on_scene: 'Rescuer Arrived',
        rescued: 'Rescued',
        safe: 'Safe',
        completed: 'Completed',
        cancelled: 'Cancelled',
    };
    return texts[status] || status;
};

// Methods
const fetchData = async () => {
    try {
        const code = props.code || localStorage.getItem('lastRescueCode');
        const id = props.rescueId || localStorage.getItem('lastRescueRequestId');
        
        let data;
        if (code) {
            data = await getRescueRequestByCode(code);
        } else if (id) {
            data = await getRescueRequestById(id);
        } else {
            showSnackbar('No rescue request found', 'error');
            setTimeout(() => router.visit('/user/dashboard'), 2000);
            return;
        }
        
        const rescueData = data.data || data;
        rescueRequest.value = rescueData;
        
        // Fetch building floors with floor_plan_data
        if (rescueData.building_id) {
            try {
                const response = await fetch('/api/buildings');
                const buildings = await response.json();
                const building = buildings?.find(b => b.id === rescueData.building_id);
                
                if (building?.floors) {
                    const floorsData = [];
                    for (const floor of building.floors) {
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
                    rooms.value = building.floors.flatMap(f => f.rooms || []);
                }
                
                // Set initial floor to user's floor
                const targetFloorIndex = floors.value.findIndex(
                    f => f.id === rescueData.floor_id
                );
                if (targetFloorIndex !== -1) {
                    selectedFloorIndex.value = targetFloorIndex;
                }
            } catch (error) {
                console.error('Error fetching floor data:', error);
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
    const code = props.code || localStorage.getItem('lastRescueCode');
    if (code) {
        router.visit(`/user/help-coming/${code}`);
    } else {
        router.visit('/user/dashboard');
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

// Lifecycle
onMounted(async () => {
    await fetchData();
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

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    text-align: center;
    padding: 24px;
}

.empty-state h3 {
    margin-top: 16px;
    color: #333;
}

.empty-state p {
    color: #666;
}

/* Map Container */
.map-container {
    padding: 0;
}

/* Location Banner */
.location-banner {
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 100%);
    margin: 0;
    padding: 10px 12px;
}

.location-banner-content {
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 800px;
    margin: 0 auto;
}

.location-icon {
    width: 36px;
    height: 36px;
    min-width: 36px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.location-info {
    flex: 1;
    min-width: 0;
}

.location-label {
    display: block;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.location-name {
    display: block;
    color: white;
    font-size: 0.9rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.status-chip {
    font-size: 0.65rem;
    font-weight: 600;
    flex-shrink: 0;
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
    border: 3px solid #3674B5;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: visible;
    box-shadow: 0 4px 12px rgba(54, 116, 181, 0.4);
}

.room-annotation.your-room {
    z-index: 5;
    animation: glow 2s ease-in-out infinite;
}

@keyframes glow {
    0%, 100% { box-shadow: 0 4px 12px rgba(54, 116, 181, 0.4); }
    50% { box-shadow: 0 4px 20px rgba(54, 116, 181, 0.7); }
}

.room-annotation-label {
    font-size: 10px;
    font-weight: 700;
    color: #1a4a7a;
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

.your-location-indicator {
    position: absolute;
    top: -10px;
    right: -10px;
    width: 24px;
    height: 24px;
    background: #3674B5;
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
    border: 3px solid #3674B5;
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

.room-cell.your-room {
    border-color: #3674B5;
    background: rgba(54, 116, 181, 0.05);
}

.room-cell.selected {
    border-color: #9c27b0;
    background: rgba(156, 39, 176, 0.05);
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

.room-cell.your-room .room-icon {
    background: rgba(54, 116, 181, 0.1);
}

.room-label {
    font-size: 0.75rem;
    font-weight: 500;
    text-align: center;
    color: #333;
    word-break: break-word;
}

.your-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #3674B5;
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
    left: 12px;
    right: 12px;
    background: white;
    border-radius: 16px;
    padding: 14px;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
    z-index: 50;
    max-width: 500px;
    margin: 0 auto;
}

.room-info-header {
    display: flex;
    align-items: center;
    gap: 10px;
}

.room-info-icon {
    width: 36px;
    height: 36px;
    min-width: 36px;
    background: #9e9e9e;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.room-info-icon.yours {
    background: #3674B5;
}

.room-info-details {
    flex: 1;
    min-width: 0;
}

.room-info-details h3 {
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0;
    color: #333;
}

.room-info-details p {
    font-size: 0.75rem;
    color: #666;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Legend Card */
.legend-card {
    background: white;
    margin: 12px;
    padding: 10px 14px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.legend-header {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.7rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.legend-items {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    color: #555;
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.legend-dot.primary { background-color: #3674B5; }
.legend-dot.secondary { background-color: #9c27b0; }
.legend-dot.success { background-color: #4caf50; }
.legend-dot.exit { background-color: #4caf50; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }

.legend-line {
    width: 18px;
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

/* Help Card */
.help-card {
    background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
    margin: 0 12px 12px;
    padding: 12px;
    border-radius: 12px;
    border-left: 4px solid #4CAF50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.help-icon {
    width: 36px;
    height: 36px;
    min-width: 36px;
    background: #4CAF50;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.help-content h4 {
    font-size: 0.85rem;
    font-weight: 600;
    color: #2E7D32;
    margin: 0 0 2px;
}

.help-content p {
    font-size: 0.75rem;
    color: #388E3C;
    margin: 0;
}

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
    
    .location-banner {
        padding: 8px 10px;
    }
    
    .location-icon {
        width: 32px;
        height: 32px;
        min-width: 32px;
    }
    
    .location-label {
        font-size: 0.55rem;
    }
    
    .location-name {
        font-size: 0.75rem;
    }
    
    .status-chip {
        font-size: 0.55rem;
    }
    
    .room-annotation-label {
        font-size: 8px;
        padding: 2px 4px;
    }
    
    .legend-card,
    .help-card {
        margin: 8px;
        padding: 10px;
    }
    
    .legend-header {
        font-size: 0.6rem;
    }
    
    .legend-items {
        gap: 8px;
    }
    
    .legend-item {
        font-size: 0.65rem;
    }
    
    .help-content h4 {
        font-size: 0.75rem;
    }
    
    .help-content p {
        font-size: 0.65rem;
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
    
    .location-label {
        font-size: 0.6rem;
    }
    
    .location-name {
        font-size: 0.85rem;
    }
    
    .room-annotation-label {
        font-size: 9px;
    }
    
    .legend-item {
        font-size: 0.7rem;
    }
    
    .help-content h4 {
        font-size: 0.8rem;
    }
    
    .help-content p {
        font-size: 0.7rem;
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
    
    .location-label {
        font-size: 0.7rem;
    }
    
    .location-name {
        font-size: 1rem;
    }
    
    .room-annotation-label {
        font-size: 11px;
        padding: 5px 10px;
    }
    
    .legend-card,
    .help-card {
        margin-left: 24px;
        margin-right: 24px;
    }
    
    .legend-header {
        font-size: 0.75rem;
    }
    
    .legend-item {
        font-size: 0.8rem;
    }
    
    .help-content h4 {
        font-size: 0.9rem;
    }
    
    .help-content p {
        font-size: 0.8rem;
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
    }
    
    .map-area {
        margin: 20px 24px;
        min-height: 400px;
        max-height: 60vh;
    }
    
    .location-label {
        font-size: 0.7rem;
    }
    
    .location-name {
        font-size: 1.05rem;
    }
    
    .room-annotation-label {
        font-size: 12px;
        padding: 6px 12px;
    }
    
    .legend-card,
    .help-card {
        margin-left: 24px;
        margin-right: 24px;
    }
    
    .legend-header {
        font-size: 0.8rem;
    }
    
    .legend-item {
        font-size: 0.85rem;
    }
    
    .help-content h4 {
        font-size: 0.95rem;
    }
    
    .help-content p {
        font-size: 0.85rem;
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
    
    .location-banner-content {
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
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 120px);
    }
    
    .map-container {
        padding-bottom: 20px;
    }
}

@media (max-width: 600px) {
    .map-main {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 130px);
    }
}
</style>
