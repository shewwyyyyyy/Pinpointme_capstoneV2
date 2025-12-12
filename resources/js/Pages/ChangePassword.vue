<template>
  <v-app>
    <v-main class="change-password-bg">
      <v-container fluid class="fill-height">
        <v-row align="center" justify="center" class="fill-height">
          <v-col cols="12" sm="10" md="8" lg="6" xl="5">
            <v-card class="elevation-12 rounded-xl overflow-hidden">
              <!-- Header -->
              <div class="header-gradient pa-6 text-center">
                <v-icon size="64" color="white" class="mb-3">mdi-lock-reset</v-icon>
                <h1 class="text-h4 font-weight-bold text-white">Password Change Required</h1>
                <p class="text-body-1 text-white mt-2 opacity-80">
                  For security purposes, please change your temporary password
                </p>
              </div>

              <v-card-text class="pa-6">
                <!-- Step 1: Request OTP -->
                <div v-if="step === 1">
                  <v-alert type="info" variant="tonal" class="mb-6">
                    <v-icon start>mdi-information</v-icon>
                    Click below to receive a verification code at your email address
                  </v-alert>

                  <div class="text-center mb-6">
                    <v-chip color="primary" variant="flat" size="large" class="px-6">
                      <v-icon start>mdi-email</v-icon>
                      {{ userEmail }}
                    </v-chip>
                  </div>

                  <v-btn
                    block
                    size="x-large"
                    color="primary"
                    :loading="sendingOtp"
                    @click="sendOtp"
                    class="rounded-lg"
                  >
                    <v-icon start>mdi-send</v-icon>
                    Send Verification Code
                  </v-btn>
                </div>

                <!-- Step 2: Enter OTP -->
                <div v-else-if="step === 2">
                  <v-alert type="success" variant="tonal" class="mb-6">
                    <v-icon start>mdi-check-circle</v-icon>
                    Verification code sent to {{ userEmail }}
                  </v-alert>

                  <div class="text-center mb-4">
                    <p class="text-body-2 text-grey-darken-1">Enter the 6-digit code:</p>
                  </div>

                  <v-otp-input
                    v-model="otpCode"
                    :length="6"
                    variant="outlined"
                    class="mb-4 otp-input"
                    @finish="verifyOtp"
                  />

                  <div class="text-center mb-4">
                    <span class="text-caption text-grey">Code expires in </span>
                    <span class="text-caption font-weight-bold" :class="otpTimer <= 60 ? 'text-error' : 'text-primary'">
                      {{ formatTimer(otpTimer) }}
                    </span>
                  </div>

                  <v-btn
                    block
                    size="large"
                    color="primary"
                    :loading="verifyingOtp"
                    :disabled="getOtpLength() !== 6"
                    @click="verifyOtp"
                    class="rounded-lg mb-3"
                  >
                    <v-icon start>mdi-check</v-icon>
                    Verify Code
                  </v-btn>

                  <v-btn
                    block
                    variant="text"
                    :disabled="resendCooldown > 0"
                    @click="resendOtp"
                  >
                    <span v-if="resendCooldown > 0">Resend in {{ resendCooldown }}s</span>
                    <span v-else>Resend Code</span>
                  </v-btn>
                </div>

                <!-- Step 3: Set New Password -->
                <div v-else-if="step === 3">
                  <v-alert type="success" variant="tonal" class="mb-6">
                    <v-icon start>mdi-shield-check</v-icon>
                    Email verified! Please set your new password
                  </v-alert>

                  <v-form ref="passwordForm" @submit.prevent="changePassword">
                    <v-text-field
                      v-model="newPassword"
                      :type="showPassword ? 'text' : 'password'"
                      label="New Password"
                      variant="outlined"
                      :rules="passwordRules"
                      :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                      @click:append-inner="showPassword = !showPassword"
                      class="mb-2"
                    />

                    <!-- Password Strength Indicator -->
                    <div class="mb-4">
                      <v-progress-linear
                        :model-value="passwordStrength"
                        :color="passwordStrengthColor"
                        height="8"
                        rounded
                        class="mb-1"
                      />
                      <div class="d-flex justify-space-between text-caption">
                        <span>Password Strength</span>
                        <span :class="`text-${passwordStrengthColor}`">{{ passwordStrengthText }}</span>
                      </div>
                    </div>

                    <v-text-field
                      v-model="confirmPassword"
                      :type="showConfirmPassword ? 'text' : 'password'"
                      label="Confirm New Password"
                      variant="outlined"
                      :rules="confirmPasswordRules"
                      :append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
                      @click:append-inner="showConfirmPassword = !showConfirmPassword"
                      class="mb-4"
                    />

                    <!-- Password Requirements -->
                    <v-card variant="outlined" class="mb-4 pa-3">
                      <p class="text-caption text-grey-darken-1 mb-2">Password Requirements:</p>
                      <div class="d-flex flex-wrap gap-2">
                        <v-chip 
                          :color="hasMinLength ? 'success' : 'grey'" 
                          size="small" 
                          variant="tonal"
                        >
                          <v-icon start size="14">{{ hasMinLength ? 'mdi-check' : 'mdi-close' }}</v-icon>
                          8+ characters
                        </v-chip>
                        <v-chip 
                          :color="hasUppercase ? 'success' : 'grey'" 
                          size="small" 
                          variant="tonal"
                        >
                          <v-icon start size="14">{{ hasUppercase ? 'mdi-check' : 'mdi-close' }}</v-icon>
                          Uppercase
                        </v-chip>
                        <v-chip 
                          :color="hasLowercase ? 'success' : 'grey'" 
                          size="small" 
                          variant="tonal"
                        >
                          <v-icon start size="14">{{ hasLowercase ? 'mdi-check' : 'mdi-close' }}</v-icon>
                          Lowercase
                        </v-chip>
                        <v-chip 
                          :color="hasNumber ? 'success' : 'grey'" 
                          size="small" 
                          variant="tonal"
                        >
                          <v-icon start size="14">{{ hasNumber ? 'mdi-check' : 'mdi-close' }}</v-icon>
                          Number
                        </v-chip>
                      </div>
                    </v-card>

                    <v-btn
                      block
                      type="submit"
                      size="x-large"
                      color="primary"
                      :loading="changingPassword"
                      :disabled="!isPasswordValid"
                      class="rounded-lg"
                    >
                      <v-icon start>mdi-lock-check</v-icon>
                      Change Password
                    </v-btn>
                  </v-form>
                </div>
              </v-card-text>
            </v-card>

            <!-- Logout option -->
            <div class="text-center mt-4">
              <v-btn variant="text" color="white" @click="logout">
                <v-icon start>mdi-logout</v-icon>
                Cancel & Logout
              </v-btn>
            </div>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Success Dialog -->
    <v-dialog v-model="successDialog" persistent max-width="400">
      <v-card class="rounded-xl text-center pa-6">
        <v-icon size="80" color="success" class="mb-4">mdi-check-circle</v-icon>
        <v-card-title class="text-h5 font-weight-bold">Password Changed!</v-card-title>
        <v-card-text class="text-body-1 text-grey-darken-1">
          Your password has been updated successfully. You will be redirected shortly.
        </v-card-text>
        <v-card-actions class="justify-center">
          <v-btn color="primary" size="large" @click="redirectToDashboard">
            Continue
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar for messages -->
    <v-snackbar
      v-model="snackbar"
      :color="snackbarColor"
      :timeout="3000"
      location="top"
    >
      {{ snackbarMessage }}
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const userEmail = ref(page.props.email || '');
const userRole = ref(page.props.role || 'student');

const step = ref(1);
const otpCode = ref('');
const verificationToken = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const showPassword = ref(false);
const showConfirmPassword = ref(false);

const sendingOtp = ref(false);
const verifyingOtp = ref(false);
const changingPassword = ref(false);

const otpTimer = ref(300); // 5 minutes
const resendCooldown = ref(0);
let timerInterval = null;
let cooldownInterval = null;

const snackbar = ref(false);
const snackbarMessage = ref('');
const snackbarColor = ref('success');
const successDialog = ref(false);

// Password validation
const hasMinLength = computed(() => newPassword.value.length >= 8);
const hasUppercase = computed(() => /[A-Z]/.test(newPassword.value));
const hasLowercase = computed(() => /[a-z]/.test(newPassword.value));
const hasNumber = computed(() => /[0-9]/.test(newPassword.value));

const isPasswordValid = computed(() => 
  hasMinLength.value && 
  hasUppercase.value && 
  hasLowercase.value && 
  hasNumber.value && 
  newPassword.value === confirmPassword.value
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
];

const confirmPasswordRules = [
  v => !!v || 'Please confirm your password',
  v => v === newPassword.value || 'Passwords do not match',
];

// Helper to get OTP length regardless of format
function getOtpLength() {
  if (Array.isArray(otpCode.value)) {
    return otpCode.value.join('').length;
  }
  return String(otpCode.value || '').length;
}

function formatTimer(seconds) {
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${mins}:${secs.toString().padStart(2, '0')}`;
}

function startOtpTimer() {
  otpTimer.value = 300;
  if (timerInterval) clearInterval(timerInterval);
  timerInterval = setInterval(() => {
    if (otpTimer.value > 0) {
      otpTimer.value--;
    } else {
      clearInterval(timerInterval);
      showMessage('OTP expired. Please request a new code.', 'error');
      step.value = 1;
    }
  }, 1000);
}

function startResendCooldown() {
  resendCooldown.value = 60;
  if (cooldownInterval) clearInterval(cooldownInterval);
  cooldownInterval = setInterval(() => {
    if (resendCooldown.value > 0) {
      resendCooldown.value--;
    } else {
      clearInterval(cooldownInterval);
    }
  }, 1000);
}

async function sendOtp() {
  sendingOtp.value = true;
  try {
    const response = await axios.post('/api/auth/send-password-change-otp', {
      email: userEmail.value,
    });
    
    if (response.data.success) {
      showMessage('Verification code sent to your email!', 'success');
      step.value = 2;
      startOtpTimer();
      startResendCooldown();
    } else {
      showMessage(response.data.message || 'Failed to send OTP', 'error');
    }
  } catch (error) {
    showMessage(error.response?.data?.message || 'Failed to send verification code', 'error');
  } finally {
    sendingOtp.value = false;
  }
}

async function resendOtp() {
  await sendOtp();
}

async function verifyOtp() {
  // Handle both string and array formats from v-otp-input
  const otpValue = Array.isArray(otpCode.value) 
    ? otpCode.value.join('') 
    : String(otpCode.value).trim();
  
  console.log('OTP value:', otpValue, 'Type:', typeof otpValue, 'Length:', otpValue.length);
  
  if (otpValue.length !== 6) return;
  
  verifyingOtp.value = true;
  try {
    const response = await axios.post('/api/auth/verify-password-change-otp', {
      email: userEmail.value,
      otp: otpValue,
    });
    
    if (response.data.success) {
      verificationToken.value = response.data.token;
      showMessage('Email verified successfully!', 'success');
      step.value = 3;
      if (timerInterval) clearInterval(timerInterval);
    } else {
      showMessage(response.data.message || 'Invalid OTP', 'error');
    }
  } catch (error) {
    showMessage(error.response?.data?.message || 'Failed to verify OTP', 'error');
  } finally {
    verifyingOtp.value = false;
  }
}

async function changePassword() {
  if (!isPasswordValid.value) return;
  
  changingPassword.value = true;
  try {
    const response = await axios.post('/api/auth/complete-password-change', {
      email: userEmail.value,
      token: verificationToken.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    });
    
    if (response.data.success) {
      successDialog.value = true;
    } else {
      showMessage(response.data.message || 'Failed to change password', 'error');
    }
  } catch (error) {
    showMessage(error.response?.data?.message || 'Failed to change password', 'error');
  } finally {
    changingPassword.value = false;
  }
}

function redirectToDashboard() {
  // Redirect based on role
  if (userRole.value === 'admin') {
    window.location.href = '/admin/dashboard';
  } else if (userRole.value === 'rescuer') {
    window.location.href = '/rescuer/dashboard';
  } else {
    window.location.href = '/user/scanner';
  }
}

function logout() {
  router.post('/logout');
}

function showMessage(message, color = 'success') {
  snackbarMessage.value = message;
  snackbarColor.value = color;
  snackbar.value = true;
}

onMounted(() => {
  // Get user email from props
  if (page.props.auth?.user?.email) {
    userEmail.value = page.props.auth.user.email;
    userRole.value = page.props.auth.user.role;
  }
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
  if (cooldownInterval) clearInterval(cooldownInterval);
});
</script>

<style scoped>
.change-password-bg {
  background: linear-gradient(135deg, #1976D2 0%, #0D47A1 50%, #002171 100%);
  min-height: 100vh;
}

.header-gradient {
  background: linear-gradient(135deg, #1976D2, #0D47A1);
}

.opacity-80 {
  opacity: 0.8;
}

.otp-input :deep(.v-otp-input__content) {
  justify-content: center;
  gap: 8px;
}

.otp-input :deep(input) {
  font-size: 24px;
  font-weight: bold;
  text-align: center;
}

.gap-2 {
  gap: 8px;
}
</style>
