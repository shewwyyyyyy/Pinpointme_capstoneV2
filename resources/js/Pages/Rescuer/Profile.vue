<template>
    <v-app class="bg-user-gradient-light">
        <!-- App Bar -->
        <v-app-bar color="primary" density="comfortable">
            <v-btn icon @click="drawer = true">
                <v-icon>mdi-menu</v-icon>
            </v-btn>
            <v-app-bar-title>Profile</v-app-bar-title>
            <v-spacer />
            <v-btn icon @click="isEditing = !isEditing">
                <v-icon>{{ isEditing ? 'mdi-close' : 'mdi-pencil' }}</v-icon>
            </v-btn>
            <v-avatar 
                size="32" 
                class="ml-1 mr-2"
                :style="profilePicture ? 'cursor: pointer' : ''"
                @click="profilePicture && (photoDialog = true)"
            >
                <v-img v-if="profilePicture" :src="profilePicture" cover />
                <v-icon v-else color="white">mdi-account-circle</v-icon>
            </v-avatar>
        </v-app-bar>

        <!-- Navigation Drawer -->
        <RescuerMenu v-model="drawer" />

        <!-- Main Content -->
        <v-main>
            <div v-if="loading" class="d-flex justify-center align-center" style="min-height: 60vh;">
                <v-progress-circular indeterminate color="primary" size="64" />
            </div>

            <div v-else class="pa-4">
                <!-- Profile Header -->
                <v-card class="mb-4" elevation="2">
                    <v-card-text class="text-center py-6">
                        <div class="position-relative d-inline-block">
                            <v-avatar size="100" color="primary" class="mb-4">
                                <v-img 
                                    v-if="profilePictureUrl" 
                                    :src="profilePictureUrl" 
                                    cover
                                />
                                <span v-else class="text-h3 text-white">{{ getInitials }}</span>
                            </v-avatar>
                            <v-btn
                                icon
                                size="small"
                                color="primary"
                                class="position-absolute"
                                style="bottom: 12px; right: -8px;"
                                @click="openPhotoDialog"
                            >
                                <v-icon size="16">mdi-camera</v-icon>
                            </v-btn>
                        </div>
                        <h2 class="text-h5 font-weight-bold">{{ fullName }}</h2>
                        <p class="text-grey text-subtitle-2">Rescuer</p>
                        <v-chip
                            :color="profile.is_active ? 'success' : 'grey'"
                            size="small"
                            variant="flat"
                            class="mt-2"
                        >
                            {{ profile.is_active ? 'Active' : 'Inactive' }}
                        </v-chip>
                    </v-card-text>
                </v-card>

                <!-- Stats Card -->
                <v-card class="mb-4" elevation="2">
                    <v-card-title class="text-subtitle-1">
                        <v-icon start>mdi-chart-bar</v-icon>
                        Statistics
                    </v-card-title>
                    <v-card-text>
                        <v-row>
                            <v-col cols="4" class="text-center">
                                <div class="text-h4 font-weight-bold text-success">{{ stats.completed }}</div>
                                <div class="text-caption text-grey">Completed</div>
                            </v-col>
                            <v-col cols="4" class="text-center">
                                <div class="text-h4 font-weight-bold text-primary">{{ stats.inProgress }}</div>
                                <div class="text-caption text-grey">In Progress</div>
                            </v-col>
                            <v-col cols="4" class="text-center">
                                <div class="text-h4 font-weight-bold text-info">{{ stats.avgTime }}</div>
                                <div class="text-caption text-grey">Avg Time</div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <!-- Profile Form -->
                <v-card elevation="2">
                    <v-card-title class="text-subtitle-1">
                        <v-icon start>mdi-account-details</v-icon>
                        Personal Information
                    </v-card-title>
                    <v-card-text>
                        <v-form ref="formRef" v-model="formValid">
                            <v-row dense>
                                <v-col cols="6">
                                    <v-text-field
                                        v-model="profile.first_name"
                                        label="First Name"
                                        variant="outlined"
                                        density="comfortable"
                                        :readonly="!isEditing"
                                        :rules="[rules.required]"
                                    />
                                </v-col>
                                <v-col cols="6">
                                    <v-text-field
                                        v-model="profile.last_name"
                                        label="Last Name"
                                        variant="outlined"
                                        density="comfortable"
                                        :readonly="!isEditing"
                                        :rules="[rules.required]"
                                    />
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="profile.email"
                                        label="Email"
                                        type="email"
                                        variant="outlined"
                                        density="comfortable"
                                        :readonly="!isEditing"
                                        :rules="[rules.required, rules.email]"
                                    />
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="profile.contact_number"
                                        label="Contact Number"
                                        variant="outlined"
                                        density="comfortable"
                                        :readonly="!isEditing"
                                    />
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="profile.employee_id"
                                        label="Employee ID"
                                        variant="outlined"
                                        density="comfortable"
                                        readonly
                                        hint="Employee ID cannot be changed"
                                        persistent-hint
                                    />
                                </v-col>
                            </v-row>
                        </v-form>
                    </v-card-text>

                    <v-divider v-if="isEditing" />

                    <v-card-actions v-if="isEditing">
                        <v-spacer />
                        <v-btn
                            variant="text"
                            @click="cancelEdit"
                        >
                            Cancel
                        </v-btn>
                        <v-btn
                            color="primary"
                            variant="flat"
                            :loading="saving"
                            :disabled="!formValid"
                            @click="saveProfile"
                        >
                            Save Changes
                        </v-btn>
                    </v-card-actions>
                </v-card>

                <!-- Change Password Section -->
                <v-card class="mt-4" elevation="2">
                    <v-card-title class="text-subtitle-1">
                        <v-icon start>mdi-lock</v-icon>
                        Change Password
                    </v-card-title>
                    <v-card-text>
                        <v-form ref="passwordFormRef" v-model="passwordFormValid">
                            <v-text-field
                                v-model="passwordForm.current_password"
                                label="Current Password"
                                type="password"
                                variant="outlined"
                                density="comfortable"
                                :rules="[rules.required]"
                            />
                            <v-text-field
                                v-model="passwordForm.new_password"
                                label="New Password"
                                type="password"
                                variant="outlined"
                                density="comfortable"
                                :rules="[rules.required, rules.minLength]"
                            />
                            <v-text-field
                                v-model="passwordForm.confirm_password"
                                label="Confirm New Password"
                                type="password"
                                variant="outlined"
                                density="comfortable"
                                :rules="[rules.required, rules.passwordMatch]"
                            />
                        </v-form>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer />
                        <v-btn
                            color="primary"
                            variant="flat"
                            :loading="changingPassword"
                            :disabled="!passwordFormValid"
                            @click="changePassword"
                        >
                            Update Password
                        </v-btn>
                    </v-card-actions>
                </v-card>

                <!-- Settings Section -->
                <v-card class="mt-4" elevation="2">
                    <v-card-title class="text-subtitle-1">
                        <v-icon start>mdi-cog</v-icon>
                        Settings
                    </v-card-title>
                    <v-card-text>
                        <v-list>
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon color="primary">mdi-bell</v-icon>
                                </template>
                                <v-list-item-title>Push Notifications</v-list-item-title>
                                <v-list-item-subtitle>Receive rescue alerts</v-list-item-subtitle>
                                <template v-slot:append>
                                    <v-switch
                                        v-model="settings.notifications"
                                        color="primary"
                                        hide-details
                                        density="compact"
                                        @change="updateSetting('notifications')"
                                    />
                                </template>
                            </v-list-item>
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon color="primary">mdi-volume-high</v-icon>
                                </template>
                                <v-list-item-title>Sound Alerts</v-list-item-title>
                                <v-list-item-subtitle>Play sound for new rescues</v-list-item-subtitle>
                                <template v-slot:append>
                                    <v-switch
                                        v-model="settings.sound"
                                        color="primary"
                                        hide-details
                                        density="compact"
                                        @change="updateSetting('sound')"
                                    />
                                </template>
                            </v-list-item>
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon color="primary">mdi-map-marker</v-icon>
                                </template>
                                <v-list-item-title>Location Services</v-list-item-title>
                                <v-list-item-subtitle>Share location during rescue</v-list-item-subtitle>
                                <template v-slot:append>
                                    <v-switch
                                        v-model="settings.location"
                                        color="primary"
                                        hide-details
                                        density="compact"
                                        @change="updateSetting('location')"
                                    />
                                </template>
                            </v-list-item>
                        </v-list>
                    </v-card-text>
                </v-card>

                <!-- Availability Toggle -->
                <v-card class="mt-4" elevation="2">
                    <v-card-item>
                        <template v-slot:prepend>
                            <v-avatar :color="profile.is_active ? 'success' : 'grey'" size="40">
                                <v-icon color="white">
                                    {{ profile.is_active ? 'mdi-check-circle' : 'mdi-close-circle' }}
                                </v-icon>
                            </v-avatar>
                        </template>
                        <v-card-title>Availability Status</v-card-title>
                        <v-card-subtitle>
                            {{ profile.is_active ? 'You are receiving rescue requests' : 'You are not receiving requests' }}
                        </v-card-subtitle>
                        <template v-slot:append>
                            <v-switch
                                v-model="profile.is_active"
                                color="success"
                                hide-details
                                @change="toggleAvailability"
                            />
                        </template>
                    </v-card-item>
                </v-card>

                <!-- Logout Button -->
                <v-btn
                    block
                    color="error"
                    variant="tonal"
                    size="large"
                    class="mt-6"
                    prepend-icon="mdi-logout"
                    @click="showLogoutDialog = true"
                >
                    Logout
                </v-btn>

                <!-- App Version -->
                <div class="text-center text-caption text-grey mt-4">
                    Version 1.0.0
                </div>
            </div>
        </v-main>

        <!-- Logout Confirmation Dialog -->
        <v-dialog v-model="showLogoutDialog" max-width="320">
            <v-card>
                <v-card-title class="text-h6">Logout</v-card-title>
                <v-card-text>Are you sure you want to logout?</v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showLogoutDialog = false">Cancel</v-btn>
                    <v-btn color="error" variant="flat" @click="handleLogout">Logout</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Avatar Upload (hidden) -->
        <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/gif,image/webp"
            style="display: none;"
            @change="handleFileSelect"
        />

        <!-- Profile Photo Dialog -->
        <v-dialog v-model="photoDialog" max-width="400">
            <v-card>
                <v-card-title class="d-flex align-center">
                    <v-icon class="mr-2">mdi-camera</v-icon>
                    Profile Photo
                </v-card-title>
                <v-divider />
                <v-card-text class="text-center py-6">
                    <v-avatar size="120" color="primary" class="mb-4">
                        <v-img v-if="previewUrl || profilePictureUrl" :src="previewUrl || profilePictureUrl" cover />
                        <span v-else class="text-h3 text-white">{{ getInitials }}</span>
                    </v-avatar>
                    
                    <div class="d-flex justify-center ga-2 mt-4">
                        <v-btn
                            color="primary"
                            variant="outlined"
                            prepend-icon="mdi-upload"
                            @click="triggerFileInput"
                        >
                            {{ profilePictureUrl ? 'Change Photo' : 'Upload Photo' }}
                        </v-btn>
                        <v-btn
                            v-if="profilePictureUrl && !previewUrl"
                            color="error"
                            variant="outlined"
                            prepend-icon="mdi-delete"
                            @click="confirmDeletePhoto"
                        >
                            Remove
                        </v-btn>
                    </div>
                    
                    <p class="text-caption text-grey mt-3">
                        Supported formats: JPEG, PNG, GIF, WebP (max 5MB)
                    </p>
                </v-card-text>
                <v-divider />
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="cancelPhotoUpload">Cancel</v-btn>
                    <v-btn
                        v-if="selectedFile"
                        color="primary"
                        variant="flat"
                        :loading="uploadingPhoto"
                        @click="uploadPhoto"
                    >
                        Save Photo
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Photo Confirmation -->
        <v-dialog v-model="deletePhotoDialog" max-width="320">
            <v-card>
                <v-card-title>Remove Photo?</v-card-title>
                <v-card-text>
                    Are you sure you want to remove your profile photo?
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deletePhotoDialog = false">Cancel</v-btn>
                    <v-btn
                        color="error"
                        variant="flat"
                        :loading="deletingPhoto"
                        @click="deletePhoto"
                    >
                        Remove
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000">
            {{ snackbar.message }}
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { apiFetch, uploadProfilePicture, deleteProfilePicture, getProfilePictureUrl, updateUser } from '@/Composables/useApi';
import RescuerMenu from '@/Components/Pages/Rescuer/Menu/RescuerMenu.vue';

// Refs
const formRef = ref(null);
const passwordFormRef = ref(null);

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

// Photo upload
const fileInput = ref(null);
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
    required: v => !!v || 'This field is required',
    email: v => /.+@.+\..+/.test(v) || 'Invalid email format',
    minLength: v => (v && v.length >= 8) || 'Minimum 8 characters',
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
    // If it's already a full URL or data URL, return as is
    if (picturePath.startsWith('http') || picturePath.startsWith('data:')) return picturePath;
    return getProfilePictureUrl(picturePath);
});

// Methods
const fetchProfile = async () => {
    try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        if (!userData.id) {
            router.visit('/login');
            return;
        }

        // Use stored user data directly instead of API call
        profile.value = {
            id: userData.id,
            first_name: userData.firstName || userData.first_name || '',
            last_name: userData.lastName || userData.last_name || '',
            email: userData.email || '',
            contact_number: userData.phone || userData.contact_number || '',
            employee_id: userData.rescuer_id || userData.username || userData.id,
            avatar: userData.profile_picture || userData.avatar || null,
            profile_picture: userData.profile_picture || userData.avatar || null,
            is_active: userData.is_active !== false, // Default to true
        };
        originalProfile.value = { ...profile.value };

        // Fetch stats
        await fetchStats(userData.id);

        // Load settings from localStorage
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
            
            // Calculate average time (simplified)
            const avgMinutes = completed > 0 ? Math.round(Math.random() * 45 + 15) : 0; // Mock data for now
            
            stats.value = {
                completed,
                inProgress,
                avgTime: avgMinutes > 60 ? `${Math.round(avgMinutes / 60)}h` : `${avgMinutes}m`,
            };
        } else {
            // Default stats if no data
            stats.value = {
                completed: 0,
                inProgress: 0,
                avgTime: '0m',
            };
        }
    } catch (error) {
        console.error('Error fetching stats:', error);
        // Set default stats on error
        stats.value = {
            completed: 0,
            inProgress: 0,
            avgTime: '0m',
        };
    }
};

const saveProfile = async () => {
    if (!formRef.value) return;
    
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    saving.value = true;
    try {
        // Make API call to update the user
        const updateData = {
            first_name: profile.value.first_name,
            last_name: profile.value.last_name,
            phone_number: profile.value.contact_number,
        };
        
        await updateUser(profile.value.id, updateData);
        
        // Update localStorage with the new data
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
        showSnackbar('Profile updated successfully', 'success');
    } catch (error) {
        console.error('Error saving profile:', error);
        showSnackbar('Failed to update profile', 'error');
    } finally {
        saving.value = false;
    }
};

const cancelEdit = () => {
    profile.value = { ...originalProfile.value };
    isEditing.value = false;
};

const changePassword = async () => {
    if (!passwordFormRef.value) return;
    
    const { valid } = await passwordFormRef.value.validate();
    if (!valid) return;

    changingPassword.value = true;
    try {
        // API call to change the password
        await updateUser(profile.value.id, {
            current_password: passwordForm.value.current_password,
            password: passwordForm.value.new_password,
            password_confirmation: passwordForm.value.confirm_password,
        });
        
        passwordForm.value = {
            current_password: '',
            new_password: '',
            confirm_password: '',
        };
        passwordFormRef.value.reset();
        showSnackbar('Password changed successfully', 'success');
    } catch (error) {
        console.error('Error changing password:', error);
        showSnackbar(error.message || 'Failed to change password', 'error');
    } finally {
        changingPassword.value = false;
    }
};

const toggleAvailability = async () => {
    try {
        // Update localStorage directly
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
        showSnackbar('Failed to update availability', 'error');
    }
};

const updateSetting = (setting) => {
    localStorage.setItem('rescuerSettings', JSON.stringify(settings.value));
    showSnackbar(`${setting} setting updated`, 'success');
};

// Photo handling functions
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
    
    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        showSnackbar('File size must be less than 5MB', 'error');
        return;
    }
    
    // Validate file type
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        showSnackbar('Invalid file type. Use JPEG, PNG, GIF, or WebP', 'error');
        return;
    }
    
    selectedFile.value = file;
    previewUrl.value = URL.createObjectURL(file);
};

const cancelPhotoUpload = () => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
    selectedFile.value = null;
    previewUrl.value = null;
    photoDialog.value = false;
};

const uploadPhoto = async () => {
    if (!selectedFile.value || !profile.value.id) return;
    
    uploadingPhoto.value = true;
    try {
        const result = await uploadProfilePicture(profile.value.id, selectedFile.value);
        
        // Update profile data with new profile picture
        profile.value.avatar = result.profile_picture;
        profile.value.profile_picture = result.profile_picture;
        
        // Update localStorage
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        userData.profile_picture = result.profile_picture;
        userData.avatar = result.profile_picture;
        localStorage.setItem('userData', JSON.stringify(userData));
        
        showSnackbar('Profile photo updated successfully', 'success');
        
        // Clean up
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
        }
        selectedFile.value = null;
        previewUrl.value = null;
        photoDialog.value = false;
    } catch (err) {
        console.error('Error uploading photo:', err);
        showSnackbar('Failed to upload photo', 'error');
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
        
        // Update profile data to remove profile picture
        profile.value.avatar = null;
        profile.value.profile_picture = null;
        
        // Update localStorage
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        userData.profile_picture = null;
        userData.avatar = null;
        localStorage.setItem('userData', JSON.stringify(userData));
        
        showSnackbar('Profile photo removed', 'success');
        deletePhotoDialog.value = false;
        photoDialog.value = false;
    } catch (err) {
        console.error('Error deleting photo:', err);
        showSnackbar('Failed to remove photo', 'error');
    } finally {
        deletingPhoto.value = false;
    }
};

const handleLogout = () => {
    localStorage.removeItem('userData');
    localStorage.removeItem('authToken');
    localStorage.removeItem('lastRescueRequestId');
    localStorage.removeItem('rescuerSettings');
    router.visit('/login');
};

const showSnackbar = (message, color = 'success') => {
    snackbar.value = { show: true, message, color };
};

// Lifecycle
onMounted(() => {
    // Check for auth token, redirect to login if missing
    const authToken = localStorage.getItem('authToken');
    if (!authToken) {
        console.warn('No auth token found, redirecting to login');
        router.visit('/login');
        return;
    }
    fetchProfile();
});
</script>

<style scoped>
.position-relative {
    position: relative;
}

.position-absolute {
    position: absolute;
}

.d-inline-block {
    display: inline-block;
}
</style>
