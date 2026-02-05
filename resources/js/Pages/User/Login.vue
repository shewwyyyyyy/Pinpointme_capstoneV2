<template>
    <v-app class="bg-user-gradient-light">
        <v-main class="min-h-screen">
            <v-container fluid class="fill-height pa-0">
                <v-row no-gutters class="fill-height">
                    <!-- Left Panel - Laptop/Desktop Only -->
                    <v-col cols="12" lg="6" class="d-none d-lg-flex">
                        <div
                            class="w-100 h-100 d-flex flex-column justify-center align-center position-relative left-panel"
                        >
                            <div class="logo-container">
                                <v-img
                                    src="/images/logos/pinpointme.png"
                                    max-height="200"
                                    max-width="200"
                                    contain
                                    class="logo-image"
                                />
                            </div>
                            <h1 class="text-h3 text-white mt-6 font-weight-bold brand-title">
                                PinPointMe
                            </h1>
                            <p class="text-h6 text-white-darken-1 text-center mt-2 brand-subtitle">
                                COMING YOUR WAY.
                            </p>
                            <p class="text-body-2 text-white-darken-2 text-center mt-4 px-8" style="max-width: 400px;">
                                Your trusted emergency rescue system. Fast, reliable, and always ready to help.
                            </p>
                        </div>
                    </v-col>

                    <!-- Right Panel - Login Form -->
                    <v-col cols="12" lg="6" class="d-flex align-center justify-center">
                        <v-card
                            class="pa-8 mx-4"
                            max-width="450"
                            width="100%"
                            elevation="8"
                            rounded="lg"
                        >
                            <!-- Mobile/Tablet Logo -->
                            <div class="d-lg-none text-center mb-6">
                                <v-img
                                    src="/images/logos/pinpointme.png"
                                    max-height="120"
                                    max-width="120"
                                    contain
                                    class="mx-auto"
                                />
                                <h2 class="text-h5 font-weight-bold text-primary mt-2">PinPointMe</h2>
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

                                <!-- Google OAuth Error -->
                                <v-alert
                                    v-if="googleError"
                                    type="error"
                                    variant="tonal"
                                    class="mb-4"
                                    closable
                                    @click:close="googleError = ''"
                                >
                                    {{ googleError }}
                                </v-alert>

                                <!-- Google Sign-In Button -->
                                <v-btn
                                    variant="outlined"
                                    size="large"
                                    block
                                    :loading="isGoogleLoading"
                                    :disabled="isLoading"
                                    class="mb-4 google-btn"
                                    @click="handleGoogleLogin"
                                >
                                    <template v-slot:prepend>
                                        <img 
                                            src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" 
                                            alt="Google" 
                                            class="google-icon"
                                        >
                                    </template>
                                    <span class="google-btn-text">Continue with SDCA Google</span>
                                </v-btn>

                                <!-- Divider -->
                                <div class="divider-container">
                                    <v-divider class="divider-line"></v-divider>
                                    <span class="divider-text">or</span>
                                    <v-divider class="divider-line"></v-divider>
                                </div>

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
                                <v-btn variant="text" color="primary" size="small" @click="showForgotPassword = true">
                                    Forgot Password?
                                </v-btn>
                                <br>
                                <v-btn variant="text" color="primary" size="small" @click="showRegister = true" class="mt-2">
                                    Register Now
                                </v-btn>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>

            <!-- Forgot Password Dialog - OTP Based -->
            <v-dialog v-model="showForgotPassword" max-width="450" persistent>
                <v-card rounded="lg">
                    <v-card-title class="d-flex align-center pa-4 bg-primary">
                        <v-icon color="white" class="mr-2">mdi-lock-reset</v-icon>
                        <span class="text-white">Reset Password</span>
                        <v-spacer />
                        <v-btn icon variant="text" @click="closeForgotPassword" :disabled="forgotPasswordLoading">
                            <v-icon color="white">mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>

                    <v-card-text class="pa-6">
                        <!-- Step Indicator -->
                        <div class="d-flex justify-center mb-4">
                            <div class="d-flex align-center">
                                <v-avatar :color="resetStep >= 1 ? 'primary' : 'grey-lighten-2'" size="28">
                                    <span class="text-white text-caption font-weight-bold">1</span>
                                </v-avatar>
                                <div class="step-line" :class="{ 'active': resetStep >= 2 }"></div>
                                <v-avatar :color="resetStep >= 2 ? 'primary' : 'grey-lighten-2'" size="28">
                                    <span :class="resetStep >= 2 ? 'text-white' : 'text-grey'" class="text-caption font-weight-bold">2</span>
                                </v-avatar>
                                <div class="step-line" :class="{ 'active': resetStep >= 3 }"></div>
                                <v-avatar :color="resetStep >= 3 ? 'primary' : 'grey-lighten-2'" size="28">
                                    <span :class="resetStep >= 3 ? 'text-white' : 'text-grey'" class="text-caption font-weight-bold">3</span>
                                </v-avatar>
                            </div>
                        </div>

                        <!-- Success State -->
                        <div v-if="resetComplete" class="text-center py-4">
                            <v-icon size="64" color="success" class="mb-4">mdi-check-circle</v-icon>
                            <h3 class="text-h6 font-weight-bold mb-2">Password Reset Successful!</h3>
                            <p class="text-body-2 text-grey mb-4">
                                Your password has been changed successfully. You can now log in with your new password.
                            </p>
                        </div>

                        <!-- Step 1: Enter Email -->
                        <div v-else-if="resetStep === 1">
                            <p class="text-body-2 text-grey mb-4">
                                Enter your email address and we'll send you a verification code.
                            </p>

                            <v-alert
                                v-if="forgotPasswordError"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                                closable
                                @click:close="forgotPasswordError = ''"
                            >
                                {{ forgotPasswordError }}
                            </v-alert>

                            <v-form @submit.prevent="sendResetOtp" ref="forgotFormRef">
                                <v-text-field
                                    v-model="forgotPasswordEmail"
                                    label="Email Address"
                                    type="email"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-email"
                                    :rules="[rules.required, rules.email]"
                                    :disabled="forgotPasswordLoading"
                                    autofocus
                                />
                            </v-form>
                        </div>

                        <!-- Step 2: Enter OTP -->
                        <div v-else-if="resetStep === 2">
                            <div class="text-center mb-4">
                                <v-icon size="48" color="primary" class="mb-2">mdi-email-check</v-icon>
                                <p class="text-body-2 text-grey">
                                    We've sent a 6-digit code to <strong>{{ forgotPasswordEmail }}</strong>
                                </p>
                            </div>

                            <v-alert
                                v-if="forgotPasswordError"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                                closable
                                @click:close="forgotPasswordError = ''"
                            >
                                {{ forgotPasswordError }}
                            </v-alert>

                            <v-form @submit.prevent="verifyResetOtp" ref="otpFormRef">
                                <v-otp-input
                                    v-model="resetOtp"
                                    length="6"
                                    variant="outlined"
                                    :disabled="forgotPasswordLoading"
                                    class="mb-4"
                                />
                            </v-form>

                            <p class="text-caption text-grey text-center">
                                Didn't receive the code? 
                                <v-btn 
                                    variant="text" 
                                    color="primary" 
                                    size="small" 
                                    @click="resendResetOtp"
                                    :disabled="resendCooldown > 0 || forgotPasswordLoading"
                                >
                                    {{ resendCooldown > 0 ? `Resend in ${resendCooldown}s` : 'Resend Code' }}
                                </v-btn>
                            </p>
                        </div>

                        <!-- Step 3: Enter New Password -->
                        <div v-else-if="resetStep === 3">
                            <p class="text-body-2 text-grey mb-4 text-center">
                                Create a new password for your account.
                            </p>

                            <v-alert
                                v-if="forgotPasswordError"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                                closable
                                @click:close="forgotPasswordError = ''"
                            >
                                {{ forgotPasswordError }}
                            </v-alert>

                            <v-form @submit.prevent="completePasswordReset" ref="newPasswordFormRef">
                                <v-text-field
                                    v-model="newPassword"
                                    label="New Password"
                                    :type="showNewPassword ? 'text' : 'password'"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-lock"
                                    :append-inner-icon="showNewPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                    @click:append-inner="showNewPassword = !showNewPassword"
                                    :rules="[rules.required, rules.minLength, rules.hasUppercase, rules.hasLowercase, rules.hasNumber, rules.hasSpecial]"
                                    :disabled="forgotPasswordLoading"
                                    class="mb-1"
                                />

                                <!-- Password Requirements Checklist -->
                                <div class="password-requirements mb-4 px-2">
                                    <p class="text-caption text-grey-darken-1 mb-2">Password must contain:</p>
                                    <div class="requirements-grid">
                                        <div class="requirement-item" :class="{ 'met': passwordChecks.length }">
                                            <v-icon size="14" :color="passwordChecks.length ? 'success' : 'grey-lighten-1'">
                                                {{ passwordChecks.length ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                            </v-icon>
                                            <span>At least 8 characters</span>
                                        </div>
                                        <div class="requirement-item" :class="{ 'met': passwordChecks.uppercase }">
                                            <v-icon size="14" :color="passwordChecks.uppercase ? 'success' : 'grey-lighten-1'">
                                                {{ passwordChecks.uppercase ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                            </v-icon>
                                            <span>One uppercase letter (A-Z)</span>
                                        </div>
                                        <div class="requirement-item" :class="{ 'met': passwordChecks.lowercase }">
                                            <v-icon size="14" :color="passwordChecks.lowercase ? 'success' : 'grey-lighten-1'">
                                                {{ passwordChecks.lowercase ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                            </v-icon>
                                            <span>One lowercase letter (a-z)</span>
                                        </div>
                                        <div class="requirement-item" :class="{ 'met': passwordChecks.number }">
                                            <v-icon size="14" :color="passwordChecks.number ? 'success' : 'grey-lighten-1'">
                                                {{ passwordChecks.number ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                            </v-icon>
                                            <span>One number (0-9)</span>
                                        </div>
                                        <div class="requirement-item" :class="{ 'met': passwordChecks.special }">
                                            <v-icon size="14" :color="passwordChecks.special ? 'success' : 'grey-lighten-1'">
                                                {{ passwordChecks.special ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                            </v-icon>
                                            <span>One special character (!@#$%...)</span>
                                        </div>
                                    </div>
                                </div>

                                <v-text-field
                                    v-model="confirmNewPassword"
                                    label="Confirm New Password"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-lock-check"
                                    :append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                    @click:append-inner="showConfirmPassword = !showConfirmPassword"
                                    :rules="[rules.required, rules.passwordMatch]"
                                    :disabled="forgotPasswordLoading"
                                    :error="confirmNewPassword && confirmNewPassword !== newPassword"
                                />
                                <p v-if="confirmNewPassword && confirmNewPassword === newPassword" class="text-caption text-success mt-1 px-2">
                                    <v-icon size="14" color="success">mdi-check-circle</v-icon>
                                    Passwords match
                                </p>
                            </v-form>
                        </div>
                    </v-card-text>

                    <v-card-actions class="pa-4 pt-0">
                        <!-- Success State Actions -->
                        <template v-if="resetComplete">
                            <v-spacer />
                            <v-btn color="primary" @click="closeForgotPassword">
                                Back to Login
                            </v-btn>
                        </template>

                        <!-- Step 1 Actions -->
                        <template v-else-if="resetStep === 1">
                            <v-btn variant="text" @click="closeForgotPassword" :disabled="forgotPasswordLoading">
                                Cancel
                            </v-btn>
                            <v-spacer />
                            <v-btn 
                                color="primary" 
                                @click="sendResetOtp"
                                :loading="forgotPasswordLoading"
                            >
                                Send Code
                            </v-btn>
                        </template>

                        <!-- Step 2 Actions -->
                        <template v-else-if="resetStep === 2">
                            <v-btn variant="text" @click="resetStep = 1" :disabled="forgotPasswordLoading">
                                Back
                            </v-btn>
                            <v-spacer />
                            <v-btn 
                                color="primary" 
                                @click="verifyResetOtp"
                                :loading="forgotPasswordLoading"
                                :disabled="resetOtp.length !== 6"
                            >
                                Verify Code
                            </v-btn>
                        </template>

                        <!-- Step 3 Actions -->
                        <template v-else-if="resetStep === 3">
                            <v-btn variant="text" @click="resetStep = 2; resetOtp = ''" :disabled="forgotPasswordLoading">
                                Back
                            </v-btn>
                            <v-spacer />
                            <v-btn 
                                color="primary" 
                                @click="completePasswordReset"
                                :loading="forgotPasswordLoading"
                                :disabled="!isPasswordValid || !confirmNewPassword || confirmNewPassword !== newPassword"
                            >
                                Reset Password
                            </v-btn>
                        </template>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Registration Dialog -->
            <v-dialog v-model="showRegister" max-width="450" persistent>
                <v-card rounded="lg">
                    <v-card-title class="d-flex align-center pa-4 bg-primary">
                        <v-icon color="white" class="mr-2">mdi-account-plus</v-icon>
                        <span class="text-white">Register New Account</span>
                        <v-spacer />
                        <v-btn icon variant="text" @click="closeRegister" :disabled="registerLoading">
                            <v-icon color="white">mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>

                    <v-card-text class="pa-6">
                        <!-- Step Indicator -->
                        <div class="d-flex justify-center mb-4">
                            <div class="d-flex align-center">
                                <v-avatar :color="registerStep >= 1 ? 'primary' : 'grey-lighten-2'" size="28">
                                    <span class="text-white text-caption font-weight-bold">1</span>
                                </v-avatar>
                                <div class="step-line" :class="{ 'active': registerStep >= 2 }"></div>
                                <v-avatar :color="registerStep >= 2 ? 'primary' : 'grey-lighten-2'" size="28">
                                    <span :class="registerStep >= 2 ? 'text-white' : 'text-grey'" class="text-caption font-weight-bold">2</span>
                                </v-avatar>
                                <div class="step-line" :class="{ 'active': registerStep >= 3 }"></div>
                                <v-avatar :color="registerStep >= 3 ? 'primary' : 'grey-lighten-2'" size="28">
                                    <span :class="registerStep >= 3 ? 'text-white' : 'text-grey'" class="text-caption font-weight-bold">3</span>
                                </v-avatar>
                            </div>
                        </div>

                        <!-- Step 1: Enter Email -->
                        <div v-if="registerStep === 1">
                            <p class="text-body-2 text-grey mb-4">
                                Enter your SDCA email address to register. We'll send you an OTP and temporary password.
                            </p>

                            <v-alert
                                v-if="registerError"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                                closable
                                @click:close="registerError = ''"
                            >
                                {{ registerError }}
                            </v-alert>

                            <v-form @submit.prevent="sendRegisterOtp" ref="registerFormRef">
                                <v-text-field
                                    v-model="registerEmail"
                                    label="SDCA Email Address"
                                    type="email"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-email"
                                    :rules="[rules.required, rules.email, rules.sdcaEmail]"
                                    :disabled="registerLoading"
                                />
                            </v-form>
                        </div>

                        <!-- Step 2: Enter OTP -->
                        <div v-else-if="registerStep === 2">
                            <div class="text-center mb-4">
                                <v-icon size="48" color="primary" class="mb-2">mdi-email-check</v-icon>
                                <p class="text-body-2 text-grey">
                                    We've sent a 6-digit code and temporary password to <strong>{{ registerEmail }}</strong>
                                </p>
                            </div>

                            <v-alert
                                v-if="registerError"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                                closable
                                @click:close="registerError = ''"
                            >
                                {{ registerError }}
                            </v-alert>

                            <v-form @submit.prevent="verifyRegisterOtp" ref="registerOtpFormRef">
                                <v-otp-input
                                    v-model="registerOtp"
                                    length="6"
                                    variant="outlined"
                                    :disabled="registerLoading"
                                    class="mb-4"
                                />
                            </v-form>

                            <p class="text-caption text-grey text-center">
                                Didn't receive the code? 
                                <v-btn 
                                    variant="text" 
                                    color="primary" 
                                    size="small" 
                                    @click="resendRegisterOtp"
                                    :disabled="registerResendCooldown > 0 || registerLoading"
                                >
                                    {{ registerResendCooldown > 0 ? `Resend in ${registerResendCooldown}s` : 'Resend Code' }}
                                </v-btn>
                            </p>
                        </div>

                        <!-- Step 3: Set New Password -->
                        <div v-else-if="registerStep === 3">
                            <p class="text-body-2 text-grey mb-4 text-center">
                                Create a strong password for your account.
                            </p>

                            <v-alert
                                v-if="registerError"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                                closable
                                @click:close="registerError = ''"
                            >
                                {{ registerError }}
                            </v-alert>

                            <v-form @submit.prevent="completeRegister" ref="registerPasswordFormRef">
                                <v-text-field
                                    v-model="registerPassword"
                                    label="New Password"
                                    :type="showRegisterPassword ? 'text' : 'password'"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-lock"
                                    :append-inner-icon="showRegisterPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                    @click:append-inner="showRegisterPassword = !showRegisterPassword"
                                    :rules="[rules.required, rules.minLength, rules.hasUppercase, rules.hasLowercase, rules.hasNumber, rules.hasSpecial]"
                                    :disabled="registerLoading"
                                    autocomplete="new-password"
                                    class="mb-1"
                                />

                                <!-- Password Requirements Checklist -->
                                <div class="password-requirements mb-4 px-2">
                                    <div class="requirements-grid">
                                        <div :class="['requirement-item', registerPasswordChecks.length ? 'met' : '']">
                                            <v-icon size="16" :color="registerPasswordChecks.length ? 'success' : 'grey'">
                                                {{ registerPasswordChecks.length ? 'mdi-check-circle' : 'mdi-checkbox-blank-circle-outline' }}
                                            </v-icon>
                                            <span>At least 8 characters</span>
                                        </div>
                                        <div :class="['requirement-item', registerPasswordChecks.uppercase ? 'met' : '']">
                                            <v-icon size="16" :color="registerPasswordChecks.uppercase ? 'success' : 'grey'">
                                                {{ registerPasswordChecks.uppercase ? 'mdi-check-circle' : 'mdi-checkbox-blank-circle-outline' }}
                                            </v-icon>
                                            <span>One uppercase letter (A-Z)</span>
                                        </div>
                                        <div :class="['requirement-item', registerPasswordChecks.lowercase ? 'met' : '']">
                                            <v-icon size="16" :color="registerPasswordChecks.lowercase ? 'success' : 'grey'">
                                                {{ registerPasswordChecks.lowercase ? 'mdi-check-circle' : 'mdi-checkbox-blank-circle-outline' }}
                                            </v-icon>
                                            <span>One lowercase letter (a-z)</span>
                                        </div>
                                        <div :class="['requirement-item', registerPasswordChecks.number ? 'met' : '']">
                                            <v-icon size="16" :color="registerPasswordChecks.number ? 'success' : 'grey'">
                                                {{ registerPasswordChecks.number ? 'mdi-check-circle' : 'mdi-checkbox-blank-circle-outline' }}
                                            </v-icon>
                                            <span>One number (0-9)</span>
                                        </div>
                                        <div :class="['requirement-item', registerPasswordChecks.special ? 'met' : '']">
                                            <v-icon size="16" :color="registerPasswordChecks.special ? 'success' : 'grey'">
                                                {{ registerPasswordChecks.special ? 'mdi-check-circle' : 'mdi-checkbox-blank-circle-outline' }}
                                            </v-icon>
                                            <span>One special character (!@#$%^&*)</span>
                                        </div>
                                    </div>
                                </div>

                                <v-text-field
                                    v-model="registerConfirmPassword"
                                    label="Confirm Password"
                                    :type="showRegisterConfirmPassword ? 'text' : 'password'"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-lock-check"
                                    :append-inner-icon="showRegisterConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                    @click:append-inner="showRegisterConfirmPassword = !showRegisterConfirmPassword"
                                    :rules="[rules.required, v => v === registerPassword || 'Passwords do not match']"
                                    :disabled="registerLoading"
                                    autocomplete="new-password"
                                    :error="registerConfirmPassword && registerConfirmPassword !== registerPassword"
                                />
                                <p v-if="registerConfirmPassword && registerConfirmPassword === registerPassword" class="text-caption text-success mt-1 px-2">
                                    <v-icon size="16" color="success">mdi-check</v-icon> Passwords match
                                </p>
                            </v-form>
                        </div>

                        <!-- Success State -->
                        <div v-else-if="registerStep === 4" class="text-center py-4 success-state">
                            <v-icon size="64" color="success" class="mb-4">mdi-check-circle</v-icon>
                            <h3 class="text-h6 font-weight-bold mb-2">Registration Successful!</h3>
                            <p class="text-body-2 text-grey mb-4">
                                Your account has been created successfully. You can now log in with your new password.
                            </p>
                        </div>
                    </v-card-text>

                    <v-card-actions class="pa-4 pt-0">
                        <!-- Success State Actions -->
                        <template v-if="registerStep === 4">
                            <v-spacer />
                            <v-btn color="primary" @click="closeRegister">
                                Back to Login
                            </v-btn>
                        </template>

                        <!-- Step 1 Actions -->
                        <template v-else-if="registerStep === 1">
                            <v-btn variant="text" @click="closeRegister" :disabled="registerLoading">
                                Cancel
                            </v-btn>
                            <v-spacer />
                            <v-btn 
                                color="primary" 
                                @click="sendRegisterOtp"
                                :loading="registerLoading"
                            >
                                Send Code
                            </v-btn>
                        </template>

                        <!-- Step 2 Actions -->
                        <template v-else-if="registerStep === 2">
                            <v-btn variant="text" @click="registerStep = 1" :disabled="registerLoading">
                                Back
                            </v-btn>
                            <v-spacer />
                            <v-btn 
                                color="primary" 
                                @click="verifyRegisterOtp"
                                :loading="registerLoading"
                                :disabled="registerOtp.length !== 6"
                            >
                                Verify Code
                            </v-btn>
                        </template>

                        <!-- Step 3 Actions -->
                        <template v-else-if="registerStep === 3">
                            <v-btn variant="text" @click="registerStep = 2; registerOtp = ''" :disabled="registerLoading">
                                Back
                            </v-btn>
                            <v-spacer />
                            <v-btn 
                                color="primary" 
                                @click="completeRegister"
                                :loading="registerLoading"
                                :disabled="!isRegisterPasswordValid || !registerConfirmPassword || registerConfirmPassword !== registerPassword"
                            >
                                Create Account
                            </v-btn>
                        </template>
                    </v-card-actions>
                </v-card>
            </v-dialog>

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
import { ref, onMounted, computed, watch } from 'vue';
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

// Google OAuth state
const isGoogleLoading = ref(false);
const googleError = ref('');

// Forgot Password state
const showForgotPassword = ref(false);
const forgotPasswordEmail = ref('');
const forgotPasswordLoading = ref(false);
const forgotPasswordError = ref('');
const forgotFormRef = ref(null);
const otpFormRef = ref(null);
const newPasswordFormRef = ref(null);

// OTP-based reset state
const resetStep = ref(1); // 1: email, 2: OTP, 3: new password
const resetOtp = ref('');
const resetToken = ref('');
const newPassword = ref('');
const confirmNewPassword = ref('');
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
const resetComplete = ref(false);
const resendCooldown = ref(0);
let cooldownInterval = null;

// Registration state
const showRegister = ref(false);
const registerStep = ref(1); // 1: email, 2: OTP, 3: new password, 4: success
const registerEmail = ref('');
const registerOtp = ref('');
const registerToken = ref('');
const registerPassword = ref('');
const registerConfirmPassword = ref('');
const showRegisterPassword = ref(false);
const showRegisterConfirmPassword = ref(false);
const registerLoading = ref(false);
const registerError = ref('');
const registerFormRef = ref(null);
const registerOtpFormRef = ref(null);
const registerPasswordFormRef = ref(null);
const registerResendCooldown = ref(0);
let registerCooldownInterval = null;

// Validation rules
const rules = {
    required: (v) => !!v || 'This field is required',
    email: (v) => /.+@.+\..+/.test(v) || 'Please enter a valid email',
    minLength: (v) => (v && v.length >= 8) || 'Password must be at least 8 characters',
    hasUppercase: (v) => /[A-Z]/.test(v) || 'Must contain at least one uppercase letter',
    hasLowercase: (v) => /[a-z]/.test(v) || 'Must contain at least one lowercase letter',
    hasNumber: (v) => /[0-9]/.test(v) || 'Must contain at least one number',
    hasSpecial: (v) => /[!@#$%^&*(),.?":{}|<>]/.test(v) || 'Must contain at least one special character',
    passwordMatch: (v) => v === newPassword.value || 'Passwords do not match',
};

// Password strength checker
const passwordChecks = computed(() => ({
    length: newPassword.value.length >= 8,
    uppercase: /[A-Z]/.test(newPassword.value),
    lowercase: /[a-z]/.test(newPassword.value),
    number: /[0-9]/.test(newPassword.value),
    special: /[!@#$%^&*(),.?":{}|<>]/.test(newPassword.value),
}));

const isPasswordValid = computed(() => {
    const checks = passwordChecks.value;
    return checks.length && checks.uppercase && checks.lowercase && checks.number && checks.special;
});

// Registration password strength checker
const registerPasswordChecks = computed(() => ({
    length: registerPassword.value.length >= 8,
    uppercase: /[A-Z]/.test(registerPassword.value),
    lowercase: /[a-z]/.test(registerPassword.value),
    number: /[0-9]/.test(registerPassword.value),
    special: /[!@#$%^&*(),.?":{}|<>]/.test(registerPassword.value),
}));

const isRegisterPasswordValid = computed(() => {
    const checks = registerPasswordChecks.value;
    return checks.length && checks.uppercase && checks.lowercase && checks.number && checks.special;
});

// Add SDCA email validation rule
rules.sdcaEmail = (v) => {
    if (!v) return true;
    const domain = v.split('@')[1]?.toLowerCase();
    return domain === 'sdca.edu.ph' || 'Only SDCA email addresses (@sdca.edu.ph) are allowed';
};

// Check for existing session on mount
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const logout = urlParams.get('logout');
    
    // Check for Google OAuth errors from backend
    const errors = page.props?.errors;
    if (errors?.google) {
        googleError.value = errors.google;
    }

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

// Handle Google OAuth login
const handleGoogleLogin = () => {
    isGoogleLoading.value = true;
    googleError.value = '';
    // Redirect to Google OAuth endpoint
    window.location.href = '/auth/google';
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

// Forgot Password handlers - OTP Based
const sendResetOtp = async () => {
    if (forgotFormRef.value) {
        const { valid } = await forgotFormRef.value.validate();
        if (!valid) return;
    }

    forgotPasswordError.value = '';
    forgotPasswordLoading.value = true;

    try {
        const response = await fetch('/api/auth/send-password-change-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                email: forgotPasswordEmail.value.trim(),
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            resetStep.value = 2;
            startResendCooldown();
            toastMessage.value = 'Verification code sent!';
            toastColor.value = 'success';
            showToast.value = true;
        } else {
            forgotPasswordError.value = data.message || 'Failed to send verification code. Please try again.';
        }
    } catch (err) {
        console.error('Send OTP error:', err);
        forgotPasswordError.value = 'An error occurred. Please try again later.';
    } finally {
        forgotPasswordLoading.value = false;
    }
};

const verifyResetOtp = async () => {
    if (resetOtp.value.length !== 6) {
        forgotPasswordError.value = 'Please enter the 6-digit code';
        return;
    }

    forgotPasswordError.value = '';
    forgotPasswordLoading.value = true;

    try {
        const response = await fetch('/api/auth/verify-password-change-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                email: forgotPasswordEmail.value.trim(),
                otp: resetOtp.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            resetToken.value = data.token;
            resetStep.value = 3;
            toastMessage.value = 'Code verified!';
            toastColor.value = 'success';
            showToast.value = true;
        } else {
            forgotPasswordError.value = data.message || 'Invalid verification code. Please try again.';
        }
    } catch (err) {
        console.error('Verify OTP error:', err);
        forgotPasswordError.value = 'An error occurred. Please try again later.';
    } finally {
        forgotPasswordLoading.value = false;
    }
};

const completePasswordReset = async () => {
    if (newPasswordFormRef.value) {
        const { valid } = await newPasswordFormRef.value.validate();
        if (!valid) return;
    }

    if (newPassword.value !== confirmNewPassword.value) {
        forgotPasswordError.value = 'Passwords do not match';
        return;
    }

    if (!isPasswordValid.value) {
        forgotPasswordError.value = 'Password does not meet all requirements';
        return;
    }

    forgotPasswordError.value = '';
    forgotPasswordLoading.value = true;

    try {
        const response = await fetch('/api/auth/complete-password-change', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                email: forgotPasswordEmail.value.trim(),
                token: resetToken.value,
                password: newPassword.value,
                password_confirmation: confirmNewPassword.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            resetComplete.value = true;
            toastMessage.value = 'Password reset successful!';
            toastColor.value = 'success';
            showToast.value = true;
        } else {
            forgotPasswordError.value = data.message || 'Failed to reset password. Please try again.';
        }
    } catch (err) {
        console.error('Password reset error:', err);
        forgotPasswordError.value = 'An error occurred. Please try again later.';
    } finally {
        forgotPasswordLoading.value = false;
    }
};

const resendResetOtp = async () => {
    if (resendCooldown.value > 0) return;
    await sendResetOtp();
};

const startResendCooldown = () => {
    resendCooldown.value = 60;
    if (cooldownInterval) clearInterval(cooldownInterval);
    cooldownInterval = setInterval(() => {
        resendCooldown.value--;
        if (resendCooldown.value <= 0) {
            clearInterval(cooldownInterval);
            cooldownInterval = null;
        }
    }, 1000);
};

const closeForgotPassword = () => {
    showForgotPassword.value = false;
    forgotPasswordEmail.value = '';
    forgotPasswordError.value = '';
    resetStep.value = 1;
    resetOtp.value = '';
    resetToken.value = '';
    newPassword.value = '';
    confirmNewPassword.value = '';
    resetComplete.value = false;
    resendCooldown.value = 0;
    if (cooldownInterval) {
        clearInterval(cooldownInterval);
        cooldownInterval = null;
    }
};

// Registration handlers
const sendRegisterOtp = async () => {
    if (registerFormRef.value) {
        const { valid } = await registerFormRef.value.validate();
        if (!valid) return;
    }

    registerError.value = '';
    registerLoading.value = true;

    try {
        const response = await fetch('/api/auth/register-send-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                email: registerEmail.value.trim(),
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            registerStep.value = 2;
            startRegisterResendCooldown();
            toastMessage.value = 'Registration code sent to your email!';
            toastColor.value = 'success';
            showToast.value = true;
        } else {
            registerError.value = data.message || 'Failed to send registration code. Please try again.';
        }
    } catch (err) {
        console.error('Send register OTP error:', err);
        registerError.value = 'An error occurred. Please try again later.';
    } finally {
        registerLoading.value = false;
    }
};

const verifyRegisterOtp = async () => {
    if (registerOtp.value.length !== 6) {
        registerError.value = 'Please enter the 6-digit code';
        return;
    }

    registerError.value = '';
    registerLoading.value = true;

    try {
        const response = await fetch('/api/auth/register-verify-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                email: registerEmail.value.trim(),
                otp: registerOtp.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            registerToken.value = data.token;
            registerStep.value = 3;
            toastMessage.value = 'Code verified!';
            toastColor.value = 'success';
            showToast.value = true;
        } else {
            registerError.value = data.message || 'Invalid verification code. Please try again.';
        }
    } catch (err) {
        console.error('Verify register OTP error:', err);
        registerError.value = 'An error occurred. Please try again later.';
    } finally {
        registerLoading.value = false;
    }
};

const completeRegister = async () => {
    if (registerPasswordFormRef.value) {
        const { valid } = await registerPasswordFormRef.value.validate();
        if (!valid) return;
    }

    if (registerPassword.value !== registerConfirmPassword.value) {
        registerError.value = 'Passwords do not match';
        return;
    }

    if (!isRegisterPasswordValid.value) {
        registerError.value = 'Password does not meet all requirements';
        return;
    }

    registerError.value = '';
    registerLoading.value = true;

    try {
        const response = await fetch('/api/auth/register-complete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                email: registerEmail.value.trim(),
                token: registerToken.value,
                password: registerPassword.value,
                password_confirmation: registerConfirmPassword.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            registerStep.value = 4;
            toastMessage.value = 'Registration successful!';
            toastColor.value = 'success';
            showToast.value = true;
        } else {
            registerError.value = data.message || 'Failed to complete registration. Please try again.';
        }
    } catch (err) {
        console.error('Complete register error:', err);
        registerError.value = 'An error occurred. Please try again later.';
    } finally {
        registerLoading.value = false;
    }
};

const resendRegisterOtp = async () => {
    if (registerResendCooldown.value > 0) return;
    await sendRegisterOtp();
};

const startRegisterResendCooldown = () => {
    registerResendCooldown.value = 60;
    if (registerCooldownInterval) clearInterval(registerCooldownInterval);
    registerCooldownInterval = setInterval(() => {
        registerResendCooldown.value--;
        if (registerResendCooldown.value <= 0) {
            clearInterval(registerCooldownInterval);
            registerCooldownInterval = null;
        }
    }, 1000);
};

const closeRegister = () => {
    showRegister.value = false;
    registerEmail.value = '';
    registerError.value = '';
    registerStep.value = 1;
    registerOtp.value = '';
    registerToken.value = '';
    registerPassword.value = '';
    registerConfirmPassword.value = '';
    registerResendCooldown.value = 0;
    if (registerCooldownInterval) {
        clearInterval(registerCooldownInterval);
        registerCooldownInterval = null;
    }
};
</script>

<style scoped>
/* Left Panel Styles */
.left-panel {
    background: linear-gradient(135deg, #3674B5 0%, #2D5F96 50%, #1E4A7A 100%);
    position: relative;
    overflow: hidden;
}

.left-panel::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
    animation: pulse 15s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1) rotate(0deg); }
    50% { transform: scale(1.1) rotate(180deg); }
}

.logo-container {
    position: relative;
    z-index: 1;
}

.logo-image {
    width: 200px;
    height: 200px;
    object-fit: contain;
    filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.3));
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.brand-title {
    font-size: 3rem;
    font-weight: 800;
    letter-spacing: 4px;
    text-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 1;
}

.brand-subtitle {
    font-size: 1.1rem;
    letter-spacing: 8px;
    opacity: 0.9;
    position: relative;
    z-index: 1;
}

/* Right Panel (Form) Styles */
.right-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: linear-gradient(145deg, #f5f7fa 0%, #e4e8ec 100%);
}

@media (max-width: 1279px) {
    .right-panel {
        min-height: 100vh;
    }
}

/* Mobile Logo Styles */
.mobile-logo-section {
    padding-top: 40px;
    padding-bottom: 16px;
}

.mobile-logo {
    width: 80px;
    height: 80px;
    object-fit: contain;
}

.mobile-brand-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #3674B5;
    letter-spacing: 2px;
}

/* Text color utilities */
.text-white-darken-1 {
    color: rgba(255, 255, 255, 0.95) !important;
}

.text-white-darken-2 {
    color: rgba(255, 255, 255, 0.85) !important;
}

/* Login Card Styles */
.login-card {
    width: 100%;
    max-width: 420px;
    border-radius: 16px !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1) !important;
}

.login-card .v-card-title {
    padding: 24px 24px 8px 24px;
}

.login-card .v-card-text {
    padding: 16px 24px 24px 24px;
}

/* Form field improvements */
:deep(.v-text-field .v-field) {
    border-radius: 10px;
}

:deep(.v-text-field .v-field__outline) {
    --v-field-border-opacity: 0.3;
}

/* Button styles */
.login-btn {
    text-transform: none;
    font-weight: 600;
    font-size: 1rem;
    letter-spacing: 0.5px;
}

/* Forgot password link */
.forgot-link {
    color: #3674B5;
    text-decoration: none;
    font-size: 0.875rem;
    cursor: pointer;
    transition: color 0.2s ease;
}

.forgot-link:hover {
    color: #2D5F96;
    text-decoration: underline;
}

/* Step indicator styles */
.step-line {
    width: 40px;
    height: 3px;
    background-color: #e0e0e0;
    margin: 0 8px;
    border-radius: 2px;
    transition: background-color 0.3s ease;
}

.step-line.active {
    background-color: #3674B5;
}

/* OTP Input styling */
:deep(.v-otp-input) {
    justify-content: center;
}

:deep(.v-otp-input input) {
    font-size: 1.5rem;
    font-weight: 600;
}

/* Password requirements styling */
.password-requirements {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px;
}

.requirements-grid {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.requirement-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    color: #9e9e9e;
    transition: color 0.2s ease;
}

.requirement-item.met {
    color: #4caf50;
}

.requirement-item span {
    line-height: 1.2;
}

/* Google Sign-In Button */
.google-btn {
    text-transform: none !important;
    font-weight: 500;
    letter-spacing: 0;
    border: 2px solid #dadce0 !important;
    color: #3c4043 !important;
    background-color: #fff !important;
    min-height: 48px;
    border-radius: 8px !important;
    transition: all 0.2s ease;
}

.google-btn:hover {
    background-color: #f8f9fa !important;
    border-color: #c4c4c4 !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.google-btn .google-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.google-btn .google-btn-text {
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Mobile: Shorter text */
@media (max-width: 400px) {
    .google-btn .google-btn-text {
        font-size: 0.875rem;
    }
}

/* Divider Styles */
.divider-container {
    display: flex;
    align-items: center;
    margin-bottom: 16px;
    gap: 12px;
}

.divider-line {
    flex: 1;
}

.divider-text {
    color: #9e9e9e;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Registration Success State */
.success-state {
    position: relative;
    z-index: 10;
    background-color: white;
    min-height: 200px;
}
</style>
