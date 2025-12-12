<template>
    <v-app>
        <v-app-bar color="primary" density="compact">
            <v-btn icon @click="goBack">
                <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            <v-app-bar-title>Profile</v-app-bar-title>
            <template v-slot:append>
                <v-btn icon @click="openEditDialog">
                    <v-icon>mdi-pencil</v-icon>
                </v-btn>
                <v-avatar 
                    size="32" 
                    class="ml-1 mr-2"
                    :style="profilePictureUrl ? 'cursor: pointer' : ''"
                    @click="profilePictureUrl && (photoDialog = true)"
                >
                    <v-img v-if="profilePictureUrl" :src="profilePictureUrl" cover />
                    <v-icon v-else color="white">mdi-account-circle</v-icon>
                </v-avatar>
            </template>
        </v-app-bar>

        <v-main class="bg-user-gradient-light">
            <v-container>
                <!-- Profile Card -->
                <v-card class="mb-4">
                    <v-card-text class="text-center py-6">
                        <div class="position-relative d-inline-block">
                            <v-avatar size="100" color="primary" class="mb-4">
                                <v-img v-if="profilePictureUrl" :src="profilePictureUrl" cover />
                                <span v-else class="text-h4 text-white">
                                    {{ getInitials(user?.name) }}
                                </span>
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
                        <h2 class="text-h5 font-weight-bold">{{ user?.name || 'User' }}</h2>
                        <p class="text-grey text-subtitle-2">{{ user?.email || '' }}</p>
                        <v-chip
                            :color="user?.is_verified ? 'success' : 'warning'"
                            size="small"
                            class="mt-2"
                        >
                            {{ user?.is_verified ? 'Verified' : 'Not Verified' }}
                        </v-chip>
                    </v-card-text>
                </v-card>

                <!-- Personal Information -->
                <v-card class="mb-4">
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="primary">mdi-account</v-icon>
                        Personal Information
                    </v-card-title>
                    <v-divider />
                    <v-list density="compact">
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="grey">mdi-account-outline</v-icon>
                            </template>
                            <v-list-item-title>First Name</v-list-item-title>
                            <v-list-item-subtitle>{{ user?.first_name || '-' }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="grey">mdi-account-outline</v-icon>
                            </template>
                            <v-list-item-title>Last Name</v-list-item-title>
                            <v-list-item-subtitle>{{ user?.last_name || '-' }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="grey">mdi-email-outline</v-icon>
                            </template>
                            <v-list-item-title>Email</v-list-item-title>
                            <v-list-item-subtitle>{{ user?.email || '-' }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="grey">mdi-phone-outline</v-icon>
                            </template>
                            <v-list-item-title>Phone Number</v-list-item-title>
                            <v-list-item-subtitle>{{ user?.phone_number || '-' }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="grey">mdi-calendar-outline</v-icon>
                            </template>
                            <v-list-item-title>Member Since</v-list-item-title>
                            <v-list-item-subtitle>{{ formatDate(user?.created_at) }}</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card>

                <!-- Emergency Contact -->
                <v-card class="mb-4">
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="error">mdi-phone-in-talk</v-icon>
                        Emergency Contact
                    </v-card-title>
                    <v-divider />
                    <v-list density="compact">
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="grey">mdi-account-heart-outline</v-icon>
                            </template>
                            <v-list-item-title>Contact Name</v-list-item-title>
                            <v-list-item-subtitle>{{ user?.emergency_contact_name || '-' }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="grey">mdi-phone-outline</v-icon>
                            </template>
                            <v-list-item-title>Contact Phone</v-list-item-title>
                            <v-list-item-subtitle>{{ user?.emergency_contact_phone || '-' }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="grey">mdi-account-tie-outline</v-icon>
                            </template>
                            <v-list-item-title>Relationship</v-list-item-title>
                            <v-list-item-subtitle>{{ user?.emergency_contact_relation || '-' }}</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card>

                <!-- Medical Information -->
                <v-card class="mb-4">
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="red">mdi-medical-bag</v-icon>
                        Medical Information
                    </v-card-title>
                    <v-divider />
                    <v-list density="compact">
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="grey">mdi-blood-bag</v-icon>
                            </template>
                            <v-list-item-title>Blood Type</v-list-item-title>
                            <v-list-item-subtitle>{{ user?.blood_type || '-' }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="grey">mdi-allergy</v-icon>
                            </template>
                            <v-list-item-title>Allergies</v-list-item-title>
                            <v-list-item-subtitle>{{ user?.allergies || '-' }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="grey">mdi-pill</v-icon>
                            </template>
                            <v-list-item-title>Medical Conditions</v-list-item-title>
                            <v-list-item-subtitle>{{ user?.medical_conditions || '-' }}</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card>

                <!-- App Settings -->
                <v-card class="mb-4">
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="grey">mdi-cog-outline</v-icon>
                        Settings
                    </v-card-title>
                    <v-divider />
                    <v-list density="compact">
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="grey">mdi-bell-outline</v-icon>
                            </template>
                            <v-list-item-title>Push Notifications</v-list-item-title>
                            <template v-slot:append>
                                <v-switch
                                    v-model="settings.pushNotifications"
                                    color="primary"
                                    hide-details
                                    density="compact"
                                />
                            </template>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon color="grey">mdi-map-marker-outline</v-icon>
                            </template>
                            <v-list-item-title>Location Services</v-list-item-title>
                            <template v-slot:append>
                                <v-switch
                                    v-model="settings.locationServices"
                                    color="primary"
                                    hide-details
                                    density="compact"
                                />
                            </template>
                        </v-list-item>
                    </v-list>
                </v-card>

                <!-- Actions -->
                <v-card class="mb-4">
                    <v-list density="compact">
                        <v-list-item @click="openChangePassword">
                            <template v-slot:prepend>
                                <v-icon color="grey">mdi-lock-outline</v-icon>
                            </template>
                            <v-list-item-title>Change Password</v-list-item-title>
                            <template v-slot:append>
                                <v-icon size="small">mdi-chevron-right</v-icon>
                            </template>
                        </v-list-item>
                        <v-divider />
                        <v-list-item @click="handleLogout" class="text-error">
                            <template v-slot:prepend>
                                <v-icon color="error">mdi-logout</v-icon>
                            </template>
                            <v-list-item-title>Logout</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-card>

                <!-- App Version -->
                <p class="text-center text-caption text-grey mt-4 mb-8">
                    PinPointMe v1.0.0
                </p>
            </v-container>

            <!-- Edit Profile Dialog -->
            <v-dialog v-model="editDialog" max-width="500" persistent>
                <v-card>
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2">mdi-account-edit</v-icon>
                        Edit Profile
                    </v-card-title>
                    <v-divider />
                    <v-card-text>
                        <v-form ref="editForm" v-model="formValid">
                            <v-row>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editData.first_name"
                                        label="First Name"
                                        variant="outlined"
                                        density="compact"
                                        :rules="[rules.required]"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editData.last_name"
                                        label="Last Name"
                                        variant="outlined"
                                        density="compact"
                                        :rules="[rules.required]"
                                    />
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="editData.phone_number"
                                        label="Phone Number"
                                        variant="outlined"
                                        density="compact"
                                        prepend-inner-icon="mdi-phone"
                                    />
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="editData.emergency_contact_name"
                                        label="Emergency Contact Name"
                                        variant="outlined"
                                        density="compact"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editData.emergency_contact_phone"
                                        label="Emergency Contact Phone"
                                        variant="outlined"
                                        density="compact"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editData.emergency_contact_relation"
                                        label="Relationship"
                                        variant="outlined"
                                        density="compact"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-select
                                        v-model="editData.blood_type"
                                        label="Blood Type"
                                        :items="['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']"
                                        variant="outlined"
                                        density="compact"
                                        clearable
                                    />
                                </v-col>
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="editData.allergies"
                                        label="Allergies"
                                        variant="outlined"
                                        density="compact"
                                        rows="2"
                                        placeholder="List any known allergies..."
                                    />
                                </v-col>
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="editData.medical_conditions"
                                        label="Medical Conditions"
                                        variant="outlined"
                                        density="compact"
                                        rows="2"
                                        placeholder="List any medical conditions..."
                                    />
                                </v-col>
                            </v-row>
                        </v-form>
                    </v-card-text>
                    <v-divider />
                    <v-card-actions>
                        <v-spacer />
                        <v-btn variant="text" @click="editDialog = false">Cancel</v-btn>
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
            </v-dialog>

            <!-- Change Password Dialog -->
            <v-dialog v-model="passwordDialog" max-width="400" persistent>
                <v-card>
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2">mdi-lock</v-icon>
                        Change Password
                    </v-card-title>
                    <v-divider />
                    <v-card-text>
                        <v-form ref="passwordForm" v-model="passwordFormValid">
                            <v-text-field
                                v-model="passwordData.current_password"
                                label="Current Password"
                                :type="showCurrentPassword ? 'text' : 'password'"
                                variant="outlined"
                                density="compact"
                                class="mb-3"
                                :rules="[rules.required]"
                                :append-inner-icon="showCurrentPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                @click:append-inner="showCurrentPassword = !showCurrentPassword"
                            />
                            <v-text-field
                                v-model="passwordData.new_password"
                                label="New Password"
                                :type="showNewPassword ? 'text' : 'password'"
                                variant="outlined"
                                density="compact"
                                class="mb-3"
                                :rules="[rules.required, rules.minLength]"
                                :append-inner-icon="showNewPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                @click:append-inner="showNewPassword = !showNewPassword"
                            />
                            <v-text-field
                                v-model="passwordData.confirm_password"
                                label="Confirm New Password"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                variant="outlined"
                                density="compact"
                                :rules="[rules.required, rules.passwordMatch]"
                                :append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                @click:append-inner="showConfirmPassword = !showConfirmPassword"
                            />
                        </v-form>
                    </v-card-text>
                    <v-divider />
                    <v-card-actions>
                        <v-spacer />
                        <v-btn variant="text" @click="passwordDialog = false">Cancel</v-btn>
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
            </v-dialog>

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
                            <span v-else class="text-h3 text-white">
                                {{ getInitials(user?.name) }}
                            </span>
                        </v-avatar>
                        
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/jpeg,image/png,image/gif,image/webp"
                            style="display: none;"
                            @change="handleFileSelect"
                        />
                        
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

            <!-- Toast Notification -->
            <v-snackbar v-model="showToast" :color="toastColor" location="top">
                {{ toastMessage }}
                <template v-slot:actions>
                    <v-btn variant="text" @click="showToast = false">Close</v-btn>
                </template>
            </v-snackbar>
        </v-main>
    </v-app>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { getCurrentUser, updateUser, uploadProfilePicture, deleteProfilePicture, getProfilePictureUrl } from '@/Composables/useApi';

// Get Inertia page for auth
const page = usePage();
const authUser = computed(() => page.props?.auth?.user);

// State
const user = ref(null);
const loading = ref(false);
const saving = ref(false);
const changingPassword = ref(false);
const uploadingPhoto = ref(false);
const deletingPhoto = ref(false);
const editDialog = ref(false);
const passwordDialog = ref(false);
const photoDialog = ref(false);
const deletePhotoDialog = ref(false);
const formValid = ref(false);
const passwordFormValid = ref(false);

// Photo upload
const fileInput = ref(null);
const selectedFile = ref(null);
const previewUrl = ref(null);

// Password visibility
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

// Settings - Load from localStorage for persistence
const settings = reactive({
    pushNotifications: true,
    locationServices: true,
});

// Load settings from localStorage on mount
const loadSettings = () => {
    const savedSettings = localStorage.getItem('userSettings');
    if (savedSettings) {
        const parsed = JSON.parse(savedSettings);
        settings.pushNotifications = parsed.pushNotifications ?? true;
        settings.locationServices = parsed.locationServices ?? true;
    }
};

// Save settings to localStorage when they change
watch(settings, (newSettings) => {
    localStorage.setItem('userSettings', JSON.stringify(newSettings));
}, { deep: true });

// Edit data
const editData = reactive({
    first_name: '',
    last_name: '',
    phone_number: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    emergency_contact_relation: '',
    blood_type: '',
    allergies: '',
    medical_conditions: '',
});

// Password data
const passwordData = reactive({
    current_password: '',
    new_password: '',
    confirm_password: '',
});

// Validation rules
const rules = {
    required: (v) => !!v || 'This field is required',
    minLength: (v) => (v && v.length >= 8) || 'Password must be at least 8 characters',
    passwordMatch: (v) => v === passwordData.new_password || 'Passwords do not match',
};

// Toast
const showToast = ref(false);
const toastMessage = ref('');
const toastColor = ref('success');

// Computed property for profile picture URL
const profilePictureUrl = computed(() => {
    if (!user.value) return null;
    const picturePath = user.value.profile_picture || user.value.profile_photo;
    if (!picturePath) return null;
    return getProfilePictureUrl(picturePath);
});

onMounted(async () => {
    loadSettings();
    await loadUser();
});

const loadUser = async () => {
    loading.value = true;
    try {
        // Check authentication via Inertia first
        if (!authUser.value) {
            console.warn('No authenticated user, redirecting to login');
            router.visit('/login');
            return;
        }

        // Use Inertia auth user data
        const inertiaUser = authUser.value;
        user.value = {
            id: inertiaUser.id,
            email: inertiaUser.email,
            first_name: inertiaUser.first_name || '',
            last_name: inertiaUser.last_name || '',
            name: `${inertiaUser.first_name || ''} ${inertiaUser.last_name || ''}`.trim(),
            phone_number: inertiaUser.phone_number || inertiaUser.contact_number || '',
            role: inertiaUser.role || 'student',
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
        
        // Also save to localStorage for other components
        localStorage.setItem('userData', JSON.stringify(user.value));

        // Optionally try to refresh from API for full data
        try {
            const data = await getCurrentUser();
            if (data) {
                user.value = { ...user.value, ...data };
                localStorage.setItem('userData', JSON.stringify(user.value));
            }
        } catch (apiErr) {
            // API call failed, but we still have Inertia auth data
            console.warn('API call failed, using Inertia auth data:', apiErr.message);
        }
    } catch (err) {
        console.error('Error loading user:', err);
        router.visit('/login');
    } finally {
        loading.value = false;
    }
};

const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const openEditDialog = () => {
    if (user.value) {
        editData.first_name = user.value.first_name || '';
        editData.last_name = user.value.last_name || '';
        editData.phone_number = user.value.phone_number || '';
        editData.emergency_contact_name = user.value.emergency_contact_name || '';
        editData.emergency_contact_phone = user.value.emergency_contact_phone || '';
        editData.emergency_contact_relation = user.value.emergency_contact_relation || '';
        editData.blood_type = user.value.blood_type || '';
        editData.allergies = user.value.allergies || '';
        editData.medical_conditions = user.value.medical_conditions || '';
    }
    editDialog.value = true;
};

const saveProfile = async () => {
    if (!formValid.value) return;

    saving.value = true;
    try {
        const updatedUser = await updateUser(user.value.id, editData);
        user.value = { ...user.value, ...updatedUser };
        localStorage.setItem('userData', JSON.stringify(user.value));

        toastMessage.value = 'Profile updated successfully';
        toastColor.value = 'success';
        showToast.value = true;
        editDialog.value = false;
    } catch (err) {
        console.error('Error updating profile:', err);
        toastMessage.value = 'Failed to update profile';
        toastColor.value = 'error';
        showToast.value = true;
    } finally {
        saving.value = false;
    }
};

const openChangePassword = () => {
    passwordData.current_password = '';
    passwordData.new_password = '';
    passwordData.confirm_password = '';
    passwordDialog.value = true;
};

const changePassword = async () => {
    if (!passwordFormValid.value) return;

    changingPassword.value = true;
    try {
        // API call to change password
        await updateUser(user.value.id, {
            current_password: passwordData.current_password,
            password: passwordData.new_password,
            password_confirmation: passwordData.confirm_password,
        });

        toastMessage.value = 'Password changed successfully';
        toastColor.value = 'success';
        showToast.value = true;
        passwordDialog.value = false;
    } catch (err) {
        console.error('Error changing password:', err);
        toastMessage.value = err.message || 'Failed to change password';
        toastColor.value = 'error';
        showToast.value = true;
    } finally {
        changingPassword.value = false;
    }
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
        toastMessage.value = 'File size must be less than 5MB';
        toastColor.value = 'error';
        showToast.value = true;
        return;
    }
    
    // Validate file type
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        toastMessage.value = 'Invalid file type. Use JPEG, PNG, GIF, or WebP';
        toastColor.value = 'error';
        showToast.value = true;
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
    if (!selectedFile.value || !user.value) return;
    
    uploadingPhoto.value = true;
    try {
        const result = await uploadProfilePicture(user.value.id, selectedFile.value);
        
        // Update user data with new profile picture
        user.value = { ...user.value, ...result.user, profile_picture: result.profile_picture };
        localStorage.setItem('userData', JSON.stringify(user.value));
        
        toastMessage.value = 'Profile photo updated successfully';
        toastColor.value = 'success';
        showToast.value = true;
        
        // Clean up
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
        }
        selectedFile.value = null;
        previewUrl.value = null;
        photoDialog.value = false;
    } catch (err) {
        console.error('Error uploading photo:', err);
        toastMessage.value = 'Failed to upload photo';
        toastColor.value = 'error';
        showToast.value = true;
    } finally {
        uploadingPhoto.value = false;
    }
};

const confirmDeletePhoto = () => {
    deletePhotoDialog.value = true;
};

const deletePhoto = async () => {
    if (!user.value) return;
    
    deletingPhoto.value = true;
    try {
        await deleteProfilePicture(user.value.id);
        
        // Update user data to remove profile picture
        user.value = { ...user.value, profile_picture: null };
        localStorage.setItem('userData', JSON.stringify(user.value));
        
        toastMessage.value = 'Profile photo removed';
        toastColor.value = 'success';
        showToast.value = true;
        deletePhotoDialog.value = false;
        photoDialog.value = false;
    } catch (err) {
        console.error('Error deleting photo:', err);
        toastMessage.value = 'Failed to remove photo';
        toastColor.value = 'error';
        showToast.value = true;
    } finally {
        deletingPhoto.value = false;
    }
};

const handleLogout = () => {
    localStorage.removeItem('authToken');
    localStorage.removeItem('userData');
    router.visit('/login');
};

const goBack = () => {
    window.history.back();
};
</script>

<style scoped>
/* Additional styles if needed */
</style>
