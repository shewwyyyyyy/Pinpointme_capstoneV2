<template>
    <v-app class="bg-user-gradient-light">
        <!-- Header - matches Rescuer Dashboard style -->
        <div class="scanner-header">
            <div class="header-content">
                <v-btn icon variant="text" @click="drawer = !drawer" class="menu-btn desktop-only">
                    <v-icon>mdi-menu</v-icon>
                </v-btn>
                <div class="header-title">
                    <h1>PinPointMe</h1>
                    <p>COMING YOUR WAY.</p>
                </div>
                <div class="header-actions">
                    <v-btn icon variant="text" @click="showHelp = true" class="action-btn">
                        <v-icon>mdi-help-circle-outline</v-icon>
                    </v-btn>
                    <v-btn icon variant="text" @click="showNotificationPanel = !showNotificationPanel" class="desktop-only action-btn">
                        <v-badge :content="totalNotificationCount" :model-value="totalNotificationCount > 0" color="error" overlap>
                            <v-icon>mdi-bell</v-icon>
                        </v-badge>
                    </v-btn>
                </div>
            </div>
        </div>

        <!-- Navigation Drawer - handles its own visibility -->
        <UserMenu v-model="drawer" />

        <!-- Notification Panel -->
        <v-navigation-drawer
            v-model="showNotificationPanel"
            location="right"
            temporary
            :width="notificationDrawerWidth"
            class="notification-drawer"
            touchless
        >
            <div class="notification-header">
                <v-icon color="white" class="mr-2">mdi-bell</v-icon>
                <div>
                    <h3>Notifications</h3>
                    <p>{{ totalNotificationCount }} notification{{ totalNotificationCount !== 1 ? 's' : '' }}</p>
                </div>
            </div>

            <!-- Active Rescue Section -->
            <div v-if="hasActiveRequest && activeRequest" class="notification-section">
                <div class="section-header warning">
                    <v-icon size="18" class="mr-2">mdi-ambulance</v-icon>
                    Active Rescue
                </div>
                <div class="notification-item" @click="goToActiveRescue">
                    <v-avatar :color="getStatusColor(activeRequest.status)" size="44">
                        <v-icon color="white">mdi-ambulance</v-icon>
                    </v-avatar>
                    <div class="notification-content">
                        <h4>Rescue {{ activeRequest.rescue_code }}</h4>
                        <v-chip :color="getStatusColor(activeRequest.status)" size="x-small" variant="flat" class="mt-1">
                            {{ formatStatus(activeRequest.status) }}
                        </v-chip>
                        <p class="time"><v-icon size="12">mdi-clock</v-icon> {{ formatTimeAgo(activeRequest.created_at) }}</p>
                    </div>
                    <v-icon size="20" color="grey">mdi-chevron-right</v-icon>
                </div>
            </div>

            <!-- Chat Messages Section -->
            <div v-if="unreadChats.length > 0" class="notification-section">
                <div class="section-header info">
                    <v-icon size="18" class="mr-2">mdi-chat</v-icon>
                    Unread Messages ({{ unreadMessageCount }})
                </div>
                <div v-for="chat in unreadChats" :key="chat.id" class="notification-item" @click="openChat(chat)">
                    <v-avatar color="info" size="44">
                        <v-img v-if="chat.other_user_picture" :src="chat.other_user_picture" cover />
                        <v-icon v-else color="white">mdi-account</v-icon>
                    </v-avatar>
                    <div class="notification-content">
                        <h4>{{ chat.other_user_name }}</h4>
                        <p class="message">{{ chat.last_message }}</p>
                    </div>
                    <v-badge :content="chat.unread_count" color="error" inline />
                </div>
            </div>

            <!-- No Notifications -->
            <div v-if="!hasActiveRequest && unreadChats.length === 0" class="no-notifications">
                <v-icon size="56" color="grey-lighten-1">mdi-bell-off-outline</v-icon>
                <p>No new notifications</p>
            </div>

            <template v-slot:append>
                <div class="notification-actions">
                    <v-btn v-if="hasActiveRequest" block color="warning" variant="flat" class="mb-2 rounded-lg" @click="showNotificationPanel = false; goToActiveRescue()">
                        <v-icon start>mdi-ambulance</v-icon>
                        View Active Rescue
                    </v-btn>
                    <v-btn block color="primary" variant="tonal" class="rounded-lg" @click="showNotificationPanel = false; goToInbox()">
                        <v-icon start>mdi-email</v-icon>
                        Open Inbox
                    </v-btn>
                </div>
            </template>
        </v-navigation-drawer>

        <v-main class="main-container">
            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <!-- Loading State -->
                <div v-if="isCheckingActiveRequest" class="loading-state">
                    <v-progress-circular indeterminate color="primary" size="56" width="5" />
                    <p>Checking for active rescue requests...</p>
                </div>
                
                <!-- Active Rescue Request Display -->
                <div v-else-if="hasActiveRequest && activeRequest" class="active-rescue-display">
                    <div class="active-hero">
                        <div class="active-hero-content">
                            <div class="active-icon-wrapper">
                                <v-avatar color="warning" size="80">
                                    <v-icon size="40" color="white">mdi-alert-circle</v-icon>
                                </v-avatar>
                                <div class="pulse-ring warning"></div>
                            </div>
                            <h2>Active Rescue Request</h2>
                            <p>You have an ongoing rescue request</p>
                        </div>
                    </div>
                    
                    <div class="active-content">
                        <v-card class="mb-4 rounded-xl" elevation="0">
                            <div class="active-card-header">
                                <v-chip :color="getStatusColor(activeRequest.status)" variant="flat">
                                    {{ formatStatus(activeRequest.status) }}
                                </v-chip>
                                <span class="rescue-code">{{ activeRequest.rescue_code }}</span>
                            </div>
                            <v-card-text>
                                <div class="active-details">
                                    <div class="detail-row" v-if="activeRequest.building || activeRequest.floor || activeRequest.room">
                                        <v-icon color="primary" size="20">mdi-map-marker</v-icon>
                                        <div>
                                            <span class="label">Location</span>
                                            <span class="value">
                                                {{ activeRequest.building?.name || 'N/A' }}
                                                <span v-if="activeRequest.floor"> - {{ activeRequest.floor.floor_name }}</span>
                                                <span v-if="activeRequest.room"> - {{ activeRequest.room.room_name }}</span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="detail-row" v-if="activeRequest.rescuer">
                                        <v-icon color="success" size="20">mdi-account-hard-hat</v-icon>
                                        <div>
                                            <span class="label">Assigned Rescuer</span>
                                            <span class="value">{{ activeRequest.rescuer.first_name }} {{ activeRequest.rescuer.last_name }}</span>
                                        </div>
                                    </div>
                                    <div class="detail-row" v-else>
                                        <v-icon color="grey" size="20">mdi-account-search</v-icon>
                                        <div>
                                            <span class="label">Rescuer</span>
                                            <span class="value text-grey">Waiting for assignment...</span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <v-icon color="primary" size="20">mdi-clock</v-icon>
                                        <div>
                                            <span class="label">Requested</span>
                                            <span class="value">{{ formatTimeAgo(activeRequest.created_at) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </v-card-text>
                            <v-card-actions class="pa-4 pt-0">
                                <v-btn color="primary" block size="large" class="rounded-xl" elevation="2" @click="goToActiveRescue">
                                    <v-icon start>mdi-eye</v-icon>
                                    View Status
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                        
                        <v-alert
                            :type="activeRequest?.status === 'rescued' || activeRequest?.status === 'completed' ? 'warning' : 'info'"
                            variant="tonal"
                            class="rounded-xl"
                        >
                            <v-alert-title v-if="activeRequest?.status === 'rescued' || activeRequest?.status === 'completed'" class="font-weight-bold">
                                Please confirm you are safe
                            </v-alert-title>
                            <v-alert-title v-else class="font-weight-bold">Need help?</v-alert-title>
                            <p class="mt-2 mb-0" v-if="activeRequest?.status === 'rescued' || activeRequest?.status === 'completed'">
                                Your rescue has been completed. Please confirm that you are safe before submitting a new rescue request.
                            </p>
                            <p class="mt-2 mb-0" v-else>
                                You can only have one active rescue request at a time. Wait for your current request to be completed.
                            </p>
                        </v-alert>
                    </div>
                </div>

                <!-- Normal Scanner Form -->
                <div v-else>
                    <!-- Hero Section -->
                    <div class="scanner-hero">
                        <div class="hero-content">
                            <v-icon size="56" color="white" class="mb-3">mdi-map-marker-radius</v-icon>
                            <h1>Emergency Scanner</h1>
                            <p>Scan your location or describe your emergency</p>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="scanner-content">
                        <!-- Quick Action Cards -->
                        <div class="action-cards mb-4">
                            <!-- QR Scan Card - Hidden on mobile/tablet (use bottom nav instead) -->
                            <div class="action-card primary hide-on-mobile" @click="startQrScan" :class="{ disabled: isScanning }">
                                <div class="action-icon">
                                    <v-icon size="36" color="white">mdi-qrcode-scan</v-icon>
                                </div>
                                <div class="action-text">
                                    <h3>Scan QR Code</h3>
                                    <p>Quick location detection</p>
                                </div>
                                <v-progress-circular v-if="isScanning" indeterminate size="24" color="white" />
                                <v-icon v-else color="white" size="20">mdi-chevron-right</v-icon>
                            </div>
                            <div class="action-card success" @click="toggleVoiceInput">
                                <div class="action-icon" :class="{ recording: isRecording }">
                                    <v-icon size="36" color="white">{{ isRecording ? 'mdi-microphone-off' : 'mdi-microphone' }}</v-icon>
                                </div>
                                <div class="action-text">
                                    <h3>{{ isRecording ? 'Stop Recording' : 'Voice Command' }}</h3>
                                    <p>{{ isRecording ? formatRecordingTime : 'Say location & emergency' }}</p>
                                </div>
                                <v-progress-circular v-if="isProcessingAudio" indeterminate size="24" color="white" />
                                <v-icon v-else color="white" size="20">mdi-chevron-right</v-icon>
                            </div>
                        </div>
                        
                        <!-- Voice Command Tips (shown when recording) -->
                        <v-alert 
                            v-if="isRecording" 
                            type="info" 
                            variant="tonal" 
                            class="mb-4 rounded-xl voice-tips-alert"
                            density="compact"
                        >
                            <div class="voice-tips">
                                <div class="voice-tip-header">
                                    <v-icon size="20" class="mr-2 pulse-icon">mdi-microphone</v-icon>
                                    <strong>Listening... Speak your location clearly:</strong>
                                </div>
                                <div class="voice-examples mt-2">
                                    <div class="text-caption mb-1">📍 <strong>Location:</strong> "Building A, Floor 2, Room 201"</div>
                                    <div class="text-caption mb-1">🆘 <strong>Emergency:</strong> "I need help, I'm injured"</div>
                                    <div class="text-caption text-grey-darken-1" style="font-size: 11px;">
                                        Tip: Say building name, floor number, and room number
                                    </div>
                                </div>
                            </div>
                        </v-alert>

                        <!-- Location Status Card -->
                        <v-card v-if="selectedBuilding || selectedFloor || selectedRoom" class="mb-4 rounded-xl location-status-card" :class="{ scanned: locationScanned }" elevation="0" ref="locationCard">
                            <div class="location-status-header" :class="{ success: locationScanned }">
                                <v-icon :color="locationScanned ? 'white' : 'primary'" size="24">
                                    {{ locationScanned ? 'mdi-check-circle' : 'mdi-map-marker' }}
                                </v-icon>
                                <span>{{ locationScanned ? 'Location Scanned!' : 'Current Location' }}</span>
                                <v-chip v-if="locationScanned" size="x-small" color="white" variant="flat" class="ml-auto">
                                    <v-icon start size="12">mdi-qrcode-scan</v-icon>
                                    QR
                                </v-chip>
                            </div>
                            <v-card-text>
                                <div class="location-chips">
                                    <v-chip v-if="selectedBuilding" color="primary" variant="tonal" size="small">
                                        <v-icon start size="14">mdi-office-building</v-icon>
                                        {{ selectedBuilding.name }}
                                    </v-chip>
                                    <v-chip v-if="selectedFloor" color="secondary" variant="tonal" size="small">
                                        <v-icon start size="14">mdi-stairs</v-icon>
                                        {{ selectedFloor.floor_name }}
                                    </v-chip>
                                    <v-chip v-if="selectedRoom" color="success" variant="tonal" size="small">
                                        <v-icon start size="14">mdi-door</v-icon>
                                        {{ selectedRoom.room_name }}
                                    </v-chip>
                                </div>
                            </v-card-text>
                        </v-card>

                        <!-- Manual Location Selection -->
                        <v-card class="mb-4 rounded-xl manual-location-card" elevation="0">
                            <div class="manual-location-header">
                                <v-avatar color="grey-lighten-2" size="40" class="mr-3">
                                    <v-icon color="grey-darken-1" size="20">mdi-map-marker-plus</v-icon>
                                </v-avatar>
                                <div>
                                    <h3 class="text-subtitle-1 font-weight-bold mb-0">Manual Location</h3>
                                    <p class="text-caption text-grey mb-0">Select your location manually</p>
                                </div>
                            </div>
                            <v-card-text class="pt-3">
                                <v-row dense>
                                    <v-col cols="12" sm="4">
                                        <v-select
                                            v-model="selectedBuilding"
                                            :items="buildings"
                                            item-title="name"
                                            label="Building"
                                            variant="outlined"
                                            density="comfortable"
                                            return-object
                                            :loading="isLoadingBuildings"
                                            @update:model-value="onBuildingChange"
                                            hide-details
                                            prepend-inner-icon="mdi-office-building"
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="4">
                                        <v-select
                                            v-model="selectedFloor"
                                            :items="availableFloors"
                                            item-title="floor_name"
                                            label="Floor"
                                            variant="outlined"
                                            density="comfortable"
                                            return-object
                                            :disabled="!selectedBuilding"
                                            @update:model-value="onFloorChange"
                                            hide-details
                                            prepend-inner-icon="mdi-stairs"
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="4">
                                        <v-select
                                            v-model="selectedRoom"
                                            :items="availableRooms"
                                            item-title="room_name"
                                            label="Room"
                                            variant="outlined"
                                            density="comfortable"
                                            return-object
                                            :disabled="!selectedFloor"
                                            hide-details
                                            prepend-inner-icon="mdi-door"
                                        />
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>

                        <!-- Evacuation Path (Collapsible) -->
                        <v-expansion-panels v-if="selectedRoom && hasFloorPlan" class="mb-4" variant="accordion">
                            <v-expansion-panel elevation="0" class="rounded-xl">
                                <v-expansion-panel-title class="py-3">
                                    <div class="d-flex align-center">
                                        <v-avatar color="error-lighten-4" size="40" class="mr-3">
                                            <v-icon color="error" size="20">mdi-exit-run</v-icon>
                                        </v-avatar>
                                        <div>
                                            <h3 class="text-subtitle-1 font-weight-bold mb-0">Evacuation Path</h3>
                                            <p class="text-caption text-grey mb-0">{{ selectedRoom.room_name }} - {{ selectedFloor?.floor_name }}</p>
                                        </div>
                                    </div>
                                </v-expansion-panel-title>
                                <v-expansion-panel-text>
                                    <div class="evacuation-canvas-container">
                                        <div class="evacuation-canvas-wrapper" :style="evacuationWrapperStyle">
                                            <img ref="evacuationImage" :src="selectedFloor.floor_plan_url" class="evacuation-image" @load="onEvacuationImageLoad" />
                                            <canvas ref="evacuationCanvas" class="evacuation-overlay"></canvas>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-center align-center mt-3 gap-2">
                                        <v-btn size="small" icon variant="tonal" @click="evacuationZoomOut"><v-icon>mdi-minus</v-icon></v-btn>
                                        <v-chip size="small">{{ Math.round(evacuationZoom * 100) }}%</v-chip>
                                        <v-btn size="small" icon variant="tonal" @click="evacuationZoomIn"><v-icon>mdi-plus</v-icon></v-btn>
                                        <v-btn size="small" icon variant="tonal" @click="resetEvacuationZoom"><v-icon>mdi-restore</v-icon></v-btn>
                                    </div>
                                    <div class="evacuation-legend mt-3">
                                        <div class="legend-item"><div class="legend-line"></div><span>Evacuation Path</span></div>
                                        <div class="legend-item"><div class="legend-room"></div><span>Your Room</span></div>
                                    </div>
                                </v-expansion-panel-text>
                            </v-expansion-panel>
                        </v-expansion-panels>

                        <!-- Emergency Form -->
                        <v-card id="emergency-form-section" class="mb-4 rounded-xl emergency-form-card" elevation="0">
                            <div class="emergency-form-header">
                                <v-icon color="white" size="24" class="mr-2">mdi-alert-circle</v-icon>
                                <span>Emergency Details</span>
                                <v-chip v-if="canSubmit" size="x-small" color="white" variant="flat" class="ml-auto">
                                    <v-icon start size="12">mdi-check</v-icon>
                                    Ready
                                </v-chip>
                            </div>
                            <v-card-text class="pt-4">
                                <v-form ref="emergencyFormRef">
                                    <v-row dense>
                                        <v-col cols="12" class="pb-0">
                                            <div class="samaritan-note mb-2">
                                                <v-icon size="14" color="info" class="mr-1">mdi-information</v-icon>
                                                <span class="text-caption text-grey-darken-1">Update the name if reporting for someone else (Samaritan Report)</span>
                                            </div>
                                        </v-col>
                                        <v-col cols="6">
                                            <v-text-field
                                                v-model="emergencyForm.firstName"
                                                label="First Name"
                                                variant="outlined"
                                                density="comfortable"
                                                prepend-inner-icon="mdi-account"
                                                hide-details
                                                placeholder="Person in need"
                                            />
                                        </v-col>
                                        <v-col cols="6">
                                            <v-text-field
                                                v-model="emergencyForm.lastName"
                                                label="Last Name"
                                                variant="outlined"
                                                density="comfortable"
                                                prepend-inner-icon="mdi-account"
                                                hide-details
                                                placeholder="Person in need"
                                            />
                                        </v-col>
                                        <v-col cols="12">
                                            <v-textarea
                                                v-model="emergencyForm.description"
                                                label="Describe your emergency"
                                                variant="outlined"
                                                rows="2"
                                                prepend-inner-icon="mdi-text"
                                                hide-details
                                                class="mt-3"
                                            />
                                        </v-col>
                                        <v-col cols="6">
                                            <v-select
                                                v-model="emergencyForm.mobilityStatus"
                                                :items="mobilityOptions"
                                                label="Mobility"
                                                variant="outlined"
                                                density="comfortable"
                                                prepend-inner-icon="mdi-walk"
                                                hide-details
                                                class="mt-3"
                                            />
                                        </v-col>
                                        <v-col cols="6">
                                            <v-select
                                                v-model="emergencyForm.urgencyLevel"
                                                :items="urgencyOptions"
                                                label="Urgency"
                                                variant="outlined"
                                                density="comfortable"
                                                prepend-inner-icon="mdi-speedometer"
                                                hide-details
                                                class="mt-3"
                                            />
                                        </v-col>
                                        <v-col cols="12">
                                            <v-select
                                                v-model="emergencyForm.injuries"
                                                :items="injuryOptions"
                                                label="Injuries"
                                                variant="outlined"
                                                density="comfortable"
                                                prepend-inner-icon="mdi-medical-bag"
                                                multiple
                                                chips
                                                closable-chips
                                                hide-details
                                                class="mt-3"
                                            />
                                        </v-col>
                                        <v-col cols="12" v-if="emergencyForm.injuries?.includes('other')">
                                            <v-text-field
                                                v-model="emergencyForm.otherInjury"
                                                label="Specify Other Injury"
                                                variant="outlined"
                                                density="comfortable"
                                                prepend-inner-icon="mdi-pencil"
                                                hide-details
                                                class="mt-3"
                                            />
                                        </v-col>
                                        
                                        <!-- Media Attachments Section -->
                                        <v-col cols="12">
                                            <div class="media-attachments-section mt-3">
                                                <div class="section-label mb-2">
                                                    <v-icon size="18" color="grey-darken-1" class="mr-1">mdi-paperclip</v-icon>
                                                    <span class="text-body-2 font-weight-medium">Attach Photos/Videos (Optional)</span>
                                                    <v-chip size="x-small" color="grey" variant="tonal" class="ml-2">
                                                        {{ mediaFiles.length }}/5
                                                    </v-chip>
                                                </div>
                                                
                                                <!-- Media Preview Grid -->
                                                <div v-if="mediaFiles.length > 0" class="media-preview-grid mb-3">
                                                    <div 
                                                        v-for="(file, index) in mediaFiles" 
                                                        :key="index" 
                                                        class="media-preview-item"
                                                        @click="previewMedia(file)"
                                                    >
                                                        <!-- Image Preview -->
                                                        <img 
                                                            v-if="file.type.startsWith('image/')" 
                                                            :src="file.preview" 
                                                            class="media-thumbnail"
                                                            alt="Preview"
                                                        />
                                                        <!-- Video Preview -->
                                                        <div v-else-if="file.type.startsWith('video/')" class="video-thumbnail">
                                                            <video :src="file.preview" class="media-thumbnail" muted></video>
                                                            <div class="video-overlay">
                                                                <v-icon color="white" size="24">mdi-play-circle</v-icon>
                                                            </div>
                                                        </div>
                                                        <!-- File Info -->
                                                        <div class="media-info">
                                                            <span class="file-size">{{ formatFileSize(file.size) }}</span>
                                                        </div>
                                                        <!-- Remove Button -->
                                                        <v-btn
                                                            icon
                                                            size="x-small"
                                                            color="error"
                                                            variant="flat"
                                                            class="remove-media-btn"
                                                            @click.stop="removeMediaFile(index)"
                                                        >
                                                            <v-icon size="14">mdi-close</v-icon>
                                                        </v-btn>
                                                    </div>
                                                    
                                                    <!-- Add More Button (if less than 5) -->
                                                    <div 
                                                        v-if="mediaFiles.length < 5" 
                                                        class="media-preview-item add-more"
                                                        @click="triggerMediaInput"
                                                    >
                                                        <v-icon size="28" color="grey">mdi-plus</v-icon>
                                                        <span class="text-caption text-grey">Add</span>
                                                    </div>
                                                </div>
                                                
                                                <!-- Upload Buttons (when no files) -->
                                                <div v-else class="media-upload-buttons">
                                                    <v-btn
                                                        variant="outlined"
                                                        color="primary"
                                                        size="small"
                                                        class="mr-2"
                                                        @click="triggerMediaInput"
                                                    >
                                                        <v-icon start size="18">mdi-image-plus</v-icon>
                                                        Photo
                                                    </v-btn>
                                                    <v-btn
                                                        variant="outlined"
                                                        color="primary"
                                                        size="small"
                                                        class="mr-2"
                                                        @click="triggerVideoInput"
                                                    >
                                                        <v-icon start size="18">mdi-video-plus</v-icon>
                                                        Video
                                                    </v-btn>
                                                    <v-btn
                                                        variant="outlined"
                                                        color="secondary"
                                                        size="small"
                                                        @click="openCamera"
                                                    >
                                                        <v-icon start size="18">mdi-camera</v-icon>
                                                        Camera
                                                    </v-btn>
                                                </div>
                                                
                                                <!-- Hidden File Inputs -->
                                                <input
                                                    ref="mediaInputRef"
                                                    type="file"
                                                    accept="image/*,video/*"
                                                    multiple
                                                    style="display: none"
                                                    @change="handleMediaSelect"
                                                />
                                                <input
                                                    ref="videoInputRef"
                                                    type="file"
                                                    accept="video/*"
                                                    style="display: none"
                                                    @change="handleMediaSelect"
                                                />
                                                <input
                                                    ref="cameraInputRef"
                                                    type="file"
                                                    accept="image/*"
                                                    capture="environment"
                                                    style="display: none"
                                                    @change="handleMediaSelect"
                                                />
                                                
                                                <p class="text-caption text-grey mt-2">
                                                    <v-icon size="12">mdi-information</v-icon>
                                                    Max 5 files, 10MB each. Photos & videos help rescuers assess the situation.
                                                </p>
                                            </div>
                                        </v-col>
                                    </v-row>
                                </v-form>
                            </v-card-text>
                            <v-card-actions class="pa-4 pt-2">
                                <v-btn
                                    color="error"
                                    size="large"
                                    variant="flat"
                                    block
                                    :loading="isSubmitting"
                                    :disabled="!canSubmit"
                                    @click="submitRescueRequest"
                                    class="rounded-xl submit-btn"
                                    elevation="2"
                                >
                                    <v-icon start>mdi-send</v-icon>
                                    Request Rescue
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </div>
                </div>
            </div>

            <!-- QR Scanner Dialog -->
            <v-dialog v-model="showQrScanner" fullscreen>
                <v-card class="fill-height qr-scanner-dialog" color="black">
                    <div class="qr-scanner-header">
                        <v-btn icon variant="text" @click="closeQrScanner">
                            <v-icon color="white">mdi-close</v-icon>
                        </v-btn>
                        <span class="qr-title">Scan QR Code</span>
                        
                        <!-- Camera Control Buttons -->
                        <div class="camera-controls-header">
                            <v-btn 
                                icon 
                                variant="text" 
                                @click="toggleFlash"
                                :color="flashEnabled ? 'yellow' : 'white'"
                                :disabled="!flashSupported"
                                :title="flashSupported ? (flashEnabled ? 'Turn off flash' : 'Turn on flash') : 'Flash not supported'"
                            >
                                <v-icon>{{ flashEnabled ? 'mdi-flashlight' : 'mdi-flashlight-off' }}</v-icon>
                            </v-btn>
                            <v-btn 
                                icon 
                                variant="text" 
                                color="white"
                                @click="flipCamera"
                                title="Switch camera"
                            >
                                <v-icon>mdi-camera-flip</v-icon>
                            </v-btn>
                        </div>
                    </div>
                    <v-card-text class="d-flex flex-column align-center justify-center fill-height pa-0 qr-scanner-content">
                        <div id="qr-reader" style="width: 100%; max-width: 500px;"></div>
                        
                        <!-- Scanning Tips -->
                        <div class="scanning-tips">
                            <p class="text-white text-center mt-3 mb-1" style="font-size: 1rem; font-weight: 500;">
                                <v-icon color="white" size="18" class="mr-1">mdi-qrcode-scan</v-icon>
                                Point camera at QR code
                            </p>
                            <p class="text-white text-center mb-2" style="font-size: 0.8rem; opacity: 0.8;">
                                Keep the QR code within the frame • Move closer if needed
                            </p>
                        </div>
                        
                        <!-- Camera Controls Bottom Panel -->
                        <div class="camera-controls-panel" v-if="isScanning">
                            <!-- Zoom Controls -->
                            <div class="zoom-controls-inline">
                                <v-btn 
                                    icon 
                                    size="small" 
                                    variant="tonal" 
                                    color="white"
                                    @click="decreaseZoom"
                                    :disabled="currentZoom <= minZoom"
                                >
                                    <v-icon size="20">mdi-magnify-minus</v-icon>
                                </v-btn>
                                <div class="zoom-value">{{ Math.round(currentZoom * 10) / 10 }}x</div>
                                <v-btn 
                                    icon 
                                    size="small" 
                                    variant="tonal" 
                                    color="white"
                                    @click="increaseZoom"
                                    :disabled="currentZoom >= maxZoom"
                                >
                                    <v-icon size="20">mdi-magnify-plus</v-icon>
                                </v-btn>
                            </div>
                            
                            <!-- Flash indicator when on -->
                            <div v-if="flashEnabled" class="flash-indicator">
                                <v-icon color="yellow" size="16">mdi-flashlight</v-icon>
                                <span>Flash ON</span>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-dialog>

            <!-- Help Dialog -->
            <v-dialog v-model="showHelp" max-width="450">
                <v-card class="rounded-xl">
                    <v-card-text class="pa-0">
                        <div class="help-header">
                            <v-icon size="40" color="white">mdi-help-circle</v-icon>
                            <h2>How to Use</h2>
                        </div>
                        <div class="help-steps pa-4">
                            <div class="help-step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>Scan QR Code</h4>
                                    <p>Scan the room's QR code to automatically set your location</p>
                                </div>
                            </div>
                            <div class="help-step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Or Select Manually</h4>
                                    <p>Choose your building, floor, and room from the dropdowns</p>
                                </div>
                            </div>
                            <div class="help-step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>Describe Emergency</h4>
                                    <p>Fill in the emergency details or use voice input</p>
                                </div>
                            </div>
                            <div class="help-step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h4>Request Rescue</h4>
                                    <p>Submit your request and wait for help</p>
                                </div>
                            </div>
                        </div>
                    </v-card-text>
                    <v-card-actions class="pa-4 pt-0">
                        <v-btn color="primary" block class="rounded-lg" @click="showHelp = false">Got it</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Media Preview Dialog -->
            <v-dialog v-model="showMediaPreview" max-width="600" content-class="media-preview-dialog">
                <v-card class="rounded-xl" color="black">
                    <v-card-title class="d-flex justify-space-between align-center pa-3">
                        <span class="text-white text-body-1">{{ previewMediaFile?.name }}</span>
                        <v-btn icon variant="text" @click="showMediaPreview = false">
                            <v-icon color="white">mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>
                    <v-card-text class="pa-0 d-flex justify-center align-center" style="min-height: 300px;">
                        <!-- Image Preview -->
                        <img 
                            v-if="previewMediaFile?.type?.startsWith('image/')" 
                            :src="previewMediaFile?.preview" 
                            style="max-width: 100%; max-height: 70vh; object-fit: contain;"
                            alt="Preview"
                        />
                        <!-- Video Preview -->
                        <video 
                            v-else-if="previewMediaFile?.type?.startsWith('video/')" 
                            :src="previewMediaFile?.preview" 
                            controls
                            style="max-width: 100%; max-height: 70vh;"
                        ></video>
                    </v-card-text>
                    <v-card-actions class="pa-3">
                        <v-chip size="small" color="grey-darken-3" variant="flat">
                            <v-icon start size="14">{{ previewMediaFile?.type?.startsWith('video/') ? 'mdi-video' : 'mdi-image' }}</v-icon>
                            {{ formatFileSize(previewMediaFile?.size || 0) }}
                        </v-chip>
                        <v-spacer></v-spacer>
                        <v-btn 
                            color="error" 
                            variant="tonal" 
                            size="small"
                            @click="removePreviewedMedia"
                        >
                            <v-icon start size="16">mdi-delete</v-icon>
                            Remove
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Toast Notification -->
            <v-snackbar v-model="showToast" :color="toastColor" location="top">
                {{ toastMessage }}
                <template v-slot:actions>
                    <v-btn variant="text" @click="showToast = false">Close</v-btn>
                </template>
            </v-snackbar>
            
            <!-- Popup Notification Alert -->
            <NotificationPopup
                :show="popupAlert.show"
                :title="popupAlert.title"
                :message="popupAlert.message"
                :type="popupAlert.type"
                :icon="popupAlert.icon"
                @close="popupAlert.show = false"
                @click="handlePopupClick"
            />
        </v-main>
        
        <!-- Bottom Navigation for Mobile -->
        <UserBottomNav 
            :notification-count="totalNotificationCount" 
            :message-count="unreadMessageCount"
            :show-notification-panel="showNotificationPanel"
            @toggle-notifications="showNotificationPanel = !showNotificationPanel"
        />
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Html5Qrcode } from 'html5-qrcode';
import {
    getBuildingsFullStructure,
    createRescueRequest,
    transcribeAudio,
    extractFieldsAndInferLocation,
    getProfilePictureUrl,
    getUserActiveRescueRequest,
    getConversations,
} from '@/Composables/useApi';
import { useAudioRecording } from '@/Composables/useAudioRecording';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import UserMenu from '@/Components/Pages/User/Menu/UserMenu.vue';
import UserBottomNav from '@/Components/Pages/User/Menu/UserBottomNav.vue';
import NotificationPopup from '@/Components/NotificationPopup.vue';

// Get Inertia page for auth
const page = usePage();
const authUser = computed(() => page.props?.auth?.user);

// Notification Alert System
const { playNotificationSound, vibrate } = useNotificationAlert();

// Popup alert state
const popupAlert = ref({
    show: false,
    title: '',
    message: '',
    type: 'info',
    icon: 'mdi-bell',
    callback: null,
});

// Polling interval
let pollingInterval = null;
const POLLING_INTERVAL = 10000; // 10 seconds

// Track previous state for detecting changes
const previousStatus = ref(null);
const previousUnreadCount = ref(0);

// Simple route helper since ziggy is not available
const route = (name, params = {}) => {
    const routes = {
        'user.help-coming': (code) => `/user/help-coming/${code}`,
    };
    return routes[name] ? routes[name](params) : `/${name}`;
};

// Navigation
const drawer = ref(false);

// Notification Panel
const showNotificationPanel = ref(false);
const conversations = ref([]);

// Responsive notification drawer width
const notificationDrawerWidth = computed(() => {
    if (typeof window !== 'undefined') {
        return window.innerWidth < 400 ? window.innerWidth * 0.9 : 320;
    }
    return 320;
});

// Computed unread chats
const unreadChats = computed(() => {
    return conversations.value.filter(c => c.unread_count > 0);
});

// Computed unread message count
const unreadMessageCount = computed(() => {
    return conversations.value.reduce((acc, c) => acc + (c.unread_count || 0), 0);
});

// Total notification count (active rescue + unread messages)
const totalNotificationCount = computed(() => {
    let count = unreadMessageCount.value;
    if (hasActiveRequest.value) count += 1;
    return count;
});

// Format time ago for notifications
const formatTimeAgo = (dateString) => {
    if (!dateString) return 'Unknown';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
};

// Navigate to inbox
const goToInbox = () => {
    router.visit('/user/inbox');
};

// Open chat from notification
const openChat = (chat) => {
    showNotificationPanel.value = false;
    router.visit(`/user/chat/${chat.id}`);
};

// Fetch conversations for notifications
const fetchConversations = async () => {
    try {
        if (!userData.value?.id) return;
        
        // Store previous unread counts for detecting new messages
        const previousUnreadCounts = {};
        conversations.value.forEach(c => {
            previousUnreadCounts[c.id] = c.unread_count || 0;
        });
        const wasEmpty = conversations.value.length === 0;
        
        const response = await getConversations(userData.value.id);
        const data = response.data || response;
        const convList = Array.isArray(data) ? data : (data?.data || []);
        
        const newConversations = convList.map((conv) => {
            // Find the CURRENT user's participant record (for unread_count)
            const myParticipant = conv.participants?.find(
                (p) => String(p.user_id) === String(userData.value.id)
            );
            
            // Find the OTHER participant (for display name/picture)
            const otherParticipant = conv.participants?.find(
                (p) => String(p.user_id) !== String(userData.value.id)
            );
            
            const otherUser = otherParticipant?.user;
            
            return {
                id: conv.id,
                other_user_name: otherUser
                    ? `${otherUser.first_name || ''} ${otherUser.last_name || ''}`.trim() || 'Rescuer'
                    : 'Rescuer',
                other_user_picture: otherUser?.profile_picture 
                    ? getProfilePictureUrl(otherUser.profile_picture)
                    : null,
                last_message: conv.last_message?.content || 'No messages yet',
                last_message_time: conv.last_message?.timestamp || conv.updated_at,
                // Use CURRENT user's unread_count (what I haven't read)
                unread_count: myParticipant?.unread_count || 0,
            };
        });
        
        // Note: Chat notifications are handled by the Chat module itself
        // We only track unread counts here for the badge display
        
        conversations.value = newConversations;
    } catch (err) {
        console.error('Error fetching conversations:', err);
    }
};

// User data for profile picture
const userData = ref(null);

// Computed property for user's profile picture
const userProfilePicture = computed(() => {
    const picturePath = userData.value?.profile_picture || userData.value?.avatar;
    if (!picturePath) return null;
    if (picturePath.startsWith('http') || picturePath.startsWith('data:')) return picturePath;
    return getProfilePictureUrl(picturePath);
});

// Navigate to profile
const goToProfile = () => {
    router.visit('/user/profile');
};

// Buildings data
const buildings = ref([]);
const isLoadingBuildings = ref(false);
const selectedBuilding = ref(null);
const selectedFloor = ref(null);
const selectedRoom = ref(null);
const locationScanned = ref(false); // Track if location was scanned via QR
const locationCard = ref(null); // Reference to location card for scrolling
const emergencyFormCard = ref(null); // Reference to emergency form card

// Evacuation path display refs
const evacuationImage = ref(null);
const evacuationCanvas = ref(null);
const evacuationZoom = ref(1);
const evacuationWrapperStyle = computed(() => ({
    transform: `scale(${evacuationZoom.value})`,
    transformOrigin: 'top left'
}));

// Camera zoom controls
const zoomSupported = ref(false);
const currentZoom = ref(1);
const minZoom = ref(1);
const maxZoom = ref(4);
let videoTrack = null;

// Camera flash and flip controls
const flashSupported = ref(false);
const flashEnabled = ref(false);
const currentFacingMode = ref('environment'); // 'environment' (back) or 'user' (front)

// Active rescue request check
const hasActiveRequest = ref(false);
const activeRequest = ref(null);
const isCheckingActiveRequest = ref(true);

// Computed properties for cascading selects
const availableFloors = computed(() => {
    return selectedBuilding.value?.floors || [];
});

const availableRooms = computed(() => {
    return selectedFloor.value?.rooms || [];
});

const hasFloorPlan = computed(() => {
    return selectedFloor.value?.floor_plan_url && selectedFloor.value?.floor_plan_data;
});

const evacuationPaths = computed(() => {
    if (!selectedFloor.value?.floor_plan_data) return [];
    const paths = selectedFloor.value.floor_plan_data.evacuation_paths || [];
    // Filter paths for selected room
    if (selectedRoom.value) {
        return paths.filter(path => path.room_id === selectedRoom.value.id);
    }
    return [];
});

const floorPlanRooms = computed(() => {
    if (!selectedFloor.value?.floor_plan_data) return [];
    return selectedFloor.value.floor_plan_data.rooms || [];
});

// QR Scanner
const showQrScanner = ref(false);
const isScanning = ref(false);
let html5QrCode = null;

// Audio Recording
const {
    isRecording,
    recordingTime,
    startRecording: startAudioRecording,
    stopRecording: stopAudioRecording,
    cancelRecording,
    formatTime,
} = useAudioRecording();
const isProcessingAudio = ref(false);

const formatRecordingTime = computed(() => formatTime(recordingTime.value));

// Emergency Form
const emergencyFormRef = ref(null);
const emergencyForm = ref({
    firstName: '',
    lastName: '',
    description: '',
    mobilityStatus: '',
    urgencyLevel: '',
    injuries: [],
    otherInjury: '',
    additionalInfo: '',
});

// Media Attachments
const mediaFiles = ref([]);
const mediaInputRef = ref(null);
const videoInputRef = ref(null);
const cameraInputRef = ref(null);
const showMediaPreview = ref(false);
const previewMediaFile = ref(null);
const MAX_FILES = 5;
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

const mobilityOptions = [
    { title: 'Can walk normally', value: 'normal' },
    { title: 'Limited mobility', value: 'limited' },
    { title: 'Cannot move / Immobile', value: 'immobile' },
    { title: 'Unknown', value: 'unknown' },
];

const urgencyOptions = [
    { title: 'Low - Not life threatening', value: 'low' },
    { title: 'Medium - Needs attention soon', value: 'medium' },
    { title: 'High - Urgent attention needed', value: 'high' },
    { title: 'Critical - Life threatening', value: 'critical' },
];

const injuryOptions = [
    { title: 'None', value: 'none' },
    { title: 'Bleeding / Cut', value: 'bleeding' },
    { title: 'Fracture / Broken Bone', value: 'fracture' },
    { title: 'Burn', value: 'burn' },
    { title: 'Head Injury', value: 'head' },
    { title: 'Breathing Difficulty', value: 'breathing' },
    { title: 'Unconscious', value: 'unconscious' },
    { title: 'Chest Pain', value: 'chest_pain' },
    { title: 'Seizure', value: 'seizure' },
    { title: 'Allergic Reaction', value: 'allergic' },
    { title: 'Other', value: 'other' },
];

// Submission
const isSubmitting = ref(false);
const canSubmit = computed(() => {
    return selectedBuilding.value && selectedFloor.value && selectedRoom.value;
});

// UI State
const showHelp = ref(false);
const showToast = ref(false);
const toastMessage = ref('');
const toastColor = ref('success');

// Load user data
onMounted(async () => {
    // Check authentication via Inertia first
    if (!authUser.value) {
        console.warn('No authenticated user, redirecting to login');
        router.visit('/login');
        return;
    }
    
    // Use Inertia auth user data
    const user = authUser.value;
    userData.value = {
        id: user.id,
        email: user.email,
        firstName: user.first_name || '',
        lastName: user.last_name || '',
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        role: user.role || 'student',
        isAdmin: user.isAdmin === true || user.isAdmin === 1 || user.role === 'admin',
        profile_picture: user.profile_picture || null,
        contact_number: user.contact_number || '',
    };
    
    // Also save to localStorage for components that need it
    localStorage.setItem('userData', JSON.stringify(userData.value));
    
    // Pre-fill name from user data
    emergencyForm.value.firstName = userData.value.first_name || userData.value.firstName || '';
    emergencyForm.value.lastName = userData.value.last_name || userData.value.lastName || '';
    
    // Load buildings FIRST before checking active request
    await loadBuildings();
    
    // Check if user has active rescue request
    await checkActiveRescueRequest();
    
    // Fetch conversations for notification count
    await fetchConversations();
    
    // Initialize previous states for change detection
    previousStatus.value = activeRequest.value?.status || null;
    previousUnreadCount.value = unreadMessageCount.value;
    
    // Start polling for updates
    startPolling();
    
    // Check if we should auto-open the QR scanner (from bottom nav or query param)
    await nextTick();
    checkAutoOpenScanner();
});

// Check if scanner should auto-open based on URL params
const checkAutoOpenScanner = () => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const shouldScan = urlParams.get('scan') === 'true';
        const mode = urlParams.get('mode');
        
        // Only auto-open if no active request
        if (!hasActiveRequest.value && !isCheckingActiveRequest.value) {
            if (shouldScan) {
                // Auto-open scanner
                setTimeout(() => {
                    startQrScan();
                }, 300);
                
                // Clean up URL (remove query params)
                const cleanUrl = window.location.pathname;
                window.history.replaceState({}, '', cleanUrl);
            } else if (mode === 'voice') {
                // Auto-start voice input
                setTimeout(() => {
                    toggleVoiceInput();
                }, 300);
                
                // Clean up URL
                const cleanUrl = window.location.pathname;
                window.history.replaceState({}, '', cleanUrl);
            } else if (mode === 'manual') {
                // Scroll to manual location section
                // Clean up URL
                const cleanUrl = window.location.pathname;
                window.history.replaceState({}, '', cleanUrl);
            }
        }
    }
};

// Start polling for status updates and new messages
const startPolling = () => {
    if (pollingInterval) clearInterval(pollingInterval);
    
    pollingInterval = setInterval(async () => {
        await pollForUpdates();
    }, POLLING_INTERVAL);
};

// Poll for updates
const pollForUpdates = async () => {
    try {
        // Check for rescue status changes
        if (userData.value?.id) {
            const response = await getUserActiveRescueRequest(userData.value.id);
            
            if (response.has_active && response.data) {
                const newStatus = response.data.status;
                const oldStatus = previousStatus.value;
                
                // Check if status changed
                if (oldStatus && oldStatus !== newStatus) {
                    triggerStatusChangeNotification(oldStatus, newStatus, response.data);
                }
                
                hasActiveRequest.value = true;
                activeRequest.value = response.data;
                previousStatus.value = newStatus;
            } else {
                hasActiveRequest.value = false;
                activeRequest.value = null;
                previousStatus.value = null;
            }
        }
        
        // Check for new messages
        await fetchConversations();
        const newUnreadCount = unreadMessageCount.value;
        
        if (newUnreadCount > previousUnreadCount.value) {
            triggerNewMessageNotification(newUnreadCount - previousUnreadCount.value);
        }
        
        previousUnreadCount.value = newUnreadCount;
        
    } catch (error) {
        console.error('Polling error:', error);
    }
};

// Trigger notification for status change
const triggerStatusChangeNotification = (oldStatus, newStatus, request) => {
    const statusMessages = {
        'accepted': { title: '🚨 Rescuer Assigned!', message: 'A rescuer has accepted your request and is on the way.', type: 'success', icon: 'mdi-account-hard-hat' },
        'in_progress': { title: '🏃 Rescue In Progress', message: 'The rescuer is now heading to your location.', type: 'info', icon: 'mdi-run' },
        'en_route': { title: '🚑 Rescuer En Route', message: 'Help is on the way! Stay calm and wait for assistance.', type: 'info', icon: 'mdi-ambulance' },
        'rescued': { title: '✅ You\'ve Been Rescued!', message: 'Please confirm you are safe to complete the rescue.', type: 'success', icon: 'mdi-check-circle' },
        'completed': { title: '✅ Rescue Completed', message: 'Your rescue has been marked as complete.', type: 'success', icon: 'mdi-check-circle' },
    };
    
    const notification = statusMessages[newStatus];
    if (notification) {
        showPopupNotification(
            notification.title,
            notification.message,
            notification.type,
            notification.icon,
            () => goToActiveRescue()
        );
    }
};

// Note: Chat notifications are handled within the Chat module itself
// Removed triggerChatNotification to avoid duplicate notifications

// Trigger notification for new messages (generic count-based)
const triggerNewMessageNotification = (newCount) => {
    showPopupNotification(
        '💬 New Message',
        `You have ${newCount} new message${newCount > 1 ? 's' : ''}`,
        'info',
        'mdi-message-text',
        () => goToInbox()
    );
};

// Show popup notification with sound and vibration
const showPopupNotification = (title, message, type = 'info', icon = 'mdi-bell', callback = null) => {
    popupAlert.value = {
        show: true,
        title,
        message,
        type,
        icon,
        callback,
    };
    
    // Play sound based on type
    if (type === 'error' || title.includes('🚨')) {
        playNotificationSound('emergency');
        vibrate([300, 100, 300, 100, 300]);
    } else if (title.includes('💬')) {
        playNotificationSound('message');
        vibrate([100, 50, 100]);
    } else {
        playNotificationSound('notification');
        vibrate([200, 100, 200]);
    }
    
    // Auto-hide after 8 seconds
    setTimeout(() => {
        popupAlert.value.show = false;
    }, 8000);
};

// Handle popup click
const handlePopupClick = () => {
    if (popupAlert.value.callback) {
        popupAlert.value.callback();
    }
    popupAlert.value.show = false;
};

// Check for active rescue request
const checkActiveRescueRequest = async () => {
    if (!userData.value?.id) {
        console.log('No user ID, skipping active rescue check');
        isCheckingActiveRequest.value = false;
        return;
    }
    
    try {
        console.log('🔍 Checking for active rescue request for user:', userData.value.id);
        const response = await getUserActiveRescueRequest(userData.value.id);
        console.log('🔍 Active rescue check response:', response);
        if (response.has_active && response.data) {
            hasActiveRequest.value = true;
            activeRequest.value = response.data;
            console.log('📋 Active request status:', response.data.status);
        } else {
            hasActiveRequest.value = false;
            activeRequest.value = null;
        }
    } catch (error) {
        console.error('Failed to check active rescue request:', error);
        hasActiveRequest.value = false;
    } finally {
        isCheckingActiveRequest.value = false;
    }
};

// Navigate to existing rescue
const goToActiveRescue = () => {
    if (activeRequest.value?.rescue_code) {
        router.visit(`/user/help-coming/${activeRequest.value.rescue_code}`);
    }
};

// Get status color for chip
const getStatusColor = (status) => {
    const colors = {
        'pending': 'warning',
        'accepted': 'info',
        'in_progress': 'primary',
        'en_route': 'primary',
        'rescued': 'success',
        'completed': 'success',
        'safe': 'success',
        'cancelled': 'error',
    };
    return colors[status] || 'grey';
};

// Format status text
const formatStatus = (status) => {
    const labels = {
        'pending': 'Pending',
        'accepted': 'Rescuer Assigned',
        'in_progress': 'Rescue In Progress',
        'en_route': 'Rescuer En Route',
        'rescued': 'Rescued - Please confirm you are safe',
        'completed': 'Completed - Please confirm you are safe',
        'safe': 'Marked as Safe',
        'cancelled': 'Cancelled',
    };
    return labels[status] || status;
};

// Cleanup on unmount
onUnmounted(() => {
    stopQrScanner();
    // Clear polling interval
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
    // Clear media file previews to free memory
    clearMediaFiles();
});

const loadBuildings = async () => {
    console.log('🏗️ Starting to load buildings...');
    isLoadingBuildings.value = true;
    try {
        const result = await getBuildingsFullStructure();
        console.log('✅ Buildings loaded successfully:', result);
        console.log('📊 Total buildings:', result.length);
        
        // Log building structure for debugging QR matching
        result.forEach(b => {
            console.log(`  🏢 Building: "${b.name}" (ID: ${b.id})`);
            (b.floors || []).forEach(f => {
                console.log(`    📋 Floor: "${f.floor_name}" (ID: ${f.id})`);
                (f.rooms || []).forEach(r => {
                    console.log(`      🚪 Room: "${r.room_name}" (ID: ${r.id})`);
                });
            });
        });
        
        buildings.value = result;
    } catch (error) {
        console.error('❌ Failed to load buildings:', error);
        showNotification('Failed to load buildings. Please refresh the page.', 'error');
    } finally {
        isLoadingBuildings.value = false;
    }
};

const onBuildingChange = () => {
    selectedFloor.value = null;
    selectedRoom.value = null;
    locationScanned.value = false; // Reset scanned state when manually changing
};

const onFloorChange = () => {
    selectedRoom.value = null;
    locationScanned.value = false; // Reset scanned state when manually changing
};

// Watch for room selection changes to draw evacuation path
watch([selectedRoom, selectedFloor], () => {
    if (selectedRoom.value && hasFloorPlan.value) {
        nextTick(() => {
            setTimeout(() => drawEvacuationPaths(), 100);
        });
    }
});

// QR Scanner Implementation
const startQrScan = async () => {
    isScanning.value = true;
    showQrScanner.value = true;
    
    // Reset zoom values
    currentZoom.value = 1;
    zoomSupported.value = false;
    videoTrack = null;

    await nextTick();

    try {
        html5QrCode = new Html5Qrcode('qr-reader');
        
        // Get screen dimensions for responsive QR box
        const screenWidth = window.innerWidth;
        const qrBoxSize = Math.min(screenWidth * 0.7, 300); // 70% of screen width, max 300px
        
        await html5QrCode.start(
            { facingMode: 'environment' },
            {
                fps: 15, // Higher FPS for faster detection
                qrbox: { width: qrBoxSize, height: qrBoxSize }, // Larger, responsive scan area
                aspectRatio: 1.0,
                disableFlip: false, // Allow flipped QR codes
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true // Use native detector if available
                },
                formatsToSupport: [ 0 ] // QR_CODE format only for faster scanning
            },
            onQrCodeScanned,
            (errorMessage) => {
                // QR code not found - this is normal, ignore
            }
        );
        
        // Try to get zoom capabilities after scanner starts
        await nextTick();
        setTimeout(() => {
            setupZoomCapabilities();
        }, 500);
    } catch (error) {
        console.error('QR scan error:', error);
        showNotification('Failed to start camera. Please allow camera permissions.', 'error');
        isScanning.value = false;
        showQrScanner.value = false;
    }
};

// Setup zoom capabilities
const setupZoomCapabilities = async () => {
    try {
        // Get the video element from html5-qrcode
        const videoElement = document.querySelector('#qr-reader video');
        if (!videoElement || !videoElement.srcObject) {
            console.log('Video element not ready');
            return;
        }
        
        const stream = videoElement.srcObject;
        const tracks = stream.getVideoTracks();
        
        if (tracks.length === 0) {
            console.log('No video tracks found');
            return;
        }
        
        videoTrack = tracks[0];
        const capabilities = videoTrack.getCapabilities();
        
        console.log('Camera capabilities:', capabilities);
        
        // Check zoom support
        if (capabilities.zoom) {
            zoomSupported.value = true;
            minZoom.value = capabilities.zoom.min || 1;
            maxZoom.value = capabilities.zoom.max || 4;
            currentZoom.value = capabilities.zoom.min || 1;
            console.log(`Zoom supported: ${minZoom.value}x - ${maxZoom.value}x`);
        } else {
            console.log('Zoom not supported by this camera');
            zoomSupported.value = false;
        }
        
        // Check flash/torch support
        if (capabilities.torch) {
            flashSupported.value = true;
            console.log('Flash/torch supported');
        } else {
            flashSupported.value = false;
            console.log('Flash/torch not supported by this camera');
        }
    } catch (error) {
        console.log('Error setting up camera capabilities:', error);
        zoomSupported.value = false;
        flashSupported.value = false;
    }
};

// Apply zoom level
const applyZoom = async (zoomLevel) => {
    if (!videoTrack || !zoomSupported.value) return;
    
    try {
        await videoTrack.applyConstraints({
            advanced: [{ zoom: zoomLevel }]
        });
        console.log('Zoom applied:', zoomLevel);
    } catch (error) {
        console.error('Error applying zoom:', error);
    }
};

// Toggle flash/torch
const toggleFlash = async () => {
    if (!videoTrack || !flashSupported.value) {
        showNotification('Flash not supported on this device', 'warning');
        return;
    }
    
    try {
        const newFlashState = !flashEnabled.value;
        await videoTrack.applyConstraints({
            advanced: [{ torch: newFlashState }]
        });
        flashEnabled.value = newFlashState;
        console.log('Flash toggled:', newFlashState ? 'ON' : 'OFF');
    } catch (error) {
        console.error('Error toggling flash:', error);
        showNotification('Failed to toggle flash', 'error');
    }
};

// Flip camera (front/back)
const flipCamera = async () => {
    try {
        // Stop current scanner
        await stopQrScanner();
        
        // Toggle facing mode
        currentFacingMode.value = currentFacingMode.value === 'environment' ? 'user' : 'environment';
        
        // Reset flash when flipping (front camera usually doesn't have flash)
        flashEnabled.value = false;
        flashSupported.value = false;
        
        // Restart scanner with new camera
        await startQrScanWithFacingMode(currentFacingMode.value);
        
        console.log('Camera flipped to:', currentFacingMode.value);
    } catch (error) {
        console.error('Error flipping camera:', error);
        showNotification('Failed to switch camera', 'error');
        // Try to restart with original camera
        await startQrScanWithFacingMode('environment');
    }
};

// Start QR scan with specific facing mode
const startQrScanWithFacingMode = async (facingMode) => {
    isScanning.value = true;
    
    // Reset values
    currentZoom.value = 1;
    zoomSupported.value = false;
    videoTrack = null;

    await nextTick();

    try {
        html5QrCode = new Html5Qrcode('qr-reader');
        
        // Get screen dimensions for responsive QR box
        const screenWidth = window.innerWidth;
        const qrBoxSize = Math.min(screenWidth * 0.7, 300);
        
        await html5QrCode.start(
            { facingMode: facingMode },
            {
                fps: 15,
                qrbox: { width: qrBoxSize, height: qrBoxSize },
                aspectRatio: 1.0,
                disableFlip: false,
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                },
                formatsToSupport: [ 0 ]
            },
            onQrCodeScanned,
            (errorMessage) => {
                // QR code not found - this is normal, ignore
            }
        );
        
        // Setup camera capabilities after a short delay
        await nextTick();
        setTimeout(() => {
            setupZoomCapabilities();
        }, 500);
    } catch (error) {
        console.error('QR scan error:', error);
        showNotification('Failed to start camera. Please allow camera permissions.', 'error');
        isScanning.value = false;
        showQrScanner.value = false;
    }
};

// Zoom control functions
const increaseZoom = () => {
    const step = 0.5;
    const newZoom = Math.min(currentZoom.value + step, maxZoom.value);
    currentZoom.value = newZoom;
    applyZoom(newZoom);
};

const decreaseZoom = () => {
    const step = 0.5;
    const newZoom = Math.max(currentZoom.value - step, minZoom.value);
    currentZoom.value = newZoom;
    applyZoom(newZoom);
};

// Close QR scanner properly
const closeQrScanner = async () => {
    // Turn off flash before closing
    if (flashEnabled.value && videoTrack) {
        try {
            await videoTrack.applyConstraints({
                advanced: [{ torch: false }]
            });
        } catch (e) {
            console.log('Error turning off flash');
        }
    }
    
    await stopQrScanner();
    showQrScanner.value = false;
    videoTrack = null;
    zoomSupported.value = false;
    flashSupported.value = false;
    flashEnabled.value = false;
    currentFacingMode.value = 'environment';
};

const stopQrScanner = async () => {
    // Turn off flash before stopping
    if (flashEnabled.value && videoTrack) {
        try {
            await videoTrack.applyConstraints({
                advanced: [{ torch: false }]
            });
            flashEnabled.value = false;
        } catch (e) {
            console.log('Error turning off flash');
        }
    }
    
    if (html5QrCode) {
        try {
            await html5QrCode.stop();
            html5QrCode.clear();
        } catch (e) {
            console.log('Scanner already stopped');
        }
        html5QrCode = null;
    }
    isScanning.value = false;
    videoTrack = null;
};

const onQrCodeScanned = async (decodedText, decodedResult) => {
    console.log('========== QR CODE SCANNED ==========');
    console.log('Raw QR Text:', decodedText);
    console.log('QR Result Object:', decodedResult);
    console.log('Buildings loaded:', buildings.value.length);
    
    // Stop scanning first
    await stopQrScanner();
    showQrScanner.value = false;
    
    // Make sure buildings are loaded
    if (buildings.value.length === 0) {
        console.log('⏳ Buildings not loaded yet, loading now...');
        await loadBuildings();
    }
    
    try {
        // Parse QR code data - support multiple formats
        let locationData = null;
        let parseMethod = '';
        
        // Try parsing as JSON first
        try {
            locationData = JSON.parse(decodedText);
            parseMethod = 'JSON';
            console.log('✅ Parsed as JSON:', locationData);
            
            // Validate QR code structure
            if (locationData.building_id && locationData.floor_id && locationData.room_id) {
                console.log('✅ Valid QR code structure detected');
                console.log(`📍 Location: ${locationData.building_name} > ${locationData.floor_name} > ${locationData.room_name}`);
                
                if (locationData.version) {
                    console.log(`📊 QR Version: ${locationData.version}`);
                }
                
                if (locationData.unique_hash) {
                    console.log(`🔑 Unique Hash: ${locationData.unique_hash}`);
                }
            }
            
        } catch (jsonError) {
            console.log('❌ Not valid JSON:', jsonError.message);
            
            // Try parsing as URL params or custom format
            // Format could be: building_id:1,floor_id:2,room_id:3
            // Or: 1-2-3 (building-floor-room)
            // Or just room_id
            if (decodedText.includes(':')) {
                const pairs = decodedText.split(',');
                locationData = {};
                pairs.forEach(pair => {
                    const [key, value] = pair.split(':');
                    if (key && value) {
                        locationData[key.trim()] = value.trim();
                    }
                });
                parseMethod = 'Colon-separated';
                console.log('✅ Parsed as colon-separated');
            } else if (decodedText.includes('-')) {
                const parts = decodedText.split('-');
                locationData = {
                    building_id: parts[0],
                    floor_id: parts[1],
                    room_id: parts[2]
                };
                parseMethod = 'Dash-separated';
                console.log('✅ Parsed as dash-separated');
            } else {
                // Assume it's just a room ID or name
                locationData = { room_id: decodedText, room_name: decodedText };
                parseMethod = 'Plain text (room ID/name)';
                console.log('✅ Parsed as plain text');
            }
        }
        
        console.log('Parse Method:', parseMethod);
        console.log('Parsed Location Data:', JSON.stringify(locationData, null, 2));
        
        // Validate that we have some data
        if (!locationData || Object.keys(locationData).length === 0) {
            throw new Error('QR code contains no valid location data');
        }
        
        // Validate QR code with backend to check if it's still valid (not expired)
        if (locationData.room_id && locationData.version && locationData.unique_hash) {
            console.log('🔍 Validating QR code with backend...');
            
            const isValid = await validateQrCodeWithBackend(locationData);
            
            if (!isValid.valid) {
                locationScanned.value = false;
                console.error('❌ QR code validation failed:', isValid.error);
                showNotification(isValid.message || 'This QR code is expired or invalid. Please use the updated QR code.', 'error');
                return;
            }
            
            console.log('✅ QR code validated successfully');
        } else if (locationData.room_id) {
            // QR code without version - could be an old format
            // Check if room has a newer version in database
            console.log('⚠️ QR code has no version info - checking if room has been updated...');
            
            const checkResult = await validateQrCodeWithBackend({
                room_id: locationData.room_id,
                version: 0, // Version 0 means "no version" - should fail if room has a version
                unique_hash: 'legacy'
            });
            
            if (!checkResult.valid && checkResult.error === 'QR code expired') {
                locationScanned.value = false;
                console.error('❌ Old QR code - room has been updated');
                showNotification('This QR code is outdated. The room has been updated. Please use the new QR code.', 'error');
                return;
            }
        }
        
        // Find and select the location
        const found = await selectLocationFromQr(locationData);
        
        if (found) {
            // Mark as scanned via QR
            locationScanned.value = true;
            
            showNotification('Location scanned! Fill in emergency details and submit.', 'success');
            
            // Wait for DOM update then scroll to the emergency form
            await nextTick();
            setTimeout(() => {
                scrollToEmergencyForm();
            }, 300);
        } else {
            locationScanned.value = false;
            console.warn('⚠️ QR data parsed but no matching location found');
            console.log('QR Data:', locationData);
            console.log('Available buildings:', buildings.value.map(b => ({ id: b.id, name: b.name })));
            showNotification(`QR scanned but location not found. Please select manually.`, 'warning');
        }
    } catch (error) {
        locationScanned.value = false;
        console.error('❌ Failed to process QR code:', error);
        console.log('Raw QR text was:', decodedText);
        showNotification(`Invalid QR code: ${error.message}`, 'error');
    }
    console.log('=====================================');
};

// Validate QR code with backend to check if it's still valid
const validateQrCodeWithBackend = async (qrData) => {
    try {
        const response = await fetch('/api/rooms/validate-qr', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                room_id: qrData.room_id,
                version: qrData.version,
                unique_hash: qrData.unique_hash
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            return { valid: true, data: result };
        } else {
            // QR code is invalid or expired
            return { 
                valid: false, 
                error: result.error,
                message: result.message || 'QR code is no longer valid'
            };
        }
    } catch (error) {
        console.error('❌ Error validating QR code:', error);
        // On network error, allow scanning to proceed (offline support)
        console.log('⚠️ Could not validate QR with backend, allowing scan to proceed');
        return { valid: true, offline: true };
    }
};

// Scroll to the emergency form section
const scrollToEmergencyForm = () => {
    const formElement = document.getElementById('emergency-form-section');
    if (formElement) {
        formElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const selectLocationFromQr = async (data) => {
    // Support both IDs and names
    const buildingId = data.building_id || data.buildingId || data.building;
    const floorId = data.floor_id || data.floorId || data.floor;
    const roomId = data.room_id || data.roomId || data.room;
    
    // Support name-based QR codes
    const buildingName = data.building_name || data.buildingName;
    const floorName = data.floor_name || data.floorName;
    const roomName = data.room_name || data.roomName;
    
    console.log('🔍 Searching for location with:', { buildingId, floorId, roomId, buildingName, floorName, roomName });
    console.log('📦 Available buildings count:', buildings.value.length);
    
    if (buildings.value.length === 0) {
        console.error('❌ No buildings loaded! Cannot match QR code.');
        return false;
    }
    
    // Debug: Log all available locations
    console.log('📋 Available locations:');
    buildings.value.forEach(b => {
        console.log(`  Building: "${b.name}" (id: ${b.id})`);
        (b.floors || []).forEach(f => {
            console.log(`    Floor: "${f.floor_name}" (id: ${f.id})`);
            (f.rooms || []).forEach(r => {
                console.log(`      Room: "${r.room_name}" (id: ${r.id})`);
            });
        });
    });
    
    // First try to find by names (most common QR format)
    if (buildingName && buildings.value.length > 0) {
        console.log(`🏢 Searching for building by name: "${buildingName}"`);
        const building = buildings.value.find(b => 
            b.name.toLowerCase() === buildingName.toLowerCase() ||
            b.name.toLowerCase().includes(buildingName.toLowerCase()) ||
            buildingName.toLowerCase().includes(b.name.toLowerCase())
        );
        
        if (building) {
            console.log(`✅ Found building: "${building.name}"`);
            selectedBuilding.value = building;
            await nextTick();
            
            if (floorName) {
                console.log(`🏢 Searching for floor by name: "${floorName}"`);
                const floor = (building.floors || []).find(f => 
                    f.floor_name.toLowerCase() === floorName.toLowerCase() ||
                    f.floor_name.toLowerCase().includes(floorName.toLowerCase()) ||
                    floorName.toLowerCase().includes(f.floor_name.toLowerCase())
                );
                
                if (floor) {
                    console.log(`✅ Found floor: "${floor.floor_name}"`);
                    selectedFloor.value = floor;
                    await nextTick();
                    
                    if (roomName) {
                        console.log(`🚪 Searching for room by name: "${roomName}"`);
                        const room = (floor.rooms || []).find(r => 
                            r.room_name.toLowerCase() === roomName.toLowerCase() ||
                            r.room_name.toLowerCase().includes(roomName.toLowerCase()) ||
                            roomName.toLowerCase().includes(r.room_name.toLowerCase())
                        );
                        
                        if (room) {
                            selectedRoom.value = room;
                            console.log(`✅ SUCCESS! Location selected: ${building.name} > ${floor.floor_name} > ${room.room_name}`);
                            return true;
                        } else {
                            console.warn(`⚠️ Room "${roomName}" not found in floor "${floor.floor_name}"`);
                        }
                    }
                } else {
                    console.warn(`⚠️ Floor "${floorName}" not found in building "${building.name}"`);
                }
            }
        } else {
            console.warn(`⚠️ Building "${buildingName}" not found`);
        }
    }
    
    // Try to find by room name alone (search all buildings/floors)
    if (roomName && buildings.value.length > 0) {
        console.log(`🔎 Global search for room: "${roomName}"`);
        for (const building of buildings.value) {
            for (const floor of building.floors || []) {
                const room = (floor.rooms || []).find(r => 
                    r.room_name.toLowerCase() === roomName.toLowerCase() ||
                    r.room_name.toLowerCase().includes(roomName.toLowerCase())
                );
                if (room) {
                    selectedBuilding.value = building;
                    await nextTick();
                    selectedFloor.value = floor;
                    await nextTick();
                    selectedRoom.value = room;
                    console.log(`✅ SUCCESS! Found room globally: ${building.name} > ${floor.floor_name} > ${room.room_name}`);
                    return true;
                }
            }
        }
        console.warn(`⚠️ Room "${roomName}" not found in any building`);
    }
    
    // Fallback: Try to find by IDs
    if (roomId && buildings.value.length > 0) {
        console.log(`🔢 Searching for room by ID: "${roomId}"`);
        for (const building of buildings.value) {
            for (const floor of building.floors || []) {
                const room = (floor.rooms || []).find(r => 
                    String(r.id) === String(roomId) || 
                    r.room_name === roomId ||
                    r.qr_code === roomId
                );
                if (room) {
                    selectedBuilding.value = building;
                    await nextTick();
                    selectedFloor.value = floor;
                    await nextTick();
                    selectedRoom.value = room;
                    console.log(`✅ SUCCESS! Found room by ID: ${building.name} > ${floor.floor_name} > ${room.room_name}`);
                    return true;
                }
            }
        }
        console.warn(`⚠️ Room with ID "${roomId}" not found`);
    }
    
    // Try with building ID and floor ID
    if (buildingId) {
        console.log(`🔢 Searching for building by ID: "${buildingId}"`);
        const building = buildings.value.find(b => 
            String(b.id) === String(buildingId) || 
            b.name.toLowerCase() === String(buildingId).toLowerCase()
        );
        
        if (building) {
            console.log(`✅ Found building: "${building.name}"`);
            selectedBuilding.value = building;
            await nextTick();
            
            if (floorId) {
                console.log(`🔢 Searching for floor by ID: "${floorId}"`);
                const floor = (building.floors || []).find(f => 
                    String(f.id) === String(floorId) ||
                    f.floor_name.toLowerCase() === String(floorId).toLowerCase()
                );
                
                if (floor) {
                    console.log(`✅ Found floor: "${floor.floor_name}"`);
                    selectedFloor.value = floor;
                    await nextTick();
                    
                    if (roomId) {
                        const room = (floor.rooms || []).find(r => 
                            String(r.id) === String(roomId) ||
                            r.room_name.toLowerCase() === String(roomId).toLowerCase()
                        );
                        
                        if (room) {
                            selectedRoom.value = room;
                            console.log(`✅ SUCCESS! Found room by IDs: ${building.name} > ${floor.floor_name} > ${room.room_name}`);
                            return true;
                        }
                    }
                }
            }
        }
    }
    
    console.error('❌ No matching location found for QR data');
    return false;
};

// Watch for QR scanner dialog close
watch(showQrScanner, (newVal) => {
    if (!newVal) {
        stopQrScanner();
    }
});

// Voice Input
const toggleVoiceInput = async () => {
    if (isRecording.value) {
        try {
            isProcessingAudio.value = true;
            const audioBlob = await stopAudioRecording();
            await processAudioTranscription(audioBlob);
        } catch (error) {
            console.error('Failed to stop recording:', error);
            showNotification('Failed to process audio', 'error');
        } finally {
            isProcessingAudio.value = false;
        }
    } else {
        try {
            await startAudioRecording();
            showNotification('🎤 Listening... Say your location (e.g., "Building A, Floor 2, Room 201")', 'info');
        } catch (error) {
            console.error('Failed to start recording:', error);
            showNotification('Failed to access microphone', 'error');
        }
    }
};

// Smart location extraction from voice transcript
const extractLocationFromVoice = (transcript) => {
    const text = transcript.toLowerCase().trim();
    console.log('🎯 Extracting location from:', text);
    
    let matchedBuilding = null;
    let matchedFloor = null;
    let matchedRoom = null;
    let confidence = { building: 0, floor: 0, room: 0 };
    
    // Number words to digits mapping
    const numberWords = {
        'ground': '0', 'g': '0', 'basement': '-1', 'b': '-1',
        'first': '1', 'second': '2', 'third': '3', 'fourth': '4', 'fifth': '5',
        'sixth': '6', 'seventh': '7', 'eighth': '8', 'ninth': '9', 'tenth': '10',
        'one': '1', 'two': '2', 'three': '3', 'four': '4', 'five': '5',
        'six': '6', 'seven': '7', 'eight': '8', 'nine': '9', 'ten': '10',
        'eleven': '11', 'twelve': '12'
    };
    
    // Convert number words in text to digits for easier matching
    let normalizedText = text;
    for (const [word, digit] of Object.entries(numberWords)) {
        normalizedText = normalizedText.replace(new RegExp(`\\b${word}\\b`, 'gi'), digit);
    }
    console.log('📝 Normalized text:', normalizedText);
    
    // Extract potential building name from voice patterns
    const buildingPatterns = [
        /(?:i'm in|i am in|i'm at|at|inside|in)\s*(?:the\s*)?(.+?)\s*(?:building|bldg)/i,
        /(?:building|bldg)\s*([a-z0-9\s]+?)(?:\s*,|\s+floor|\s+flr|\s+level|\s+room|\s+rm|$)/i,
        /(.+?)\s*(?:building|bldg)/i,
    ];
    
    let extractedBuildingText = null;
    for (const pattern of buildingPatterns) {
        const match = text.match(pattern);
        if (match && match[1]) {
            extractedBuildingText = match[1].trim().toLowerCase();
            console.log('🔍 Extracted building text:', extractedBuildingText);
            break;
        }
    }
    
    // Try to match building from database
    for (const building of buildings.value) {
        const buildingName = building.name.toLowerCase();
        const buildingWords = buildingName.split(/[\s-]+/).filter(w => w.length > 1);
        
        // Method 1: Direct name match
        if (text.includes(buildingName)) {
            matchedBuilding = building;
            confidence.building = 100;
            console.log(`✅ Building matched (exact): ${building.name}`);
            break;
        }
        
        // Method 2: Match extracted building text
        if (extractedBuildingText) {
            // Check if building name contains extracted text or vice versa
            if (buildingName.includes(extractedBuildingText) || extractedBuildingText.includes(buildingName)) {
                matchedBuilding = building;
                confidence.building = 95;
                console.log(`✅ Building matched (extracted): ${building.name}`);
                break;
            }
            
            // Check individual words match
            const extractedWords = extractedBuildingText.split(/[\s-]+/).filter(w => w.length > 1);
            const wordMatches = extractedWords.filter(ew => 
                buildingWords.some(bw => bw.includes(ew) || ew.includes(bw))
            );
            if (wordMatches.length > 0 && wordMatches.length >= extractedWords.length * 0.5) {
                if (!matchedBuilding || confidence.building < 80) {
                    matchedBuilding = building;
                    confidence.building = 80;
                    console.log(`✅ Building matched (word overlap): ${building.name}`);
                }
            }
        }
        
        // Method 3: Check if any significant building word appears in text
        for (const word of buildingWords) {
            if (word.length > 2 && (text.includes(word) || normalizedText.includes(word))) {
                if (!matchedBuilding) {
                    matchedBuilding = building;
                    confidence.building = 60;
                    console.log(`✅ Building matched (partial word): ${building.name} via "${word}"`);
                }
            }
        }
    }
    
    // If building matched, try to find floor
    if (matchedBuilding) {
        const floors = matchedBuilding.floors || [];
        console.log(`🔍 Searching ${floors.length} floors in ${matchedBuilding.name}`);
        
        // Extract potential floor from voice patterns
        const floorPatterns = [
            /(?:floor|flr|level)\s*([a-z0-9\s-]+?)(?:\s*,|\s+room|\s+rm|$)/i,
            /(\d+)(?:st|nd|rd|th)\s*(?:floor|flr|level)/i,
            /(?:floor|flr)\s*(\d+)/i,
            /level\s*(\d+)/i,
        ];
        
        let extractedFloorNum = null;
        for (const pattern of floorPatterns) {
            const match = normalizedText.match(pattern);
            if (match && match[1]) {
                extractedFloorNum = match[1].trim().replace(/\D/g, '');
                console.log('🔍 Extracted floor number:', extractedFloorNum);
                break;
            }
        }
        
        for (const floor of floors) {
            const floorName = floor.floor_name.toLowerCase();
            const floorNameNormalized = floorName;
            
            // Method 1: Direct name match
            if (text.includes(floorName) || normalizedText.includes(floorName)) {
                matchedFloor = floor;
                confidence.floor = 100;
                console.log(`✅ Floor matched (exact): ${floor.floor_name}`);
                break;
            }
            
            // Method 2: Match floor number
            const floorNumMatch = floorName.match(/(\d+)/);
            if (floorNumMatch && extractedFloorNum) {
                const dbFloorNum = floorNumMatch[1];
                if (dbFloorNum === extractedFloorNum) {
                    matchedFloor = floor;
                    confidence.floor = 95;
                    console.log(`✅ Floor matched (number): ${floor.floor_name}`);
                    break;
                }
            }
            
            // Method 3: Check if floor number appears anywhere in normalized text
            if (floorNumMatch) {
                const dbFloorNum = floorNumMatch[1];
                const floorRegex = new RegExp(`(?:floor|flr|level)\\s*${dbFloorNum}\\b|\\b${dbFloorNum}(?:st|nd|rd|th)?\\s*(?:floor|flr|level)`, 'i');
                if (floorRegex.test(normalizedText)) {
                    matchedFloor = floor;
                    confidence.floor = 90;
                    console.log(`✅ Floor matched (regex): ${floor.floor_name}`);
                    break;
                }
            }
        }
        
        // If floor matched, try to find room
        if (matchedFloor) {
            const rooms = matchedFloor.rooms || [];
            console.log(`🔍 Searching ${rooms.length} rooms in ${matchedFloor.floor_name}`);
            
            // Extract potential room from voice patterns
            const roomPatterns = [
                /(?:room|rm|office|classroom|lab|laboratory|class)\s*([a-z0-9\s-]+?)(?:\s*,|\s*\.|$)/i,
                /(?:room|rm)\s*(\d+[a-z]*)/i,
                /(\d{2,}[a-z]*)\s*(?:room)?/i, // Room numbers like 201, 302a
            ];
            
            let extractedRoomId = null;
            for (const pattern of roomPatterns) {
                const match = normalizedText.match(pattern);
                if (match && match[1]) {
                    extractedRoomId = match[1].trim().toLowerCase().replace(/\s+/g, '');
                    console.log('🔍 Extracted room identifier:', extractedRoomId);
                    break;
                }
            }
            
            for (const room of rooms) {
                const roomName = room.room_name.toLowerCase();
                const roomNameClean = roomName.replace(/\s+/g, '');
                
                // Method 1: Direct name match
                if (text.includes(roomName) || normalizedText.includes(roomName)) {
                    matchedRoom = room;
                    confidence.room = 100;
                    console.log(`✅ Room matched (exact): ${room.room_name}`);
                    break;
                }
                
                // Method 2: Match extracted room identifier
                if (extractedRoomId) {
                    if (roomNameClean.includes(extractedRoomId) || extractedRoomId.includes(roomNameClean)) {
                        matchedRoom = room;
                        confidence.room = 95;
                        console.log(`✅ Room matched (extracted): ${room.room_name}`);
                        break;
                    }
                    
                    // Check room number only
                    const roomNumMatch = roomName.match(/(\d+[a-z]*)/i);
                    if (roomNumMatch) {
                        const dbRoomNum = roomNumMatch[1].toLowerCase();
                        if (dbRoomNum === extractedRoomId || extractedRoomId.includes(dbRoomNum)) {
                            matchedRoom = room;
                            confidence.room = 90;
                            console.log(`✅ Room matched (number): ${room.room_name}`);
                            break;
                        }
                    }
                }
                
                // Method 3: Check if room number appears in text
                const roomNumMatch = roomName.match(/(\d+[a-z]*)/i);
                if (roomNumMatch) {
                    const dbRoomNum = roomNumMatch[1].toLowerCase();
                    if (normalizedText.includes(dbRoomNum)) {
                        matchedRoom = room;
                        confidence.room = 85;
                        console.log(`✅ Room matched (contains): ${room.room_name}`);
                        break;
                    }
                }
            }
        }
    }
    
    const result = {
        building: matchedBuilding,
        floor: matchedFloor,
        room: matchedRoom,
        confidence,
        hasLocation: !!(matchedBuilding || matchedFloor || matchedRoom)
    };
    
    console.log('📍 Final location extraction result:', {
        building: matchedBuilding?.name,
        floor: matchedFloor?.floor_name,
        room: matchedRoom?.room_name,
        confidence
    });
    
    return result;
};

// Extract emergency details from voice (separates location from emergency description)
const extractEmergencyFromVoice = (transcript) => {
    const text = transcript.toLowerCase();
    let cleanDescription = transcript;
    
    const result = {
        description: '',
        mobility: null,
        urgency: null,
        injuries: []
    };
    
    // Location-related phrases to REMOVE from description (these go to location fields)
    const locationPhrases = [
        // Building patterns
        /(?:i'm in|i am in|i'm at|i am at|at|inside|in)\s*(?:the\s*)?([a-z0-9\s]+?)\s*(?:building|bldg)/gi,
        /(?:building|bldg)\s*([a-z0-9\s]+?)(?:\s*,|\s+floor|\s+room|$)/gi,
        /([a-z]+)\s*building/gi,
        // Floor patterns
        /(?:floor|flr|level)\s*([a-z0-9\s-]+?)(?:\s*,|\s+room|\s+and|$)/gi,
        /(\d+)(?:st|nd|rd|th)\s*(?:floor|flr|level)/gi,
        /(?:ground|basement|first|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth)\s*(?:floor|flr|level)/gi,
        // Room patterns
        /(?:room|rm|office|classroom|lab|laboratory)\s*([a-z0-9\s-]+?)(?:\s*,|\s*\.|$)/gi,
        // Combined location references
        /(?:i'm in|i am in|located at|my location is)\s*[^,\.]+/gi,
    ];
    
    // Remove location phrases from description
    for (const pattern of locationPhrases) {
        cleanDescription = cleanDescription.replace(pattern, ' ');
    }
    
    // Clean up the description
    cleanDescription = cleanDescription
        .replace(/\s+/g, ' ')  // Multiple spaces to single
        .replace(/^\s*,\s*/g, '')  // Leading commas
        .replace(/\s*,\s*$/g, '')  // Trailing commas
        .replace(/^[\s,]+|[\s,]+$/g, '')  // Trim
        .trim();
    
    // If description is too short or empty after removing location, keep original emergency parts
    if (cleanDescription.length < 5) {
        // Try to extract just the emergency/help part
        const emergencyMatch = transcript.match(/(?:help|need|emergency|injured|hurt|stuck|trapped|bleeding|pain|broken|fire|smoke)[^]*$/i);
        if (emergencyMatch) {
            cleanDescription = emergencyMatch[0].trim();
        } else {
            cleanDescription = transcript; // Fallback to full transcript
        }
    }
    
    result.description = cleanDescription;
    
    // Mobility detection
    const mobilityPatterns = {
        'immobile': ['cannot move', "can't move", 'unable to move', 'stuck', 'trapped', 'immobile', 'paralyzed', 'pinned'],
        'limited': ['limited mobility', 'hard to walk', 'difficulty walking', 'injured leg', 'hurt leg', 'limping', 'barely walk'],
        'normal': ['can walk', 'able to walk', 'walking fine', 'mobile', 'i can move'],
    };
    
    for (const [status, patterns] of Object.entries(mobilityPatterns)) {
        if (patterns.some(p => text.includes(p))) {
            result.mobility = status;
            break;
        }
    }
    
    // Urgency detection
    const urgencyPatterns = {
        'critical': ['critical', 'dying', 'heart attack', 'stroke', 'not breathing', 'severe bleeding', 'life threatening', 'emergency', 'please hurry', 'help me'],
        'high': ['urgent', 'serious', 'bad injury', 'lots of blood', 'unconscious', 'passed out', 'seizure', 'very hurt'],
        'medium': ['injured', 'hurt', 'pain', 'bleeding', 'broken', 'sprain', 'need help', 'fell down'],
        'low': ['minor', 'small cut', 'slight', 'feeling unwell', 'dizzy', 'not serious'],
    };
    
    for (const [level, patterns] of Object.entries(urgencyPatterns)) {
        if (patterns.some(p => text.includes(p))) {
            result.urgency = level;
            break;
        }
    }
    
    // Injury detection
    const injuryPatterns = {
        'bleeding': ['bleeding', 'blood', 'cut', 'laceration', 'wound', 'gash'],
        'fracture': ['broken', 'fracture', 'bone', 'snap', 'crack'],
        'burn': ['burn', 'burned', 'fire', 'hot', 'scalded'],
        'head': ['head injury', 'hit head', 'hit my head', 'concussion', 'head hurt', 'headache'],
        'breathing': ['breathing', 'breath', 'asthma', 'choking', 'suffocating', 'can\'t breathe'],
        'unconscious': ['unconscious', 'fainted', 'passed out', 'not responding', 'blacked out'],
        'chest_pain': ['chest pain', 'heart', 'chest hurts', 'chest'],
        'seizure': ['seizure', 'convulsion', 'shaking', 'fits'],
        'allergic': ['allergic', 'allergy', 'swelling', 'hives', 'anaphylaxis'],
    };
    
    for (const [injury, patterns] of Object.entries(injuryPatterns)) {
        if (patterns.some(p => text.includes(p))) {
            result.injuries.push(injury);
        }
    }
    
    console.log('🔍 Emergency extraction - Original:', transcript);
    console.log('🔍 Emergency extraction - Clean description:', cleanDescription);
    console.log('🔍 Emergency extraction - Result:', result);
    
    return result;
};

const processAudioTranscription = async (audioBlob) => {
    try {
        console.log('🎤 Starting audio transcription...');
        console.log('Audio blob size:', audioBlob.size, 'bytes');
        console.log('Audio blob type:', audioBlob.type);
        
        const transcript = await transcribeAudio(audioBlob);
        console.log('✅ Transcription result:', transcript);
        
        if (transcript) {
            // Step 1: Try to extract location from voice directly
            const locationResult = extractLocationFromVoice(transcript);
            console.log('📍 Location extraction result:', locationResult);
            
            // Step 2: Extract emergency details from voice
            const emergencyResult = extractEmergencyFromVoice(transcript);
            console.log('🚨 Emergency extraction result:', emergencyResult);
            
            // Step 3: Apply extracted location
            let locationApplied = false;
            if (locationResult.hasLocation) {
                if (locationResult.building) {
                    selectedBuilding.value = locationResult.building;
                    await nextTick();
                    
                    if (locationResult.floor) {
                        selectedFloor.value = locationResult.floor;
                        await nextTick();
                        
                        if (locationResult.room) {
                            selectedRoom.value = locationResult.room;
                            locationApplied = true;
                            locationScanned.value = false; // Mark as voice input, not QR
                            
                            showNotification(
                                `✅ Location detected: ${locationResult.building.name} → ${locationResult.floor.floor_name} → ${locationResult.room.room_name}`,
                                'success'
                            );
                        } else {
                            showNotification(
                                `📍 Building & Floor detected: ${locationResult.building.name} → ${locationResult.floor.floor_name}. Please select a room.`,
                                'info'
                            );
                        }
                    } else {
                        showNotification(
                            `📍 Building detected: ${locationResult.building.name}. Please select floor and room.`,
                            'info'
                        );
                    }
                }
            }
            
            // Step 4: Apply emergency details
            emergencyForm.value.description = emergencyResult.description;
            if (emergencyResult.mobility) {
                emergencyForm.value.mobilityStatus = emergencyResult.mobility;
            }
            if (emergencyResult.urgency) {
                emergencyForm.value.urgencyLevel = emergencyResult.urgency;
            }
            if (emergencyResult.injuries.length > 0) {
                emergencyForm.value.injuries = emergencyResult.injuries;
            }
            
            // Step 5: If no location found locally, try API extraction as fallback
            if (!locationResult.hasLocation) {
                try {
                    const fields = await extractFieldsAndInferLocation(transcript);
                    if (fields && fields.location_inference) {
                        await applyLocationInference(fields.location_inference);
                    } else {
                        showNotification(
                            '🎤 Voice recorded! Please select your location manually or say it more clearly.',
                            'warning'
                        );
                    }
                } catch (apiError) {
                    console.log('API extraction failed, using local extraction only');
                    showNotification(
                        '🎤 Voice recorded! Please select your location from the dropdown.',
                        'info'
                    );
                }
            }
            
            // Scroll to form after processing
            scrollToEmergencyForm();
            
        } else {
            showNotification('No speech detected. Please try again.', 'warning');
        }
    } catch (error) {
        console.error('❌ Transcription error:', error);
        
        // Parse error message for better user feedback
        let errorMessage = 'Failed to transcribe audio';
        const errorText = error.message || '';
        
        if (errorText.includes('api_key_missing')) {
            errorMessage = 'Voice feature not configured. Please contact admin.';
        } else if (errorText.includes('validation_failed')) {
            errorMessage = 'Invalid audio format. Please try again.';
        } else if (errorText.includes('transcription_failed')) {
            errorMessage = 'Transcription failed. Check your internet connection.';
        } else if (errorText.includes('500')) {
            errorMessage = 'Server error. Voice feature may not be configured.';
        }
        
        showNotification(errorMessage, 'error');
    }
};

const applyLocationInference = async (inference) => {
    // Handle both naming conventions from API
    const buildingName = (inference.building_name || inference.building_name_match)?.toLowerCase();
    const floorName = (inference.floor_name || inference.floor_name_match)?.toLowerCase();
    const roomName = (inference.room_name || inference.room_name_match)?.toLowerCase();
    
    // If we have IDs directly, use them
    if (inference.building_id && inference.floor_id && inference.room_id) {
        const building = buildings.value.find(b => String(b.id) === String(inference.building_id));
        if (building) {
            selectedBuilding.value = building;
            await nextTick();
            
            const floor = (building.floors || []).find(f => String(f.id) === String(inference.floor_id));
            if (floor) {
                selectedFloor.value = floor;
                await nextTick();
                
                const room = (floor.rooms || []).find(r => String(r.id) === String(inference.room_id));
                if (room) {
                    selectedRoom.value = room;
                    showNotification(`Location auto-selected: ${room.room_name}`, 'info');
                    return;
                }
            }
        }
    }

    // Fallback to name-based matching
    if (buildingName) {
        const building = buildings.value.find(
            (b) => b.name.toLowerCase().includes(buildingName)
        );
        if (building) {
            selectedBuilding.value = building;
            await nextTick();

            if (floorName) {
                const floor = (building.floors || []).find(
                    (f) => f.floor_name.toLowerCase().includes(floorName)
                );
                if (floor) {
                    selectedFloor.value = floor;
                    await nextTick();

                    if (roomName) {
                        const room = (floor.rooms || []).find(
                            (r) => r.room_name.toLowerCase().includes(roomName)
                        );
                        if (room) {
                            selectedRoom.value = room;
                            showNotification(`Location auto-selected: ${room.room_name}`, 'info');
                        }
                    }
                }
            }
        }
    }
};

// Submit Rescue Request
const submitRescueRequest = async () => {
    if (!canSubmit.value) {
        showNotification('Please select a location first', 'warning');
        return;
    }

    isSubmitting.value = true;
    try {
        // Combine injuries into a string, replacing 'other' with actual value
        let injuriesString = '';
        if (emergencyForm.value.injuries && emergencyForm.value.injuries.length > 0) {
            const injuries = emergencyForm.value.injuries.map(i => 
                i === 'other' && emergencyForm.value.otherInjury 
                    ? emergencyForm.value.otherInjury 
                    : i
            );
            injuriesString = injuries.join(', ');
        }

        // Use FormData if there are media files attached
        let result;
        
        if (mediaFiles.value.length > 0) {
            // Create FormData for file upload
            const formData = new FormData();
            formData.append('user_id', userData.value?.id);
            formData.append('building_id', selectedBuilding.value.id);
            formData.append('floor_id', selectedFloor.value.id);
            formData.append('room_id', selectedRoom.value.id);
            formData.append('description', emergencyForm.value.description || '');
            formData.append('mobility_status', emergencyForm.value.mobilityStatus || '');
            formData.append('urgency_level', emergencyForm.value.urgencyLevel || '');
            formData.append('injuries', injuriesString);
            formData.append('additional_info', emergencyForm.value.additionalInfo || '');
            formData.append('firstName', emergencyForm.value.firstName || '');
            formData.append('lastName', emergencyForm.value.lastName || '');
            
            // Append media files
            mediaFiles.value.forEach((mediaFile, index) => {
                formData.append(`media_files[${index}]`, mediaFile.file, mediaFile.name);
            });
            
            // Submit with FormData
            result = await createRescueRequest(formData, true);
        } else {
            // Regular JSON payload without files
            const payload = {
                user_id: userData.value?.id,
                building_id: selectedBuilding.value.id,
                floor_id: selectedFloor.value.id,
                room_id: selectedRoom.value.id,
                description: emergencyForm.value.description,
                mobility_status: emergencyForm.value.mobilityStatus,
                urgency_level: emergencyForm.value.urgencyLevel,
                injuries: injuriesString,
                additional_info: emergencyForm.value.additionalInfo,
                firstName: emergencyForm.value.firstName,
                lastName: emergencyForm.value.lastName,
            };
            
            result = await createRescueRequest(payload);
        }

        if (result.rescueCode && result.requestId) {
            localStorage.setItem('lastRescueCode', result.rescueCode);
            localStorage.setItem('lastRescueRequestId', result.requestId);
            localStorage.setItem('lastRescueRequestTime', new Date().toISOString());

            showNotification('Rescue request submitted successfully!', 'success');
            
            // Clear media files after successful submission
            clearMediaFiles();

            // Navigate to help coming page
            setTimeout(() => {
                router.visit(`/user/help-coming/${result.rescueCode}`);
            }, 500);
        }
    } catch (error) {
        console.error('Failed to submit rescue request:', error);
        
        // Check if it's a profile completion error from the API response
        const errorData = error.data || {};
        if (errorData.must_update_profile === true) {
            showNotification(
                errorData.message || 
                'You must complete your profile information before you can submit emergency reports. Please update your personal information, emergency contact, and medical details.',
                'warning'
            );
            
            // Redirect to profile page after showing the message
            setTimeout(() => {
                router.visit('/user/profile');
            }, 3000);
        } else {
            // Check if there's a specific error message from the API
            const errorMessage = errorData.message || 'Failed to submit rescue request';
            showNotification(errorMessage, 'error');
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Evacuation Path Drawing Functions
const onEvacuationImageLoad = () => {
    drawEvacuationPaths();
};

const drawEvacuationPaths = () => {
    const canvas = evacuationCanvas.value;
    const image = evacuationImage.value;
    
    if (!canvas || !image || !selectedRoom.value) return;
    
    // Set canvas size to match image
    canvas.width = image.naturalWidth;
    canvas.height = image.naturalHeight;
    
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Draw all room boxes (faded)
    floorPlanRooms.value.forEach(room => {
        const isSelectedRoom = room.room_id === selectedRoom.value.id;
        const opacity = isSelectedRoom ? 0.5 : 0.1;
        
        // Fill
        ctx.fillStyle = hexToRgbaEvac(room.color || '#4CAF50', opacity);
        ctx.fillRect(room.x, room.y, room.width, room.height);
        
        // Border
        ctx.strokeStyle = room.color || '#4CAF50';
        ctx.lineWidth = isSelectedRoom ? 3 : 1;
        ctx.strokeRect(room.x, room.y, room.width, room.height);
        
        // Label
        if (room.room_name) {
            ctx.fillStyle = '#000';
            ctx.font = 'bold 14px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(room.room_name, room.x + room.width / 2, room.y + room.height / 2);
        }
    });
    
    // Draw evacuation paths for selected room
    evacuationPaths.value.forEach(path => {
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
            drawArrowHeadEvac(ctx, path.points[i - 1], path.points[i], path.color || '#FF5722');
        }
        
        // Draw points
        path.points.forEach((point, i) => {
            ctx.beginPath();
            ctx.arc(point.x, point.y, 6, 0, Math.PI * 2);
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
            ctx.fillText(path.name, path.points[0].x + 10, path.points[0].y - 10);
        }
    });
};

const drawArrowHeadEvac = (ctx, from, to, color) => {
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

const hexToRgbaEvac = (hex, alpha) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    if (result) {
        return `rgba(${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}, ${alpha})`;
    }
    return `rgba(76, 175, 80, ${alpha})`;
};

const evacuationZoomIn = () => {
    evacuationZoom.value = Math.min(3, evacuationZoom.value + 0.25);
};

const evacuationZoomOut = () => {
    evacuationZoom.value = Math.max(0.25, evacuationZoom.value - 0.25);
};

const resetEvacuationZoom = () => {
    evacuationZoom.value = 1;
};

// ==================== Media Attachment Functions ====================

// Trigger file input for photos/videos
const triggerMediaInput = () => {
    if (mediaInputRef.value) {
        mediaInputRef.value.click();
    }
};

// Trigger file input for videos only
const triggerVideoInput = () => {
    if (videoInputRef.value) {
        videoInputRef.value.click();
    }
};

// Open camera for photo capture
const openCamera = () => {
    if (cameraInputRef.value) {
        cameraInputRef.value.click();
    }
};

// Handle media file selection
const handleMediaSelect = async (event) => {
    const files = event.target.files;
    if (!files || files.length === 0) return;
    
    const remainingSlots = MAX_FILES - mediaFiles.value.length;
    if (remainingSlots <= 0) {
        showNotification('Maximum 5 files allowed', 'warning');
        return;
    }
    
    const filesToAdd = Array.from(files).slice(0, remainingSlots);
    
    for (const file of filesToAdd) {
        // Validate file type
        if (!file.type.startsWith('image/') && !file.type.startsWith('video/')) {
            showNotification(`${file.name} is not a valid image or video`, 'error');
            continue;
        }
        
        // Validate file size
        if (file.size > MAX_FILE_SIZE) {
            showNotification(`${file.name} exceeds 10MB limit`, 'error');
            continue;
        }
        
        // Create preview URL
        const preview = URL.createObjectURL(file);
        
        mediaFiles.value.push({
            file: file,
            name: file.name,
            type: file.type,
            size: file.size,
            preview: preview,
        });
    }
    
    // Reset the input
    event.target.value = '';
    
    if (filesToAdd.length > 0) {
        showNotification(`${filesToAdd.length} file(s) added`, 'success');
    }
};

// Remove a media file
const removeMediaFile = (index) => {
    const file = mediaFiles.value[index];
    if (file.preview) {
        URL.revokeObjectURL(file.preview);
    }
    mediaFiles.value.splice(index, 1);
};

// Preview a media file
const previewMedia = (file) => {
    previewMediaFile.value = file;
    showMediaPreview.value = true;
};

// Remove the currently previewed media
const removePreviewedMedia = () => {
    if (previewMediaFile.value) {
        const index = mediaFiles.value.findIndex(f => f.preview === previewMediaFile.value.preview);
        if (index !== -1) {
            removeMediaFile(index);
        }
    }
    showMediaPreview.value = false;
    previewMediaFile.value = null;
};

// Format file size for display
const formatFileSize = (bytes) => {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

// Clear all media files (call when form is reset or submitted)
const clearMediaFiles = () => {
    mediaFiles.value.forEach(file => {
        if (file.preview) {
            URL.revokeObjectURL(file.preview);
        }
    });
    mediaFiles.value = [];
};

// ==================== End Media Attachment Functions ====================

const showNotification = (message, color = 'info') => {
    toastMessage.value = message;
    toastColor.value = color;
    showToast.value = true;
};
</script>

<style scoped>
/* App Container - Allow proper scrolling */
.app-container {
    position: relative;
    width: 100%;
    min-height: 100vh;
    height: 100%;
    background: #f5f7fa;
}

.app-container :deep(.v-application__wrap) {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Main Container */
.main-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

/* Content Wrapper - Scrollable Area */
.content-wrapper {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
}

/* Header Styling - Matches Rescuer Dashboard */
.scanner-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #3674B5;
    padding: env(safe-area-inset-top, 0) 0 0 0;
    flex-shrink: 0;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    gap: 12px;
}

.menu-btn, .action-btn {
    color: white;
}

.header-title {
    flex: 1;
    text-align: center;
}

.header-title h1 {
    font-size: 1.25rem;
    font-weight: 700;
    font-style: italic;
    color: white;
    margin: 0;
}

.header-title p {
    font-size: 0.65rem;
    letter-spacing: 2px;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    text-transform: uppercase;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Desktop-only elements (hidden on mobile/tablet) */
.desktop-only {
    display: flex;
}

/* Mobile-only elements (hidden on desktop) */
.mobile-only {
    display: none;
}

/* Responsive visibility */
@media (max-width: 1023px) {
    /* On mobile/tablet: hide desktop menu button */
    .desktop-only {
        display: none !important;
    }
    
    .mobile-only {
        display: flex;
    }
    
    /* Add padding for bottom nav on mobile/tablet */
    .content-wrapper {
        padding-bottom: 100px;
    }
    
    .scanner-content {
        padding-bottom: 24px;
    }
}

@media (min-width: 1024px) {
    /* On desktop: show menu button, hide bottom nav */
    .desktop-only {
        display: flex;
    }
    
    /* No bottom padding needed on desktop */
    .content-wrapper {
        padding-bottom: 40px;
    }
}

/* Notification Drawer */
.notification-drawer {
    z-index: 2000 !important;
}

.notification-header {
    display: flex;
    align-items: center;
    padding: 20px;
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 100%);
    color: white;
}

.notification-header h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
}

.notification-header p {
    font-size: 0.75rem;
    opacity: 0.8;
    margin: 0;
}

.notification-section {
    border-bottom: 1px solid #eee;
}

.section-header {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.section-header.warning {
    background: #FFF8E1;
    color: #FF8F00;
}

.section-header.info {
    background: #E3F2FD;
    color: #1976D2;
}

.notification-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    gap: 12px;
    cursor: pointer;
    transition: background 0.2s;
}

.notification-item:hover {
    background: #f5f5f5;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-content h4 {
    font-size: 0.875rem;
    font-weight: 600;
    margin: 0;
    color: #333;
}

.notification-content .message {
    font-size: 0.8rem;
    color: #666;
    margin: 4px 0 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.notification-content .time {
    font-size: 0.7rem;
    color: #999;
    margin: 4px 0 0;
    display: flex;
    align-items: center;
    gap: 4px;
}

.no-notifications {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px 24px;
    color: #999;
}

.no-notifications p {
    margin: 8px 0 0;
}

.notification-actions {
    padding: 16px;
}

/* Loading State */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    padding: 24px;
}

.loading-state p {
    margin-top: 16px;
    color: #666;
}

/* Active Rescue Display */
.active-rescue-display {
    /* Removed min-height to allow proper scrolling */
}

.active-hero {
    background: linear-gradient(135deg, #FF9800 0%, #FFC107 100%);
    padding: 40px 20px;
    position: relative;
    overflow: hidden;
}

.active-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.3;
}

.active-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
}

.active-icon-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 16px;
}

.pulse-ring {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 80px;
    height: 80px;
    border-radius: 50%;
    animation: pulse 2s ease-out infinite;
}

.pulse-ring.warning {
    border: 3px solid rgba(255, 255, 255, 0.5);
}

@keyframes pulse {
    0% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 0.5;
    }
    100% {
        transform: translate(-50%, -50%) scale(1.5);
        opacity: 0;
    }
}

.active-hero h2 {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 8px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.active-hero p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.9rem;
    margin: 0;
}

.active-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-bottom: 1px solid #eee;
}

.rescue-code {
    font-size: 0.9rem;
    font-weight: 600;
    color: #666;
}

.active-details {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.active-details .detail-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.active-details .detail-row > div {
    display: flex;
    flex-direction: column;
}

.active-details .label {
    font-size: 0.7rem;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.active-details .value {
    font-size: 0.9rem;
    font-weight: 500;
    color: #333;
}

/* Scanner Hero */
.scanner-hero {
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 100%);
    padding: 32px 20px 48px;
    position: relative;
    overflow: hidden;
}

.scanner-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.3;
}

.hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
}

.hero-content h1 {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 8px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.hero-content p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.9rem;
    margin: 0;
}

/* Scanner Content */
.scanner-content {
    margin-top: -24px;
    border-radius: 24px 24px 0 0;
    background: #f8fafb;
    position: relative;
    z-index: 2;
    padding: 20px 16px 40px;
}

/* Active Content */
.active-content {
    margin-top: -20px;
    border-radius: 24px 24px 0 0;
    background: #f8fafb;
    position: relative;
    z-index: 2;
    padding: 20px 16px 40px;
}

/* Constrain content width on larger screens */
@media (min-width: 600px) {
    .scanner-content,
    .active-content {
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
        margin-top: -24px;
    }
    
    .hero-content,
    .active-hero-content {
        max-width: 600px;
        margin: 0 auto;
    }
    
    .action-cards {
        flex-direction: row;
    }
    
    .action-card {
        flex: 1;
    }
}

/* Action Cards */
.action-cards {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.action-card {
    display: flex;
    align-items: center;
    padding: 16px;
    border-radius: 16px;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    gap: 16px;
}

.action-card:active {
    transform: scale(0.98);
}

.action-card.disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.action-card.primary {
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 100%);
    box-shadow: 0 4px 16px rgba(54, 116, 181, 0.3);
}

.action-card.success {
    background: linear-gradient(135deg, #388E3C 0%, #4CAF50 100%);
    box-shadow: 0 4px 16px rgba(76, 175, 80, 0.3);
}

/* Hide scan QR card on mobile/tablet - use bottom nav instead */
@media (max-width: 1024px) {
    .action-card.hide-on-mobile {
        display: none !important;
    }
    
    /* Make voice input card full width when scan card is hidden */
    .action-cards {
        grid-template-columns: 1fr;
    }
}

.action-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.action-icon.recording {
    animation: recordingPulse 1s ease-in-out infinite;
    background: rgba(255, 100, 100, 0.3);
}

@keyframes recordingPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Voice Tips Alert */
.voice-tips-alert {
    border: 1px solid rgba(33, 150, 243, 0.3);
}

.voice-tips {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.voice-tip-header {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
}

.voice-examples {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.pulse-icon {
    animation: recordingPulse 1s ease-in-out infinite;
    color: #f44336;
}

/* Samaritan Report Note */
.samaritan-note {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    background: linear-gradient(135deg, rgba(33, 150, 243, 0.08) 0%, rgba(33, 150, 243, 0.04) 100%);
    border-radius: 8px;
    border-left: 3px solid #2196F3;
}

.samaritan-note .v-icon {
    flex-shrink: 0;
}

/* Media Attachments Section */
.media-attachments-section {
    padding: 12px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 1px dashed #ddd;
}

.section-label {
    display: flex;
    align-items: center;
}

.media-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 8px;
}

.media-preview-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    background: #e0e0e0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 2px solid transparent;
    transition: all 0.2s ease;
}

.media-preview-item:hover {
    border-color: #3674B5;
    transform: scale(1.02);
}

.media-preview-item.add-more {
    background: #fff;
    border: 2px dashed #ccc;
    gap: 4px;
}

.media-preview-item.add-more:hover {
    border-color: #3674B5;
    background: #f0f7ff;
}

.media-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.video-thumbnail {
    position: relative;
    width: 100%;
    height: 100%;
}

.video-thumbnail video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.video-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
}

.media-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 4px;
    background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
}

.media-info .file-size {
    font-size: 10px;
    color: white;
    font-weight: 500;
}

.remove-media-btn {
    position: absolute !important;
    top: 4px;
    right: 4px;
    opacity: 0;
    transition: opacity 0.2s;
}

.media-preview-item:hover .remove-media-btn {
    opacity: 1;
}

.media-upload-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

/* Media Preview Dialog */
.media-preview-dialog .v-card {
    overflow: hidden;
}

@media (max-width: 600px) {
    .media-preview-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .media-upload-buttons {
        flex-direction: column;
    }
    
    .media-upload-buttons .v-btn {
        width: 100%;
        margin-right: 0 !important;
    }
}

.action-text {
    flex: 1;
}

.action-text h3 {
    color: white;
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 4px;
}

.action-text p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.8rem;
    margin: 0;
}

/* Location Status Card */
.location-status-card {
    border: 2px solid rgba(54, 116, 181, 0.2);
    background: white;
}

.location-status-card.scanned {
    border-color: #4CAF50;
}

.location-status-header {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    gap: 8px;
    font-weight: 600;
    color: #1a3a5c;
    background: rgba(54, 116, 181, 0.05);
}

.location-status-header.success {
    background: linear-gradient(135deg, #388E3C 0%, #4CAF50 100%);
    color: white;
}

.location-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

/* Manual Location Card */
.manual-location-card {
    border: 1px solid rgba(54, 116, 181, 0.15);
    overflow: hidden;
    background: white;
}

.manual-location-header {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    background: linear-gradient(135deg, rgba(54, 116, 181, 0.08) 0%, rgba(54, 116, 181, 0.03) 100%);
    border-bottom: 1px solid rgba(54, 116, 181, 0.1);
}

.manual-location-header .v-avatar {
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 100%) !important;
}

.manual-location-header .v-avatar .v-icon {
    color: white !important;
}

.manual-location-header h3 {
    color: #1a3a5c;
}

.manual-location-header p {
    color: #6b8299;
}

/* Responsive adjustments for manual location */
@media (max-width: 599px) {
    .manual-location-header {
        padding: 12px 14px;
    }
    
    .manual-location-header .v-avatar {
        width: 36px !important;
        height: 36px !important;
        min-width: 36px !important;
    }
    
    .manual-location-header h3 {
        font-size: 0.9rem !important;
    }
    
    .manual-location-card :deep(.v-card-text) {
        padding: 12px 14px !important;
    }
    
    .manual-location-card :deep(.v-field) {
        min-height: 48px;
    }
    
    .manual-location-card :deep(.v-row) {
        margin: 0 -4px;
    }
    
    .manual-location-card :deep(.v-col) {
        padding: 4px;
        margin-bottom: 8px;
    }
    
    .manual-location-card :deep(.v-col:last-child) {
        margin-bottom: 0;
    }
}

@media (min-width: 600px) {
    .manual-location-card :deep(.v-row) {
        margin: -6px;
    }
    
    .manual-location-card :deep(.v-col) {
        padding: 6px;
    }
    
    .manual-location-card :deep(.v-card-text) {
        padding: 16px !important;
    }
}

/* Emergency Form Card */
.emergency-form-card {
    border: 2px solid rgba(211, 47, 47, 0.2);
    background: white;
    overflow: hidden;
}

.emergency-form-header {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    background: linear-gradient(135deg, #c62828 0%, #d32f2f 100%);
    color: white;
    font-weight: 600;
}

.emergency-form-card :deep(.v-card-text) {
    padding: 16px !important;
}

@media (max-width: 599px) {
    .emergency-form-card :deep(.v-card-text) {
        padding: 12px !important;
    }
    
    .emergency-form-header {
        padding: 12px 14px;
    }
}

.submit-btn {
    font-weight: 600;
    font-size: 1rem;
    letter-spacing: 0.5px;
    min-height: 52px;
}

/* QR Scanner Dialog */
.qr-scanner-dialog {
    background: black !important;
}

.qr-scanner-header {
    display: flex;
    align-items: center;
    padding: 12px 8px;
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 100%);
    gap: 8px;
}

.qr-title {
    flex: 1;
    color: white;
    font-size: 1.125rem;
    font-weight: 600;
}

.camera-controls-header {
    display: flex;
    align-items: center;
    gap: 4px;
}

.qr-scanner-content {
    position: relative;
}

/* Scanner styling for better visibility */
.qr-scanner-content :deep(#qr-reader) {
    border-radius: 16px;
    overflow: hidden;
}

.qr-scanner-content :deep(#qr-reader video) {
    border-radius: 16px;
}

.qr-scanner-content :deep(#qr-reader__scan_region) {
    background: transparent !important;
}

/* Make the scanning box more visible */
.qr-scanner-content :deep(#qr-shaded-region) {
    border-color: rgba(54, 116, 181, 0.7) !important;
}

.scanning-tips {
    background: rgba(0, 0, 0, 0.3);
    padding: 8px 16px;
    border-radius: 12px;
    backdrop-filter: blur(5px);
}

/* Camera Controls Bottom Panel */
.camera-controls-panel {
    width: 100%;
    max-width: 350px;
    padding: 12px 16px;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 16px;
    margin-top: 16px;
    backdrop-filter: blur(10px);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.zoom-controls-inline {
    display: flex;
    align-items: center;
    gap: 16px;
}

.zoom-value {
    color: white;
    font-size: 1rem;
    font-weight: 600;
    min-width: 50px;
    text-align: center;
}

.flash-indicator {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255, 235, 59, 0.2);
    padding: 4px 12px;
    border-radius: 20px;
    color: #ffeb3b;
    font-size: 0.8rem;
    font-weight: 500;
}

/* Zoom Controls */
.zoom-controls {
    width: 100%;
    max-width: 320px;
    padding: 16px 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    margin-top: 16px;
    backdrop-filter: blur(10px);
}

.zoom-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: white;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 12px;
}

.zoom-slider-container {
    display: flex;
    align-items: center;
    gap: 8px;
}

.zoom-slider {
    flex: 1;
}

.zoom-hint {
    text-align: center;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.75rem;
    margin-top: 8px;
    margin-bottom: 0;
}

/* Help Dialog */
.help-header {
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 100%);
    padding: 24px;
    text-align: center;
    color: white;
}

.help-header h2 {
    margin: 12px 0 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.help-steps {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.help-step {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.step-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    flex-shrink: 0;
}

.step-content {
    flex: 1;
}

.step-content h4 {
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0 0 4px;
    color: #333;
}

.step-content p {
    font-size: 0.8rem;
    color: #666;
    margin: 0;
}

/* Evacuation Canvas */
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
    max-height: 50vh;
    min-height: 200px;
    border-radius: 12px;
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

.evacuation-legend {
    display: flex;
    gap: 24px;
    justify-content: center;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    color: #666;
}

.legend-line {
    width: 24px;
    height: 4px;
    background: #FF5722;
    border-radius: 2px;
}

.legend-room {
    width: 20px;
    height: 14px;
    background: rgba(76, 175, 80, 0.3);
    border: 2px solid #4CAF50;
    border-radius: 2px;
}

/* Utility */
.gap-2 {
    gap: 8px;
}

/* Mobile-responsive layout */
.v-main {
    padding-bottom: 0 !important;
}

/* Expansion Panel Customization */
:deep(.v-expansion-panel-title) {
    padding: 12px 16px !important;
    min-height: auto !important;
}

:deep(.v-expansion-panel-text__wrapper) {
    padding: 0 16px 16px !important;
}

/* Scrollbar Styling */
.content-wrapper::-webkit-scrollbar {
    width: 4px;
}

.content-wrapper::-webkit-scrollbar-track {
    background: transparent;
}

.content-wrapper::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.2);
    border-radius: 4px;
}

/* Prevent text selection everywhere except inputs */
* {
    -webkit-user-select: none;
    user-select: none;
}

input, textarea {
    -webkit-user-select: auto;
    user-select: auto;
}
</style>
