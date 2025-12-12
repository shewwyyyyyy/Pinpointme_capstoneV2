<template>
    <Head title="Verify Account — PinPointMe" />
    <v-app class="bg-grey-lighten-4">
        <v-main>
            <v-container class="fill-height d-flex align-center justify-center">
                <v-card max-width="450" class="pa-6" rounded="xl" elevation="8">
                    <!-- Header -->
                    <div class="text-center mb-6">
                        <v-avatar color="primary" size="80" class="mb-4">
                            <v-icon size="48" color="white">mdi-shield-check</v-icon>
                        </v-avatar>
                        <h1 class="text-h5 font-weight-bold">Verify Your Account</h1>
                        <p class="text-grey mt-2">
                            {{ step === 'otp' ? 'Enter the OTP sent to your email' : 'Create a new password' }}
                        </p>
                    </div>

                    <!-- OTP Step -->
                    <div v-if="step === 'otp'">
                        <v-alert v-if="email" type="info" variant="tonal" class="mb-4" density="compact">
                            <v-icon start size="small">mdi-email</v-icon>
                            Verification code sent to: <strong>{{ email }}</strong>
                        </v-alert>

                        <v-otp-input
                            v-model="otpCode"
                            length="6"
                            type="number"
                            class="mb-4"
                            :loading="loading"
                        ></v-otp-input>

                        <v-btn
                            color="primary"
                            block
                            size="large"
                            rounded="lg"
                            :loading="loading"
                            :disabled="otpCode.length !== 6"
                            @click="verifyOtp"
                        >
                            <v-icon start>mdi-check</v-icon>
                            Verify OTP
                        </v-btn>

                        <div class="text-center mt-4">
                            <p class="text-caption text-grey">
                                Didn't receive the code?
                                <v-btn 
                                    variant="text" 
                                    color="primary" 
                                    size="small" 
                                    :loading="resending"
                                    :disabled="resendCountdown > 0"
                                    @click="resendOtp"
                                >
                                    {{ resendCountdown > 0 ? `Resend in ${resendCountdown}s` : 'Resend OTP' }}
                                </v-btn>
                            </p>
                        </div>
                    </div>

                    <!-- Password Step -->
                    <div v-else-if="step === 'password'">
                        <v-form ref="passwordForm" @submit.prevent="changePassword">
                            <v-text-field
                                v-model="newPassword"
                                label="New Password"
                                :type="showPassword ? 'text' : 'password'"
                                variant="outlined"
                                :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                @click:append-inner="showPassword = !showPassword"
                                :rules="passwordRules"
                                class="mb-3"
                            />
                            <v-text-field
                                v-model="confirmPassword"
                                label="Confirm Password"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                variant="outlined"
                                :append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                @click:append-inner="showConfirmPassword = !showConfirmPassword"
                                :rules="confirmPasswordRules"
                                class="mb-4"
                            />

                            <v-alert type="info" variant="tonal" class="mb-4" density="compact">
                                <v-icon start size="small">mdi-information</v-icon>
                                Password must be at least 8 characters with uppercase, lowercase, and number.
                            </v-alert>

                            <v-btn
                                color="primary"
                                block
                                size="large"
                                rounded="lg"
                                type="submit"
                                :loading="loading"
                            >
                                <v-icon start>mdi-lock-reset</v-icon>
                                Set Password & Activate Account
                            </v-btn>
                        </v-form>
                    </div>

                    <!-- Success Step -->
                    <div v-else-if="step === 'success'" class="text-center">
                        <v-icon size="80" color="success" class="mb-4">mdi-check-circle</v-icon>
                        <h2 class="text-h5 font-weight-bold mb-2">Account Activated!</h2>
                        <p class="text-grey mb-4">Your account is now active. You can now log in.</p>
                        <v-btn
                            color="primary"
                            block
                            size="large"
                            rounded="lg"
                            href="/login"
                        >
                            <v-icon start>mdi-login</v-icon>
                            Go to Login
                        </v-btn>
                    </div>
                </v-card>
            </v-container>
        </v-main>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000">
            {{ snackbarText }}
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

const props = defineProps({
    email: { type: String, default: '' },
    token: { type: String, default: '' }
});

const page = usePage();

const step = ref('otp');
const otpCode = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const showPassword = ref(false);
const showConfirmPassword = ref(false);
const loading = ref(false);
const resending = ref(false);
const resendCountdown = ref(0);
const snackbar = ref(false);
const snackbarText = ref('');
const snackbarColor = ref('success');

const email = ref(props.email || page.props.email || '');
const verificationToken = ref(props.token || '');

let countdownInterval = null;

const passwordRules = [
    v => !!v || 'Password is required',
    v => v.length >= 8 || 'Password must be at least 8 characters',
    v => /[A-Z]/.test(v) || 'Password must contain an uppercase letter',
    v => /[a-z]/.test(v) || 'Password must contain a lowercase letter',
    v => /[0-9]/.test(v) || 'Password must contain a number'
];

const confirmPasswordRules = [
    v => !!v || 'Please confirm your password',
    v => v === newPassword.value || 'Passwords do not match'
];

const showSnackbar = (text, color = 'success') => {
    snackbarText.value = text;
    snackbarColor.value = color;
    snackbar.value = true;
};

const startResendCountdown = () => {
    resendCountdown.value = 60;
    countdownInterval = setInterval(() => {
        if (resendCountdown.value > 0) {
            resendCountdown.value--;
        } else {
            clearInterval(countdownInterval);
        }
    }, 1000);
};

const verifyOtp = async () => {
    if (otpCode.value.length !== 6) return;

    loading.value = true;
    try {
        const response = await fetch('/api/verify-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                email: email.value,
                otp: otpCode.value
            })
        });

        const data = await response.json();
        
        if (data.success) {
            verificationToken.value = data.token;
            step.value = 'password';
            showSnackbar('OTP verified! Please set your new password.', 'success');
        } else {
            showSnackbar(data.message || 'Invalid or expired OTP', 'error');
        }
    } catch (error) {
        console.error('Error verifying OTP:', error);
        showSnackbar('Error verifying OTP. Please try again.', 'error');
    } finally {
        loading.value = false;
    }
};

const resendOtp = async () => {
    if (resendCountdown.value > 0) return;

    resending.value = true;
    try {
        const response = await fetch('/api/resend-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ email: email.value })
        });

        const data = await response.json();
        
        if (data.success) {
            showSnackbar('New OTP sent to your email', 'success');
            startResendCountdown();
        } else {
            showSnackbar(data.message || 'Failed to resend OTP', 'error');
        }
    } catch (error) {
        console.error('Error resending OTP:', error);
        showSnackbar('Error resending OTP', 'error');
    } finally {
        resending.value = false;
    }
};

const changePassword = async () => {
    if (newPassword.value !== confirmPassword.value) {
        showSnackbar('Passwords do not match', 'error');
        return;
    }

    loading.value = true;
    try {
        const response = await fetch('/api/activate-account', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                email: email.value,
                token: verificationToken.value,
                password: newPassword.value,
                password_confirmation: confirmPassword.value
            })
        });

        const data = await response.json();
        
        if (data.success) {
            step.value = 'success';
        } else {
            showSnackbar(data.message || 'Failed to activate account', 'error');
        }
    } catch (error) {
        console.error('Error activating account:', error);
        showSnackbar('Error activating account. Please try again.', 'error');
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    // Get email from URL params if not provided
    const urlParams = new URLSearchParams(window.location.search);
    if (!email.value && urlParams.get('email')) {
        email.value = urlParams.get('email');
    }
    startResendCountdown();
});

onUnmounted(() => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
});
</script>

<style scoped>
.v-otp-input {
    justify-content: center;
}
</style>
