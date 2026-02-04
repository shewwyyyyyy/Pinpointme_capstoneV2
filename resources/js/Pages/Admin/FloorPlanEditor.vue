<template>
    <v-app class="bg-grey-lighten-4">

        <!-- App Bar (Unified) -->
        <v-app-bar color="primary" elevation="2">
            <v-app-bar-nav-icon @click="goBack"></v-app-bar-nav-icon>
            <v-app-bar-title>
                <v-icon class="mr-2" color="white">mdi-shield-check</v-icon>
                <span class="text-white font-weight-bold">PinPointMe Admin</span>
            </v-app-bar-title>
            <v-spacer />
            <v-btn color="success" variant="elevated" class="mr-2" :loading="saving" @click="saveAnnotations">
                <v-icon left>mdi-content-save</v-icon>
                Save
            </v-btn>
        </v-app-bar>

        <!-- Main Content -->
        <v-main>
            <v-container fluid class="pa-4">
                <!-- Header Info -->
                <v-card class="mb-4">
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2">mdi-office-building</v-icon>
                        {{ floorData.building_name }} - {{ floorData.floor_name }}
                    </v-card-title>
                    <v-card-subtitle>
                        Click and drag to draw room boxes. Click points to create evacuation paths.
                    </v-card-subtitle>
                </v-card>

                <v-row>
                    <!-- Left Panel - Tools & Properties -->
                    <v-col cols="12" md="3">
                        <!-- Upload Floor Plan -->
                        <v-card class="mb-4">
                            <v-card-title class="text-subtitle-1">
                                <v-icon class="mr-2">mdi-image</v-icon>
                                Floor Plan Image
                            </v-card-title>
                            <v-card-text>
                                <v-file-input
                                    v-model="selectedFile"
                                    accept="image/*"
                                    label="Upload floor plan"
                                    prepend-icon="mdi-camera"
                                    variant="outlined"
                                    density="compact"
                                    :loading="uploading"
                                    @update:model-value="uploadFloorPlan"
                                ></v-file-input>
                                <v-btn
                                    v-if="floorPlanUrl"
                                    color="error"
                                    variant="outlined"
                                    size="small"
                                    block
                                    @click="deleteFloorPlan"
                                >
                                    <v-icon left>mdi-delete</v-icon>
                                    Remove Image
                                </v-btn>
                            </v-card-text>
                        </v-card>

                        <!-- Drawing Tools -->
                        <v-card class="mb-4">
                            <v-card-title class="text-subtitle-1">
                                <v-icon class="mr-2">mdi-tools</v-icon>
                                Drawing Tools
                            </v-card-title>
                            <v-card-text>
                                <v-btn-toggle
                                    v-model="currentTool"
                                    mandatory
                                    class="mb-3"
                                    color="primary"
                                    divided
                                >
                                    <v-btn value="select" size="small">
                                        <v-icon>mdi-cursor-default</v-icon>
                                        <span class="ml-1">Select</span>
                                    </v-btn>
                                    <v-btn value="room" size="small">
                                        <v-icon>mdi-square-outline</v-icon>
                                        <span class="ml-1">Room</span>
                                    </v-btn>
                                    <v-btn value="path" size="small">
                                        <v-icon>mdi-vector-polyline</v-icon>
                                        <span class="ml-1">Path</span>
                                    </v-btn>
                                </v-btn-toggle>

                                <v-divider class="my-3"></v-divider>

                                <!-- Room Color Picker -->
                                <div v-if="currentTool === 'room'" class="mb-3">
                                    <v-label class="mb-1">Room Color</v-label>
                                    <v-color-picker
                                        v-model="roomColor"
                                        mode="hexa"
                                        hide-inputs
                                        show-swatches
                                        swatches-max-height="100"
                                    ></v-color-picker>
                                </div>

                                <!-- Path Color Picker -->
                                <div v-if="currentTool === 'path'" class="mb-3">
                                    <v-label class="mb-1">Path Color</v-label>
                                    <v-color-picker
                                        v-model="pathColor"
                                        mode="hexa"
                                        hide-inputs
                                        show-swatches
                                        swatches-max-height="100"
                                    ></v-color-picker>
                                    <v-btn
                                        v-if="isDrawingPath"
                                        color="success"
                                        variant="outlined"
                                        size="small"
                                        block
                                        class="mt-2"
                                        @click="finishPath"
                                    >
                                        <v-icon left>mdi-check</v-icon>
                                        Finish Path
                                    </v-btn>
                                </div>
                            </v-card-text>
                        </v-card>

                        <!-- Selected Item Properties -->
                        <v-card v-if="selectedItem" class="mb-4">
                            <v-card-title class="text-subtitle-1">
                                <v-icon class="mr-2">mdi-tune</v-icon>
                                Properties
                            </v-card-title>
                            <v-card-text>
                                <!-- Room Properties -->
                                <div v-if="selectedItem.type === 'room'">
                                    <v-select
                                        v-model="selectedItem.room_id"
                                        :items="availableRooms"
                                        item-title="room_name"
                                        item-value="id"
                                        label="Link to Room"
                                        variant="outlined"
                                        density="compact"
                                        clearable
                                        class="mb-2"
                                        @update:model-value="updateRoomLink"
                                    ></v-select>
                                    <v-text-field
                                        v-model="selectedItem.room_name"
                                        label="Room Label"
                                        variant="outlined"
                                        density="compact"
                                        class="mb-2"
                                    ></v-text-field>
                                    <v-color-picker
                                        v-model="selectedItem.color"
                                        mode="hexa"
                                        hide-inputs
                                        show-swatches
                                        swatches-max-height="80"
                                        class="mb-2"
                                    ></v-color-picker>
                                </div>

                                <!-- Path Properties -->
                                <div v-if="selectedItem.type === 'path'">
                                    <v-select
                                        v-model="selectedItem.room_id"
                                        :items="availableRooms"
                                        item-title="room_name"
                                        item-value="id"
                                        label="Associated Room"
                                        variant="outlined"
                                        density="compact"
                                        clearable
                                        class="mb-2"
                                        hint="Select the room this evacuation path belongs to"
                                        persistent-hint
                                    ></v-select>
                                    <v-text-field
                                        v-model="selectedItem.name"
                                        label="Path Name"
                                        variant="outlined"
                                        density="compact"
                                        class="mb-2"
                                    ></v-text-field>
                                    <v-color-picker
                                        v-model="selectedItem.color"
                                        mode="hexa"
                                        hide-inputs
                                        show-swatches
                                        swatches-max-height="80"
                                        class="mb-2"
                                    ></v-color-picker>
                                </div>

                                <v-btn
                                    color="error"
                                    variant="outlined"
                                    size="small"
                                    block
                                    @click="deleteSelectedItem"
                                >
                                    <v-icon left>mdi-delete</v-icon>
                                    Delete
                                </v-btn>
                            </v-card-text>
                        </v-card>

                        <!-- Items List -->
                        <v-card>
                            <v-card-title class="text-subtitle-1">
                                <v-icon class="mr-2">mdi-format-list-bulleted</v-icon>
                                Annotations
                            </v-card-title>
                            <v-card-text class="pa-0">
                                <v-list density="compact">
                                    <v-list-subheader>Rooms ({{ rooms.length }})</v-list-subheader>
                                    <v-list-item
                                        v-for="(room, index) in rooms"
                                        :key="'room-' + index"
                                        :active="selectedItem === room"
                                        @click="selectItem(room)"
                                    >
                                        <template v-slot:prepend>
                                            <v-avatar :color="room.color || '#4CAF50'" size="24">
                                                <v-icon size="14">mdi-door</v-icon>
                                            </v-avatar>
                                        </template>
                                        <v-list-item-title>
                                            {{ room.room_name || 'Unnamed Room' }}
                                        </v-list-item-title>
                                    </v-list-item>

                                    <v-divider></v-divider>

                                    <v-list-subheader>Evacuation Paths ({{ paths.length }})</v-list-subheader>
                                    <v-list-item
                                        v-for="(path, index) in paths"
                                        :key="'path-' + index"
                                        :active="selectedItem === path"
                                        @click="selectItem(path)"
                                    >
                                        <template v-slot:prepend>
                                            <v-avatar :color="path.color || '#FF5722'" size="24">
                                                <v-icon size="14">mdi-routes</v-icon>
                                            </v-avatar>
                                        </template>
                                        <v-list-item-title>
                                            {{ path.name || 'Evacuation Path ' + (index + 1) }}
                                        </v-list-item-title>
                                        <v-list-item-subtitle>
                                            {{ path.points.length }} points
                                            <span v-if="path.room_id" class="ml-1">
                                                • {{ availableRooms.find(r => r.id === path.room_id)?.room_name || 'Room' }}
                                            </span>
                                        </v-list-item-subtitle>
                                    </v-list-item>
                                </v-list>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- Right Panel - Canvas -->
                    <v-col cols="12" md="9">
                        <v-card class="canvas-card">
                            <v-card-text class="pa-0">
                                <div 
                                    ref="canvasContainer" 
                                    class="canvas-container"
                                    @wheel.prevent="handleZoom"
                                >
                                    <div
                                        class="canvas-wrapper"
                                        :style="canvasWrapperStyle"
                                    >
                                        <!-- Floor Plan Image -->
                                        <img
                                            v-if="floorPlanUrl"
                                            ref="floorPlanImage"
                                            :src="floorPlanUrl"
                                            class="floor-plan-image"
                                            @load="onImageLoad"
                                            draggable="false"
                                        />
                                        <div
                                            v-else
                                            class="no-image-placeholder"
                                            :style="{ width: canvasWidth + 'px', height: canvasHeight + 'px' }"
                                        >
                                            <v-icon size="64" color="grey">mdi-image-off</v-icon>
                                            <p class="text-grey mt-2">Upload a floor plan image to start</p>
                                        </div>

                                        <!-- Canvas for drawing -->
                                        <canvas
                                            ref="drawingCanvas"
                                            class="drawing-canvas"
                                            :width="canvasWidth"
                                            :height="canvasHeight"
                                            @mousedown="onCanvasMouseDown"
                                            @mousemove="onCanvasMouseMove"
                                            @mouseup="onCanvasMouseUp"
                                            @mouseleave="onCanvasMouseLeave"
                                        ></canvas>
                                    </div>
                                </div>
                            </v-card-text>
                        </v-card>

                        <!-- Zoom Controls -->
                        <v-card class="mt-2">
                            <v-card-text class="d-flex align-center justify-center py-2">
                                <v-btn icon size="small" @click="zoomOut">
                                    <v-icon>mdi-minus</v-icon>
                                </v-btn>
                                <span class="mx-4">{{ Math.round(zoom * 100) }}%</span>
                                <v-btn icon size="small" @click="zoomIn">
                                    <v-icon>mdi-plus</v-icon>
                                </v-btn>
                                <v-btn icon size="small" class="ml-4" @click="resetZoom">
                                    <v-icon>mdi-fit-to-screen</v-icon>
                                </v-btn>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>
        </v-main>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000">
            {{ snackbarText }}
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    floor: {
        type: Object,
        required: true
    }
});

// Refs
const canvasContainer = ref(null);
const floorPlanImage = ref(null);
const drawingCanvas = ref(null);

// State
const floorData = ref(props.floor);
const floorPlanUrl = ref(props.floor.floor_plan_url || '');
const availableRooms = ref(props.floor.rooms || []);

const selectedFile = ref(null);
const uploading = ref(false);
const saving = ref(false);
const snackbar = ref(false);
const snackbarText = ref('');
const snackbarColor = ref('success');

// Canvas state
const canvasWidth = ref(800);
const canvasHeight = ref(600);
const zoom = ref(1);
const panX = ref(0);
const panY = ref(0);

// Drawing state
const currentTool = ref('select');
const roomColor = ref('#4CAF50');
const pathColor = ref('#FF5722');
const isDrawing = ref(false);
const isDrawingPath = ref(false);
const isDragging = ref(false);
const dragStartX = ref(0);
const dragStartY = ref(0);
const currentPath = ref([]);

// Annotations
const rooms = ref([]);
const paths = ref([]);
const selectedItem = ref(null);

// Temporary drawing state
const tempRect = ref(null);

// Computed
const canvasWrapperStyle = computed(() => ({
    transform: `scale(${zoom.value}) translate(${panX.value}px, ${panY.value}px)`,
    transformOrigin: 'top left'
}));

// Initialize from saved data
onMounted(() => {
    if (props.floor.floor_plan_data) {
        rooms.value = (props.floor.floor_plan_data.rooms || []).map(r => ({ ...r, type: 'room' }));
        paths.value = (props.floor.floor_plan_data.evacuation_paths || []).map(p => ({ ...p, type: 'path' }));
    }
    
    nextTick(() => {
        redraw();
    });
});

// Watch for tool changes
watch(currentTool, () => {
    selectedItem.value = null;
    if (isDrawingPath.value) {
        finishPath();
    }
    redraw();
});

// Image load handler
const onImageLoad = () => {
    if (floorPlanImage.value) {
        canvasWidth.value = floorPlanImage.value.naturalWidth;
        canvasHeight.value = floorPlanImage.value.naturalHeight;
        nextTick(() => {
            redraw();
        });
    }
};

// Canvas event handlers
const onCanvasMouseDown = (e) => {
    const rect = drawingCanvas.value.getBoundingClientRect();
    const x = (e.clientX - rect.left) / zoom.value;
    const y = (e.clientY - rect.top) / zoom.value;

    if (currentTool.value === 'select') {
        // Try to select an item
        const item = findItemAtPoint(x, y);
        if (item) {
            selectItem(item);
            isDragging.value = true;
            dragStartX.value = x - (item.x || item.points?.[0]?.x || 0);
            dragStartY.value = y - (item.y || item.points?.[0]?.y || 0);
        } else {
            selectedItem.value = null;
            redraw();
        }
    } else if (currentTool.value === 'room') {
        // Start drawing a room
        isDrawing.value = true;
        dragStartX.value = x;
        dragStartY.value = y;
        tempRect.value = { x, y, width: 0, height: 0 };
    } else if (currentTool.value === 'path') {
        // Add point to path
        if (!isDrawingPath.value) {
            isDrawingPath.value = true;
            currentPath.value = [{ x, y }];
        } else {
            currentPath.value.push({ x, y });
        }
        redraw();
    }
};

const onCanvasMouseMove = (e) => {
    const rect = drawingCanvas.value.getBoundingClientRect();
    const x = (e.clientX - rect.left) / zoom.value;
    const y = (e.clientY - rect.top) / zoom.value;

    if (currentTool.value === 'room' && isDrawing.value) {
        // Update temp rectangle
        tempRect.value.width = x - dragStartX.value;
        tempRect.value.height = y - dragStartY.value;
        redraw();
    } else if (currentTool.value === 'select' && isDragging.value && selectedItem.value) {
        // Drag selected item
        if (selectedItem.value.type === 'room') {
            selectedItem.value.x = x - dragStartX.value;
            selectedItem.value.y = y - dragStartY.value;
        } else if (selectedItem.value.type === 'path') {
            const dx = x - dragStartX.value - selectedItem.value.points[0].x;
            const dy = y - dragStartY.value - selectedItem.value.points[0].y;
            selectedItem.value.points = selectedItem.value.points.map(p => ({
                x: p.x + dx,
                y: p.y + dy
            }));
            dragStartX.value = x - selectedItem.value.points[0].x;
            dragStartY.value = y - selectedItem.value.points[0].y;
        }
        redraw();
    } else if (currentTool.value === 'path' && isDrawingPath.value && currentPath.value.length > 0) {
        // Preview line to cursor
        redraw();
        const ctx = drawingCanvas.value.getContext('2d');
        const lastPoint = currentPath.value[currentPath.value.length - 1];
        ctx.beginPath();
        ctx.moveTo(lastPoint.x, lastPoint.y);
        ctx.lineTo(x, y);
        ctx.strokeStyle = pathColor.value;
        ctx.lineWidth = 3;
        ctx.setLineDash([5, 5]);
        ctx.stroke();
        ctx.setLineDash([]);
    }
};

const onCanvasMouseUp = () => {
    if (currentTool.value === 'room' && isDrawing.value && tempRect.value) {
        // Normalize rectangle (handle negative width/height)
        const rect = normalizeRect(tempRect.value);
        
        // Only add if it has meaningful size
        if (rect.width > 10 && rect.height > 10) {
            const newRoom = {
                type: 'room',
                id: Date.now(),
                room_id: null,
                room_name: '',
                x: rect.x,
                y: rect.y,
                width: rect.width,
                height: rect.height,
                color: roomColor.value
            };
            rooms.value.push(newRoom);
            selectItem(newRoom);
        }
        
        tempRect.value = null;
    }
    
    isDrawing.value = false;
    isDragging.value = false;
    redraw();
};

const onCanvasMouseLeave = () => {
    if (isDrawing.value) {
        tempRect.value = null;
        isDrawing.value = false;
        redraw();
    }
};

// Finish drawing path
const finishPath = () => {
    if (currentPath.value.length >= 2) {
        const newPath = {
            type: 'path',
            id: Date.now(),
            name: 'Evacuation Path ' + (paths.value.length + 1),
            points: [...currentPath.value],
            color: pathColor.value,
            room_id: null
        };
        paths.value.push(newPath);
        selectItem(newPath);
    }
    
    currentPath.value = [];
    isDrawingPath.value = false;
    redraw();
};

// Find item at point
const findItemAtPoint = (x, y) => {
    // Check rooms first (they're on top visually)
    for (let i = rooms.value.length - 1; i >= 0; i--) {
        const room = rooms.value[i];
        if (x >= room.x && x <= room.x + room.width &&
            y >= room.y && y <= room.y + room.height) {
            return room;
        }
    }
    
    // Check paths
    for (let i = paths.value.length - 1; i >= 0; i--) {
        const path = paths.value[i];
        for (const point of path.points) {
            const dist = Math.sqrt((x - point.x) ** 2 + (y - point.y) ** 2);
            if (dist < 15) {
                return path;
            }
        }
    }
    
    return null;
};

// Select item
const selectItem = (item) => {
    selectedItem.value = item;
    redraw();
};

// Update room link
const updateRoomLink = () => {
    if (selectedItem.value && selectedItem.value.room_id) {
        const linkedRoom = availableRooms.value.find(r => r.id === selectedItem.value.room_id);
        if (linkedRoom && !selectedItem.value.room_name) {
            selectedItem.value.room_name = linkedRoom.room_name;
        }
    }
    redraw();
};

// Delete selected item
const deleteSelectedItem = () => {
    if (!selectedItem.value) return;
    
    if (selectedItem.value.type === 'room') {
        const index = rooms.value.indexOf(selectedItem.value);
        if (index > -1) rooms.value.splice(index, 1);
    } else if (selectedItem.value.type === 'path') {
        const index = paths.value.indexOf(selectedItem.value);
        if (index > -1) paths.value.splice(index, 1);
    }
    
    selectedItem.value = null;
    redraw();
};

// Normalize rectangle
const normalizeRect = (rect) => {
    let { x, y, width, height } = rect;
    if (width < 0) {
        x += width;
        width = Math.abs(width);
    }
    if (height < 0) {
        y += height;
        height = Math.abs(height);
    }
    return { x, y, width, height };
};

// Redraw canvas
const redraw = () => {
    if (!drawingCanvas.value) return;
    
    const ctx = drawingCanvas.value.getContext('2d');
    ctx.clearRect(0, 0, canvasWidth.value, canvasHeight.value);
    
    // Draw rooms
    rooms.value.forEach(room => {
        const isSelected = selectedItem.value === room;
        
        // Fill
        ctx.fillStyle = hexToRgba(room.color || '#4CAF50', 0.3);
        ctx.fillRect(room.x, room.y, room.width, room.height);
        
        // Border
        ctx.strokeStyle = room.color || '#4CAF50';
        ctx.lineWidth = isSelected ? 4 : 2;
        ctx.strokeRect(room.x, room.y, room.width, room.height);
        
        // Label
        if (room.room_name) {
            ctx.fillStyle = '#000';
            ctx.font = 'bold 14px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(
                room.room_name,
                room.x + room.width / 2,
                room.y + room.height / 2
            );
        }
        
        // Selection handles
        if (isSelected) {
            drawSelectionHandles(ctx, room);
        }
    });
    
    // Draw paths
    paths.value.forEach(path => {
        if (path.points.length < 2) return;
        
        const isSelected = selectedItem.value === path;
        
        ctx.beginPath();
        ctx.moveTo(path.points[0].x, path.points[0].y);
        for (let i = 1; i < path.points.length; i++) {
            ctx.lineTo(path.points[i].x, path.points[i].y);
        }
        ctx.strokeStyle = path.color || '#FF5722';
        ctx.lineWidth = isSelected ? 5 : 3;
        ctx.stroke();
        
        // Draw arrow heads
        for (let i = 1; i < path.points.length; i++) {
            drawArrowHead(ctx, path.points[i - 1], path.points[i], path.color || '#FF5722');
        }
        
        // Draw points
        path.points.forEach((point, i) => {
            ctx.beginPath();
            ctx.arc(point.x, point.y, isSelected ? 8 : 6, 0, Math.PI * 2);
            ctx.fillStyle = i === 0 ? '#4CAF50' : (i === path.points.length - 1 ? '#f44336' : path.color || '#FF5722');
            ctx.fill();
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 2;
            ctx.stroke();
        });
    });
    
    // Draw current path being created
    if (isDrawingPath.value && currentPath.value.length > 0) {
        ctx.beginPath();
        ctx.moveTo(currentPath.value[0].x, currentPath.value[0].y);
        for (let i = 1; i < currentPath.value.length; i++) {
            ctx.lineTo(currentPath.value[i].x, currentPath.value[i].y);
        }
        ctx.strokeStyle = pathColor.value;
        ctx.lineWidth = 3;
        ctx.stroke();
        
        // Draw points
        currentPath.value.forEach((point, i) => {
            ctx.beginPath();
            ctx.arc(point.x, point.y, 6, 0, Math.PI * 2);
            ctx.fillStyle = i === 0 ? '#4CAF50' : pathColor.value;
            ctx.fill();
        });
    }
    
    // Draw temp rectangle
    if (tempRect.value) {
        const rect = normalizeRect(tempRect.value);
        ctx.fillStyle = hexToRgba(roomColor.value, 0.3);
        ctx.fillRect(rect.x, rect.y, rect.width, rect.height);
        ctx.strokeStyle = roomColor.value;
        ctx.lineWidth = 2;
        ctx.setLineDash([5, 5]);
        ctx.strokeRect(rect.x, rect.y, rect.width, rect.height);
        ctx.setLineDash([]);
    }
};

// Draw selection handles
const drawSelectionHandles = (ctx, room) => {
    const handles = [
        { x: room.x, y: room.y },
        { x: room.x + room.width, y: room.y },
        { x: room.x, y: room.y + room.height },
        { x: room.x + room.width, y: room.y + room.height }
    ];
    
    handles.forEach(handle => {
        ctx.fillStyle = '#1976D2';
        ctx.fillRect(handle.x - 5, handle.y - 5, 10, 10);
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 1;
        ctx.strokeRect(handle.x - 5, handle.y - 5, 10, 10);
    });
};

// Draw arrow head
const drawArrowHead = (ctx, from, to, color) => {
    const headLength = 12;
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
    ctx.lineWidth = 3;
    ctx.stroke();
};

// Hex to RGBA
const hexToRgba = (hex, alpha) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    if (result) {
        return `rgba(${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}, ${alpha})`;
    }
    return `rgba(76, 175, 80, ${alpha})`;
};

// Zoom controls
const handleZoom = (e) => {
    const delta = e.deltaY > 0 ? -0.1 : 0.1;
    zoom.value = Math.max(0.25, Math.min(3, zoom.value + delta));
};

const zoomIn = () => {
    zoom.value = Math.min(3, zoom.value + 0.25);
};

const zoomOut = () => {
    zoom.value = Math.max(0.25, zoom.value - 0.25);
};

const resetZoom = () => {
    zoom.value = 1;
    panX.value = 0;
    panY.value = 0;
};

// Upload floor plan
const uploadFloorPlan = async () => {
    if (!selectedFile.value) return;
    
    uploading.value = true;
    
    try {
        const formData = new FormData();
        formData.append('floor_plan', selectedFile.value);
        
        const response = await fetch(`/floor-plans/${floorData.value.id}/upload`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            floorPlanUrl.value = data.data.floor_plan_url;
            showSnackbar('Floor plan uploaded successfully', 'success');
        } else {
            showSnackbar(data.message || 'Error uploading floor plan', 'error');
        }
    } catch (error) {
        console.error('Error uploading floor plan:', error);
        showSnackbar('Error uploading floor plan', 'error');
    } finally {
        uploading.value = false;
        selectedFile.value = null;
    }
};

// Delete floor plan
const deleteFloorPlan = async () => {
    try {
        const response = await fetch(`/floor-plans/${floorData.value.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            floorPlanUrl.value = '';
            rooms.value = [];
            paths.value = [];
            selectedItem.value = null;
            redraw();
            showSnackbar('Floor plan deleted', 'success');
        }
    } catch (error) {
        console.error('Error deleting floor plan:', error);
        showSnackbar('Error deleting floor plan', 'error');
    }
};

// Save annotations
const saveAnnotations = async () => {
    saving.value = true;
    
    try {
        const floorPlanData = {
            rooms: rooms.value.map(r => ({
                id: r.id,
                room_id: r.room_id,
                room_name: r.room_name,
                x: r.x,
                y: r.y,
                width: r.width,
                height: r.height,
                color: r.color
            })),
            evacuation_paths: paths.value.map(p => ({
                id: p.id,
                name: p.name,
                points: p.points,
                color: p.color,
                room_id: p.room_id
            }))
        };
        
        const response = await fetch(`/floor-plans/${floorData.value.id}/annotations`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ floor_plan_data: floorPlanData })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSnackbar('Annotations saved successfully', 'success');
        } else {
            showSnackbar(data.message || 'Error saving annotations', 'error');
        }
    } catch (error) {
        console.error('Error saving annotations:', error);
        showSnackbar('Error saving annotations', 'error');
    } finally {
        saving.value = false;
    }
};

// Navigation
const goBack = () => {
    window.history.back();
};

// Snackbar
const showSnackbar = (text, color) => {
    snackbarText.value = text;
    snackbarColor.value = color;
    snackbar.value = true;
};
</script>

<style scoped>
.canvas-card {
    min-height: 500px;
}

.canvas-container {
    overflow: auto;
    background-color: #e0e0e0;
    background-image: 
        linear-gradient(45deg, #ccc 25%, transparent 25%),
        linear-gradient(-45deg, #ccc 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #ccc 75%),
        linear-gradient(-45deg, transparent 75%, #ccc 75%);
    background-size: 20px 20px;
    background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
    min-height: 500px;
    max-height: 70vh;
}

.canvas-wrapper {
    position: relative;
    display: inline-block;
}

.floor-plan-image {
    display: block;
    max-width: none;
    user-select: none;
    pointer-events: none;
}

.drawing-canvas {
    position: absolute;
    top: 0;
    left: 0;
    cursor: crosshair;
}

.no-image-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background-color: #f5f5f5;
    border: 2px dashed #ccc;
}
</style>
