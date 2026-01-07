<template>
    <v-app>
        <v-app-bar color="primary" density="compact">
            <v-app-bar-nav-icon @click="drawer = !drawer" />
            <v-app-bar-title>PinPointMe</v-app-bar-title>
            <v-spacer />
            <v-btn icon @click="showHelp = true">
                <v-icon>mdi-help-circle-outline</v-icon>
            </v-btn>
            <!-- Notification Bell with Badge -->
            <v-btn icon @click="showNotificationPanel = !showNotificationPanel">
                <v-badge
                    :content="totalNotificationCount"
                    :model-value="totalNotificationCount > 0"
                    color="error"
                    overlap
                >
                    <v-icon>mdi-bell</v-icon>
                </v-badge>
            </v-btn>
            <v-avatar 
                size="32" 
                class="ml-1 mr-2"
                :style="userProfilePicture ? 'cursor: pointer' : ''"
                @click="goToProfile"
            >
                <v-img v-if="userProfilePicture" :src="userProfilePicture" cover />
                <v-icon v-else color="white">mdi-account-circle</v-icon>
            </v-avatar>
        </v-app-bar>

        <!-- Navigation Drawer -->
        <UserMenu v-model="drawer" />

        <!-- Notification Panel -->
        <v-navigation-drawer
            v-model="showNotificationPanel"
            location="right"
            temporary
            width="350"
        >
            <v-list-item class="pa-4 bg-primary">
                <template v-slot:prepend>
                    <v-icon color="white">mdi-bell</v-icon>
                </template>
                <v-list-item-title class="text-white font-weight-bold">
                    Notifications
                </v-list-item-title>
                <v-list-item-subtitle class="text-white">
                    {{ totalNotificationCount }} notification{{ totalNotificationCount !== 1 ? 's' : '' }}
                </v-list-item-subtitle>
            </v-list-item>

            <v-divider />

            <!-- Active Rescue Section -->
            <div v-if="hasActiveRequest && activeRequest">
                <v-list-subheader class="bg-warning-lighten-4 font-weight-bold">
                    <v-icon size="small" class="mr-1">mdi-ambulance</v-icon>
                    Active Rescue
                </v-list-subheader>
                <v-list-item @click="goToActiveRescue" class="notification-item">
                    <template v-slot:prepend>
                        <v-avatar :color="getStatusColor(activeRequest.status)" size="40">
                            <v-icon color="white">mdi-ambulance</v-icon>
                        </v-avatar>
                    </template>
                    <v-list-item-title class="font-weight-bold">
                        Rescue {{ activeRequest.rescue_code }}
                    </v-list-item-title>
                    <v-list-item-subtitle>
                        <v-chip
                            :color="getStatusColor(activeRequest.status)"
                            size="x-small"
                            variant="flat"
                            class="mr-2"
                        >
                            {{ formatStatus(activeRequest.status) }}
                        </v-chip>
                    </v-list-item-subtitle>
                    <v-list-item-subtitle>
                        <v-icon size="12">mdi-clock</v-icon>
                        {{ formatTimeAgo(activeRequest.created_at) }}
                    </v-list-item-subtitle>
                </v-list-item>
                <v-divider />
            </div>

            <!-- Chat Messages Section -->
            <div v-if="unreadChats.length > 0">
                <v-list-subheader class="bg-info-lighten-4 font-weight-bold">
                    <v-icon size="small" class="mr-1">mdi-chat</v-icon>
                    Unread Messages ({{ unreadMessageCount }})
                </v-list-subheader>
                <v-list lines="three">
                    <v-list-item
                        v-for="chat in unreadChats"
                        :key="chat.id"
                        @click="openChat(chat)"
                        class="notification-item"
                    >
                        <template v-slot:prepend>
                            <v-avatar color="info" size="40">
                                <v-img v-if="chat.other_user_picture" :src="chat.other_user_picture" cover />
                                <v-icon v-else color="white">mdi-account</v-icon>
                            </v-avatar>
                        </template>
                        <v-list-item-title class="font-weight-bold">
                            {{ chat.other_user_name }}
                        </v-list-item-title>
                        <v-list-item-subtitle class="text-truncate">
                            {{ chat.last_message }}
                        </v-list-item-subtitle>
                        <template v-slot:append>
                            <v-badge
                                :content="chat.unread_count"
                                color="error"
                                inline
                            />
                        </template>
                    </v-list-item>
                </v-list>
                <v-divider />
            </div>

            <!-- No Notifications -->
            <div v-if="!hasActiveRequest && unreadChats.length === 0" class="text-center py-8">
                <v-icon size="48" color="grey">mdi-bell-off-outline</v-icon>
                <p class="text-grey mt-2">No new notifications</p>
            </div>

            <template v-slot:append>
                <div class="pa-4">
                    <v-btn
                        v-if="hasActiveRequest"
                        block
                        color="warning"
                        variant="tonal"
                        class="mb-2"
                        @click="showNotificationPanel = false; goToActiveRescue()"
                    >
                        <v-icon start>mdi-ambulance</v-icon>
                        View Active Rescue
                    </v-btn>
                    <v-btn
                        block
                        color="primary"
                        variant="tonal"
                        @click="showNotificationPanel = false; goToInbox()"
                    >
                        <v-icon start>mdi-email</v-icon>
                        Open Inbox
                    </v-btn>
                </div>
            </template>
        </v-navigation-drawer>

        <v-main class="bg-user-gradient-light">
            <v-container fluid class="pa-4">
                <!-- Loading State while checking active request -->
                <div v-if="isCheckingActiveRequest" class="text-center py-10">
                    <v-progress-circular
                        indeterminate
                        color="primary"
                        size="50"
                    />
                    <p class="text-grey mt-4">Checking for active rescue requests...</p>
                </div>
                
                <!-- Active Rescue Request Display -->
                <div v-else-if="hasActiveRequest && activeRequest">
                    <div class="text-center mb-6">
                        <v-icon
                            size="80"
                            color="warning"
                        >
                            mdi-alert-circle
                        </v-icon>
                        <h1 class="text-h5 font-weight-bold mt-4">Active Rescue Request</h1>
                        <p class="text-body-2 text-grey">You have an ongoing rescue request</p>
                    </div>
                    
                    <v-card class="mb-6" elevation="4" rounded="lg">
                        <v-card-title class="d-flex align-center bg-warning">
                            <v-icon class="mr-2">mdi-ambulance</v-icon>
                            Current Rescue Request
                            <v-spacer />
                            <v-chip
                                :color="getStatusColor(activeRequest.status)"
                                variant="flat"
                                size="small"
                            >
                                {{ formatStatus(activeRequest.status) }}
                            </v-chip>
                        </v-card-title>
                        <v-card-text class="pt-4">
                            <v-list>
                                <v-list-item>
                                    <template v-slot:prepend>
                                        <v-icon color="primary">mdi-identifier</v-icon>
                                    </template>
                                    <v-list-item-title class="font-weight-bold">Rescue Code</v-list-item-title>
                                    <v-list-item-subtitle>{{ activeRequest.rescue_code }}</v-list-item-subtitle>
                                </v-list-item>
                                
                                <v-list-item v-if="activeRequest.building || activeRequest.floor || activeRequest.room">
                                    <template v-slot:prepend>
                                        <v-icon color="primary">mdi-map-marker</v-icon>
                                    </template>
                                    <v-list-item-title class="font-weight-bold">Location</v-list-item-title>
                                    <v-list-item-subtitle>
                                        {{ activeRequest.building?.name || 'N/A' }}
                                        <span v-if="activeRequest.floor"> - {{ activeRequest.floor.floor_name }}</span>
                                        <span v-if="activeRequest.room"> - {{ activeRequest.room.room_name }}</span>
                                    </v-list-item-subtitle>
                                </v-list-item>
                                
                                <v-list-item v-if="activeRequest.description">
                                    <template v-slot:prepend>
                                        <v-icon color="primary">mdi-text</v-icon>
                                    </template>
                                    <v-list-item-title class="font-weight-bold">Description</v-list-item-title>
                                    <v-list-item-subtitle>{{ activeRequest.description }}</v-list-item-subtitle>
                                </v-list-item>
                                
                                <v-list-item v-if="activeRequest.rescuer">
                                    <template v-slot:prepend>
                                        <v-icon color="success">mdi-account-hard-hat</v-icon>
                                    </template>
                                    <v-list-item-title class="font-weight-bold">Assigned Rescuer</v-list-item-title>
                                    <v-list-item-subtitle>
                                        {{ activeRequest.rescuer.first_name }} {{ activeRequest.rescuer.last_name }}
                                    </v-list-item-subtitle>
                                </v-list-item>
                                
                                <v-list-item v-else>
                                    <template v-slot:prepend>
                                        <v-icon color="grey">mdi-account-search</v-icon>
                                    </template>
                                    <v-list-item-title class="font-weight-bold">Assigned Rescuer</v-list-item-title>
                                    <v-list-item-subtitle>Waiting for rescuer assignment...</v-list-item-subtitle>
                                </v-list-item>
                                
                                <v-list-item>
                                    <template v-slot:prepend>
                                        <v-icon color="primary">mdi-clock</v-icon>
                                    </template>
                                    <v-list-item-title class="font-weight-bold">Requested At</v-list-item-title>
                                    <v-list-item-subtitle>
                                        {{ new Date(activeRequest.created_at).toLocaleString() }}
                                    </v-list-item-subtitle>
                                </v-list-item>
                            </v-list>
                        </v-card-text>
                        <v-card-actions class="pa-4">
                            <v-spacer />
                            <v-btn
                                color="primary"
                                size="large"
                                variant="elevated"
                                @click="goToActiveRescue"
                                prepend-icon="mdi-eye"
                            >
                                View Status
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                    
                    <v-alert
                        :type="activeRequest?.status === 'rescued' || activeRequest?.status === 'completed' ? 'warning' : 'info'"
                        variant="tonal"
                        class="mb-4"
                    >
                        <v-alert-title v-if="activeRequest?.status === 'rescued' || activeRequest?.status === 'completed'">
                            Please confirm you are safe
                        </v-alert-title>
                        <v-alert-title v-else>Need help?</v-alert-title>
                        <p class="mt-2" v-if="activeRequest?.status === 'rescued' || activeRequest?.status === 'completed'">
                            Your rescue has been completed. Please click "View Status" and confirm that you are safe 
                            before you can submit a new rescue request.
                        </p>
                        <p class="mt-2" v-else>
                            You can only have one active rescue request at a time. 
                            Please wait for your current request to be completed or cancelled 
                            before submitting a new one.
                        </p>
                    </v-alert>
                </div>

                <!-- Normal Scanner Form (only shown when no active request) -->
                <div v-else>
                <!-- Header Section -->
                <div class="text-center mb-6">
                    <div class="text-center mb-2">
                        <v-icon
                            size="80"
                            color="primary"
                        >
                            mdi-map-marker-radius
                        </v-icon>
                    </div>
                    <h1 class="text-h5 font-weight-bold">Emergency Scanner</h1>
                    <p class="text-body-2 text-grey">Scan your location or describe your emergency</p>
                </div>

                <!-- Main Action Cards -->
                <v-row justify="center" class="mb-6">
                    <!-- QR Scanner Card -->
                    <v-col cols="12" sm="6" md="4">
                        <v-card
                            class="text-center pa-6 h-100"
                            elevation="4"
                            rounded="lg"
                            @click="startQrScan"
                            :disabled="isScanning"
                        >
                            <v-icon size="64" color="primary" class="mb-4">
                                mdi-qrcode-scan
                            </v-icon>
                            <v-card-title class="text-h6">Scan QR Code</v-card-title>
                            <v-card-text>
                                Scan the room's QR code to set your location
                            </v-card-text>
                            <v-btn
                                color="primary"
                                variant="tonal"
                                :loading="isScanning"
                                @click.stop="startQrScan"
                            >
                                Start Scanning
                            </v-btn>
                        </v-card>
                    </v-col>

                    <!-- Voice Input Card -->
                    <v-col cols="12" sm="6" md="4">
                        <v-card
                            class="text-center pa-6 h-100"
                            elevation="4"
                            rounded="lg"
                            @click="toggleVoiceInput"
                        >
                            <v-icon
                                size="64"
                                :color="isRecording ? 'error' : 'success'"
                                class="mb-4"
                            >
                                {{ isRecording ? 'mdi-microphone-off' : 'mdi-microphone' }}
                            </v-icon>
                            <v-card-title class="text-h6">Voice Description</v-card-title>
                            <v-card-text>
                                Describe your emergency situation verbally
                            </v-card-text>
                            <v-btn
                                :color="isRecording ? 'error' : 'success'"
                                variant="tonal"
                                :loading="isProcessingAudio"
                                @click.stop="toggleVoiceInput"
                            >
                                {{ isRecording ? 'Stop Recording' : 'Start Recording' }}
                            </v-btn>
                            <div v-if="isRecording" class="mt-2 text-error">
                                Recording: {{ formatRecordingTime }}
                            </div>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Current Location Display -->
                <v-card
                    v-if="selectedBuilding || selectedFloor || selectedRoom"
                    class="mb-6"
                    :elevation="locationScanned ? 6 : 2"
                    rounded="lg"
                    :class="{ 'border-success': locationScanned }"
                    :style="locationScanned ? 'border: 2px solid rgb(var(--v-theme-success))' : ''"
                    ref="locationCard"
                >
                    <v-card-title class="d-flex align-center" :class="{ 'bg-success text-white': locationScanned }">
                        <v-icon :color="locationScanned ? 'white' : 'primary'" class="mr-2">
                            {{ locationScanned ? 'mdi-check-circle' : 'mdi-map-marker' }}
                        </v-icon>
                        {{ locationScanned ? 'Location Scanned Successfully!' : 'Current Location' }}
                        <v-spacer />
                        <v-chip v-if="locationScanned" color="white" variant="flat" size="small">
                            <v-icon start size="small">mdi-qrcode-scan</v-icon>
                            QR Scanned
                        </v-chip>
                    </v-card-title>
                    <v-card-text>
                        <v-chip-group>
                            <v-chip v-if="selectedBuilding" color="primary" variant="tonal">
                                <v-icon start>mdi-office-building</v-icon>
                                {{ selectedBuilding.name }}
                            </v-chip>
                            <v-chip v-if="selectedFloor" color="secondary" variant="tonal">
                                <v-icon start>mdi-stairs</v-icon>
                                {{ selectedFloor.floor_name }}
                            </v-chip>
                            <v-chip v-if="selectedRoom" color="success" variant="tonal">
                                <v-icon start>mdi-door</v-icon>
                                {{ selectedRoom.room_name }}
                            </v-chip>
                        </v-chip-group>
                        <p v-if="locationScanned" class="text-success mt-3 mb-0">
                            <v-icon size="small" class="mr-1">mdi-arrow-down</v-icon>
                            Fill in emergency details below and submit your rescue request
                        </p>
                    </v-card-text>
                </v-card>

                <!-- Manual Location Selection -->
                <v-card class="mb-6" elevation="2" rounded="lg">
                    <v-card-title>
                        <v-icon class="mr-2">mdi-map-marker-plus</v-icon>
                        Select Location Manually
                    </v-card-title>
                    <v-card-text>
                        <v-row>
                            <v-col cols="12" md="4">
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
                                />
                            </v-col>
                            <v-col cols="12" md="4">
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
                                />
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-select
                                    v-model="selectedRoom"
                                    :items="availableRooms"
                                    item-title="room_name"
                                    label="Room"
                                    variant="outlined"
                                    density="comfortable"
                                    return-object
                                    :disabled="!selectedFloor"
                                />
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <!-- Evacuation Path Display -->
                <v-card v-if="selectedRoom && hasFloorPlan" class="mb-6" elevation="3" rounded="lg">
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="error">mdi-exit-run</v-icon>
                        Evacuation Path for {{ selectedRoom.room_name }}
                    </v-card-title>
                    <v-card-subtitle>
                        {{ selectedBuilding?.name }} - {{ selectedFloor?.floor_name }}
                    </v-card-subtitle>
                    <v-card-text>
                        <div class="evacuation-canvas-container">
                            <div class="evacuation-canvas-wrapper" :style="evacuationWrapperStyle">
                                <img
                                    ref="evacuationImage"
                                    :src="selectedFloor.floor_plan_url"
                                    class="evacuation-image"
                                    @load="onEvacuationImageLoad"
                                />
                                <canvas
                                    ref="evacuationCanvas"
                                    class="evacuation-overlay"
                                ></canvas>
                            </div>
                        </div>
                        
                        <!-- Zoom Controls -->
                        <div class="d-flex justify-center align-center mt-3 gap-2">
                            <v-btn size="small" icon @click="evacuationZoomOut">
                                <v-icon>mdi-minus</v-icon>
                            </v-btn>
                            <v-chip size="small">{{ Math.round(evacuationZoom * 100) }}%</v-chip>
                            <v-btn size="small" icon @click="evacuationZoomIn">
                                <v-icon>mdi-plus</v-icon>
                            </v-btn>
                            <v-btn size="small" icon @click="resetEvacuationZoom">
                                <v-icon>mdi-restore</v-icon>
                            </v-btn>
                        </div>

                        <!-- Legend -->
                        <v-row class="mt-4">
                            <v-col cols="12" sm="6">
                                <div class="d-flex align-center">
                                    <div class="legend-line mr-2"></div>
                                    <span class="text-body-2">Evacuation Path</span>
                                </div>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <div class="d-flex align-center">
                                    <div class="legend-room mr-2"></div>
                                    <span class="text-body-2">Your Room</span>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <!-- Emergency Form -->
                <v-card id="emergency-form-section" class="mb-6" elevation="4" rounded="lg">
                    <v-card-title class="d-flex align-center bg-error text-white">
                        <v-icon class="mr-2">mdi-alert-circle</v-icon>
                        Emergency Details
                        <v-spacer />
                        <v-chip v-if="canSubmit" color="white" variant="flat" size="small">
                            <v-icon start size="small">mdi-check</v-icon>
                            Ready to Submit
                        </v-chip>
                    </v-card-title>
                    <v-card-text class="pt-4">
                        <v-form ref="emergencyFormRef">
                            <v-row>
                                <v-col cols="12" md="6">
                                    <v-text-field
                                        v-model="emergencyForm.firstName"
                                        label="First Name"
                                        variant="outlined"
                                        density="comfortable"
                                        prepend-inner-icon="mdi-account"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field
                                        v-model="emergencyForm.lastName"
                                        label="Last Name"
                                        variant="outlined"
                                        density="comfortable"
                                        prepend-inner-icon="mdi-account"
                                    />
                                </v-col>
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="emergencyForm.description"
                                        label="Describe your emergency"
                                        variant="outlined"
                                        rows="3"
                                        prepend-inner-icon="mdi-text"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-select
                                        v-model="emergencyForm.mobilityStatus"
                                        :items="mobilityOptions"
                                        label="Mobility Status"
                                        variant="outlined"
                                        density="comfortable"
                                        prepend-inner-icon="mdi-walk"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-select
                                        v-model="emergencyForm.urgencyLevel"
                                        :items="urgencyOptions"
                                        label="Urgency Level"
                                        variant="outlined"
                                        density="comfortable"
                                        prepend-inner-icon="mdi-speedometer"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-select
                                        v-model="emergencyForm.injuries"
                                        :items="injuryOptions"
                                        label="Type of Injury"
                                        variant="outlined"
                                        density="comfortable"
                                        prepend-inner-icon="mdi-medical-bag"
                                        multiple
                                        chips
                                        closable-chips
                                    />
                                </v-col>
                                <v-col cols="12" md="6" v-if="emergencyForm.injuries?.includes('other')">
                                    <v-text-field
                                        v-model="emergencyForm.otherInjury"
                                        label="Specify Other Injury"
                                        variant="outlined"
                                        density="comfortable"
                                        prepend-inner-icon="mdi-pencil"
                                    />
                                </v-col>
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="emergencyForm.additionalInfo"
                                        label="Additional Information"
                                        variant="outlined"
                                        rows="2"
                                        prepend-inner-icon="mdi-information"
                                    />
                                </v-col>
                            </v-row>
                        </v-form>
                    </v-card-text>
                    <v-card-actions class="pa-4">
                        <v-spacer />
                        <v-btn
                            color="error"
                            size="large"
                            variant="elevated"
                            :loading="isSubmitting"
                            :disabled="!canSubmit"
                            @click="submitRescueRequest"
                            prepend-icon="mdi-send"
                        >
                            Request Rescue
                        </v-btn>
                    </v-card-actions>
                </v-card>
                </div>
                <!-- End of Normal Scanner Form -->
            </v-container>

            <!-- QR Scanner Dialog -->
            <v-dialog v-model="showQrScanner" fullscreen>
                <v-card class="fill-height" color="black">
                    <v-toolbar color="primary">
                        <v-btn icon @click="showQrScanner = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                        <v-toolbar-title>Scan QR Code</v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-chip v-if="isScanning" color="success" size="small" class="mr-2">
                            <v-icon start size="small">mdi-circle</v-icon>
                            Scanning
                        </v-chip>
                    </v-toolbar>
                    <v-card-text class="d-flex flex-column align-center justify-center fill-height pa-0">
                        <div id="qr-reader" style="width: 100%; max-width: 500px;"></div>
                        <p class="text-white text-center mt-4">
                            Point your camera at a room QR code
                        </p>
                    </v-card-text>
                </v-card>
            </v-dialog>

            <!-- Help Dialog -->
            <v-dialog v-model="showHelp" max-width="500">
                <v-card>
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="primary">mdi-help-circle</v-icon>
                        How to Use
                    </v-card-title>
                    <v-card-text>
                        <v-list>
                            <v-list-item prepend-icon="mdi-numeric-1-circle">
                                <v-list-item-title>Scan QR Code</v-list-item-title>
                                <v-list-item-subtitle>
                                    Scan the room's QR code to automatically set your location
                                </v-list-item-subtitle>
                            </v-list-item>
                            <v-list-item prepend-icon="mdi-numeric-2-circle">
                                <v-list-item-title>Or Select Manually</v-list-item-title>
                                <v-list-item-subtitle>
                                    Choose your building, floor, and room from the dropdowns
                                </v-list-item-subtitle>
                            </v-list-item>
                            <v-list-item prepend-icon="mdi-numeric-3-circle">
                                <v-list-item-title>Describe Emergency</v-list-item-title>
                                <v-list-item-subtitle>
                                    Fill in the emergency details or use voice input
                                </v-list-item-subtitle>
                            </v-list-item>
                            <v-list-item prepend-icon="mdi-numeric-4-circle">
                                <v-list-item-title>Request Rescue</v-list-item-title>
                                <v-list-item-subtitle>
                                    Submit your request and wait for help
                                </v-list-item-subtitle>
                            </v-list-item>
                        </v-list>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer />
                        <v-btn color="primary" @click="showHelp = false">Got it</v-btn>
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
        
        const response = await getConversations(userData.value.id);
        const data = response.data || response;
        const convList = Array.isArray(data) ? data : (data?.data || []);
        
        conversations.value = convList.map((conv) => {
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
                unread_count: otherParticipant?.unread_count || 0,
            };
        });
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
});

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

// Trigger notification for new messages
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

    await nextTick();

    try {
        html5QrCode = new Html5Qrcode('qr-reader');
        
        await html5QrCode.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
            },
            onQrCodeScanned,
            (errorMessage) => {
                // QR code not found - this is normal, ignore
            }
        );
    } catch (error) {
        console.error('QR scan error:', error);
        showNotification('Failed to start camera. Please allow camera permissions.', 'error');
        isScanning.value = false;
        showQrScanner.value = false;
    }
};

const stopQrScanner = async () => {
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
        } catch (error) {
            console.error('Failed to start recording:', error);
            showNotification('Failed to access microphone', 'error');
        }
    }
};

const processAudioTranscription = async (audioBlob) => {
    try {
        console.log('🎤 Starting audio transcription...');
        console.log('Audio blob size:', audioBlob.size, 'bytes');
        console.log('Audio blob type:', audioBlob.type);
        
        const transcript = await transcribeAudio(audioBlob);
        console.log('✅ Transcription result:', transcript);
        
        if (transcript) {
            emergencyForm.value.description = transcript;

            // Try to extract emergency fields
            const fields = await extractFieldsAndInferLocation(transcript);
            if (fields) {
                if (fields.mobility_status) emergencyForm.value.mobilityStatus = fields.mobility_status;
                if (fields.urgency_level) emergencyForm.value.urgencyLevel = fields.urgency_level;
                if (fields.injuries) emergencyForm.value.injuries = fields.injuries;
                if (fields.additional_info) emergencyForm.value.additionalInfo = fields.additional_info;

                // Handle location inference
                if (fields.location_inference) {
                    await applyLocationInference(fields.location_inference);
                }
            }
            showNotification('Audio transcribed successfully', 'success');
        } else {
            showNotification('No speech detected in audio', 'warning');
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

        const result = await createRescueRequest(payload);

        if (result.rescueCode && result.requestId) {
            localStorage.setItem('lastRescueCode', result.rescueCode);
            localStorage.setItem('lastRescueRequestId', result.requestId);
            localStorage.setItem('lastRescueRequestTime', new Date().toISOString());

            showNotification('Rescue request submitted successfully!', 'success');

            // Navigate to help coming page
            setTimeout(() => {
                router.visit(`/user/help-coming/${result.rescueCode}`);
            }, 500);
        }
    } catch (error) {
        console.error('Failed to submit rescue request:', error);
        showNotification('Failed to submit rescue request', 'error');
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

const showNotification = (message, color = 'info') => {
    toastMessage.value = message;
    toastColor.value = color;
    showToast.value = true;
};
</script>

<style scoped>
/* Component-specific styles only - background is now global */
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

.gap-2 {
    gap: 8px;
}
</style>
