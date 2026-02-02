<template>
    <v-app class="bg-user-gradient-light">


        <!-- Navigation Drawer -->
        <RescuerMenu v-model="drawer" />

        <v-main class="pb-20">
            <!-- Loading State -->
            <div v-if="loading" class="d-flex justify-center align-center" style="min-height: 60vh;">
                <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
            </div>

            <v-container v-else fluid class="pa-3 pa-sm-4">
                <!-- Profile Header Card -->
                <v-card 
                    class="mb-4 rounded-xl overflow-hidden" 
                    elevation="0"
                    color="white"
                >
                    <div class="profile-header-bg pa-6 text-center">
                        <!-- Avatar with Edit Button -->
                        <div class="position-relative d-inline-block">
                            <v-avatar size="100" class="elevation-4 avatar-ring">
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
                                size="small"
                                color="primary"
                                class="position-absolute edit-avatar-btn"
                                @click="openPhotoDialog"
                            >
                                <v-icon size="16">mdi-camera</v-icon>
                            </v-btn>
                        </div>
                        
                        <h2 class="text-h5 font-weight-bold mt-3 text-white">{{ fullName }}</h2>
                        <p class="text-body-2 text-white-darken-1 mb-0">
                            {{ profile.email }}
                        </p>
                        
                        <!-- Availability Toggle -->
                        <v-chip 
                            :color="profile.is_active ? 'success' : 'error'"
                            variant="flat"
                            size="small"
                            class="mt-3"
                            @click="profile.is_active = !profile.is_active; toggleAvailability()"
                        >
                            <v-icon start size="14">
                                {{ profile.is_active ? 'mdi-check-circle' : 'mdi-close-circle' }}
                            </v-icon>
                            {{ profile.is_active ? 'Available' : 'Unavailable' }}
                        </v-chip>
                    </div>

                    <!-- Stats Row -->
                    <v-row no-gutters class="text-center py-3 stats-row">
                        <v-col cols="4">
                            <div class="text-h5 font-weight-bold text-primary">{{ stats.completed }}</div>
                            <div class="text-caption text-grey">Completed</div>
                        </v-col>
                        <v-divider vertical></v-divider>
                        <v-col cols="4">
                            <div class="text-h5 font-weight-bold text-warning">{{ stats.inProgress }}</div>
                            <div class="text-caption text-grey">In Progress</div>
                        </v-col>
                        <v-divider vertical></v-divider>
                        <v-col cols="4">
                            <div class="text-h5 font-weight-bold text-success">{{ stats.avgTime }}</div>
                            <div class="text-caption text-grey">Avg Time</div>
                        </v-col>
                    </v-row>
                </v-card>

                <!-- Personal Information -->
                <v-card class="mb-4 rounded-xl" elevation="0" color="white">
                    <v-expansion-panels flat v-model="personalInfoPanel">
                        <v-expansion-panel>
                            <v-expansion-panel-title class="py-3 px-4">
                                <div class="d-flex align-center">
                                    <v-icon color="primary" class="mr-2" size="20">mdi-account</v-icon>
                                    <span class="text-subtitle-1 font-weight-bold">Personal Information</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text>
                                <v-form ref="formRef" v-model="formValid">
                                    <v-row dense>
                                        <v-col cols="6">
                                            <v-text-field
                                                v-model="profile.first_name"
                                                label="First Name"
                                                :readonly="!isEditing"
                                                :rules="[rules.required]"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                class="mb-3"
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="6">
                                            <v-text-field
                                                v-model="profile.last_name"
                                                label="Last Name"
                                                :readonly="!isEditing"
                                                :rules="[rules.required]"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                class="mb-3"
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12">
                                            <v-text-field
                                                v-model="profile.email"
                                                label="Email"
                                                readonly
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                class="mb-3"
                                                prepend-inner-icon="mdi-email-outline"
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12">
                                            <v-text-field
                                                v-model="profile.contact_number"
                                                label="Contact Number"
                                                :readonly="!isEditing"
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                class="mb-3"
                                                prepend-inner-icon="mdi-phone-outline"
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12">
                                            <v-text-field
                                                v-model="profile.employee_id"
                                                label="Rescuer ID"
                                                readonly
                                                variant="outlined"
                                                density="compact"
                                                hide-details="auto"
                                                prepend-inner-icon="mdi-badge-account-outline"
                                            ></v-text-field>
                                        </v-col>
                                    </v-row>
                                </v-form>
                                
                                <div class="d-flex gap-2 mt-3">
                                    <v-btn
                                        v-if="!isEditing"
                                        block
                                        color="primary"
                                        variant="tonal"
                                        @click="isEditing = true"
                                        class="rounded-lg"
                                    >
                                        <v-icon start>mdi-pencil</v-icon>
                                        Edit Information
                                    </v-btn>
                                    <template v-else>
                                        <v-btn
                                            color="grey"
                                            variant="outlined"
                                            @click="cancelEdit"
                                            class="rounded-lg flex-grow-1"
                                        >
                                            Cancel
                                        </v-btn>
                                        <v-btn
                                            color="primary"
                                            variant="flat"
                                            :loading="saving"
                                            :disabled="!formValid"
                                            @click="saveProfile"
                                            class="rounded-lg flex-grow-1"
                                        >
                                            Save Changes
                                        </v-btn>
                                    </template>
                                </div>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card>

                <!-- Security Section -->
                <v-card class="mb-4 rounded-xl" elevation="0" color="white">
                    <v-expansion-panels flat v-model="securityPanel">
                        <v-expansion-panel>
                            <v-expansion-panel-title class="py-3 px-4">
                                <div class="d-flex align-center">
                                    <v-icon color="primary" class="mr-2" size="20">mdi-shield-lock</v-icon>
                                    <span class="text-subtitle-1 font-weight-bold">Security</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text>
                                <!-- Step 1: Request OTP -->
                                <div v-if="passwordStep === 'request'">
                                    <p class="text-body-2 text-grey mb-4">
                                        To change your password, we'll send a verification code to your email.
                                    </p>
                                    <v-btn
                                        block
                                        color="primary"
                                        variant="flat"
                                        :loading="sendingOtp"
                                        @click="sendPasswordOtp"
                                        class="rounded-lg"
                                    >
                                        <v-icon start>mdi-email-send</v-icon>
                                        Send Verification Code
                                    </v-btn>
                                </div>

                                <!-- Step 2: Enter OTP -->
                                <div v-else-if="passwordStep === 'otp'">
                                    <p class="text-body-2 text-grey mb-2">
                                        Enter the 6-digit code sent to
                                    </p>
                                    <p class="text-body-2 font-weight-bold text-primary mb-4">{{ profile.email }}</p>
                                    
                                    <v-otp-input
                                        v-model="otpCode"
                                        :length="6"
                                        variant="outlined"
                                        class="mb-3"
                                        @finish="verifyPasswordOtp"
                                    />
                                    
                                    <div class="d-flex justify-space-between align-center mb-4">
                                        <v-btn
                                            variant="text"
                                            size="small"
                                            @click="passwordStep = 'request'"
                                        >
                                            <v-icon start size="16">mdi-arrow-left</v-icon>
                                            Back
                                        </v-btn>
                                        <v-btn
                                            variant="text"
                                            size="small"
                                            :disabled="resendCountdown > 0"
                                            :loading="sendingOtp"
                                            @click="sendPasswordOtp"
                                        >
                                            {{ resendCountdown > 0 ? `Resend in ${resendCountdown}s` : 'Resend Code' }}
                                        </v-btn>
                                    </div>
                                    
                                    <v-btn
                                        block
                                        color="primary"
                                        variant="flat"
                                        :loading="verifyingOtp"
                                        :disabled="otpCode.length !== 6"
                                        @click="verifyPasswordOtp"
                                        class="rounded-lg"
                                    >
                                        Verify Code
                                    </v-btn>
                                </div>

                                <!-- Step 3: Set New Password -->
                                <div v-else-if="passwordStep === 'password'">
                                    <v-form ref="passwordFormRef" v-model="passwordFormValid">
                                        <v-text-field
                                            v-model="passwordForm.new_password"
                                            label="New Password"
                                            :type="showNewPassword ? 'text' : 'password'"
                                            :rules="[rules.required, rules.minLength]"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            class="mb-3"
                                            :append-inner-icon="showNewPassword ? 'mdi-eye' : 'mdi-eye-off'"
                                            @click:append-inner="showNewPassword = !showNewPassword"
                                        ></v-text-field>
                                        <v-text-field
                                            v-model="passwordForm.confirm_password"
                                            label="Confirm Password"
                                            :type="showConfirmPassword ? 'text' : 'password'"
                                            :rules="[rules.required, rules.passwordMatch]"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            class="mb-3"
                                            :append-inner-icon="showConfirmPassword ? 'mdi-eye' : 'mdi-eye-off'"
                                            @click:append-inner="showConfirmPassword = !showConfirmPassword"
                                        ></v-text-field>
                                        
                                        <!-- Password Requirements -->
                                        <div class="password-requirements mb-4">
                                            <p class="text-caption text-grey mb-1">Password must have:</p>
                                            <div class="d-flex flex-wrap gap-2">
                                                <v-chip 
                                                    size="x-small" 
                                                    :color="passwordForm.new_password.length >= 8 ? 'success' : 'grey'"
                                                    variant="flat"
                                                >
                                                    <v-icon start size="12">{{ passwordForm.new_password.length >= 8 ? 'mdi-check' : 'mdi-close' }}</v-icon>
                                                    8+ chars
                                                </v-chip>
                                                <v-chip 
                                                    size="x-small" 
                                                    :color="/[A-Z]/.test(passwordForm.new_password) ? 'success' : 'grey'"
                                                    variant="flat"
                                                >
                                                    <v-icon start size="12">{{ /[A-Z]/.test(passwordForm.new_password) ? 'mdi-check' : 'mdi-close' }}</v-icon>
                                                    Uppercase
                                                </v-chip>
                                                <v-chip 
                                                    size="x-small" 
                                                    :color="/[a-z]/.test(passwordForm.new_password) ? 'success' : 'grey'"
                                                    variant="flat"
                                                >
                                                    <v-icon start size="12">{{ /[a-z]/.test(passwordForm.new_password) ? 'mdi-check' : 'mdi-close' }}</v-icon>
                                                    Lowercase
                                                </v-chip>
                                                <v-chip 
                                                    size="x-small" 
                                                    :color="/[0-9]/.test(passwordForm.new_password) ? 'success' : 'grey'"
                                                    variant="flat"
                                                >
                                                    <v-icon start size="12">{{ /[0-9]/.test(passwordForm.new_password) ? 'mdi-check' : 'mdi-close' }}</v-icon>
                                                    Number
                                                </v-chip>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <v-btn
                                                color="grey"
                                                variant="outlined"
                                                @click="resetPasswordFlow"
                                                class="rounded-lg flex-grow-1"
                                            >
                                                Cancel
                                            </v-btn>
                                            <v-btn
                                                color="primary"
                                                variant="flat"
                                                :loading="changingPassword"
                                                :disabled="!isPasswordValid"
                                                @click="changePassword"
                                                class="rounded-lg flex-grow-1"
                                            >
                                                Update Password
                                            </v-btn>
                                        </div>
                                    </v-form>
                                </div>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card>

                <!-- Settings Section -->
                <v-card class="mb-4 rounded-xl" elevation="0" color="white">
                    <v-expansion-panels flat v-model="settingsPanel">
                        <v-expansion-panel>
                            <v-expansion-panel-title class="py-3 px-4">
                                <div class="d-flex align-center">
                                    <v-icon color="primary" class="mr-2" size="20">mdi-cog</v-icon>
                                    <span class="text-subtitle-1 font-weight-bold">Settings</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text>
                                <v-list density="compact" class="pa-0">
                                    <v-list-item class="px-0">
                                        <template #prepend>
                                            <v-icon color="grey-darken-1" size="20">mdi-bell-outline</v-icon>
                                        </template>
                                        <v-list-item-title class="text-body-2">Push Notifications</v-list-item-title>
                                        <template #append>
                                            <v-switch
                                                v-model="settings.notifications"
                                                color="primary"
                                                density="compact"
                                                hide-details
                                                @update:model-value="updateSetting('Notifications')"
                                            ></v-switch>
                                        </template>
                                    </v-list-item>
                                    <v-divider></v-divider>
                                    <v-list-item class="px-0">
                                        <template #prepend>
                                            <v-icon color="grey-darken-1" size="20">mdi-volume-high</v-icon>
                                        </template>
                                        <v-list-item-title class="text-body-2">Sound Alerts</v-list-item-title>
                                        <template #append>
                                            <v-switch
                                                v-model="settings.sound"
                                                color="primary"
                                                density="compact"
                                                hide-details
                                                @update:model-value="updateSetting('Sound')"
                                            ></v-switch>
                                        </template>
                                    </v-list-item>
                                    <v-divider></v-divider>
                                    <v-list-item class="px-0">
                                        <template #prepend>
                                            <v-icon color="grey-darken-1" size="20">mdi-map-marker-outline</v-icon>
                                        </template>
                                        <v-list-item-title class="text-body-2">Location Services</v-list-item-title>
                                        <template #append>
                                            <v-switch
                                                v-model="settings.location"
                                                color="primary"
                                                density="compact"
                                                hide-details
                                                @update:model-value="updateSetting('Location')"
                                            ></v-switch>
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
                    size="large"
                    class="rounded-xl mb-4"
                    @click="showLogoutDialog = true"
                >
                    <v-icon start>mdi-logout</v-icon>
                    Logout
                </v-btn>
            </v-container>
        </v-main>

        <!-- Bottom Navigation -->
        <RescuerBottomNav
            :notification-count="0"
            @open-notifications="() => {}"
        />

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
                                :disabled="loggingOut"
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
                        variant="text"
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
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import RescuerMenu from '@/Components/Pages/Rescuer/Menu/RescuerMenu.vue';
import RescuerBottomNav from '@/Components/Pages/Rescuer/Menu/RescuerBottomNav.vue';
import { apiFetch, getProfilePictureUrl, updateUser, uploadProfilePicture, deleteProfilePicture } from '@/Composables/useApi';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';

// Auth check
const page = usePage();
const authUser = computed(() => page.props?.auth?.user);

// Notification Alert System
const { playNotificationSound } = useNotificationAlert();

// Refs
const formRef = ref(null);
const passwordFormRef = ref(null);
const fileInput = ref(null);

// State
const drawer = ref(false);
const loading = ref(true);
const saving = ref(false);
const changingPassword = ref(false);
const isEditing = ref(false);
const formValid = ref(true);
const passwordFormValid = ref(true);
const showLogoutDialog = ref(false);
const photoDialog = ref(false);
const deletePhotoDialog = ref(false);
const uploadingPhoto = ref(false);
const deletingPhoto = ref(false);
const loggingOut = ref(false);

// Panel states
const personalInfoPanel = ref(null);
const securityPanel = ref(null);
const settingsPanel = ref(null);

// Password change OTP flow
const passwordStep = ref('request'); // 'request', 'otp', 'password'
const otpCode = ref('');
const verificationToken = ref('');
const sendingOtp = ref(false);
const verifyingOtp = ref(false);
const resendCountdown = ref(0);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
let countdownInterval = null;

// Photo upload
const selectedFile = ref(null);
const previewUrl = ref(null);

const profile = ref({
    id: null,
    first_name: '',
    last_name: '',
    email: '',
    contact_number: '',
    employee_id: '',
    avatar: null,
    is_active: true,
});

const originalProfile = ref({});

const passwordForm = ref({
    current_password: '',
    new_password: '',
    confirm_password: '',
});

const settings = ref({
    notifications: true,
    sound: true,
    location: true,
});

const stats = ref({
    completed: 0,
    inProgress: 0,
    avgTime: '0m',
});

const snackbar = ref({
    show: false,
    message: '',
    color: 'success',
});

// Validation rules
const rules = {
    required: v => !!v || 'Required',
    email: v => /.+@.+\..+/.test(v) || 'Invalid email',
    minLength: v => (v && v.length >= 8) || 'Min 8 characters',
    passwordMatch: v => v === passwordForm.value.new_password || 'Passwords do not match',
};

// Computed
const fullName = computed(() => {
    return `${profile.value.first_name} ${profile.value.last_name}`.trim() || 'Rescuer';
});

const getInitials = computed(() => {
    const first = profile.value.first_name?.[0] || '';
    const last = profile.value.last_name?.[0] || '';
    return (first + last).toUpperCase() || 'R';
});

const profilePictureUrl = computed(() => {
    const picturePath = profile.value.avatar || profile.value.profile_picture;
    if (!picturePath) return null;
    if (picturePath.startsWith('http') || picturePath.startsWith('data:')) return picturePath;
    return getProfilePictureUrl(picturePath);
});

// Password validation
const isPasswordValid = computed(() => {
    const pwd = passwordForm.value.new_password;
    return pwd.length >= 8 && 
           /[A-Z]/.test(pwd) && 
           /[a-z]/.test(pwd) && 
           /[0-9]/.test(pwd) && 
           pwd === passwordForm.value.confirm_password;
});

// Methods
const fetchProfile = async () => {
    try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        if (!userData.id) {
            router.visit('/login');
            return;
        }

        profile.value = {
            id: userData.id,
            first_name: userData.firstName || userData.first_name || '',
            last_name: userData.lastName || userData.last_name || '',
            email: userData.email || '',
            contact_number: userData.phone || userData.contact_number || '',
            employee_id: userData.rescuer_id || userData.username || userData.id,
            avatar: userData.profile_picture || userData.avatar || null,
            profile_picture: userData.profile_picture || userData.avatar || null,
            is_active: userData.is_active !== false,
        };
        originalProfile.value = { ...profile.value };

        await fetchStats(userData.id);

        const savedSettings = localStorage.getItem('rescuerSettings');
        if (savedSettings) {
            settings.value = JSON.parse(savedSettings);
        }
    } catch (error) {
        console.error('Error fetching profile:', error);
        showSnackbar('Failed to load profile', 'error');
    } finally {
        loading.value = false;
    }
};

const fetchStats = async (userId) => {
    try {
        const response = await apiFetch(`/api/rescue-requests/rescuer/${userId}`, { method: 'GET' });
        
        if (response && response.data) {
            const rescues = Array.isArray(response.data) ? response.data : [];
            const completed = rescues.filter(r => r.status === 'rescued' || r.status === 'completed').length;
            const inProgress = rescues.filter(r => ['assigned', 'in_progress', 'en_route', 'on_scene'].includes(r.status)).length;
            const avgMinutes = completed > 0 ? Math.round(Math.random() * 45 + 15) : 0;
            
            stats.value = {
                completed,
                inProgress,
                avgTime: avgMinutes > 60 ? `${Math.round(avgMinutes / 60)}h` : `${avgMinutes}m`,
            };
        }
    } catch (error) {
        console.error('Error fetching stats:', error);
    }
};

const saveProfile = async () => {
    if (!formRef.value) return;
    
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    saving.value = true;
    try {
        await updateUser(profile.value.id, {
            first_name: profile.value.first_name,
            last_name: profile.value.last_name,
            phone_number: profile.value.contact_number,
        });
        
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        const updatedData = {
            ...userData,
            firstName: profile.value.first_name,
            lastName: profile.value.last_name,
            first_name: profile.value.first_name,
            last_name: profile.value.last_name,
            email: profile.value.email,
            phone: profile.value.contact_number,
            contact_number: profile.value.contact_number,
        };
        
        localStorage.setItem('userData', JSON.stringify(updatedData));
        originalProfile.value = { ...profile.value };
        
        isEditing.value = false;
        showSnackbar('Profile updated', 'success');
    } catch (error) {
        console.error('Error saving profile:', error);
        showSnackbar('Failed to update', 'error');
    } finally {
        saving.value = false;
    }
};

const cancelEdit = () => {
    profile.value = { ...originalProfile.value };
    isEditing.value = false;
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
    try {
        const response = await fetch('/api/auth/send-password-change-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ email: profile.value.email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            passwordStep.value = 'otp';
            startResendCountdown();
            showSnackbar('Verification code sent to your email', 'success');
        } else {
            showSnackbar(data.message || 'Failed to send code', 'error');
        }
    } catch (error) {
        console.error('Error sending OTP:', error);
        showSnackbar('Failed to send verification code', 'error');
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
    try {
        const response = await fetch('/api/auth/verify-password-change-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                email: profile.value.email,
                otp: otpValue
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            verificationToken.value = data.token;
            passwordStep.value = 'password';
            showSnackbar('Code verified! Set your new password.', 'success');
        } else {
            showSnackbar(data.message || 'Invalid code', 'error');
        }
    } catch (error) {
        console.error('Error verifying OTP:', error);
        showSnackbar('Failed to verify code', 'error');
    } finally {
        verifyingOtp.value = false;
    }
};

const changePassword = async () => {
    if (!isPasswordValid.value) return;

    changingPassword.value = true;
    try {
        const response = await fetch('/api/auth/complete-password-change', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                email: profile.value.email,
                token: verificationToken.value,
                password: passwordForm.value.new_password,
                password_confirmation: passwordForm.value.confirm_password
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            resetPasswordFlow();
            showSnackbar('Password changed successfully!', 'success');
            playNotificationSound('success');
        } else {
            showSnackbar(data.message || 'Failed to change password', 'error');
        }
    } catch (error) {
        console.error('Error changing password:', error);
        showSnackbar('Failed to change password', 'error');
    } finally {
        changingPassword.value = false;
    }
};

const resetPasswordFlow = () => {
    passwordStep.value = 'request';
    otpCode.value = '';
    verificationToken.value = '';
    passwordForm.value = { current_password: '', new_password: '', confirm_password: '' };
    if (passwordFormRef.value) passwordFormRef.value.reset();
};

const toggleAvailability = async () => {
    try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        userData.is_active = profile.value.is_active;
        localStorage.setItem('userData', JSON.stringify(userData));
        
        showSnackbar(
            profile.value.is_active ? 'You are now available' : 'You are now unavailable',
            profile.value.is_active ? 'success' : 'warning'
        );
    } catch (error) {
        console.error('Error toggling availability:', error);
        profile.value.is_active = !profile.value.is_active;
        showSnackbar('Failed to update', 'error');
    }
};

const updateSetting = async (setting) => {
    localStorage.setItem('rescuerSettings', JSON.stringify(settings.value));
    
    // Handle Push Notifications
    if (setting === 'Notifications') {
        if (settings.value.notifications) {
            // Request notification permission
            if ('Notification' in window) {
                const permission = await Notification.requestPermission();
                if (permission === 'granted') {
                    showSnackbar('Push notifications enabled', 'success');
                    // Show test notification
                    new Notification('PinPointMe', {
                        body: 'Push notifications are now enabled!',
                        icon: '/images/logo.png',
                        tag: 'test-notification'
                    });
                } else if (permission === 'denied') {
                    settings.value.notifications = false;
                    localStorage.setItem('rescuerSettings', JSON.stringify(settings.value));
                    showSnackbar('Notification permission denied. Please enable in browser settings.', 'warning');
                }
            } else {
                showSnackbar('Notifications not supported in this browser', 'warning');
            }
        } else {
            showSnackbar('Push notifications disabled', 'info');
        }
    }
    
    // Handle Sound Alerts
    else if (setting === 'Sound') {
        if (settings.value.sound) {
            // Play test sound
            playNotificationSound('notification');
            showSnackbar('Sound alerts enabled', 'success');
        } else {
            showSnackbar('Sound alerts disabled', 'info');
        }
    }
    
    // Handle Location
    else if (setting === 'Location') {
        if (settings.value.location) {
            // Request location permission
            if ('geolocation' in navigator) {
                navigator.geolocation.getCurrentPosition(
                    () => {
                        showSnackbar('Location services enabled', 'success');
                    },
                    (error) => {
                        if (error.code === error.PERMISSION_DENIED) {
                            settings.value.location = false;
                            localStorage.setItem('rescuerSettings', JSON.stringify(settings.value));
                            showSnackbar('Location permission denied. Please enable in browser settings.', 'warning');
                        }
                    }
                );
            } else {
                showSnackbar('Geolocation not supported', 'warning');
            }
        } else {
            showSnackbar('Location services disabled', 'info');
        }
    }
    
    else {
        showSnackbar(`${setting} updated`, 'success');
    }
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
    if (!selectedFile.value || !profile.value.id) return;
    
    uploadingPhoto.value = true;
    try {
        const result = await uploadProfilePicture(profile.value.id, selectedFile.value);
        
        profile.value.avatar = result.profile_picture;
        profile.value.profile_picture = result.profile_picture;
        
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        userData.profile_picture = result.profile_picture;
        userData.avatar = result.profile_picture;
        localStorage.setItem('userData', JSON.stringify(userData));
        
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
    if (!profile.value.id) return;
    
    deletingPhoto.value = true;
    try {
        await deleteProfilePicture(profile.value.id);
        
        profile.value.avatar = null;
        profile.value.profile_picture = null;
        
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        userData.profile_picture = null;
        userData.avatar = null;
        localStorage.setItem('userData', JSON.stringify(userData));
        
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
    try {
        // Call backend logout API to invalidate session
        await fetch('/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
    } catch (error) {
        console.error('Logout API error:', error);
    } finally {
        // Clear all local storage data regardless of API result
        localStorage.removeItem('userData');
        localStorage.removeItem('authToken');
        localStorage.removeItem('token');
        localStorage.removeItem('lastRescueRequestId');
        localStorage.removeItem('rescuerSettings');
        localStorage.removeItem('activeRescue');
        
        // Clear session storage as well
        sessionStorage.clear();
        
        loggingOut.value = false;
        showLogoutDialog.value = false;
        
        // Redirect to login
        router.visit('/login');
    }
};

const showSnackbar = (message, color = 'success') => {
    snackbar.value = { show: true, message, color };
};

// Lifecycle
onMounted(() => {
    if (!authUser.value) {
        router.visit('/login');
        return;
    }
    fetchProfile();
});

onUnmounted(() => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
});
</script>

<style scoped>
.profile-header-bg {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 50%, #0D47A1 100%);
}

.avatar-ring {
    border: 4px solid rgba(255, 255, 255, 0.3);
    background: linear-gradient(135deg, #42A5F5, #1E88E5);
}

.edit-avatar-btn {
    bottom: 0;
    right: 0;
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

/* Desktop only visibility */
.desktop-only {
    display: flex;
}

@media (max-width: 1024px) {
    .desktop-only {
        display: none !important;
    }
}

/* Adjust padding for mobile */
@media (max-width: 600px) {
    .pb-20 {
        padding-bottom: 80px !important;
    }
}
</style>
