<template>
    <Head title="Verify Account — PinPointMe" />
    <v-app>
        <v-main class="verify-account-bg">
            <v-container fluid class="fill-height pa-4">
                <v-row align="center" justify="center" class="fill-height ma-0">
                    <v-col cols="12" sm="10" md="8" lg="6" xl="5">
                        <!-- Main Card Container -->
                        <v-card 
                            class="verification-card mx-auto" 
                            :class="{ 'success-card': step === 'success' }"
                            rounded="xl" 
                            elevation="8"
                            max-width="500"
                        >
                            <!-- OTP Step -->
                            <template v-if="step === 'otp'">
                                <v-card-text class="pa-6 pa-sm-8 text-center">
                                    <!-- Logo -->
                                    <div class="mb-4">
                                        <img 
                                            src="/images/pinpointme-logo.png" 
                                            alt="PinPointMe" 
                                            height="48" 
                                            class="logo-img"
                                            onerror="this.style.display='none'"
                                        />
                                    </div>
                                    
                                    <!-- Green Checkmark Badge -->
                                    <div class="badge-icon mb-5">
                                        <div class="badge-circle">
                                            <v-icon size="48" color="white">mdi-email-check</v-icon>
                                        </div>
                                    </div>

                                    <!-- Title -->
                                    <h1 class="text-h5 text-sm-h4 font-weight-bold mb-2 title-text">Verify Your Email</h1>
                                    <p class="text-body-2 text-sm-body-1 subtitle-text mb-2">
                                        We've sent a 6-digit verification code to
                                    </p>
                                    <p class="text-body-2 email-text mb-6">{{ email }}</p>

                                    <!-- OTP Input Boxes -->
                                    <div class="otp-container mb-4">
                                        <v-otp-input
                                            v-model="otpCode"
                                            :length="6"
                                            variant="outlined"
                                            class="otp-input-custom"
                                            @finish="verifyOtp"
                                        />
                                    </div>

                                    <!-- OTP Validation Message -->
                                    <v-alert 
                                        v-if="otpError" 
                                        type="error" 
                                        variant="tonal" 
                                        density="compact"
                                        class="mb-4"
                                        closable
                                        @click:close="otpError = ''"
                                    >
                                        {{ otpError }}
                                    </v-alert>

                                    <!-- Resend Code Link -->
                                    <div class="d-flex justify-space-between align-center mb-5">
                                        <span class="text-body-2 text-grey">Didn't receive the code?</span>
                                        <a 
                                            href="#" 
                                            class="resend-link"
                                            :class="{ 'disabled-link': resendCountdown > 0 || resending }"
                                            @click.prevent="resendCountdown === 0 && !resending && resendOtp()"
                                        >
                                            <v-progress-circular 
                                                v-if="resending" 
                                                indeterminate 
                                                size="14" 
                                                width="2"
                                                class="mr-1"
                                            />
                                            <span v-if="resendCountdown > 0">Resend in {{ resendCountdown }}s</span>
                                            <span v-else-if="resending">Sending...</span>
                                            <span v-else>Resend code</span>
                                        </a>
                                    </div>

                                    <!-- Verify Button -->
                                    <v-btn
                                        block
                                        size="x-large"
                                        color="#1976D2"
                                        :loading="loading"
                                        :disabled="getOtpLength() !== 6 || loading"
                                        @click="verifyOtp"
                                        class="verify-btn rounded-pill text-white"
                                        elevation="2"
                                    >
                                        <v-icon start>mdi-shield-check</v-icon>
                                        Verify Code
                                    </v-btn>
                                </v-card-text>
                            </template>

                            <!-- Password Step -->
                            <template v-else-if="step === 'password'">
                                <v-card-text class="pa-6 pa-sm-8 text-center">
                                    <!-- Back Button -->
                                    <div class="text-left mb-2">
                                        <v-btn icon variant="text" @click="step = 'otp'" class="back-btn" size="small">
                                            <v-icon>mdi-arrow-left</v-icon>
                                        </v-btn>
                                    </div>

                                    <!-- Lock Icon -->
                                    <div class="lock-icon mb-5">
                                        <div class="lock-circle">
                                            <v-icon size="48" color="#1976D2">mdi-lock</v-icon>
                                        </div>
                                    </div>

                                    <!-- Title -->
                                    <h1 class="text-h5 text-sm-h4 font-weight-bold mb-2 title-text">Create Your Password</h1>
                                    <p class="text-body-2 subtitle-text mb-6">
                                        Set a secure password for your account
                                    </p>

                                    <!-- Password Form -->
                                    <v-form ref="passwordForm" @submit.prevent="changePassword">
                                        <v-text-field
                                            v-model="newPassword"
                                            :type="showPassword ? 'text' : 'password'"
                                            placeholder="Enter new password"
                                            variant="outlined"
                                            :rules="passwordRules"
                                            :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                                            @click:append-inner="showPassword = !showPassword"
                                            class="mb-3 password-field"
                                            hide-details="auto"
                                            bg-color="white"
                                            rounded="lg"
                                        />

                                        <v-text-field
                                            v-model="confirmPassword"
                                            :type="showConfirmPassword ? 'text' : 'password'"
                                            placeholder="Confirm new password"
                                            variant="outlined"
                                            :rules="confirmPasswordRules"
                                            :append-inner-icon="showConfirmPassword ? 'mdi-eye' : 'mdi-eye-off'"
                                            @click:append-inner="showConfirmPassword = !showConfirmPassword"
                                            class="mb-4 password-field"
                                            hide-details="auto"
                                            bg-color="white"
                                            rounded="lg"
                                        />

                                        <!-- Password Strength Indicator -->
                                        <div class="password-requirements mb-5">
                                            <p class="text-body-2 text-grey-darken-1 mb-2 text-left">Password Requirements:</p>
                                            <div class="d-flex flex-wrap gap-2">
                                                <v-chip 
                                                    :color="hasMinLength ? 'success' : 'grey'" 
                                                    :variant="hasMinLength ? 'flat' : 'outlined'"
                                                    size="small"
                                                >
                                                    <v-icon start size="14">{{ hasMinLength ? 'mdi-check' : 'mdi-circle-outline' }}</v-icon>
                                                    8+ characters
                                                </v-chip>
                                                <v-chip 
                                                    :color="hasUppercase ? 'success' : 'grey'" 
                                                    :variant="hasUppercase ? 'flat' : 'outlined'"
                                                    size="small"
                                                >
                                                    <v-icon start size="14">{{ hasUppercase ? 'mdi-check' : 'mdi-circle-outline' }}</v-icon>
                                                    Uppercase
                                                </v-chip>
                                                <v-chip 
                                                    :color="hasLowercase ? 'success' : 'grey'" 
                                                    :variant="hasLowercase ? 'flat' : 'outlined'"
                                                    size="small"
                                                >
                                                    <v-icon start size="14">{{ hasLowercase ? 'mdi-check' : 'mdi-circle-outline' }}</v-icon>
                                                    Lowercase
                                                </v-chip>
                                                <v-chip 
                                                    :color="hasNumber ? 'success' : 'grey'" 
                                                    :variant="hasNumber ? 'flat' : 'outlined'"
                                                    size="small"
                                                >
                                                    <v-icon start size="14">{{ hasNumber ? 'mdi-check' : 'mdi-circle-outline' }}</v-icon>
                                                    Number
                                                </v-chip>
                                                <v-chip 
                                                    :color="passwordsMatch ? 'success' : 'grey'" 
                                                    :variant="passwordsMatch ? 'flat' : 'outlined'"
                                                    size="small"
                                                >
                                                    <v-icon start size="14">{{ passwordsMatch ? 'mdi-check' : 'mdi-circle-outline' }}</v-icon>
                                                    Passwords match
                                                </v-chip>
                                            </div>
                                        </div>

                                        <!-- Password Strength Bar -->
                                        <div class="mb-5">
                                            <div class="d-flex justify-space-between mb-1">
                                                <span class="text-caption text-grey">Password Strength</span>
                                                <span class="text-caption" :class="`text-${passwordStrengthColor}`">{{ passwordStrengthText }}</span>
                                            </div>
                                            <v-progress-linear
                                                :model-value="passwordStrength"
                                                :color="passwordStrengthColor"
                                                height="6"
                                                rounded
                                            />
                                        </div>

                                        <!-- Activate Account Button -->
                                        <v-btn
                                            block
                                            type="submit"
                                            size="x-large"
                                            color="#1976D2"
                                            :loading="loading"
                                            :disabled="!isPasswordValid || loading"
                                            class="activate-btn rounded-pill text-white"
                                            elevation="2"
                                        >
                                            <v-icon start>mdi-account-check</v-icon>
                                            Activate Account
                                        </v-btn>
                                    </v-form>
                                </v-card-text>
                            </template>

                            <!-- Success Step (Card-based) -->
                            <template v-else-if="step === 'success'">
                                <v-card-text class="pa-6 pa-sm-8 text-center success-content">
                                    <!-- Confetti Animation -->
                                    <div class="confetti-wrapper">
                                        <div v-for="n in 12" :key="n" :class="`confetti confetti-${n}`"></div>
                                    </div>
                                    
                                    <!-- Success Icon -->
                                    <div class="success-icon mb-5">
                                        <div class="success-circle">
                                            <v-icon size="64" color="white">mdi-check</v-icon>
                                        </div>
                                    </div>

                                    <!-- Celebration Illustration -->
                                    <div class="celebration-icon mb-4">
                                        <svg width="150" height="150" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <!-- YAY! text -->
                                            <text x="100" y="25" text-anchor="middle" fill="#1976D2" font-size="18" font-weight="bold" font-family="Arial">YAY!</text>
                                            <!-- Stars -->
                                            <polygon points="30,40 33,50 43,50 35,57 38,67 30,61 22,67 25,57 17,50 27,50" fill="#FFD700"/>
                                            <polygon points="170,40 173,50 183,50 175,57 178,67 170,61 162,67 165,57 157,50 167,50" fill="#FFD700"/>
                                            <polygon points="100,170 103,180 113,180 105,187 108,197 100,191 92,197 95,187 87,180 97,180" fill="#FFD700"/>
                                            <!-- Person body -->
                                            <ellipse cx="100" cy="95" rx="25" ry="12" fill="#1976D2"/>
                                            <!-- Head -->
                                            <circle cx="100" cy="70" r="18" fill="#FFE4C4"/>
                                            <!-- Hair -->
                                            <path d="M82 65 Q85 50 100 52 Q115 50 118 65 Q120 58 115 55 Q100 45 85 55 Q80 58 82 65" fill="#333"/>
                                            <!-- Face -->
                                            <circle cx="94" cy="68" r="2" fill="#333"/>
                                            <circle cx="106" cy="68" r="2" fill="#333"/>
                                            <path d="M96 76 Q100 80 104 76" stroke="#333" stroke-width="1.5" fill="none"/>
                                            <!-- Arms raised -->
                                            <path d="M75 95 Q60 80 55 60" stroke="#FFE4C4" stroke-width="8" stroke-linecap="round"/>
                                            <path d="M125 95 Q140 80 145 60" stroke="#FFE4C4" stroke-width="8" stroke-linecap="round"/>
                                            <!-- Hands -->
                                            <circle cx="55" cy="58" r="6" fill="#FFE4C4"/>
                                            <circle cx="145" cy="58" r="6" fill="#FFE4C4"/>
                                            <!-- Shirt -->
                                            <path d="M75 100 L80 140 L120 140 L125 100 Q100 105 75 100" fill="#1976D2"/>
                                            <!-- Legs -->
                                            <path d="M90 140 L85 165" stroke="#333" stroke-width="10" stroke-linecap="round"/>
                                            <path d="M110 140 L115 165" stroke="#333" stroke-width="10" stroke-linecap="round"/>
                                        </svg>
                                    </div>

                                    <!-- Success Message -->
                                    <h1 class="text-h5 text-sm-h4 font-weight-bold mb-2 success-title">Account Activated!</h1>
                                    <p class="text-body-2 text-sm-body-1 success-subtitle mb-2">
                                        Congratulations! Your account has been successfully activated.
                                    </p>
                                    <p class="text-body-2 text-grey mb-6">
                                        A confirmation email has been sent to <strong>{{ email }}</strong>
                                    </p>

                                    <!-- Features List -->
                                    <div class="features-list mb-6">
                                        <div class="d-flex align-center justify-center mb-2">
                                            <v-icon color="success" size="20" class="mr-2">mdi-check-circle</v-icon>
                                            <span class="text-body-2">Email verified</span>
                                        </div>
                                        <div class="d-flex align-center justify-center mb-2">
                                            <v-icon color="success" size="20" class="mr-2">mdi-check-circle</v-icon>
                                            <span class="text-body-2">Password set successfully</span>
                                        </div>
                                        <div class="d-flex align-center justify-center">
                                            <v-icon color="success" size="20" class="mr-2">mdi-check-circle</v-icon>
                                            <span class="text-body-2">Ready to use PinPointMe</span>
                                        </div>
                                    </div>

                                    <!-- Continue Button -->
                                    <v-btn
                                        block
                                        size="x-large"
                                        color="#1976D2"
                                        href="/login"
                                        class="continue-btn rounded-pill text-white"
                                        elevation="2"
                                    >
                                        <v-icon start>mdi-login</v-icon>
                                        Continue to Login
                                    </v-btn>
                                </v-card-text>
                            </template>
                        </v-card>

                        <!-- Help Text -->
                        <div class="text-center mt-4" v-if="step !== 'success'">
                            <p class="text-body-2 text-grey">
                                Having trouble? 
                                <a href="mailto:support@sdca.edu.ph" class="help-link">Contact Support</a>
                            </p>
                        </div>
                    </v-col>
                </v-row>
            </v-container>
        </v-main>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="4000" location="top" rounded="pill">
            <div class="d-flex align-center">
                <v-icon class="mr-2">{{ snackbarColor === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
                {{ snackbarText }}
            </div>
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

const props = defineProps({
    email: { type: String, default: '' },
    token: { type: String, default: '' }
});

const page = usePage();

const step = ref('otp');
const otpCode = ref('');
const otpError = ref('');
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

// Password validation
const hasMinLength = computed(() => newPassword.value.length >= 8);
const hasUppercase = computed(() => /[A-Z]/.test(newPassword.value));
const hasLowercase = computed(() => /[a-z]/.test(newPassword.value));
const hasNumber = computed(() => /[0-9]/.test(newPassword.value));
const passwordsMatch = computed(() => newPassword.value && newPassword.value === confirmPassword.value);

const isPasswordValid = computed(() => 
    hasMinLength.value && 
    hasUppercase.value && 
    hasLowercase.value && 
    hasNumber.value && 
    passwordsMatch.value
);

const passwordStrength = computed(() => {
    let strength = 0;
    if (hasMinLength.value) strength += 25;
    if (hasUppercase.value) strength += 25;
    if (hasLowercase.value) strength += 25;
    if (hasNumber.value) strength += 25;
    return strength;
});

const passwordStrengthColor = computed(() => {
    if (passwordStrength.value <= 25) return 'error';
    if (passwordStrength.value <= 50) return 'warning';
    if (passwordStrength.value <= 75) return 'info';
    return 'success';
});

const passwordStrengthText = computed(() => {
    if (passwordStrength.value <= 25) return 'Weak';
    if (passwordStrength.value <= 50) return 'Fair';
    if (passwordStrength.value <= 75) return 'Good';
    return 'Strong';
});

const passwordRules = [
    v => !!v || 'Password is required',
    v => v.length >= 8 || 'Password must be at least 8 characters',
    v => /[A-Z]/.test(v) || 'Password must contain an uppercase letter',
    v => /[a-z]/.test(v) || 'Password must contain a lowercase letter',
    v => /[0-9]/.test(v) || 'Password must contain a number',
];

const confirmPasswordRules = [
    v => !!v || 'Please confirm your password',
    v => v === newPassword.value || 'Passwords do not match'
];

// Helper to get OTP length regardless of format
function getOtpLength() {
    if (Array.isArray(otpCode.value)) {
        return otpCode.value.join('').length;
    }
    return String(otpCode.value || '').length;
}

const showSnackbar = (text, color = 'success') => {
    snackbarText.value = text;
    snackbarColor.value = color;
    snackbar.value = true;
};

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

const verifyOtp = async () => {
    // Handle both string and array formats from v-otp-input
    const otpValue = Array.isArray(otpCode.value) 
        ? otpCode.value.join('') 
        : String(otpCode.value).trim();
    
    // Validate OTP format
    if (otpValue.length !== 6) {
        otpError.value = 'Please enter a complete 6-digit code';
        return;
    }

    if (!/^\d{6}$/.test(otpValue)) {
        otpError.value = 'OTP must contain only numbers';
        return;
    }

    otpError.value = '';
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
                otp: otpValue
            })
        });

        const data = await response.json();
        
        if (data.success) {
            verificationToken.value = data.token;
            step.value = 'password';
            showSnackbar('Email verified! Please set your password.', 'success');
        } else {
            otpError.value = data.message || 'Invalid or expired OTP';
            showSnackbar(data.message || 'Invalid or expired OTP', 'error');
        }
    } catch (error) {
        console.error('Error verifying OTP:', error);
        otpError.value = 'Error verifying OTP. Please try again.';
        showSnackbar('Error verifying OTP. Please try again.', 'error');
    } finally {
        loading.value = false;
    }
};

const resendOtp = async () => {
    if (resendCountdown.value > 0 || resending.value) return;

    resending.value = true;
    otpError.value = '';
    
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
            showSnackbar('New verification code sent to your email', 'success');
            startResendCountdown();
            otpCode.value = '';
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
    if (!isPasswordValid.value) {
        showSnackbar('Please ensure all password requirements are met', 'error');
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
            showSnackbar('Account activated successfully!', 'success');
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
.verify-account-bg {
    background: linear-gradient(135deg, 
        #1976D2 0%, 
        #1565C0 25%,
        #0D47A1 50%,
        #1565C0 75%,
        #1976D2 100%);
    min-height: 100vh;
    position: relative;
    overflow: hidden;
}

.verify-account-bg::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}

/* Card Styles */
.verification-card {
    background: rgba(255, 255, 255, 0.98) !important;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.success-card {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(232, 245, 233, 0.98) 100%) !important;
}

.logo-img {
    filter: none;
}

/* Badge Icon for Verify Screen */
.badge-icon {
    display: flex;
    justify-content: center;
}

.badge-circle {
    width: 90px;
    height: 90px;
    background: linear-gradient(135deg, #1976D2 0%, #0D47A1 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    box-shadow: 0 8px 32px rgba(25, 118, 210, 0.4);
    animation: pulse-badge 2s infinite;
}

@keyframes pulse-badge {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.badge-circle::before {
    content: '';
    position: absolute;
    width: 110px;
    height: 110px;
    background: linear-gradient(135deg, #1976D2 0%, #0D47A1 100%);
    border-radius: 50%;
    z-index: -1;
    opacity: 0.3;
}

/* Lock Icon for Password Screen */
.lock-icon {
    display: flex;
    justify-content: center;
}

.lock-circle {
    width: 90px;
    height: 90px;
    background: rgba(25, 118, 210, 0.1);
    border: 3px solid #1976D2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Success Icon */
.success-icon {
    display: flex;
    justify-content: center;
}

.success-circle {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #43A047 0%, #2E7D32 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 32px rgba(67, 160, 71, 0.4);
    animation: success-pop 0.5s ease-out;
}

@keyframes success-pop {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* Typography */
.title-text {
    color: #1a237e;
}

.subtitle-text {
    color: #546e7a;
}

.email-text {
    color: #1976D2;
    font-weight: 500;
    background: rgba(25, 118, 210, 0.1);
    padding: 8px 16px;
    border-radius: 20px;
    display: inline-block;
}

/* Back Button */
.back-btn {
    color: #546e7a !important;
}

.back-btn:hover {
    background: rgba(0, 0, 0, 0.04);
}

/* OTP Input Styling */
.otp-container {
    max-width: 350px;
    margin: 0 auto;
}

.otp-input-custom :deep(.v-otp-input__content) {
    justify-content: center;
    gap: 8px;
}

.otp-input-custom :deep(.v-field) {
    border-radius: 12px;
    background: white;
    border: 2px solid #e0e0e0;
}

.otp-input-custom :deep(.v-field:focus-within) {
    border-color: #1976D2;
}

.otp-input-custom :deep(.v-field__outline__start),
.otp-input-custom :deep(.v-field__outline__end) {
    border-color: transparent;
}

.otp-input-custom :deep(input) {
    font-size: 24px;
    font-weight: 700;
    text-align: center;
    color: #1a237e;
}

/* Resend Link */
.resend-link {
    color: #1976D2;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.resend-link:hover {
    text-decoration: underline;
}

.disabled-link {
    color: #9e9e9e !important;
    pointer-events: none;
}

/* Buttons */
.verify-btn,
.activate-btn,
.continue-btn {
    font-weight: 600;
    font-size: 16px;
    letter-spacing: 0.5px;
    text-transform: none;
    height: 52px !important;
}

/* Password Fields */
.password-field :deep(.v-field) {
    border-radius: 12px;
    background: white;
}

.password-field :deep(.v-field__outline__start),
.password-field :deep(.v-field__outline__end) {
    border-color: #e0e0e0;
}

.password-field :deep(input) {
    color: #1a237e;
}

.password-field :deep(input::placeholder) {
    color: #9e9e9e;
}

/* Password Requirements */
.password-requirements {
    text-align: left;
}

.gap-2 {
    gap: 8px;
}

/* Success Content */
.success-content {
    position: relative;
    overflow: hidden;
}

.success-title {
    color: #2E7D32;
}

.success-subtitle {
    color: #546e7a;
    line-height: 1.6;
}

.celebration-icon {
    display: flex;
    justify-content: center;
}

.features-list {
    background: rgba(76, 175, 80, 0.08);
    padding: 16px;
    border-radius: 12px;
}

/* Help Link */
.help-link {
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    font-weight: 500;
}

.help-link:hover {
    text-decoration: underline;
    color: white;
}

/* Confetti Animation */
.confetti-wrapper {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    overflow: hidden;
}

.confetti {
    position: absolute;
    width: 10px;
    height: 10px;
    animation: confetti-fall 3s ease-out infinite;
}

.confetti-1 { left: 10%; background: #1976D2; animation-delay: 0s; border-radius: 50%; }
.confetti-2 { left: 20%; background: #43A047; animation-delay: 0.2s; }
.confetti-3 { left: 30%; background: #FFD700; animation-delay: 0.4s; border-radius: 50%; }
.confetti-4 { left: 40%; background: #E91E63; animation-delay: 0.6s; }
.confetti-5 { left: 50%; background: #1976D2; animation-delay: 0.8s; }
.confetti-6 { left: 60%; background: #43A047; animation-delay: 1s; border-radius: 50%; }
.confetti-7 { left: 70%; background: #FFD700; animation-delay: 1.2s; }
.confetti-8 { left: 80%; background: #E91E63; animation-delay: 1.4s; border-radius: 50%; }
.confetti-9 { left: 90%; background: #1976D2; animation-delay: 1.6s; }
.confetti-10 { left: 15%; background: #43A047; animation-delay: 1.8s; border-radius: 50%; }
.confetti-11 { left: 45%; background: #FFD700; animation-delay: 2s; }
.confetti-12 { left: 75%; background: #E91E63; animation-delay: 2.2s; border-radius: 50%; }

@keyframes confetti-fall {
    0% {
        transform: translateY(-20px) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(400px) rotate(720deg);
        opacity: 0;
    }
}

/* Responsive */
@media (max-width: 600px) {
    .verification-card {
        margin: 0 8px;
    }
    
    .otp-input-custom :deep(input) {
        font-size: 18px;
    }
    
    .badge-circle,
    .lock-circle {
        width: 80px;
        height: 80px;
    }
    
    .badge-circle::before {
        width: 100px;
        height: 100px;
    }
    
    .success-circle {
        width: 90px;
        height: 90px;
    }
}
</style>
