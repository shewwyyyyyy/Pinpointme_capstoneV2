<template>
    <v-app class="bg-user-gradient-light">
        <v-main class="d-flex align-center justify-center" style="min-height: 100vh;">>
            <v-container>
                <v-row justify="center">
                    <v-col cols="12" sm="10" md="8" lg="6">
                        <v-card class="pa-6" rounded="lg" elevation="12">
                            <!-- Header with Google Profile -->
                            <div class="text-center mb-6">
                                <v-avatar size="80" class="mb-4 elevation-4">
                                    <v-img 
                                        v-if="googleUser.profile_picture" 
                                        :src="googleUser.profile_picture"
                                        alt="Profile"
                                    />
                                    <v-icon v-else size="48" color="primary">mdi-account</v-icon>
                                </v-avatar>
                                <h2 class="text-h5 font-weight-bold mb-1">Complete Your Registration</h2>
                                <p class="text-body-2 text-grey">
                                    Welcome, {{ googleUser.first_name }}! Please provide additional information to complete your account.
                                </p>
                                <v-chip color="primary" variant="tonal" class="mt-2">
                                    <v-icon start size="16">mdi-google</v-icon>
                                    {{ googleUser.email }}
                                </v-chip>
                            </div>

                            <!-- Error Alert -->
                            <v-alert
                                v-if="error"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                                closable
                                @click:close="error = ''"
                            >
                                {{ error }}
                            </v-alert>

                            <!-- Registration Form -->
                            <v-form @submit.prevent="handleSubmit" ref="formRef">
                                <!-- ID Number Section -->
                                <div class="mb-4">
                                    <p class="text-subtitle-2 font-weight-medium mb-2">
                                        <v-icon size="18" class="mr-1">mdi-card-account-details</v-icon>
                                        ID Number
                                    </p>
                                    <v-text-field
                                        v-model="form.id_number"
                                        label="ID Number (9 digits)"
                                        variant="outlined"
                                        density="comfortable"
                                        placeholder="e.g., 201234567"
                                        :rules="[rules.required, rules.idNumber]"
                                        :disabled="isLoading"
                                        maxlength="9"
                                        @input="formatIdNumber"
                                        hint="If starts with 2 = Student, other digits = Faculty"
                                        persistent-hint
                                    />
                                    <v-chip 
                                        v-if="userRole" 
                                        :color="userRole === 'student' ? 'blue' : 'green'" 
                                        size="small" 
                                        class="mt-2"
                                    >
                                        {{ userRole === 'student' ? '🎓 Student' : '👨‍🏫 Faculty' }}
                                    </v-chip>
                                </div>

                                <!-- Phone Number Section -->
                                <div class="mb-4">
                                    <p class="text-subtitle-2 font-weight-medium mb-2">
                                        <v-icon size="18" class="mr-1">mdi-phone</v-icon>
                                        Phone Number
                                    </p>
                                    <v-text-field
                                        v-model="form.phone_number"
                                        label="Phone Number"
                                        variant="outlined"
                                        density="comfortable"
                                        placeholder="e.g., 09171234567"
                                        :rules="[rules.required, rules.phoneNumber]"
                                        :disabled="isLoading"
                                        maxlength="13"
                                        @input="formatPhoneNumber"
                                        hint="Mobile number format"
                                        persistent-hint
                                    />
                                </div>

                                <!-- Emergency Contact Section (Optional) -->
                                <!-- <v-expansion-panels class="mb-4" variant="accordion">
                                    <v-expansion-panel>
                                        <v-expansion-panel-title>
                                            <v-icon class="mr-2" size="20">mdi-account-alert</v-icon>
                                            Emergency Contact (Optional)
                                        </v-expansion-panel-title>
                                        <v-expansion-panel-text>
                                            <v-text-field
                                                v-model="form.emergency_contact_name"
                                                label="Emergency Contact Name"
                                                variant="outlined"
                                                density="comfortable"
                                                :disabled="isLoading"
                                                class="mb-3"
                                            />
                                            <v-text-field
                                                v-model="form.emergency_contact_phone"
                                                label="Emergency Contact Phone"
                                                variant="outlined"
                                                density="comfortable"
                                                placeholder="e.g., 09171234567"
                                                :rules="form.emergency_contact_phone ? [rules.phoneNumber] : []"
                                                :disabled="isLoading"
                                                maxlength="13"
                                                @input="formatEmergencyPhone"
                                                class="mb-3"
                                            />
                                            <v-text-field
                                                v-model="form.emergency_contact_relationship"
                                                label="Relationship"
                                                variant="outlined"
                                                density="comfortable"
                                                placeholder="e.g., Parent, Sibling, Guardian"
                                                :disabled="isLoading"
                                            />
                                        </v-expansion-panel-text>
                                    </v-expansion-panel>
                                </v-expansion-panels> -->

                                <!-- Submit Button -->
                                <v-btn
                                    type="submit"
                                    color="primary"
                                    size="large"
                                    block
                                    :loading="isLoading"
                                    class="mb-4"
                                >
                                    <v-icon start>mdi-check</v-icon>
                                    Complete Registration
                                </v-btn>

                                <!-- Cancel Link -->
                                <div class="text-center">
                                    <v-btn variant="text" color="grey" @click="handleCancel" :disabled="isLoading">
                                        Cancel and go back to login
                                    </v-btn>
                                </div>
                            </v-form>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>

            <!-- Success Snackbar -->
            <v-snackbar v-model="showSuccess" color="success" location="top">
                {{ successMessage }}
            </v-snackbar>
        </v-main>
    </v-app>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const googleUser = computed(() => page.props.googleUser || {});

// Form state
const formRef = ref(null);
const form = ref({
    id_number: '',
    phone_number: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    emergency_contact_relationship: '',
});

// UI state
const isLoading = ref(false);
const error = ref('');
const showSuccess = ref(false);
const successMessage = ref('');

// Computed role based on ID number first digit
const userRole = computed(() => {
    if (!form.value.id_number || form.value.id_number.length === 0) return null;
    const firstDigit = form.value.id_number.charAt(0);
    return firstDigit === '2' ? 'student' : 'faculty';
});

// Validation rules
const rules = {
    required: (v) => !!v || 'This field is required',
    idNumber: (v) => {
        if (!v) return 'ID number is required';
        if (!/^[0-9]+$/.test(v)) return 'ID number must contain only numbers';
        if (v.length !== 9) return 'ID number must be exactly 9 digits';
        return true;
    },
    phoneNumber: (v) => {
        if (!v) return 'Phone number is required';
        
        // Normalize the phone number
        let normalized = v.replace(/[^0-9+]/g, '');
        if (normalized.startsWith('+63')) {
            normalized = '0' + normalized.substring(3);
        } else if (normalized.startsWith('63')) {
            normalized = '0' + normalized.substring(2);
        } else if (normalized.startsWith('9') && normalized.length === 10) {
            normalized = '0' + normalized;
        }
        
        // Must start with 09 and have exactly 11 digits
        if (!/^09[0-9]{9}$/.test(normalized)) {
            return 'Please enter a valid number';
        }
        
        return true;
    },
};

// Format ID number (numbers only)
const formatIdNumber = () => {
    form.value.id_number = form.value.id_number.replace(/[^0-9]/g, '').substring(0, 9);
};

// Format phone number
const formatPhoneNumber = () => {
    let value = form.value.phone_number.replace(/[^0-9+]/g, '');
    
    // Handle various formats
    if (value.startsWith('+63')) {
        value = '0' + value.substring(3);
    } else if (value.startsWith('63') && !value.startsWith('639')) {
        value = '0' + value.substring(2);
    } else if (value.startsWith('9') && value.length <= 10) {
        value = '0' + value;
    }
    
    form.value.phone_number = value.substring(0, 11);
};

// Format emergency phone number
const formatEmergencyPhone = () => {
    let value = form.value.emergency_contact_phone.replace(/[^0-9+]/g, '');
    
    if (value.startsWith('+63')) {
        value = '0' + value.substring(3);
    } else if (value.startsWith('63') && !value.startsWith('639')) {
        value = '0' + value.substring(2);
    } else if (value.startsWith('9') && value.length <= 10) {
        value = '0' + value;
    }
    
    form.value.emergency_contact_phone = value.substring(0, 11);
};

// Handle form submission
const handleSubmit = async () => {
    const { valid } = await formRef.value.validate();
    if (!valid) return;
    
    isLoading.value = true;
    error.value = '';
    
    try {
        const response = await fetch('/auth/google/complete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                id_number: form.value.id_number,
                phone_number: form.value.phone_number,
                emergency_contact_name: form.value.emergency_contact_name || null,
                emergency_contact_phone: form.value.emergency_contact_phone || null,
                emergency_contact_relationship: form.value.emergency_contact_relationship || null,
            }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            successMessage.value = data.message || 'Registration completed successfully!';
            showSuccess.value = true;
            
            // Redirect to the appropriate page
            setTimeout(() => {
                window.location.href = data.redirect || '/user/scanner';
            }, 1500);
        } else {
            if (data.errors) {
                // Handle validation errors
                const errorMessages = Object.values(data.errors).flat();
                error.value = errorMessages.join(' ');
            } else {
                error.value = data.message || 'Failed to complete registration.';
            }
        }
    } catch (err) {
        console.error('Registration error:', err);
        error.value = 'An unexpected error occurred. Please try again.';
    } finally {
        isLoading.value = false;
    }
};

// Handle cancel
const handleCancel = () => {
    window.location.href = '/login';
};
</script>

<style scoped>
.v-expansion-panel-title {
    font-size: 0.95rem;
}

.v-expansion-panel-text {
    padding-top: 16px;
}
</style>
