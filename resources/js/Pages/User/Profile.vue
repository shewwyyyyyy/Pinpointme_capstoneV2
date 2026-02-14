<template>
    <v-app class="bg-user-gradient-light">
        <!-- App Bar -->
        <UserAppBar 
            title="Profile" 
            subtitle="Manage your account"
            :show-back="true"
            :notification-count="0"
            @go-back="goBack"
        />

        <v-main class="pb-20">
            <!-- Loading State -->
            <div v-if="loading" class="d-flex justify-center align-center" style="min-height: 60vh;">
                <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
            </div>

            <v-container v-else fluid class="profile-container">
                <!-- Profile Header Card -->
                <v-card 
                    class="mb-3 mb-sm-4 rounded-xl overflow-hidden" 
                    elevation="0"
                    color="white"
                >
                    <div class="profile-header-bg pa-4 pa-sm-6 text-center">
                        <!-- Avatar with Edit Button -->
                        <div class="position-relative d-inline-block">
                            <v-avatar size="88" class="elevation-4 avatar-ring avatar-mobile">
                                <v-img 
                                    v-if="profilePictureUrl" 
                                    :src="profilePictureUrl" 
                                    cover
                                ></v-img>
                                <span v-else class="text-h4 font-weight-bold text-white">
                                    {{ getInitials }}
                                </span>
                            </v-avatar>
                            <v-btn
                                icon
                                size="32"
                                color="primary"
                                class="position-absolute edit-avatar-btn elevation-3"
                                @click="openPhotoDialog"
                            >
                                <v-icon size="18">mdi-camera</v-icon>
                            </v-btn>
                        </div>
                        
                        <h2 class="text-h6 text-sm-h5 font-weight-bold mt-3 text-white">{{ fullName }}</h2>
                        <p class="text-caption text-sm-body-2 text-white-darken-1 mb-0">
                            {{ user?.email }}
                        </p>
                        
                        
                    </div>

                    <!-- Stats Row -->
                    <v-row no-gutters class="text-center py-2 py-sm-3 stats-row">
                        <v-col cols="4">
                            <div class="text-h6 text-sm-h5 font-weight-bold text-success">{{ stats.rescued }}</div>
                            <div class="text-caption text-grey">Rescued</div>
                        </v-col>
                        <v-divider vertical></v-divider>
                        <v-col cols="4">
                            <div class="text-h6 text-sm-h5 font-weight-bold text-warning">{{ stats.pending }}</div>
                            <div class="text-caption text-grey">Pending</div>
                        </v-col>
                        <v-divider vertical></v-divider>
                        <v-col cols="4">
                            <div class="text-h6 text-sm-h5 font-weight-bold text-primary">{{ stats.total }}</div>
                            <div class="text-caption text-grey">Total</div>
                        </v-col>
                    </v-row>
                </v-card>

                <!-- Personal Information -->
                <v-card class="mb-3 mb-sm-4 rounded-xl section-card" elevation="0" color="white">
                    <div class="pa-4">
                        <div class="d-flex align-center mb-4">
                            <v-avatar color="primary" size="32" class="mr-3">
                                <v-icon color="white" size="18">mdi-account</v-icon>
                            </v-avatar>
                            <span class="text-body-1 text-sm-subtitle-1 font-weight-bold">Personal Information</span>
                        </div>
                        
                        <v-form ref="formRef" v-model="formValid">
                            <v-row dense>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editData.first_name"
                                        label="First Name"
                                        :rules="[rules.required]"
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details="auto"
                                        class="mb-3 mobile-input"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editData.last_name"
                                        label="Last Name"
                                        :rules="[rules.required]"
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details="auto"
                                        class="mb-3 mobile-input"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="user.email"
                                        label="Email"
                                        readonly
                                        disabled
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details="auto"
                                        class="mb-3 non-editable-field mobile-input"
                                        prepend-inner-icon="mdi-email-outline"
                                        bg-color="grey-lighten-3"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="editData.phone_number"
                                        label="Phone Number"
                                        :rules="[rules.phoneNumber]"
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details="auto"
                                        class="mb-3 mobile-input"
                                        prepend-inner-icon="mdi-phone-outline"
                                        placeholder="09171234567"
                                        hint="Mobile number (e.g., 09171234567)"
                                        persistent-hint
                                        @input="formatPhoneNumber('phone_number')"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="editData.id_number"
                                        label="ID Number"
                                        :rules="[rules.idNumber]"
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details="auto"
                                        class="mb-3 mobile-input"
                                        prepend-inner-icon="mdi-card-account-details-outline"
                                        placeholder="Enter 9-digit ID number"
                                        maxlength="9"
                                        @input="formatIdNumber"
                                    ></v-text-field>
                                    <p v-if="editData.id_number && isValidIdNumber" class="text-caption mt-1 px-2" :class="userRoleFromId === 'student' ? 'text-primary' : 'text-success'">
                                        <v-icon size="14" class="mr-1">{{ userRoleFromId === 'student' ? 'mdi-school' : 'mdi-account-tie' }}</v-icon>
                                        This ID belongs to a <strong>{{ userRoleFromId === 'student' ? 'Student' : 'Faculty' }}</strong>
                                    </p>
                                </v-col>
                            </v-row>
                        </v-form>
                        
                        <div v-if="hasPersonalChanges" class="d-flex flex-column flex-sm-row gap-2 mt-3">
                            <v-btn
                                color="grey"
                                variant="outlined"
                                @click="cancelEdit"
                                class="rounded-lg flex-grow-1 mobile-btn"
                                size="large"
                            >
                                Cancel
                            </v-btn>
                            <v-btn
                                color="primary"
                                variant="flat"
                                :loading="saving"
                                :disabled="!formValid"
                                @click="saveProfile"
                                class="rounded-lg flex-grow-1 mobile-btn"
                                size="large"
                            >
                                Save Changes
                            </v-btn>
                        </div>
                    </div>
                </v-card>

                <!-- Emergency Contact -->
                <v-card class="mb-3 mb-sm-4 rounded-xl section-card" elevation="0" color="white">
                    <v-expansion-panels flat v-model="emergencyPanel">
                        <v-expansion-panel>
                            <v-expansion-panel-title class="panel-title-mobile">
                                <div class="d-flex align-center">
                                    <v-avatar color="error" size="32" class="mr-3">
                                        <v-icon color="white" size="18">mdi-phone-in-talk</v-icon>
                                    </v-avatar>
                                    <span class="text-body-1 text-sm-subtitle-1 font-weight-bold">Emergency Contact</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text class="panel-content-mobile">
                                <v-row dense>
                                    <v-col cols="12">
                                        <v-text-field
                                            v-model="editData.emergency_contact_name"
                                            label="Contact Name"
                                            variant="outlined"
                                            density="comfortable"
                                            hide-details="auto"
                                            class="mb-3 mobile-input"
                                            prepend-inner-icon="mdi-account-heart-outline"
                                            placeholder="Enter emergency contact name"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="editData.emergency_contact_phone"
                                            label="Contact Phone"
                                            :rules="[rules.phoneNumber]"
                                            variant="outlined"
                                            density="comfortable"
                                            hide-details="auto"
                                            class="mb-3 mobile-input"
                                            prepend-inner-icon="mdi-phone-outline"
                                            placeholder="09171234567"
                                            hint="Mobile number (e.g., 09171234567)"
                                            persistent-hint
                                            @input="formatPhoneNumber('emergency_contact_phone')"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="editData.emergency_contact_relation"
                                            label="Relationship"
                                            variant="outlined"
                                            density="comfortable"
                                            hide-details="auto"
                                            class="mb-3 mobile-input"
                                            prepend-inner-icon="mdi-account-tie-outline"
                                            placeholder="e.g., Parent, Spouse, Sibling"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                                
                                <div v-if="hasEmergencyChanges" class="d-flex flex-column flex-sm-row gap-2 mt-2">
                                    <v-btn
                                        color="grey"
                                        variant="outlined"
                                        @click="cancelEditEmergency"
                                        class="rounded-lg flex-grow-1 mobile-btn"
                                        size="large"
                                    >
                                        Cancel
                                    </v-btn>
                                    <v-btn
                                        color="primary"
                                        variant="flat"
                                        :loading="savingEmergency"
                                        @click="saveEmergencyContact"
                                        class="rounded-lg flex-grow-1 mobile-btn"
                                        size="large"
                                    >
                                        Save Changes
                                    </v-btn>
                                </div>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card>

                <!-- Medical Information -->
                <v-card class="mb-3 mb-sm-4 rounded-xl section-card" elevation="0" color="white">
                    <v-expansion-panels flat v-model="medicalPanel">
                        <v-expansion-panel>
                            <v-expansion-panel-title class="panel-title-mobile">
                                <div class="d-flex align-center">
                                    <v-avatar color="red" size="32" class="mr-3">
                                        <v-icon color="white" size="18">mdi-medical-bag</v-icon>
                                    </v-avatar>
                                    <span class="text-body-1 text-sm-subtitle-1 font-weight-bold">Medical Information</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text class="panel-content-mobile">
                                <v-row dense>
                                    <v-col cols="12" sm="6">
                                        <v-select
                                            v-model="editData.blood_type"
                                            label="Blood Type"
                                            :items="['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']"
                                            variant="outlined"
                                            density="comfortable"
                                            hide-details="auto"
                                            class="mb-3 mobile-input"
                                            clearable
                                            prepend-inner-icon="mdi-blood-bag"
                                        ></v-select>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-textarea
                                            v-model="editData.allergies"
                                            label="Allergies"
                                            variant="outlined"
                                            density="comfortable"
                                            hide-details="auto"
                                            class="mb-3 mobile-input"
                                            rows="2"
                                            placeholder="List any known allergies..."
                                            prepend-inner-icon="mdi-allergy"
                                        ></v-textarea>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-textarea
                                            v-model="editData.medical_conditions"
                                            label="Medical Conditions"
                                            variant="outlined"
                                            density="comfortable"
                                            hide-details="auto"
                                            rows="2"
                                            placeholder="List any medical conditions..."
                                            prepend-inner-icon="mdi-pill"
                                            class="mobile-input"
                                        ></v-textarea>
                                    </v-col>
                                </v-row>
                                
                                <div v-if="hasMedicalChanges" class="d-flex flex-column flex-sm-row gap-2 mt-2">
                                    <v-btn
                                        color="grey"
                                        variant="outlined"
                                        @click="cancelEditMedical"
                                        class="rounded-lg flex-grow-1 mobile-btn"
                                        size="large"
                                    >
                                        Cancel
                                    </v-btn>
                                    <v-btn
                                        color="primary"
                                        variant="flat"
                                        :loading="savingMedical"
                                        @click="saveMedicalInfo"
                                        class="rounded-lg flex-grow-1 mobile-btn"
                                        size="large"
                                    >
                                        Save Changes
                                    </v-btn>
                                </div>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card>

                <!-- Security Section -->
                <v-card class="mb-3 mb-sm-4 rounded-xl section-card" elevation="0" color="white">
                    <v-expansion-panels flat v-model="securityPanel">
                        <v-expansion-panel>
                            <v-expansion-panel-title class="panel-title-mobile">
                                <div class="d-flex align-center">
                                    <v-avatar color="indigo" size="32" class="mr-3">
                                        <v-icon color="white" size="18">mdi-shield-lock</v-icon>
                                    </v-avatar>
                                    <span class="text-body-1 text-sm-subtitle-1 font-weight-bold">Security</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text class="panel-content-mobile">
                                <v-btn
                                    block
                                    color="primary"
                                    variant="tonal"
                                    @click="openChangePasswordDialog"
                                    class="rounded-lg mobile-btn"
                                    size="large"
                                >
                                    <v-icon start>mdi-lock-outline</v-icon>
                                    Change Password
                                </v-btn>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card>

                <!-- Settings Section -->
                <v-card class="mb-3 mb-sm-4 rounded-xl section-card" elevation="0" color="white">
                    <v-expansion-panels flat v-model="settingsPanel">
                        <v-expansion-panel>
                            <v-expansion-panel-title class="panel-title-mobile">
                                <div class="d-flex align-center">
                                    <v-avatar color="grey" size="32" class="mr-3">
                                        <v-icon color="white" size="18">mdi-cog-outline</v-icon>
                                    </v-avatar>
                                    <span class="text-body-1 text-sm-subtitle-1 font-weight-bold">Settings</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text class="panel-content-mobile">
                                <v-list class="bg-transparent pa-0 settings-list">
                                    <v-list-item class="px-2 py-3 rounded-lg mb-2 setting-item">
                                        <template v-slot:prepend>
                                            <v-avatar color="primary" variant="tonal" size="36" class="mr-3">
                                                <v-icon size="20">mdi-bell-outline</v-icon>
                                            </v-avatar>
                                        </template>
                                        <v-list-item-title class="text-body-2 font-weight-medium">Push Notifications</v-list-item-title>
                                        <v-list-item-subtitle class="text-caption">Receive alerts for emergencies</v-list-item-subtitle>
                                        <template v-slot:append>
                                            <v-switch
                                                v-model="settings.pushNotifications"
                                                color="primary"
                                                hide-details
                                                inset
                                                @change="updateSetting('Notifications')"
                                            />
                                        </template>
                                    </v-list-item>
                                    <v-list-item class="px-2 py-3 rounded-lg mb-2 setting-item">
                                        <template v-slot:prepend>
                                            <v-avatar color="success" variant="tonal" size="36" class="mr-3">
                                                <v-icon size="20">mdi-map-marker-outline</v-icon>
                                            </v-avatar>
                                        </template>
                                        <v-list-item-title class="text-body-2 font-weight-medium">Location Services</v-list-item-title>
                                        <v-list-item-subtitle class="text-caption">Enable for faster rescue</v-list-item-subtitle>
                                        <template v-slot:append>
                                            <v-switch
                                                v-model="settings.locationServices"
                                                color="primary"
                                                hide-details
                                                inset
                                                @change="updateSetting('Location')"
                                            />
                                        </template>
                                    </v-list-item>
                                    <v-list-item class="px-2 py-3 rounded-lg setting-item">
                                        <template v-slot:prepend>
                                            <v-avatar color="grey-darken-1" variant="tonal" size="36" class="mr-3">
                                                <v-icon size="20">mdi-theme-light-dark</v-icon>
                                            </v-avatar>
                                        </template>
                                        <v-list-item-title class="text-body-2 font-weight-medium">Dark Mode</v-list-item-title>
                                        <v-list-item-subtitle class="text-caption">Easier on eyes at night</v-list-item-subtitle>
                                        <template v-slot:append>
                                            <v-switch
                                                v-model="settings.darkMode"
                                                color="primary"
                                                hide-details
                                                inset
                                                @change="updateSetting('DarkMode')"
                                            />
                                        </template>
                                    </v-list-item>
                                </v-list>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card>

                <!-- Logout Button -->
                <v-btn
                    block
                    color="error"
                    variant="tonal"
                    size="x-large"
                    class="rounded-xl mb-4 logout-btn"
                    @click="showLogoutDialog = true"
                >
                    <v-icon start>mdi-logout</v-icon>
                    Logout
                </v-btn>

                <!-- App Version -->
                <p class="text-center text-caption text-grey mt-4 mb-8">
                    PinPointMe v1.0.0
                </p>
            </v-container>

        </v-main>

        <!-- Bottom Navigation -->
        <UserBottomNav :notification-count="0" :message-count="unreadCount" />

        <!-- Logout Confirmation Dialog -->
        <v-dialog v-model="showLogoutDialog" max-width="340" persistent>
            <v-card class="rounded-xl">
                <v-card-text class="text-center pa-6">
                    <v-icon size="64" color="error" class="mb-4">mdi-logout-variant</v-icon>
                    <h3 class="text-h6 font-weight-bold mb-2">Logout</h3>
                    <p class="text-body-2 text-grey mb-0">Are you sure you want to logout?</p>
                </v-card-text>
                <v-card-actions class="pa-4 pt-0">
                    <v-row dense>
                        <v-col cols="6">
                            <v-btn
                                block
                                variant="outlined"
                                color="grey"
                                @click="showLogoutDialog = false"
                                class="rounded-lg"
                            >
                                Cancel
                            </v-btn>
                        </v-col>
                        <v-col cols="6">
                            <v-btn
                                block
                                color="error"
                                variant="flat"
                                :loading="loggingOut"
                                @click="handleLogout"
                                class="rounded-lg"
                            >
                                Logout
                            </v-btn>
                        </v-col>
                    </v-row>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Photo Upload Dialog -->
        <v-dialog v-model="photoDialog" max-width="340" persistent>
            <v-card class="rounded-xl">
                <v-card-title class="d-flex align-center pa-4">
                    <span class="text-subtitle-1 font-weight-bold">Profile Photo</span>
                    <v-spacer></v-spacer>
                    <v-btn icon size="small" variant="text" @click="cancelPhotoUpload">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-divider></v-divider>
                
                <v-card-text class="text-center pa-4">
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/*"
                        style="display: none;"
                        @change="handleFileSelect"
                    />
                    
                    <v-avatar size="120" class="mb-4 elevation-2">
                        <v-img 
                            v-if="previewUrl || profilePictureUrl"
                            :src="previewUrl || profilePictureUrl" 
                            cover
                        ></v-img>
                        <span v-else class="text-h3 font-weight-bold">{{ getInitials }}</span>
                    </v-avatar>
                    
                    <v-btn
                        block
                        variant="outlined"
                        color="primary"
                        class="mb-2 rounded-lg"
                        @click="triggerFileInput"
                    >
                        <v-icon start>mdi-camera</v-icon>
                        {{ previewUrl ? 'Change Photo' : 'Select Photo' }}
                    </v-btn>
                    
                    <v-btn
                        v-if="profilePictureUrl && !previewUrl"
                        block
                        variant="outlined"
                        color="error"
                        class="rounded-lg"
                        @click="confirmDeletePhoto"
                    >
                        <v-icon start>mdi-delete</v-icon>
                        Remove Photo
                    </v-btn>
                </v-card-text>
                
                <v-card-actions v-if="selectedFile" class="pa-4 pt-0">
                    <v-btn
                        block
                        color="primary"
                        variant="flat"
                        :loading="uploadingPhoto"
                        @click="uploadPhoto"
                        class="rounded-lg"
                    >
                        <v-icon start>mdi-check</v-icon>
                        Save Photo
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Photo Confirmation -->
        <v-dialog v-model="deletePhotoDialog" max-width="320" persistent>
            <v-card class="rounded-xl">
                <v-card-text class="text-center pa-6">
                    <v-icon size="48" color="error" class="mb-3">mdi-delete-alert</v-icon>
                    <h3 class="text-subtitle-1 font-weight-bold mb-2">Remove Photo?</h3>
                    <p class="text-body-2 text-grey mb-0">This action cannot be undone.</p>
                </v-card-text>
                <v-card-actions class="pa-4 pt-0">
                    <v-row dense>
                        <v-col cols="6">
                            <v-btn
                                block
                                variant="outlined"
                                color="grey"
                                @click="deletePhotoDialog = false"
                                class="rounded-lg"
                            >
                                Cancel
                            </v-btn>
                        </v-col>
                        <v-col cols="6">
                            <v-btn
                                block
                                color="error"
                                variant="flat"
                                :loading="deletingPhoto"
                                @click="deletePhoto"
                                class="rounded-lg"
                            >
                                Remove
                            </v-btn>
                        </v-col>
                    </v-row>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Change Password Dialog - OTP Based -->
        <v-dialog v-model="passwordDialog" max-width="450" persistent>
            <v-card rounded="lg">
                <v-card-title class="d-flex align-center pa-4 bg-primary">
                    <v-icon color="white" class="mr-2">mdi-lock-reset</v-icon>
                    <span class="text-white">Change Password</span>
                    <v-spacer />
                    <v-btn icon variant="text" @click="closePasswordDialog" :disabled="sendingOtp || verifyingOtp || changingPassword">
                        <v-icon color="white">mdi-close</v-icon>
                    </v-btn>
                </v-card-title>

                <v-card-text class="pa-6">
                    <!-- Step Indicator -->
                    <div class="d-flex justify-center mb-4">
                        <div class="d-flex align-center">
                            <v-avatar :color="passwordStep >= 1 ? 'primary' : 'grey-lighten-2'" size="28">
                                <span class="text-white text-caption font-weight-bold">1</span>
                            </v-avatar>
                            <div class="step-line" :class="{ 'active': passwordStep >= 2 }"></div>
                            <v-avatar :color="passwordStep >= 2 ? 'primary' : 'grey-lighten-2'" size="28">
                                <span :class="passwordStep >= 2 ? 'text-white' : 'text-grey'" class="text-caption font-weight-bold">2</span>
                            </v-avatar>
                            <div class="step-line" :class="{ 'active': passwordStep >= 3 }"></div>
                            <v-avatar :color="passwordStep >= 3 ? 'primary' : 'grey-lighten-2'" size="28">
                                <span :class="passwordStep >= 3 ? 'text-white' : 'text-grey'" class="text-caption font-weight-bold">3</span>
                            </v-avatar>
                        </div>
                    </div>

                    <!-- Success State -->
                    <div v-if="passwordComplete" class="text-center py-4">
                        <v-icon size="64" color="success" class="mb-4">mdi-check-circle</v-icon>
                        <h3 class="text-h6 font-weight-bold mb-2">Password Changed!</h3>
                        <p class="text-body-2 text-grey mb-4">
                            Your password has been changed successfully.
                        </p>
                    </div>

                    <!-- Step 1: Request OTP -->
                    <div v-else-if="passwordStep === 1">
                        <div class="text-center mb-4">
                            <v-icon size="48" color="primary" class="mb-2">mdi-email-send-outline</v-icon>
                            <p class="text-body-2 text-grey">
                                We'll send a verification code to your email
                            </p>
                            <p class="text-body-2 font-weight-bold text-primary">{{ user.email }}</p>
                        </div>

                        <v-alert
                            v-if="passwordError"
                            type="error"
                            variant="tonal"
                            class="mb-4"
                            closable
                            @click:close="passwordError = ''"
                        >
                            {{ passwordError }}
                        </v-alert>
                    </div>

                    <!-- Step 2: Enter OTP -->
                    <div v-else-if="passwordStep === 2">
                        <div class="text-center mb-4">
                            <v-icon size="48" color="primary" class="mb-2">mdi-email-check</v-icon>
                            <p class="text-body-2 text-grey">
                                Enter the 6-digit code sent to
                            </p>
                            <p class="text-body-2 font-weight-bold text-primary">{{ user.email }}</p>
                        </div>

                        <v-alert
                            v-if="passwordError"
                            type="error"
                            variant="tonal"
                            class="mb-4"
                            closable
                            @click:close="passwordError = ''"
                        >
                            {{ passwordError }}
                        </v-alert>

                        <v-otp-input
                            v-model="otpCode"
                            length="6"
                            variant="outlined"
                            :disabled="verifyingOtp"
                            class="mb-4"
                        />

                        <p class="text-caption text-grey text-center">
                            Didn't receive the code? 
                            <v-btn 
                                variant="text" 
                                color="primary" 
                                size="small" 
                                @click="sendPasswordOtp"
                                :disabled="resendCountdown > 0 || sendingOtp"
                            >
                                {{ resendCountdown > 0 ? `Resend in ${resendCountdown}s` : 'Resend Code' }}
                            </v-btn>
                        </p>
                    </div>

                    <!-- Step 3: Enter New Password -->
                    <div v-else-if="passwordStep === 3">
                        <p class="text-body-2 text-grey mb-4 text-center">
                            Create a new password for your account.
                        </p>

                        <v-alert
                            v-if="passwordError"
                            type="error"
                            variant="tonal"
                            class="mb-4"
                            closable
                            @click:close="passwordError = ''"
                        >
                            {{ passwordError }}
                        </v-alert>

                        <v-form ref="passwordFormRef" v-model="passwordFormValid">
                            <v-text-field
                                v-model="passwordData.new_password"
                                label="New Password"
                                :type="showNewPassword ? 'text' : 'password'"
                                variant="outlined"
                                density="comfortable"
                                prepend-inner-icon="mdi-lock"
                                :append-inner-icon="showNewPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                @click:append-inner="showNewPassword = !showNewPassword"
                                :rules="[rules.required, rules.minLength, rules.hasUppercase, rules.hasLowercase, rules.hasNumber, rules.hasSpecial]"
                                :disabled="changingPassword"
                                class="mb-1"
                            />

                            <!-- Password Requirements Checklist -->
                            <div class="password-requirements mb-4 px-2">
                                <p class="text-caption text-grey-darken-1 mb-2">Password must contain:</p>
                                <div class="requirements-grid">
                                    <div class="requirement-item" :class="{ 'met': passwordChecks.length }">
                                        <v-icon size="14" :color="passwordChecks.length ? 'success' : 'grey-lighten-1'">
                                            {{ passwordChecks.length ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                        </v-icon>
                                        <span>At least 8 characters</span>
                                    </div>
                                    <div class="requirement-item" :class="{ 'met': passwordChecks.uppercase }">
                                        <v-icon size="14" :color="passwordChecks.uppercase ? 'success' : 'grey-lighten-1'">
                                            {{ passwordChecks.uppercase ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                        </v-icon>
                                        <span>One uppercase letter (A-Z)</span>
                                    </div>
                                    <div class="requirement-item" :class="{ 'met': passwordChecks.lowercase }">
                                        <v-icon size="14" :color="passwordChecks.lowercase ? 'success' : 'grey-lighten-1'">
                                            {{ passwordChecks.lowercase ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                        </v-icon>
                                        <span>One lowercase letter (a-z)</span>
                                    </div>
                                    <div class="requirement-item" :class="{ 'met': passwordChecks.number }">
                                        <v-icon size="14" :color="passwordChecks.number ? 'success' : 'grey-lighten-1'">
                                            {{ passwordChecks.number ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                        </v-icon>
                                        <span>One number (0-9)</span>
                                    </div>
                                    <div class="requirement-item" :class="{ 'met': passwordChecks.special }">
                                        <v-icon size="14" :color="passwordChecks.special ? 'success' : 'grey-lighten-1'">
                                            {{ passwordChecks.special ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                        </v-icon>
                                        <span>One special character (!@#$%...)</span>
                                    </div>
                                </div>
                            </div>

                            <v-text-field
                                v-model="passwordData.confirm_password"
                                label="Confirm New Password"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                variant="outlined"
                                density="comfortable"
                                prepend-inner-icon="mdi-lock-check"
                                :append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                @click:append-inner="showConfirmPassword = !showConfirmPassword"
                                :rules="[rules.required, rules.passwordMatch]"
                                :disabled="changingPassword"
                                :error="passwordData.confirm_password && passwordData.confirm_password !== passwordData.new_password"
                            />
                            <p v-if="passwordData.confirm_password && passwordData.confirm_password === passwordData.new_password" class="text-caption text-success mt-1 px-2">
                                <v-icon size="14" color="success">mdi-check-circle</v-icon>
                                Passwords match
                            </p>
                        </v-form>
                    </div>
                </v-card-text>

                <v-card-actions class="pa-4 pt-0">
                    <!-- Success State Actions -->
                    <template v-if="passwordComplete">
                        <v-spacer />
                        <v-btn color="primary" @click="closePasswordDialog">
                            Done
                        </v-btn>
                    </template>

                    <!-- Step 1 Actions -->
                    <template v-else-if="passwordStep === 1">
                        <v-btn variant="text" @click="closePasswordDialog" :disabled="sendingOtp">
                            Cancel
                        </v-btn>
                        <v-spacer />
                        <v-btn 
                            color="primary" 
                            @click="sendPasswordOtp"
                            :loading="sendingOtp"
                        >
                            Send Code
                        </v-btn>
                    </template>

                    <!-- Step 2 Actions -->
                    <template v-else-if="passwordStep === 2">
                        <v-btn variant="text" @click="passwordStep = 1" :disabled="verifyingOtp">
                            Back
                        </v-btn>
                        <v-spacer />
                        <v-btn 
                            color="primary" 
                            @click="verifyPasswordOtp"
                            :loading="verifyingOtp"
                            :disabled="otpCode.length !== 6"
                        >
                            Verify Code
                        </v-btn>
                    </template>

                    <!-- Step 3 Actions -->
                    <template v-else-if="passwordStep === 3">
                        <v-btn variant="text" @click="passwordStep = 2; otpCode = ''" :disabled="changingPassword">
                            Back
                        </v-btn>
                        <v-spacer />
                        <v-btn 
                            color="primary" 
                            @click="changePassword"
                            :loading="changingPassword"
                            :disabled="!isPasswordValid || !passwordData.confirm_password || passwordData.confirm_password !== passwordData.new_password"
                        >
                            Update Password
                        </v-btn>
                    </template>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar
            v-model="snackbar.show"
            :color="snackbar.color"
            :timeout="3000"
            location="top"
        >
            {{ snackbar.message }}
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { getCurrentUser, updateUser, uploadProfilePicture, deleteProfilePicture, getProfilePictureUrl, getUserRescueHistory } from '@/Composables/useApi';
import { useUnreadMessages } from '@/Composables/useUnreadMessages';
import { setUserActiveStatus } from '@/Utilities/firebase';
import UserAppBar from '@/Components/Pages/User/Menu/UserAppBar.vue';
import UserBottomNav from '@/Components/Pages/User/Menu/UserBottomNav.vue';

// Get Inertia page for auth
const page = usePage();
const authUser = computed(() => page.props?.auth?.user);

// Unread messages count for bottom nav
const { unreadCount } = useUnreadMessages();

// Refs
const formRef = ref(null);
const passwordFormRef = ref(null);
const fileInput = ref(null);

// State
const loading = ref(true);
const saving = ref(false);
const changingPassword = ref(false);
const uploadingPhoto = ref(false);
const deletingPhoto = ref(false);
const loggingOut = ref(false);
const isEditing = ref(false);
const formValid = ref(true);
const passwordFormValid = ref(false);
const showLogoutDialog = ref(false);
const photoDialog = ref(false);
const deletePhotoDialog = ref(false);

// Password change OTP flow
const passwordDialog = ref(false);
const passwordStep = ref(1); // 1, 2, 3 for request, otp, password
const passwordComplete = ref(false);
const passwordError = ref('');
const otpCode = ref('');
const verificationToken = ref('');
const sendingOtp = ref(false);
const verifyingOtp = ref(false);
const resendCountdown = ref(0);
let countdownInterval = null;

// Separate editing states for Emergency Contact and Medical sections
const isEditingEmergency = ref(false);
const isEditingMedical = ref(false);
const savingEmergency = ref(false);
const savingMedical = ref(false);

// Panel states
const emergencyPanel = ref(null);
const medicalPanel = ref(null);
const historyPanel = ref(null);
const settingsPanel = ref(null);
const securityPanel = ref(null);

// Password visibility
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

// Photo upload
const selectedFile = ref(null);
const previewUrl = ref(null);

// User data
const user = ref({
    id: null,
    first_name: '',
    last_name: '',
    email: '',
    phone_number: '',
    id_number: '',
    profile_picture: null,
    emergency_contact_name: '',
    emergency_contact_phone: '',
    emergency_contact_relation: '',
    blood_type: '',
    allergies: '',
    medical_conditions: '',
    is_verified: false,
    created_at: null,
});

// Edit data
const editData = reactive({
    first_name: '',
    last_name: '',
    phone_number: '',
    id_number: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    emergency_contact_relation: '',
    blood_type: '',
    allergies: '',
    medical_conditions: '',
});

// Password data
const passwordData = reactive({
    new_password: '',
    confirm_password: '',
});

// Settings
const settings = reactive({
    pushNotifications: true,
    locationServices: true,
    darkMode: false,
});

// Location History
const locationHistory = ref([]);
const loadingHistory = ref(false);

// Stats
const stats = ref({
    rescued: 0,
    pending: 0,
    total: 0,
});

const snackbar = ref({
    show: false,
    message: '',
    color: 'success',
});

// Validation rules
const rules = {
    required: (v) => !!v || 'Required',
    minLength: (v) => (v && v.length >= 8) || 'Min 8 characters',
    hasUppercase: (v) => /[A-Z]/.test(v) || 'Must contain uppercase letter',
    hasLowercase: (v) => /[a-z]/.test(v) || 'Must contain lowercase letter',
    hasNumber: (v) => /[0-9]/.test(v) || 'Must contain a number',
    hasSpecial: (v) => /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(v) || 'Must contain special character',
    passwordMatch: (v) => v === passwordData.new_password || 'Passwords do not match',
    // Phone number validation (used for all phone fields)
    phoneNumber: (v) => {
        if (!v) return true; // Optional field
        // Remove spaces, dashes, and parentheses
        const cleaned = v.replace(/[\s\-\(\)]/g, '');
        
        // Must start with 09 and have exactly 11 digits
        if (!/^09[0-9]{9}$/.test(cleaned)) {
            return 'Please enter a valid number';
        }
        
        return true;
    },
    // ID Number validation - exactly 9 digits, no letters
    idNumber: (v) => {
        if (!v) return true; // Optional field
        // Must be exactly 9 digits, no letters or special characters
        const idRegex = /^\d{9}$/;
        if (!idRegex.test(v)) {
            return 'ID Number must be exactly 9 digits (numbers only)';
        }
        return true;
    },
};

// Password validation checks for visual feedback
const passwordChecks = computed(() => {
    const pwd = passwordData.new_password || '';
    return {
        length: pwd.length >= 8,
        uppercase: /[A-Z]/.test(pwd),
        lowercase: /[a-z]/.test(pwd),
        number: /[0-9]/.test(pwd),
        special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd),
    };
});

// Password validation computed
const isPasswordValid = computed(() => {
    const checks = passwordChecks.value;
    return checks.length && checks.uppercase && checks.lowercase && checks.number && checks.special;
});

// Computed
const fullName = computed(() => {
    return `${user.value.first_name} ${user.value.last_name}`.trim() || 'User';
});

const getInitials = computed(() => {
    const first = user.value.first_name?.[0] || '';
    const last = user.value.last_name?.[0] || '';
    return (first + last).toUpperCase() || 'U';
});

const profilePictureUrl = computed(() => {
    const picturePath = user.value.profile_picture;
    if (!picturePath) return null;
    if (picturePath.startsWith('http') || picturePath.startsWith('data:')) return picturePath;
    return getProfilePictureUrl(picturePath);
});

const displayedHistory = computed(() => {
    return locationHistory.value.slice(0, 3);
});

// Computed properties to detect changes in each section
const hasPersonalChanges = computed(() => {
    return (
        editData.first_name !== (user.value.first_name || '') ||
        editData.last_name !== (user.value.last_name || '') ||
        editData.phone_number !== (user.value.phone_number || '') ||
        editData.id_number !== (user.value.id_number || '')
    );
});

// Computed property to check if ID number is valid
const isValidIdNumber = computed(() => {
    const id = editData.id_number;
    return id && /^\d{9}$/.test(id);
});

// Computed property to determine user role based on ID number
const userRoleFromId = computed(() => {
    const id = editData.id_number;
    if (!id || !/^\d{9}$/.test(id)) return null;
    
    // Get the first digit
    const firstDigit = id.charAt(0);
    
    // If starts with digit 2, it's a student
    if (firstDigit === '2') {
        return 'student';
    }
    // Otherwise (starts with 1,3,4,5,6,7,8,9), it's faculty
    return 'faculty';
});

const hasEmergencyChanges = computed(() => {
    return (
        editData.emergency_contact_name !== (user.value.emergency_contact_name || '') ||
        editData.emergency_contact_phone !== (user.value.emergency_contact_phone || '') ||
        editData.emergency_contact_relation !== (user.value.emergency_contact_relation || '')
    );
});

const hasMedicalChanges = computed(() => {
    return (
        editData.blood_type !== (user.value.blood_type || '') ||
        editData.allergies !== (user.value.allergies || '') ||
        editData.medical_conditions !== (user.value.medical_conditions || '')
    );
});

// Load settings from localStorage
const loadSettings = () => {
    const savedSettings = localStorage.getItem('userSettings');
    if (savedSettings) {
        const parsed = JSON.parse(savedSettings);
        settings.pushNotifications = parsed.pushNotifications ?? true;
        settings.locationServices = parsed.locationServices ?? true;
        settings.darkMode = parsed.darkMode ?? false;
    }
    // Apply dark mode on load
    applyDarkMode(settings.darkMode);
};

// Save settings to localStorage when they change
watch(settings, (newSettings) => {
    localStorage.setItem('userSettings', JSON.stringify(newSettings));
}, { deep: true });

// Methods
const loadUser = async () => {
    try {
        if (!authUser.value) {
            router.visit('/login');
            return;
        }

        const inertiaUser = authUser.value;
        user.value = {
            id: inertiaUser.id,
            email: inertiaUser.email,
            first_name: inertiaUser.first_name || '',
            last_name: inertiaUser.last_name || '',
            phone_number: inertiaUser.phone_number || inertiaUser.contact_number || '',
            id_number: inertiaUser.id_number || '',
            profile_picture: inertiaUser.profile_picture || null,
            emergency_contact_name: inertiaUser.emergency_contact_name || '',
            emergency_contact_phone: inertiaUser.emergency_contact_phone || '',
            emergency_contact_relation: inertiaUser.emergency_contact_relation || '',
            blood_type: inertiaUser.blood_type || '',
            allergies: inertiaUser.allergies || '',
            medical_conditions: inertiaUser.medical_conditions || '',
            is_verified: inertiaUser.is_verified || false,
            created_at: inertiaUser.created_at || null,
        };

        // Sync edit data
        syncEditData();

        localStorage.setItem('userData', JSON.stringify(user.value));

        // Try to refresh from API
        try {
            const data = await getCurrentUser();
            if (data) {
                user.value = { ...user.value, ...data };
                syncEditData();
                localStorage.setItem('userData', JSON.stringify(user.value));
            }
        } catch (apiErr) {
            console.warn('API call failed, using Inertia auth data:', apiErr.message);
        }
    } catch (err) {
        console.error('Error loading user:', err);
        router.visit('/login');
    } finally {
        loading.value = false;
    }
};

const syncEditData = () => {
    editData.first_name = user.value.first_name || '';
    editData.last_name = user.value.last_name || '';
    editData.phone_number = user.value.phone_number || '';
    editData.id_number = user.value.id_number || '';
    editData.emergency_contact_name = user.value.emergency_contact_name || '';
    editData.emergency_contact_phone = user.value.emergency_contact_phone || '';
    editData.emergency_contact_relation = user.value.emergency_contact_relation || '';
    editData.blood_type = user.value.blood_type || '';
    editData.allergies = user.value.allergies || '';
    editData.medical_conditions = user.value.medical_conditions || '';
};

// Format ID number input to only allow digits
const formatIdNumber = () => {
    // Remove any non-digit characters
    if (editData.id_number) {
        editData.id_number = editData.id_number.replace(/\D/g, '').substring(0, 9);
    }
};

// Format phone number for  s (used for all phone fields)
const formatPhoneNumber = (field) => {
    let value = editData[field] || '';
    
    // Remove all non-digit characters except + at the beginning
    if (value.startsWith('+')) {
        value = '+' + value.substring(1).replace(/\D/g, '');
    } else {
        value = value.replace(/\D/g, '');
    }
    
    // If it starts with +63, limit to 13 characters
    if (value.startsWith('+63')) {
        value = value.substring(0, 13);
    }
    // If it starts with 63 (without +), limit to 12 characters  
    else if (value.startsWith('63')) {
        value = value.substring(0, 12);
    }
    // If it starts with 09, limit to 11 characters
    else if (value.startsWith('09') || value.startsWith('9')) {
        value = value.substring(0, 11);
    }
    // For other cases, limit to 11 characters max (standard PH mobile)
    else {
        value = value.substring(0, 11);
    }
    
    editData[field] = value;
};

const fetchLocationHistory = async () => {
    if (!user.value.id) return;

    loadingHistory.value = true;
    try {
        const data = await getUserRescueHistory(user.value.id);
        const records = Array.isArray(data) ? data : (data?.data || []);

        locationHistory.value = records.map((record) => ({
            id: record.id,
            location: formatLocationFromRecord(record),
            isRescued: ['rescued', 'safe'].includes(record.status),
            status: record.status,
            timestamp: record.created_at,
            rescue_code: record.rescue_code,
        }));

        // Calculate stats
        const rescued = locationHistory.value.filter(l => l.isRescued).length;
        const pending = locationHistory.value.filter(l => !l.isRescued && !['cancelled'].includes(l.status)).length;
        stats.value = {
            rescued,
            pending,
            total: locationHistory.value.length,
        };
    } catch (err) {
        console.error('Failed to fetch location history:', err);
    } finally {
        loadingHistory.value = false;
    }
};

const formatLocationFromRecord = (record) => {
    const parts = [];
    if (record.building_name) parts.push(record.building_name);
    if (record.floor_name) parts.push(record.floor_name);
    if (record.room_name) parts.push(record.room_name);
    return parts.join(' > ') || 'Unknown Location';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatHistoryDate = (dateString) => {
    if (!dateString) return 'Unknown';
    const date = new Date(dateString);
    const now = new Date();

    if (date.toDateString() === now.toDateString()) {
        return 'Today ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    if (date.getFullYear() === now.getFullYear()) {
        return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }

    return date.toLocaleDateString();
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'warning',
        open: 'info',
        assigned: 'primary',
        en_route: 'secondary',
        on_scene: 'success',
        rescued: 'success',
        safe: 'success',
        cancelled: 'grey',
    };
    return colors[status] || 'grey';
};

const formatStatus = (status) => {
    const labels = {
        pending: 'Pending',
        open: 'Open',
        assigned: 'Assigned',
        en_route: 'En Route',
        on_scene: 'On Scene',
        rescued: 'Rescued',
        safe: 'Safe',
        cancelled: 'Cancelled',
    };
    return labels[status] || status;
};

const viewHistoryItem = (item) => {
    if (item.rescue_code) {
        router.visit(`/user/help-coming/${item.rescue_code}`);
    }
};

const cancelEdit = () => {
    editData.first_name = user.value.first_name || '';
    editData.last_name = user.value.last_name || '';
    editData.phone_number = user.value.phone_number || '';
    editData.id_number = user.value.id_number || '';
};

const saveProfile = async () => {
    if (!formValid.value) return;

    // Validate phone number before saving
    if (editData.phone_number) {
        const phoneValidation = rules.phoneNumber(editData.phone_number);
        if (phoneValidation !== true) {
            showSnackbar(phoneValidation, 'error');
            return;
        }
    }

    // Validate ID number before saving
    if (editData.id_number) {
        const idValidation = rules.idNumber(editData.id_number);
        if (idValidation !== true) {
            showSnackbar(idValidation, 'error');
            return;
        }
    }

    saving.value = true;
    try {
        // Only save personal information fields
        const updateData = {
            first_name: editData.first_name,
            last_name: editData.last_name,
            phone_number: editData.phone_number,
            id_number: editData.id_number,
        };

        // Include role determination based on ID number
        if (editData.id_number && isValidIdNumber.value) {
            updateData.role = userRoleFromId.value;
        }

        console.log('Updating personal info with data:', updateData);
        
        const updatedUser = await updateUser(user.value.id, updateData);
        
        // Update local user state to sync with editData (hides save button)
        user.value.first_name = editData.first_name;
        user.value.last_name = editData.last_name;
        user.value.phone_number = editData.phone_number;
        user.value.id_number = editData.id_number;
        
        // Update localStorage
        localStorage.setItem('userData', JSON.stringify(user.value));

        showSnackbar('Personal information updated successfully', 'success');
    } catch (err) {
        console.error('Error updating profile:', err);
        const errorMsg = err?.data?.message || err?.message || 'Failed to update profile';
        showSnackbar(errorMsg, 'error');
    } finally {
        saving.value = false;
    }
};

// Save Emergency Contact
const saveEmergencyContact = async () => {
    // Validate emergency contact phone number before saving
    if (editData.emergency_contact_phone) {
        const phoneValidation = rules.phoneNumber(editData.emergency_contact_phone);
        if (phoneValidation !== true) {
            showSnackbar(phoneValidation, 'error');
            return;
        }
    }

    savingEmergency.value = true;
    try {
        // Build update data - only include non-empty string values
        const updateData = {};
        
        if (editData.emergency_contact_name && editData.emergency_contact_name.trim()) {
            updateData.emergency_contact_name = editData.emergency_contact_name.trim();
        }
        if (editData.emergency_contact_phone && editData.emergency_contact_phone.trim()) {
            updateData.emergency_contact_phone = editData.emergency_contact_phone.trim();
        }
        if (editData.emergency_contact_relation && editData.emergency_contact_relation.trim()) {
            updateData.emergency_contact_relation = editData.emergency_contact_relation.trim();
        }

        // Check if there's anything to save
        if (Object.keys(updateData).length === 0) {
            showSnackbar('Please fill in at least one field', 'warning');
            savingEmergency.value = false;
            return;
        }

        console.log('Updating emergency contact with data:', updateData);
        
        await updateUser(user.value.id, updateData);
        
        // Update local user state to sync with editData (hides save button)
        user.value.emergency_contact_name = editData.emergency_contact_name;
        user.value.emergency_contact_phone = editData.emergency_contact_phone;
        user.value.emergency_contact_relation = editData.emergency_contact_relation;
        
        // Update localStorage
        localStorage.setItem('userData', JSON.stringify(user.value));

        showSnackbar('Emergency contact updated successfully', 'success');
    } catch (err) {
        console.error('Error updating emergency contact:', err);
        const errorMsg = err?.data?.message || err?.message || 'Failed to update emergency contact';
        showSnackbar(errorMsg, 'error');
    } finally {
        savingEmergency.value = false;
    }
};

// Cancel Emergency Contact Edit
const cancelEditEmergency = () => {
    editData.emergency_contact_name = user.value.emergency_contact_name || '';
    editData.emergency_contact_phone = user.value.emergency_contact_phone || '';
    editData.emergency_contact_relation = user.value.emergency_contact_relation || '';
};

// Save Medical Information
const saveMedicalInfo = async () => {
    savingMedical.value = true;
    try {
        // Build update data - only include non-empty values
        const updateData = {};
        
        if (editData.blood_type) {
            updateData.blood_type = editData.blood_type;
        }
        if (editData.allergies && editData.allergies.trim()) {
            updateData.allergies = editData.allergies.trim();
        }
        if (editData.medical_conditions && editData.medical_conditions.trim()) {
            updateData.medical_conditions = editData.medical_conditions.trim();
        }

        // Check if there's anything to save
        if (Object.keys(updateData).length === 0) {
            showSnackbar('Please fill in at least one field', 'warning');
            savingMedical.value = false;
            return;
        }

        console.log('Updating medical info with data:', updateData);
        
        await updateUser(user.value.id, updateData);
        
        // Update local user state to sync with editData (hides save button)
        user.value.blood_type = editData.blood_type;
        user.value.allergies = editData.allergies;
        user.value.medical_conditions = editData.medical_conditions;
        
        // Update localStorage
        localStorage.setItem('userData', JSON.stringify(user.value));

        showSnackbar('Medical information updated successfully', 'success');
    } catch (err) {
        console.error('Error updating medical info:', err);
        const errorMsg = err?.data?.message || err?.message || 'Failed to update medical information';
        showSnackbar(errorMsg, 'error');
    } finally {
        savingMedical.value = false;
    }
};

// Cancel Medical Info Edit
const cancelEditMedical = () => {
    editData.blood_type = user.value.blood_type || '';
    editData.allergies = user.value.allergies || '';
    editData.medical_conditions = user.value.medical_conditions || '';
};

// Password Change OTP Flow
const startResendCountdown = () => {
    resendCountdown.value = 60;
    if (countdownInterval) clearInterval(countdownInterval);
    countdownInterval = setInterval(() => {
        if (resendCountdown.value > 0) {
            resendCountdown.value--;
        } else {
            clearInterval(countdownInterval);
        }
    }, 1000);
};

const sendPasswordOtp = async () => {
    sendingOtp.value = true;
    passwordError.value = '';
    try {
        const response = await fetch('/api/auth/send-password-change-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ email: user.value.email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            passwordStep.value = 2;
            startResendCountdown();
            showSnackbar('Verification code sent to your email', 'success');
        } else {
            passwordError.value = data.message || 'Failed to send code';
        }
    } catch (error) {
        console.error('Error sending OTP:', error);
        passwordError.value = 'Failed to send verification code';
    } finally {
        sendingOtp.value = false;
    }
};

const verifyPasswordOtp = async () => {
    const otpValue = Array.isArray(otpCode.value) 
        ? otpCode.value.join('') 
        : String(otpCode.value).trim();
    
    if (otpValue.length !== 6) return;
    
    verifyingOtp.value = true;
    passwordError.value = '';
    try {
        const response = await fetch('/api/auth/verify-password-change-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                email: user.value.email,
                otp: otpValue
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            verificationToken.value = data.token;
            passwordStep.value = 3;
            showSnackbar('Code verified! Set your new password.', 'success');
        } else {
            passwordError.value = data.message || 'Invalid code';
        }
    } catch (error) {
        console.error('Error verifying OTP:', error);
        passwordError.value = 'Failed to verify code';
    } finally {
        verifyingOtp.value = false;
    }
};

const changePassword = async () => {
    if (!isPasswordValid.value) return;

    changingPassword.value = true;
    passwordError.value = '';
    try {
        const response = await fetch('/api/auth/complete-password-change', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                email: user.value.email,
                token: verificationToken.value,
                password: passwordData.new_password,
                password_confirmation: passwordData.confirm_password
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            passwordComplete.value = true;
            showSnackbar('Password changed successfully!', 'success');
        } else {
            passwordError.value = data.message || 'Failed to change password';
        }
    } catch (error) {
        console.error('Error changing password:', error);
        passwordError.value = 'Failed to change password';
    } finally {
        changingPassword.value = false;
    }
};

const resetPasswordFlow = () => {
    passwordStep.value = 1;
    passwordComplete.value = false;
    passwordError.value = '';
    otpCode.value = '';
    verificationToken.value = '';
    passwordData.new_password = '';
    passwordData.confirm_password = '';
    if (passwordFormRef.value) passwordFormRef.value.reset();
};

const openChangePasswordDialog = () => {
    resetPasswordFlow();
    passwordDialog.value = true;
};

const closePasswordDialog = () => {
    passwordDialog.value = false;
    resetPasswordFlow();
};

const updateSetting = async (setting) => {
    // Save settings to localStorage immediately
    localStorage.setItem('userSettings', JSON.stringify({
        pushNotifications: settings.pushNotifications,
        locationServices: settings.locationServices,
        darkMode: settings.darkMode,
    }));
    
    if (setting === 'Notifications') {
        if (settings.pushNotifications) {
            if ('Notification' in window) {
                const permission = await Notification.requestPermission();
                if (permission === 'granted') {
                    showSnackbar('Push notifications enabled', 'success');
                } else if (permission === 'denied') {
                    settings.pushNotifications = false;
                    showSnackbar('Notification permission denied', 'warning');
                }
            }
        } else {
            showSnackbar('Push notifications disabled', 'info');
        }
    } else if (setting === 'Location') {
        if (settings.locationServices) {
            if ('geolocation' in navigator) {
                navigator.geolocation.getCurrentPosition(
                    () => showSnackbar('Location services enabled', 'success'),
                    () => {
                        settings.locationServices = false;
                        showSnackbar('Location permission denied', 'warning');
                    }
                );
            }
        } else {
            showSnackbar('Location services disabled', 'info');
        }
    } else if (setting === 'DarkMode') {
        applyDarkMode(settings.darkMode);
        showSnackbar(settings.darkMode ? 'Dark mode enabled' : 'Dark mode disabled', 'info');
    }
};

// Apply dark mode to document
const applyDarkMode = (enabled) => {
    if (enabled) {
        document.documentElement.classList.add('dark-mode');
        document.body.classList.add('dark-mode');
    } else {
        document.documentElement.classList.remove('dark-mode');
        document.body.classList.remove('dark-mode');
    }
};

// Navigate to location history
const goToLocationHistory = () => {
    router.visit('/user/history');
};

// Photo handling
const openPhotoDialog = () => {
    selectedFile.value = null;
    previewUrl.value = null;
    photoDialog.value = true;
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        showSnackbar('Max file size is 5MB', 'error');
        return;
    }

    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        showSnackbar('Invalid file type', 'error');
        return;
    }

    selectedFile.value = file;
    previewUrl.value = URL.createObjectURL(file);
};

const cancelPhotoUpload = () => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    selectedFile.value = null;
    previewUrl.value = null;
    photoDialog.value = false;
};

const uploadPhoto = async () => {
    if (!selectedFile.value || !user.value.id) return;

    uploadingPhoto.value = true;
    try {
        const result = await uploadProfilePicture(user.value.id, selectedFile.value);

        user.value.profile_picture = result.profile_picture;
        localStorage.setItem('userData', JSON.stringify(user.value));

        showSnackbar('Photo updated', 'success');

        if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
        selectedFile.value = null;
        previewUrl.value = null;
        photoDialog.value = false;
    } catch (err) {
        console.error('Error uploading photo:', err);
        showSnackbar('Failed to upload', 'error');
    } finally {
        uploadingPhoto.value = false;
    }
};

const confirmDeletePhoto = () => {
    deletePhotoDialog.value = true;
};

const deletePhoto = async () => {
    if (!user.value.id) return;

    deletingPhoto.value = true;
    try {
        await deleteProfilePicture(user.value.id);

        user.value.profile_picture = null;
        localStorage.setItem('userData', JSON.stringify(user.value));

        showSnackbar('Photo removed', 'success');
        deletePhotoDialog.value = false;
        photoDialog.value = false;
    } catch (err) {
        console.error('Error deleting photo:', err);
        showSnackbar('Failed to remove', 'error');
    } finally {
        deletingPhoto.value = false;
    }
};

const handleLogout = async () => {
    loggingOut.value = true;

    // Set user as inactive in Firebase (keep FCM token for offline notifications)
    try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        if (userData.id) {
            await setUserActiveStatus(userData.id, false);
            console.log('[Logout] User marked as inactive in Firebase');
        }
    } catch (e) {
        console.error('[Logout] Error setting user inactive:', e);
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        await fetch('/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'include'
        });
    } catch (error) {
        console.error('Logout API error:', error);
    }
    
    // Clear all localStorage data
    localStorage.removeItem('userData');
    localStorage.removeItem('authToken');
    localStorage.removeItem('token');
    localStorage.removeItem('userSettings');
    localStorage.removeItem('rescuerSettings');
    localStorage.removeItem('lastRescueCode');
    localStorage.removeItem('lastRescueRequestId');
    localStorage.removeItem('lastRescueRequestTime');
    localStorage.removeItem('conversationId');
    localStorage.removeItem('chatId');
    localStorage.removeItem('activeRescue');
    
    // Clear session storage
    sessionStorage.clear();

    loggingOut.value = false;
    showLogoutDialog.value = false;
    
    // Force redirect to login
    window.location.href = '/login';
};

const goBack = () => {
    router.visit('/user/scanner');
};

const showSnackbar = (message, color = 'success') => {
    snackbar.value = { show: true, message, color };
};

// Lifecycle
onMounted(async () => {
    loadSettings();
    await loadUser();
    await fetchLocationHistory();
});
</script>

<style scoped>
/* Profile Page Header */
.profile-page-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #3674B5;
    padding: env(safe-area-inset-top, 0) 0 0 0;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    gap: 12px;
}

.menu-btn, .placeholder-btn {
    color: white;
}

.header-title {
    flex: 1;
    text-align: center;
}

.header-title h1 {
    font-size: 1.125rem;
    font-weight: 700;
    color: white;
    margin: 0;
}

.header-title p {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
}

/* Profile Container - mobile-first padding */
.profile-container {
    padding: 12px !important;
    padding-bottom: 100px !important;
}

@media (min-width: 600px) {
    .profile-container {
        padding: 16px !important;
        padding-bottom: 100px !important;
    }
    
    .header-title h1 {
        font-size: 1.25rem;
    }
    
    .header-title p {
        font-size: 0.75rem;
    }
}

.profile-header-bg {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 50%, #0D47A1 100%);
}

.avatar-ring {
    border: 4px solid rgba(255, 255, 255, 0.3);
    background: linear-gradient(135deg, #42A5F5, #1E88E5);
}

/* Mobile avatar size adjustment */
.avatar-mobile {
    width: 80px !important;
    height: 80px !important;
}

@media (min-width: 600px) {
    .avatar-mobile {
        width: 100px !important;
        height: 100px !important;
    }
}

.edit-avatar-btn {
    bottom: -4px;
    right: -4px;
    border: 2px solid white;
}

.stats-row {
    background: rgba(25, 118, 210, 0.03);
}

.position-relative {
    position: relative;
}

.position-absolute {
    position: absolute;
}

.d-inline-block {
    display: inline-block;
}

/* Section Cards - mobile-friendly */
.section-card {
    border: 1px solid rgba(0, 0, 0, 0.05);
}

/* Panel titles - mobile-friendly with larger touch targets */
.panel-title-mobile {
    padding: 14px 12px !important;
    min-height: 60px !important;
}

@media (min-width: 600px) {
    .panel-title-mobile {
        padding: 16px !important;
        min-height: 64px !important;
    }
}

/* Panel content - mobile-friendly padding */
.panel-content-mobile {
    padding: 4px 0 !important;
}

:deep(.v-expansion-panel-text__wrapper) {
    padding: 0 12px 16px !important;
}

@media (min-width: 600px) {
    :deep(.v-expansion-panel-text__wrapper) {
        padding: 0 16px 16px !important;
    }
}

/* Mobile input fields - larger touch targets */
.mobile-input :deep(.v-field) {
    min-height: 48px !important;
}

.mobile-input :deep(.v-field__input) {
    padding-top: 12px !important;
    padding-bottom: 12px !important;
    font-size: 0.9375rem !important;
}

/* Mobile buttons - better touch targets */
.mobile-btn {
    min-height: 48px !important;
    font-weight: 600 !important;
}

/* Logout button styling */
.logout-btn {
    min-height: 52px !important;
    font-weight: 600 !important;
    letter-spacing: 0.5px;
}

/* Settings list mobile styling */
.settings-list {
    margin: 0 -4px;
}

.setting-item {
    background: rgba(0, 0, 0, 0.02);
    min-height: 64px !important;
}

.setting-item:active {
    background: rgba(0, 0, 0, 0.05);
}

/* History item styling */
.history-item {
    background: rgba(0, 0, 0, 0.02);
    transition: background 0.2s;
    min-height: 60px !important;
}

.history-item:hover,
.history-item:active {
    background: rgba(0, 0, 0, 0.06);
    cursor: pointer;
}

/* Non-editable fields styling */
.non-editable-field {
    opacity: 0.7;
}

.non-editable-field :deep(.v-field) {
    background-color: #f5f5f5 !important;
}

.non-editable-field :deep(.v-field__input) {
    color: #666 !important;
}

/* Password requirements styling */
.password-requirements {
    background: rgba(0, 0, 0, 0.02);
    border-radius: 8px;
    padding: 12px;
}

/* OTP input styling */
:deep(.v-otp-input) {
    justify-content: center;
}

:deep(.v-otp-input input) {
    font-size: 1.25rem;
    font-weight: 600;
}

/* Mobile responsiveness */
@media (max-width: 600px) {
    .pb-20 {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 140px) !important;
    }
    
    /* Ensure buttons stack nicely on very small screens */
    .d-flex.flex-column.flex-sm-row .v-btn {
        width: 100%;
    }
    
    .v-main :deep(.v-container) {
        padding-bottom: 50px !important;
    }
}

@media (max-width: 1024px) {
    .v-main {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 120px) !important;
    }
    
    .v-main :deep(.v-container) {
        padding-bottom: 40px !important;
    }
}
</style>

<!-- Global Dark Mode Styles (unscoped) -->
<style>
/* Dark Mode Theme */
html.dark-mode,
body.dark-mode {
    background-color: #121212 !important;
    color: #e0e0e0 !important;
}

.dark-mode .profile-page-header {
    background: #1e1e1e !important;
}

.dark-mode .profile-header-bg {
    background: linear-gradient(135deg, #1a237e 0%, #0d1b2a 50%, #0a1929 100%) !important;
}

.dark-mode .v-main {
    background-color: #121212 !important;
}

.dark-mode .v-card {
    background-color: #1e1e1e !important;
    color: #e0e0e0 !important;
}

.dark-mode .v-expansion-panel {
    background-color: #1e1e1e !important;
}

.dark-mode .v-expansion-panel-title {
    color: #e0e0e0 !important;
}

.dark-mode .v-expansion-panel-text {
    color: #b0b0b0 !important;
}

.dark-mode .v-list-item {
    color: #e0e0e0 !important;
}

.dark-mode .v-list-item-title {
    color: #e0e0e0 !important;
}

.dark-mode .v-list-item-subtitle {
    color: #9e9e9e !important;
}

.dark-mode .v-text-field .v-field {
    background-color: #2d2d2d !important;
}

.dark-mode .v-text-field input {
    color: #e0e0e0 !important;
}

.dark-mode .v-text-field label {
    color: #9e9e9e !important;
}

.dark-mode .v-select .v-field {
    background-color: #2d2d2d !important;
}

.dark-mode .v-textarea .v-field {
    background-color: #2d2d2d !important;
}

.dark-mode .text-grey,
.dark-mode .text-grey-darken-1 {
    color: #9e9e9e !important;
}

.dark-mode .stats-row {
    background: rgba(255, 255, 255, 0.05) !important;
}

.dark-mode .history-item {
    background: rgba(255, 255, 255, 0.05) !important;
}

.dark-mode .history-item:hover {
    background: rgba(255, 255, 255, 0.1) !important;
}

.dark-mode .non-editable-field :deep(.v-field) {
    background-color: #252525 !important;
}

.dark-mode .non-editable-field :deep(.v-field__input) {
    color: #9e9e9e !important;
}

.dark-mode .v-navigation-drawer {
    background-color: #1e1e1e !important;
}

.dark-mode .v-bottom-navigation {
    background-color: #1e1e1e !important;
}

.dark-mode .v-dialog .v-card {
    background-color: #1e1e1e !important;
}

/* Step Indicator */
.step-line {
    width: 40px;
    height: 2px;
    background-color: #e0e0e0;
    margin: 0 8px;
    transition: background-color 0.3s ease;
}

.step-line.active {
    background-color: #3674B5;
}

/* Password Requirements */
.password-requirements {
    background: #f5f5f5;
    border-radius: 8px;
    padding: 12px;
}

.requirements-grid {
    display: grid;
    gap: 6px;
}

.requirement-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #9e9e9e;
    transition: color 0.2s ease;
}

.requirement-item.met {
    color: #4caf50;
}

.requirement-item span {
    line-height: 1.2;
}

/* Dark mode for password dialog */
.dark-mode .password-requirements {
    background: #2d2d2d;
}

.dark-mode .step-line {
    background-color: #444;
}

.dark-mode .requirement-item {
    color: #757575;
}

.dark-mode .requirement-item.met {
    color: #81c784;
}
</style>
