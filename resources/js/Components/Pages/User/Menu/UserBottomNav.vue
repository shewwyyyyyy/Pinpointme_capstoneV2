<template>
    <div class="bottom-nav-container">
        <!-- Quick Action Menu (Voice & Manual) - shown on long press -->
        <Transition name="fade-up">
            <div v-if="showQuickActions" class="quick-actions-overlay" @click="showQuickActions = false">
                <div class="quick-actions-menu" @click.stop>
                    <div class="quick-action-item" @click="handleVoiceInput">
                        <div class="quick-action-icon voice">
                            <v-icon size="24" color="white">mdi-microphone</v-icon>
                        </div>
                        <span>Voice Input</span>
                    </div>
                    <div class="quick-action-item" @click="handleManualInput">
                        <div class="quick-action-icon manual">
                            <v-icon size="24" color="white">mdi-form-textbox</v-icon>
                        </div>
                        <span>Manual Input</span>
                    </div>
                </div>
            </div>
        </Transition>

        <div class="bottom-nav">
            <!-- Left Side Navigation -->
            <button 
                :class="['nav-item', isActive('/user/inbox') ? 'active' : '', messageCount > 0 ? 'has-messages' : '']"
                @click="navigateTo('/user/inbox')"
            >
                <div class="nav-icon-wrapper messages-wrapper">
                    <v-icon size="22">{{ isActive('/user/inbox') ? 'mdi-message' : 'mdi-message-outline' }}</v-icon>
                    <transition name="bounce">
                        <span v-if="messageCount > 0" class="message-badge">
                            {{ messageCount > 99 ? '99+' : messageCount }}
                        </span>
                    </transition>
                    <span v-if="messageCount > 0 && !isActive('/user/inbox')" class="pulse-ring message-pulse"></span>
                </div>
                <span class="nav-label">Messages</span>
                <div v-if="isActive('/user/inbox')" class="active-indicator"></div>
            </button>
            
            <button 
                :class="['nav-item', isActive('/user/notifications') ? 'active' : '', notificationCount > 0 ? 'has-alerts' : '']"
                @click="navigateTo('/user/notifications')"
            >
                <div class="nav-icon-wrapper alerts-wrapper">
                    <v-icon size="22">{{ isActive('/user/notifications') ? 'mdi-bell' : 'mdi-bell-outline' }}</v-icon>
                    <transition name="bounce">
                        <span v-if="notificationCount > 0" class="notification-badge">
                            {{ notificationCount > 99 ? '99+' : notificationCount }}
                        </span>
                    </transition>
                    <span v-if="notificationCount > 0 && !isActive('/user/notifications')" class="pulse-ring"></span>
                </div>
                <span class="nav-label">Alerts</span>
                <div v-if="isActive('/user/notifications')" class="active-indicator"></div>
            </button>

            <!-- Center Scan Button -->
            <div class="scan-button-container">
                <button 
                    class="scan-button"
                    :class="{ 'active': isActive('/user/scanner') }"
                    @click.prevent="handleScanClick"
                    @touchstart.prevent="startLongPress"
                    @touchend.prevent="endLongPress"
                    @touchcancel="cancelLongPress"
                >
                    <div class="scan-button-inner">
                        <v-icon size="28" color="white">mdi-qrcode-scan</v-icon>
                    </div>
                    <div class="scan-button-ring"></div>
                </button>
                <span class="scan-label">Scan</span>
            </div>

            <!-- Right Side Navigation -->
            <button 
                :class="['nav-item', isActive('/user/preventive-measures') ? 'active' : '']" 
                @click="navigateTo('/user/preventive-measures')"
            >
                <div class="nav-icon-wrapper">
                    <v-icon size="22">{{ isActive('/user/preventive-measures') ? 'mdi-shield-check' : 'mdi-shield-check-outline' }}</v-icon>
                </div>
                <span class="nav-label">Safety</span>
                <div v-if="isActive('/user/preventive-measures')" class="active-indicator"></div>
            </button>
            
            <button 
                :class="['nav-item', isActive('/user/profile') ? 'active' : '']" 
                @click="navigateTo('/user/profile')"
            >
                <div class="nav-icon-wrapper">
                    <v-icon size="22">{{ isActive('/user/profile') ? 'mdi-account' : 'mdi-account-outline' }}</v-icon>
                </div>
                <span class="nav-label">Profile</span>
                <div v-if="isActive('/user/profile')" class="active-indicator"></div>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    notificationCount: {
        type: Number,
        default: 0,
    },
    messageCount: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['voice-input', 'manual-input']);

const showQuickActions = ref(false);
let longPressTimer = null;

const navigateTo = (path) => {
    router.visit(path);
};

const handleScanClick = () => {
    // For desktop click - navigate directly to scanner with scan=true to auto-open camera
    if (!longPressTimer) {
        router.visit('/user/scanner?scan=true');
    }
};

const startLongPress = () => {
    longPressTimer = setTimeout(() => {
        showQuickActions.value = true;
        longPressTimer = null;
    }, 500);
};

const endLongPress = () => {
    if (longPressTimer) {
        // Quick tap - navigate to scanner with auto-open camera
        clearTimeout(longPressTimer);
        longPressTimer = null;
        router.visit('/user/scanner?scan=true');
    }
};

const cancelLongPress = () => {
    if (longPressTimer) {
        clearTimeout(longPressTimer);
        longPressTimer = null;
    }
};

const handleVoiceInput = () => {
    showQuickActions.value = false;
    emit('voice-input');
    // Navigate to scanner with voice mode
    router.visit('/user/scanner?mode=voice');
};

const handleManualInput = () => {
    showQuickActions.value = false;
    emit('manual-input');
    // Navigate to scanner with manual mode
    router.visit('/user/scanner?mode=manual');
};

const isActive = (path) => {
    if (typeof window !== 'undefined') {
        const currentPath = window.location.pathname;
        return currentPath === path || currentPath.startsWith(path + '/');
    }
    return false;
};
</script>

<style scoped>
/* Bottom Navigation Container */
.bottom-nav-container {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 1000;
}

/* Quick Actions Overlay */
.quick-actions-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1001;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding-bottom: 100px;
}

.quick-actions-menu {
    display: flex;
    gap: 24px;
    padding: 20px 32px;
    background: white;
    border-radius: 24px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.quick-action-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.quick-action-item:active {
    transform: scale(0.95);
}

.quick-action-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.quick-action-icon.voice {
    background: linear-gradient(135deg, #E91E63 0%, #F06292 100%);
}

.quick-action-icon.manual {
    background: linear-gradient(135deg, #FF9800 0%, #FFB74D 100%);
}

.quick-action-item span {
    font-size: 0.75rem;
    font-weight: 600;
    color: #333;
}

/* Fade up animation */
.fade-up-enter-active,
.fade-up-leave-active {
    transition: all 0.3s ease;
}

.fade-up-enter-from,
.fade-up-leave-to {
    opacity: 0;
}

.fade-up-enter-from .quick-actions-menu,
.fade-up-leave-to .quick-actions-menu {
    transform: translateY(20px);
}

/* Bottom Navigation */
.bottom-nav {
    height: 70px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 252, 251, 0.98) 100%);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 24px 24px 0 0;
    display: none;
    justify-content: space-around;
    align-items: center;
    padding: 0 8px;
    padding-bottom: env(safe-area-inset-bottom, 0);
    box-shadow: 0 -4px 30px rgba(0, 0, 0, 0.08), 
                0 -1px 0 rgba(54, 116, 181, 0.15);
    border-top: 1px solid rgba(54, 116, 181, 0.1);
}

.nav-item {
    position: relative;
    background: none;
    border: none;
    color: #78909c;
    cursor: pointer;
    padding: 6px 8px;
    border-radius: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    min-width: 52px;
}

.nav-item:active {
    transform: scale(0.92);
}

.nav-item.active {
    color: #3674B5;
}

.nav-item.active .nav-icon-wrapper {
    background: linear-gradient(135deg, rgba(54, 116, 181, 0.15) 0%, rgba(33, 150, 243, 0.15) 100%);
    transform: translateY(-2px);
}

.nav-icon-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 28px;
    border-radius: 10px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.active-indicator {
    position: absolute;
    bottom: 2px;
    width: 4px;
    height: 4px;
    background: linear-gradient(135deg, #3674B5, #2196F3);
    border-radius: 50%;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0); }
    to { opacity: 1; transform: scale(1); }
}

.nav-label {
    font-size: 0.6rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}

/* Center Scan Button */
.scan-button-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: -30px;
    z-index: 10;
}

.scan-button {
    position: relative;
    width: 60px;
    height: 60px;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: transparent;
    padding: 0;
}

.scan-button:active {
    transform: scale(0.95);
}

.scan-button-inner {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 50%, #42A5F5 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 20px rgba(54, 116, 181, 0.4),
                0 2px 8px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
    position: relative;
    z-index: 2;
}

.scan-button.active .scan-button-inner {
    background: linear-gradient(135deg, #2d5f96 0%, #1976D2 50%, #3674B5 100%);
}

.scan-button-ring {
    position: absolute;
    top: -4px;
    left: -4px;
    right: -4px;
    bottom: -4px;
    border-radius: 50%;
    border: 2px solid rgba(54, 116, 181, 0.3);
    animation: pulse-scan 2s ease-out infinite;
}

@keyframes pulse-scan {
    0% {
        transform: scale(1);
        opacity: 0.6;
    }
    100% {
        transform: scale(1.2);
        opacity: 0;
    }
}

.scan-label {
    font-size: 0.65rem;
    font-weight: 700;
    color: #3674B5;
    margin-top: 4px;
    letter-spacing: 0.02em;
}

/* Alerts & Messages styling */
.nav-item.has-alerts .nav-icon-wrapper {
    color: #f44336;
}

.nav-item.has-messages .nav-icon-wrapper {
    color: #3674B5;
}

.alerts-wrapper,
.messages-wrapper {
    position: relative;
}

.notification-badge {
    position: absolute;
    top: -4px;
    right: -6px;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    background: linear-gradient(135deg, #f44336 0%, #e53935 100%);
    color: white;
    font-size: 0.55rem;
    font-weight: 700;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(244, 67, 54, 0.4);
    border: 2px solid white;
}

.message-badge {
    position: absolute;
    top: -4px;
    right: -6px;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    background: linear-gradient(135deg, #3674B5 0%, #2d5f96 100%);
    color: white;
    font-size: 0.55rem;
    font-weight: 700;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(54, 116, 181, 0.4);
    border: 2px solid white;
}

.pulse-ring {
    position: absolute;
    top: -2px;
    right: -4px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: transparent;
    border: 2px solid #f44336;
    animation: pulse-ring 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.pulse-ring.message-pulse {
    border-color: #3674B5;
}

@keyframes pulse-ring {
    0% {
        transform: scale(0.8);
        opacity: 1;
    }
    100% {
        transform: scale(1.6);
        opacity: 0;
    }
}

/* Bounce animation for badge */
.bounce-enter-active {
    animation: bounce-in 0.4s;
}

.bounce-leave-active {
    animation: bounce-in 0.3s reverse;
}

@keyframes bounce-in {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* Show bottom nav on mobile and tablet (up to 1024px) */
@media (max-width: 1024px) {
    .bottom-nav {
        display: flex;
    }
}

/* Smaller screens adjustments */
@media (max-width: 600px) {
    .bottom-nav {
        height: 64px;
        border-radius: 20px 20px 0 0;
        padding: 0 4px;
    }
    
    .nav-item {
        padding: 4px 6px;
        min-width: 48px;
    }
    
    .nav-icon-wrapper {
        width: 32px;
        height: 24px;
    }
    
    .nav-label {
        font-size: 0.55rem;
    }
    
    .scan-button-container {
        margin-top: -26px;
    }
    
    .scan-button {
        width: 54px;
        height: 54px;
    }
    
    .scan-label {
        font-size: 0.6rem;
    }
    
    .quick-actions-overlay {
        padding-bottom: 90px;
    }
    
    .quick-actions-menu {
        gap: 20px;
        padding: 16px 24px;
    }
    
    .quick-action-icon {
        width: 50px;
        height: 50px;
    }
}

@media (max-width: 360px) {
    .nav-item {
        padding: 4px 4px;
        min-width: 44px;
    }
    
    .nav-label {
        font-size: 0.5rem;
    }
    
    .nav-icon-wrapper {
        width: 28px;
        height: 22px;
    }
    
    .nav-icon-wrapper .v-icon {
        font-size: 18px !important;
    }
    
    .scan-button {
        width: 50px;
        height: 50px;
    }
    
    .scan-button-container {
        margin-top: -24px;
    }
}
</style>
