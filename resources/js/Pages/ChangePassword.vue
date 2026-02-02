<template>
  <v-app>
    <v-main class="change-password-bg">
      <v-container fluid class="fill-height pa-0">
        <v-row align="center" justify="center" class="fill-height ma-0">
          <v-col cols="12" sm="10" md="6" lg="5" xl="4" class="px-6">
            
            <!-- Step 1 & 2: OTP Verification -->
            <div v-if="step === 1 || step === 2" class="text-center">
              <!-- Back Button for Step 2 -->
              <div v-if="step === 2" class="text-left mb-4">
                <v-btn icon variant="text" @click="step = 1" class="back-btn">
                  <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
              </div>

              <!-- Green Checkmark Badge -->
              <div class="badge-icon mb-6">
                <div class="badge-circle">
                  <v-icon size="48" color="white">mdi-check</v-icon>
                </div>
              </div>

              <!-- Title -->
              <h1 class="text-h4 font-weight-bold mb-3 title-text">Verify via email</h1>
              <p class="text-body-1 subtitle-text mb-2">
                Enter the verification code we sent to
              </p>
              <p class="text-body-2 email-text mb-8">{{ userEmail }}</p>

              <!-- Step 1: Send OTP -->
              <div v-if="step === 1">
                <v-btn
                  block
                  size="x-large"
                  color="#6BC04B"
                  :loading="sendingOtp"
                  @click="sendOtp"
                  class="verify-btn rounded-pill text-white"
                >
                  Send Code
                </v-btn>
              </div>

              <!-- Step 2: Enter OTP -->
              <div v-else>
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

                <!-- Resend Code Link -->
                <div class="text-right mb-6">
                  <a 
                    href="#" 
                    class="resend-link"
                    :class="{ 'disabled-link': resendCooldown > 0 }"
                    @click.prevent="resendCooldown === 0 && resendOtp()"
                  >
                    <span v-if="resendCooldown > 0">Resend in {{ resendCooldown }}s</span>
                    <span v-else>Resend code</span>
                  </a>
                </div>

                <!-- Verify Button -->
                <v-btn
                  block
                  size="x-large"
                  color="#6BC04B"
                  :loading="verifyingOtp"
                  :disabled="getOtpLength() !== 6"
                  @click="verifyOtp"
                  class="verify-btn rounded-pill text-white"
                >
                  Verify
                </v-btn>
              </div>
            </div>

            <!-- Step 3: Create New Password -->
            <div v-else-if="step === 3" class="text-center">
              <!-- Back Button -->
              <div class="text-left mb-4">
                <v-btn icon variant="text" @click="step = 2" class="back-btn">
                  <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
              </div>

              <!-- Lock Icon -->
              <div class="lock-icon mb-6">
                <div class="lock-circle">
                  <v-icon size="48" color="#00BCD4">mdi-lock</v-icon>
                </div>
              </div>

              <!-- Title -->
              <h1 class="text-h4 font-weight-bold mb-8 title-text">Create new password</h1>

              <!-- Password Form -->
              <v-form ref="passwordForm" @submit.prevent="changePassword">
                <v-text-field
                  v-model="newPassword"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="Set new password"
                  variant="outlined"
                  :rules="passwordRules"
                  :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                  @click:append-inner="showPassword = !showPassword"
                  class="mb-4 password-field"
                  hide-details="auto"
                  bg-color="white"
                />

                <v-text-field
                  v-model="confirmPassword"
                  :type="showConfirmPassword ? 'text' : 'password'"
                  placeholder="Confirm new password"
                  variant="outlined"
                  :rules="confirmPasswordRules"
                  :append-inner-icon="showConfirmPassword ? 'mdi-eye' : 'mdi-eye-off'"
                  @click:append-inner="showConfirmPassword = !showConfirmPassword"
                  class="mb-6 password-field"
                  hide-details="auto"
                  bg-color="white"
                />

                <!-- Reset Password Button -->
                <v-btn
                  block
                  type="submit"
                  size="x-large"
                  color="#2196F3"
                  :loading="changingPassword"
                  :disabled="!isPasswordValid"
                  class="reset-btn rounded-pill text-white"
                >
                  Reset password
                </v-btn>
              </v-form>
            </div>

          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Success Screen (Full Page) -->
    <v-dialog v-model="successDialog" persistent fullscreen>
      <div class="success-screen">
        <div class="success-content text-center">
          <!-- Celebration Illustration -->
          <div class="celebration-icon mb-6">
            <svg width="180" height="180" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
              <!-- YAY! text -->
              <text x="100" y="30" text-anchor="middle" fill="#333" font-size="16" font-weight="bold" font-family="Arial">YAY!</text>
              <!-- Person body -->
              <ellipse cx="100" cy="85" rx="25" ry="12" fill="#F5D17A"/>
              <!-- Head -->
              <circle cx="100" cy="60" r="18" fill="#FFE4C4"/>
              <!-- Hair -->
              <path d="M82 55 Q85 40 100 42 Q115 40 118 55 Q120 48 115 45 Q100 35 85 45 Q80 48 82 55" fill="#333"/>
              <!-- Face -->
              <circle cx="94" cy="58" r="2" fill="#333"/>
              <circle cx="106" cy="58" r="2" fill="#333"/>
              <path d="M96 66 Q100 70 104 66" stroke="#333" stroke-width="1.5" fill="none"/>
              <!-- Arms raised -->
              <path d="M75 85 Q60 70 55 50" stroke="#FFE4C4" stroke-width="8" stroke-linecap="round"/>
              <path d="M125 85 Q140 70 145 50" stroke="#FFE4C4" stroke-width="8" stroke-linecap="round"/>
              <!-- Hands -->
              <circle cx="55" cy="48" r="6" fill="#FFE4C4"/>
              <circle cx="145" cy="48" r="6" fill="#FFE4C4"/>
              <!-- Shirt -->
              <path d="M75 90 L80 130 L120 130 L125 90 Q100 95 75 90" fill="#F5D17A"/>
              <!-- Legs running -->
              <path d="M90 130 L75 170" stroke="#89CFF0" stroke-width="12" stroke-linecap="round"/>
              <path d="M110 130 L130 160" stroke="#89CFF0" stroke-width="12" stroke-linecap="round"/>
              <!-- Shoes -->
              <ellipse cx="73" cy="175" rx="8" ry="5" fill="#E8E8E8"/>
              <ellipse cx="133" cy="165" rx="8" ry="5" fill="#E8E8E8"/>
              <!-- Hair flowing -->
              <path d="M118 55 Q130 50 140 65 Q135 55 125 52" fill="#333"/>
            </svg>
          </div>

          <!-- Success Message -->
          <h1 class="text-h4 font-weight-bold mb-3 success-title">Password Reset!</h1>
          <p class="text-body-1 success-subtitle mb-8">
            Your password has been successfully reset.<br>
            Click continue to login.
          </p>

          <!-- Continue Button -->
          <v-btn
            size="x-large"
            color="#6BC04B"
            @click="redirectToDashboard"
            class="continue-btn rounded-pill text-white px-12"
          >
            Continue
          </v-btn>
        </div>
      </div>
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
  background: linear-gradient(180deg, 
    rgba(255, 220, 220, 0.4) 0%, 
    rgba(200, 230, 255, 0.5) 50%, 
    rgba(180, 240, 220, 0.4) 100%);
  min-height: 100vh;
}

/* Badge Icon for Verify Screen */
.badge-icon {
  display: flex;
  justify-content: center;
}

.badge-circle {
  width: 100px;
  height: 100px;
  background: #6BC04B;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.badge-circle::before {
  content: '';
  position: absolute;
  width: 120px;
  height: 120px;
  background: #6BC04B;
  border-radius: 50%;
  z-index: -1;
  clip-path: polygon(
    50% 0%, 61% 7%, 75% 3%, 82% 15%, 97% 17%, 98% 32%, 
    107% 43%, 100% 57%, 104% 72%, 92% 80%, 88% 95%, 
    73% 93%, 60% 103%, 50% 95%, 40% 103%, 27% 93%, 
    12% 95%, 8% 80%, -4% 72%, 0% 57%, -7% 43%, 
    2% 32%, 3% 17%, 18% 15%, 25% 3%, 39% 7%
  );
}

/* Lock Icon for Password Screen */
.lock-icon {
  display: flex;
  justify-content: center;
}

.lock-circle {
  width: 100px;
  height: 100px;
  background: transparent;
  border: 3px solid #333;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Typography */
.title-text {
  color: #333;
}

.subtitle-text {
  color: #666;
}

.email-text {
  color: #999;
  font-style: italic;
}

/* Back Button */
.back-btn {
  color: #333 !important;
}

/* OTP Input Styling */
.otp-container {
  max-width: 320px;
  margin: 0 auto;
}

.otp-input-custom :deep(.v-otp-input__content) {
  justify-content: center;
  gap: 8px;
}

.otp-input-custom :deep(.v-field) {
  border-radius: 8px;
  background: white;
}

.otp-input-custom :deep(.v-field__outline__start),
.otp-input-custom :deep(.v-field__outline__end) {
  border-color: #ddd;
}

.otp-input-custom :deep(input) {
  font-size: 20px;
  font-weight: 600;
  text-align: center;
  color: #333;
}

/* Resend Link */
.resend-link {
  color: #6BC04B;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
}

.resend-link:hover {
  text-decoration: underline;
}

.disabled-link {
  color: #999;
  pointer-events: none;
}

/* Verify Button (Green) */
.verify-btn {
  font-weight: 600;
  font-size: 16px;
  letter-spacing: 0;
  text-transform: none;
  height: 52px !important;
}

/* Reset Button (Blue) */
.reset-btn {
  font-weight: 600;
  font-size: 16px;
  letter-spacing: 0;
  text-transform: none;
  height: 52px !important;
}

/* Password Fields */
.password-field :deep(.v-field) {
  border-radius: 8px;
  background: white;
}

.password-field :deep(.v-field__outline__start),
.password-field :deep(.v-field__outline__end) {
  border-color: #ddd;
}

.password-field :deep(input) {
  color: #333;
}

.password-field :deep(input::placeholder) {
  color: #999;
}

/* Success Screen */
.success-screen {
  background: linear-gradient(180deg, 
    rgba(200, 240, 220, 0.6) 0%, 
    rgba(200, 230, 255, 0.5) 50%, 
    rgba(180, 240, 220, 0.4) 100%);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.success-content {
  max-width: 350px;
}

.celebration-icon {
  display: flex;
  justify-content: center;
}

.success-title {
  color: #333;
}

.success-subtitle {
  color: #666;
  line-height: 1.6;
}

.continue-btn {
  font-weight: 600;
  font-size: 16px;
  letter-spacing: 0;
  text-transform: none;
  height: 52px !important;
}
</style>
