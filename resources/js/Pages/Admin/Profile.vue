<template>
    <v-app class="bg-grey-lighten-4">
        <!-- App Bar -->
        <v-app-bar color="primary" elevation="2">
            <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
            <v-app-bar-title>
                <v-icon class="mr-2" color="white">mdi-shield-check</v-icon>
                <span class="text-white font-weight-bold">PinPointMe Admin</span>
            </v-app-bar-title>
            <v-spacer />
            <!-- Profile Avatar Menu -->
            <v-menu offset-y>
                <template v-slot:activator="{ props }">
                    <v-btn icon v-bind="props">
                        <v-avatar color="white" size="36">
                            <v-img v-if="profilePictureUrl" :src="profilePictureUrl" cover></v-img>
                            <span v-else class="text-primary font-weight-bold">{{ adminInitials }}</span>
                        </v-avatar>
                    </v-btn>
                </template>
                <v-list>
                    <v-list-item prepend-icon="mdi-account">
                        <v-list-item-title>Profile</v-list-item-title>
                    </v-list-item>
                    <v-list-item @click="toggleDarkMode" prepend-icon="mdi-theme-light-dark">
                        <v-list-item-title>{{ isDark ? 'Light Mode' : 'Dark Mode' }}</v-list-item-title>
                    </v-list-item>
                    <v-list-item @click="logout" prepend-icon="mdi-logout">
                        <v-list-item-title>Logout</v-list-item-title>
                    </v-list-item>
                </v-list>
            </v-menu>
        </v-app-bar>

        <!-- Navigation Drawer -->
        <v-navigation-drawer
            v-model="drawer"
            :permanent="!isMobile"
            :temporary="isMobile"
            app
        >
            <v-list>
                <v-list-item prepend-icon="mdi-view-dashboard" title="Dashboard" href="/admin/dashboard" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-account-group" title="Users" href="/admin/users" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-lifebuoy" title="Rescuers" href="/admin/rescuers" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-office-building" title="Buildings" href="/admin/buildings" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-file-chart" title="Reports" href="/admin/reports" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-shield-alert" title="Preventive Measures" href="/admin/preventive-measures" @click="closeDrawerOnMobile"></v-list-item>
            </v-list>
        </v-navigation-drawer>

        <!-- Main Content -->
        <v-main>
            <v-container :class="isMobile ? 'pa-3' : 'pa-6'" fluid>
                <!-- Page Header -->
                <div class="page-header mb-4 mb-md-6">
                    <div class="page-header-content">
                        <h1 :class="isMobile ? 'text-h5' : 'text-h4'" class="font-weight-bold">Admin Profile</h1>
                        <p class="text-grey mt-1 text-body-2">Manage your account settings</p>
                    </div>
                </div>

                <v-row>
                    <!-- Profile Card -->
                    <v-col cols="12" md="4">
                        <v-card rounded="xl" elevation="2">
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
                                            {{ adminInitials }}
                                        </span>
                                    </v-avatar>
                                    <v-btn
                                        icon
                                        size="small"
                                        color="primary"
                                        class="position-absolute edit-avatar-btn elevation-3"
                                        @click="openPhotoDialog"
                                    >
                                        <v-icon size="16">mdi-camera</v-icon>
                                    </v-btn>
                                </div>
                                
                                <h2 class="text-h5 font-weight-bold mt-3 text-white">{{ fullName }}</h2>
                                <p class="text-body-2 text-white-darken-1 mb-0">{{ profile.email }}</p>
                                <v-chip color="primary" variant="flat" size="small" class="mt-2">
                                    <v-icon start size="14">mdi-shield-account</v-icon>
                                    Administrator
                                </v-chip>
                            </div>
                        </v-card>
                    </v-col>

                    <!-- Edit Profile Form -->
                    <v-col cols="12" md="8">
                        <!-- Personal Information -->
                        <v-card rounded="xl" elevation="2" class="mb-4">
                            <v-card-title class="d-flex align-center pa-4">
                                <v-avatar color="primary" size="40" class="mr-3">
                                    <v-icon color="white">mdi-account</v-icon>
                                </v-avatar>
                                <span class="font-weight-bold">Personal Information</span>
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text class="pa-4">
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
                                                class="mb-3"
                                                prepend-inner-icon="mdi-account-outline"
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
                                                class="mb-3"
                                                prepend-inner-icon="mdi-account-outline"
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12">
                                            <v-text-field
                                                v-model="profile.email"
                                                label="Email"
                                                readonly
                                                disabled
                                                variant="outlined"
                                                density="comfortable"
                                                hide-details="auto"
                                                class="mb-3"
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
                                                class="mb-3"
                                                prepend-inner-icon="mdi-phone-outline"
                                                placeholder="09171234567"
                                                hint="Mobile number (e.g., 09171234567)"
                                                persistent-hint
                                                @input="formatPhoneNumber"
                                            ></v-text-field>
                                        </v-col>
                                    </v-row>
                                </v-form>
                                
                                <div v-if="hasPersonalChanges" class="d-flex flex-column flex-sm-row gap-2 mt-4">
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
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>
        </v-main>

        <!-- Profile Photo Dialog -->
        <v-dialog v-model="photoDialog" max-width="500">
            <v-card rounded="xl">
                <v-card-title class="pa-4">
                    <span class="font-weight-bold">Change Profile Photo</span>
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4 text-center">
                    <v-avatar size="150" class="mb-4">
                        <v-img v-if="previewUrl || profilePictureUrl" :src="previewUrl || profilePictureUrl" cover></v-img>
                        <span v-else class="text-h3 font-weight-bold text-primary">{{ adminInitials }}</span>
                    </v-avatar>
                    
                    <v-file-input
                        v-model="selectedFile"
                        label="Select photo"
                        accept="image/*"
                        prepend-icon="mdi-camera"
                        variant="outlined"
                        density="comfortable"
                        @update:model-value="onFileSelected"
                    ></v-file-input>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-btn variant="text" @click="photoDialog = false">Cancel</v-btn>
                    <v-spacer></v-spacer>
                    <v-btn 
                        v-if="profilePictureUrl" 
                        color="error" 
                        variant="text" 
                        @click="removePhoto"
                        :loading="removingPhoto"
                    >
                        Remove
                    </v-btn>
                    <v-btn 
                        color="primary" 
                        :loading="uploadingPhoto" 
                        :disabled="!selectedFile"
                        @click="uploadPhoto"
                    >
                        Upload
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
            {{ snackbar.message }}
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useDisplay } from 'vuetify';
import { getProfilePictureUrl, updateUser, uploadProfilePicture, deleteProfilePicture } from '@/Composables/useApi';

const { mobile } = useDisplay();
const isMobile = computed(() => mobile.value);
const page = usePage();

// State
const drawer = ref(!mobile.value);
const isDark = ref(false);
const loading = ref(false);
const saving = ref(false);
const changingPassword = ref(false);
const uploadingPhoto = ref(false);
const removingPhoto = ref(false);

// Form refs
const formRef = ref(null);
const passwordFormRef = ref(null);
const formValid = ref(false);
const passwordFormValid = ref(false);

// Password visibility
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

// Photo dialog
const photoDialog = ref(false);
const selectedFile = ref(null);
const previewUrl = ref(null);

// Snackbar
const snackbar = reactive({
    show: false,
    message: '',
    color: 'success'
});

// Profile data
const profile = ref({
    id: null,
    first_name: '',
    last_name: '',
    email: '',
    phone_number: '',
    profile_picture: null
});

// Edit data
const editData = reactive({
    first_name: '',
    last_name: '',
    phone_number: ''
});

// Password data
const passwordData = reactive({
    current_password: '',
    new_password: '',
    confirm_password: ''
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
    // Phone number validation
    phoneNumber: (v) => {
        if (!v) return true; // Optional field
        const cleaned = v.replace(/[\s\-\(\)]/g, '');
        
        // Must start with 09 and have exactly 11 digits
        if (!/^09[0-9]{9}$/.test(cleaned)) {
            return 'Please enter a valid mobile number (e.g., 09171234567)';
        }
        
        return true;
    }
};

// Computed
const fullName = computed(() => {
    return `${profile.value.first_name} ${profile.value.last_name}`.trim() || 'Administrator';
});

const adminInitials = computed(() => {
    const first = profile.value.first_name?.[0] || '';
    const last = profile.value.last_name?.[0] || '';
    return (first + last).toUpperCase() || 'AD';
});

const profilePictureUrl = computed(() => {
    const picturePath = profile.value.profile_picture;
    if (!picturePath) return null;
    if (picturePath.startsWith('http') || picturePath.startsWith('data:')) return picturePath;
    return getProfilePictureUrl(picturePath);
});

const hasPersonalChanges = computed(() => {
    return (
        editData.first_name !== (profile.value.first_name || '') ||
        editData.last_name !== (profile.value.last_name || '') ||
        editData.phone_number !== (profile.value.phone_number || '')
    );
});

const passwordChecks = computed(() => {
    const pwd = passwordData.new_password || '';
    return {
        length: pwd.length >= 8,
        uppercase: /[A-Z]/.test(pwd),
        lowercase: /[a-z]/.test(pwd),
        number: /[0-9]/.test(pwd),
        special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd)
    };
});

const isPasswordValid = computed(() => {
    const checks = passwordChecks.value;
    return checks.length && checks.uppercase && checks.lowercase && checks.number && checks.special;
});

// Methods
const closeDrawerOnMobile = () => {
    if (isMobile.value) {
        drawer.value = false;
    }
};

const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    
    // Apply theme to document
    if (isDark.value) {
        document.documentElement.classList.add('v-theme--dark');
        document.documentElement.classList.remove('v-theme--light');
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.classList.add('v-theme--light');
        document.documentElement.classList.remove('v-theme--dark');
        document.documentElement.setAttribute('data-theme', 'light');
    }
    
    // Save preference to localStorage
    localStorage.setItem('darkMode', isDark.value.toString());
    
    showSnackbar(`Switched to ${isDark.value ? 'dark' : 'light'} mode`, 'info');
};

const showSnackbar = (message, color = 'success') => {
    snackbar.message = message;
    snackbar.color = color;
    snackbar.show = true;
};

const formatPhoneNumber = (event) => {
    let value = editData.phone_number;
    if (!value) return;
    
    // Remove all non-digits except +
    value = value.replace(/[^\d+]/g, '');
    
    // If starts with +63, keep it
    if (value.startsWith('+63')) {
        value = '+63' + value.slice(3).replace(/\D/g, '');
    } else if (value.startsWith('63')) {
        value = '63' + value.slice(2).replace(/\D/g, '');
    } else if (value.startsWith('0')) {
        value = '0' + value.slice(1).replace(/\D/g, '');
    }
    
    editData.phone_number = value;
};

const loadProfile = () => {
    // Load from page props or localStorage
    const authUser = page.props.auth?.user;
    const storedUser = JSON.parse(localStorage.getItem('userData') || '{}');
    
    const userData = authUser || storedUser;
    
    if (userData) {
        profile.value = {
            id: userData.id,
            first_name: userData.first_name || '',
            last_name: userData.last_name || '',
            email: userData.email || '',
            phone_number: userData.phone_number || '',
            profile_picture: userData.profile_picture || null
        };
        
        // Initialize edit data
        editData.first_name = profile.value.first_name;
        editData.last_name = profile.value.last_name;
        editData.phone_number = profile.value.phone_number;
    }
};

const cancelEdit = () => {
    editData.first_name = profile.value.first_name || '';
    editData.last_name = profile.value.last_name || '';
    editData.phone_number = profile.value.phone_number || '';
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

    saving.value = true;
    try {
        const updateData = {
            first_name: editData.first_name,
            last_name: editData.last_name,
            phone_number: editData.phone_number
        };

        await updateUser(profile.value.id, updateData);
        
        // Update local state
        profile.value.first_name = editData.first_name;
        profile.value.last_name = editData.last_name;
        profile.value.phone_number = editData.phone_number;
        
        // Update localStorage
        localStorage.setItem('userData', JSON.stringify(profile.value));

        showSnackbar('Profile updated successfully', 'success');
    } catch (err) {
        console.error('Error updating profile:', err);
        const errorMsg = err?.data?.message || err?.message || 'Failed to update profile';
        showSnackbar(errorMsg, 'error');
    } finally {
        saving.value = false;
    }
};

const changePassword = async () => {
    if (!passwordFormValid.value || !isPasswordValid.value) return;

    changingPassword.value = true;
    try {
        const response = await fetch('/api/auth/change-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            credentials: 'include',
            body: JSON.stringify({
                current_password: passwordData.current_password,
                new_password: passwordData.new_password,
                new_password_confirmation: passwordData.confirm_password
            })
        });

        const data = await response.json();

        if (response.ok) {
            showSnackbar('Password changed successfully', 'success');
            // Clear password fields
            passwordData.current_password = '';
            passwordData.new_password = '';
            passwordData.confirm_password = '';
            passwordFormRef.value?.resetValidation();
        } else {
            showSnackbar(data.message || 'Failed to change password', 'error');
        }
    } catch (err) {
        console.error('Error changing password:', err);
        showSnackbar('Failed to change password', 'error');
    } finally {
        changingPassword.value = false;
    }
};

const openPhotoDialog = () => {
    selectedFile.value = null;
    previewUrl.value = null;
    photoDialog.value = true;
};

const onFileSelected = (file) => {
    if (file) {
        previewUrl.value = URL.createObjectURL(file);
    } else {
        previewUrl.value = null;
    }
};

const uploadPhoto = async () => {
    if (!selectedFile.value) return;

    uploadingPhoto.value = true;
    try {
        const result = await uploadProfilePicture(profile.value.id, selectedFile.value);
        
        if (result.profile_picture) {
            profile.value.profile_picture = result.profile_picture;
            localStorage.setItem('userData', JSON.stringify(profile.value));
        }
        
        showSnackbar('Profile photo updated', 'success');
        photoDialog.value = false;
    } catch (err) {
        console.error('Error uploading photo:', err);
        showSnackbar('Failed to upload photo', 'error');
    } finally {
        uploadingPhoto.value = false;
    }
};

const removePhoto = async () => {
    removingPhoto.value = true;
    try {
        await deleteProfilePicture(profile.value.id);
        
        profile.value.profile_picture = null;
        localStorage.setItem('userData', JSON.stringify(profile.value));
        
        showSnackbar('Profile photo removed', 'success');
        photoDialog.value = false;
    } catch (err) {
        console.error('Error removing photo:', err);
        showSnackbar('Failed to remove photo', 'error');
    } finally {
        removingPhoto.value = false;
    }
};

const logout = async () => {
    localStorage.removeItem('userData');
    localStorage.removeItem('authToken');
    localStorage.removeItem('token');
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        await fetch('/logout', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            credentials: 'include'
        });
    } catch (e) { console.error('Logout error:', e); }
    
    window.location.href = '/login';
};

onMounted(() => {
    loadProfile();
    
    // Initialize dark mode from localStorage
    const savedDarkMode = localStorage.getItem('darkMode');
    if (savedDarkMode !== null) {
        isDark.value = savedDarkMode === 'true';
        if (isDark.value) {
            document.documentElement.classList.add('v-theme--dark');
            document.documentElement.classList.remove('v-theme--light');
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.classList.add('v-theme--light');
            document.documentElement.classList.remove('v-theme--dark');
            document.documentElement.setAttribute('data-theme', 'light');
        }
    }
});
</script>

<style scoped>
.page-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.page-header-content {
    flex: 1;
    min-width: 200px;
}

.profile-header-bg {
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 100%);
}

.avatar-ring {
    border: 3px solid white;
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 100%);
}

.edit-avatar-btn {
    bottom: 0;
    right: 0;
    transform: translate(25%, 25%);
}

.password-requirements {
    background: #f5f5f5;
    padding: 12px;
    border-radius: 8px;
}

.gap-2 {
    gap: 8px;
}

@media (max-width: 600px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .page-header-content {
        width: 100%;
    }
}
</style>
