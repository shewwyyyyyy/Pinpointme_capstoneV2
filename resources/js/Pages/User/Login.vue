<template>
    <v-app>
        <v-main class="bg-user-gradient min-h-screen">
            <v-container fluid class="fill-height pa-0">
                <v-row no-gutters class="fill-height">
                    <!-- Left Panel - Desktop Only -->
                    <v-col cols="12" md="6" class="d-none d-md-flex">
                        <div
                            class="w-100 h-100 d-flex flex-column justify-center align-center bg-primary position-relative"
                        >
                            <v-img
                                src="/images/logos/pinpointme.png"
                                max-height="300"
                                max-width="300"
                                contain
                            />
                            <h1 class="text-h4 text-white mt-4 font-weight-bold">
                                PinPointMe
                            </h1>
                            <p class="text-white text-center mt-2">
                                Emergency Rescue System
                            </p>
                        </div>
                    </v-col>

                    <!-- Right Panel - Login Form -->
                    <v-col cols="12" md="6" class="d-flex align-center justify-center">
                        <v-card
                            class="pa-8 mx-4"
                            max-width="450"
                            width="100%"
                            elevation="8"
                            rounded="lg"
                        >
                            <!-- Mobile Logo -->
                            <div class="d-md-none text-center mb-6">
                                <v-img
                                    src="/images/logos/pinpointme.png"
                                    max-height="120"
                                    max-width="120"
                                    contain
                                    class="mx-auto"
                                />
                            </div>

                            <v-card-title class="text-h5 font-weight-bold text-center mb-2">
                                Welcome Back
                            </v-card-title>
                            <v-card-subtitle class="text-center mb-6">
                                Sign in to continue
                            </v-card-subtitle>

                            <v-form @submit.prevent="handleLogin" ref="formRef">
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

                                <v-text-field
                                    v-model="form.email"
                                    label="Email"
                                    type="email"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-email"
                                    :rules="[rules.required, rules.email]"
                                    :disabled="isLoading"
                                    class="mb-4"
                                />

                                <v-text-field
                                    v-model="form.password"
                                    label="Password"
                                    :type="showPassword ? 'text' : 'password'"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-lock"
                                    :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                    @click:append-inner="showPassword = !showPassword"
                                    :rules="[rules.required]"
                                    :disabled="isLoading"
                                    class="mb-6"
                                />

                                <v-btn
                                    type="submit"
                                    color="primary"
                                    size="large"
                                    block
                                    :loading="isLoading"
                                    class="mb-4"
                                >
                                    Sign In
                                </v-btn>
                            </v-form>

                            <div class="text-center">
                                <v-btn variant="text" color="primary" size="small">
                                    Forgot Password?
                                </v-btn>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>

            <!-- Toast Notification -->
            <v-snackbar v-model="showToast" :color="toastColor" location="top">
                {{ toastMessage }}
                <template v-slot:actions>
                    <v-btn variant="text" @click="showToast = false">
                        Close
                    </v-btn>
                </template>
            </v-snackbar>
        </v-main>
    </v-app>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();

// Check if user is already authenticated from Inertia shared data
const authUser = computed(() => page.props?.auth?.user);

// Form state
const formRef = ref(null);
const form = ref({
    email: '',
    password: '',
});

// Inertia form for session-based login
const inertiaForm = useForm({
    email: '',
    password: '',
});

// UI state
const isLoading = ref(false);
const error = ref('');
const showPassword = ref(false);
const showToast = ref(false);
const toastMessage = ref('');
const toastColor = ref('success');

// Validation rules
const rules = {
    required: (v) => !!v || 'This field is required',
    email: (v) => /.+@.+\..+/.test(v) || 'Please enter a valid email',
};

// Check for existing session on mount
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const logout = urlParams.get('logout');

    if (logout === 'true') {
        handleLogout();
    } else {
        // First check Inertia auth (server-side session)
        if (authUser.value) {
            const user = authUser.value;
            // Store user data in localStorage for components that need it
            const userData = {
                id: user.id,
                email: user.email,
                firstName: user.first_name || '',
                lastName: user.last_name || '',
                role: user.role || 'student',
                isAdmin: user.isAdmin === true || user.isAdmin === 1 || user.role === 'admin',
                profile_picture: user.profile_picture || null,
                contact_number: user.contact_number || '',
            };
            localStorage.setItem('userData', JSON.stringify(userData));
            
            // Redirect based on user role
            if (user.isAdmin === true || user.isAdmin === 1 || user.role === 'admin') {
                window.location.href = '/admin/dashboard';
            } else if (user.role === 'rescuer') {
                window.location.href = '/rescuer/dashboard';
            } else {
                window.location.href = '/user/scanner';
            }
            return;
        }
        
        // Clear any stale localStorage data if not authenticated on server
        localStorage.removeItem('userData');
        localStorage.removeItem('authToken');
        localStorage.removeItem('token');
    }
});

const handleLogout = () => {
    localStorage.removeItem('userData');
    localStorage.removeItem('authToken');
    localStorage.removeItem('token');
    localStorage.removeItem('lastRescueCode');
    localStorage.removeItem('lastRescueRequestId');
    localStorage.removeItem('lastRescueRequestTime');
    localStorage.removeItem('conversationId');
    localStorage.removeItem('chatId');
    window.history.replaceState({}, document.title, window.location.pathname);
};

const handleLogin = async () => {
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    error.value = '';
    isLoading.value = true;

    // Update inertia form with current values
    inertiaForm.email = form.value.email.trim();
    inertiaForm.password = form.value.password;

    // Use Inertia form submission for session-based authentication
    inertiaForm.post('/login', {
        onSuccess: (page) => {
            // Get user data from the response
            const user = page.props?.auth?.user;
            
            if (user) {
                const userData = {
                    id: user.id,
                    email: user.email,
                    firstName: user.first_name || '',
                    lastName: user.last_name || '',
                    role: user.role || 'student',
                    isAdmin: user.isAdmin === true || user.isAdmin === 1 || user.role === 'admin',
                    profile_picture: user.profile_picture || null,
                    contact_number: user.contact_number || '',
                };

                localStorage.setItem('userData', JSON.stringify(userData));

                toastMessage.value = 'Login successful!';
                toastColor.value = 'success';
                showToast.value = true;
            }
            
            isLoading.value = false;
            // The redirect is handled by the backend AuthController
        },
        onError: (errors) => {
            isLoading.value = false;
            if (errors.email) {
                error.value = errors.email;
            } else if (errors.password) {
                error.value = errors.password;
            } else {
                error.value = 'Login failed. Please check your credentials.';
            }
        },
        onFinish: () => {
            isLoading.value = false;
        }
    });
};
</script>

<style scoped>
/* Component-specific styles only - background is now global */
</style>
