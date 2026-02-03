<template>
    <div class="bottom-nav">
        <button 
            :class="['nav-item', isActive('/rescuer/dashboard') ? 'active' : '']" 
            @click="navigateTo('/rescuer/dashboard')"
        >
            <div class="nav-icon-wrapper">
                <v-icon size="22">{{ isActive('/rescuer/dashboard') ? 'mdi-home' : 'mdi-home-outline' }}</v-icon>
            </div>
            <span class="nav-label">Home</span>
            <div v-if="isActive('/rescuer/dashboard')" class="active-indicator"></div>
        </button>
        
        <button 
            :class="['nav-item', isActive('/rescuer/chats') ? 'active' : '', hasMessages ? 'has-messages' : '']" 
            @click="navigateTo('/rescuer/chats')"
        >
            <div class="nav-icon-wrapper messages-wrapper">
                <v-icon size="22">{{ isActive('/rescuer/chats') ? 'mdi-message' : 'mdi-message-outline' }}</v-icon>
                <transition name="bounce">
                    <span v-if="messageCount > 0" class="message-badge">
                        {{ messageCount > 99 ? '99+' : messageCount }}
                    </span>
                </transition>
                <span v-if="hasMessages && !isActive('/rescuer/chats')" class="pulse-ring message-pulse"></span>
            </div>
            <span class="nav-label">Messages</span>
            <div v-if="isActive('/rescuer/chats')" class="active-indicator"></div>
        </button>
        
        <button 
            :class="['nav-item', isActive('/rescuer/notifications') ? 'active' : '', hasNotifications ? 'has-alerts' : '']" 
            @click="navigateTo('/rescuer/notifications')"
        >
            <div class="nav-icon-wrapper alerts-wrapper">
                <v-icon size="22">{{ isActive('/rescuer/notifications') ? 'mdi-bell' : 'mdi-bell-outline' }}</v-icon>
                <transition name="bounce">
                    <span v-if="notificationCount > 0" class="notification-badge">
                        {{ notificationCount > 99 ? '99+' : notificationCount }}
                    </span>
                </transition>
                <span v-if="hasNotifications && !isActive('/rescuer/notifications')" class="pulse-ring"></span>
            </div>
            <span class="nav-label">Alerts</span>
            <div v-if="isActive('/rescuer/notifications')" class="active-indicator"></div>
        </button>
        
        <button 
            :class="['nav-item', isActive('/rescuer/profile') ? 'active' : '']" 
            @click="navigateTo('/rescuer/profile')"
        >
            <div class="nav-icon-wrapper">
                <v-icon size="22">{{ isActive('/rescuer/profile') ? 'mdi-account' : 'mdi-account-outline' }}</v-icon>
            </div>
            <span class="nav-label">Profile</span>
            <div v-if="isActive('/rescuer/profile')" class="active-indicator"></div>
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue';
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

const hasNotifications = computed(() => props.notificationCount > 0);
const hasMessages = computed(() => props.messageCount > 0);

const navigateTo = (path) => {
    router.visit(path);
};

const isActive = (path) => {
    if (typeof window !== 'undefined') {
        return window.location.pathname === path;
    }
    return false;
};
</script>

<style scoped>
/* Bottom Navigation - Only visible on mobile/tablet */
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 70px;
    background: var(--ppm-glass-bg, rgba(255, 255, 255, 0.95));
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 24px 24px 0 0;
    display: none;
    justify-content: space-around;
    align-items: center;
    padding: 0 12px;
    padding-bottom: env(safe-area-inset-bottom, 0);
    box-shadow: 0 -4px 30px rgba(0, 0, 0, 0.08), 
                0 -1px 0 var(--ppm-glass-border, rgba(161, 227, 249, 0.3));
    z-index: 1000;
    border-top: 1px solid var(--ppm-glass-border, rgba(161, 227, 249, 0.2));
}

.nav-item {
    position: relative;
    background: none;
    border: none;
    color: var(--ppm-text-muted, #78909c);
    cursor: pointer;
    padding: 8px 16px;
    border-radius: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    min-width: 64px;
}

.nav-item:active {
    transform: scale(0.92);
}

.nav-item.active {
    color: var(--ppm-primary, #1976D2);
}

.nav-item.active .nav-icon-wrapper {
    background: linear-gradient(135deg, rgba(161, 227, 249, 0.3) 0%, rgba(209, 248, 239, 0.3) 100%);
    transform: translateY(-2px);
}

.nav-icon-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 32px;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.active-indicator {
    position: absolute;
    bottom: 2px;
    width: 4px;
    height: 4px;
    background: linear-gradient(135deg, var(--ppm-primary, #1976D2), var(--ppm-primary-light, #42A5F5));
    border-radius: 50%;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0); }
    to { opacity: 1; transform: scale(1); }
}

.nav-label {
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}

/* Alerts button special styling */
.nav-item.has-alerts .nav-icon-wrapper {
    color: #f44336;
}

/* Messages button special styling */
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
    right: -4px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    background: linear-gradient(135deg, #f44336 0%, #e53935 100%);
    color: white;
    font-size: 0.6rem;
    font-weight: 700;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(244, 67, 54, 0.4);
    border: 2px solid white;
}

.message-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    background: linear-gradient(135deg, #3674B5 0%, #2d5f96 100%);
    color: white;
    font-size: 0.6rem;
    font-weight: 700;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(54, 116, 181, 0.4);
    border: 2px solid white;
}

.pulse-ring {
    position: absolute;
    top: -2px;
    right: -2px;
    width: 22px;
    height: 22px;
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
    0% {
        transform: scale(0);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
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
        padding: 0 8px;
    }
    
    .nav-item {
        padding: 6px 12px;
        min-width: 56px;
    }
    
    .nav-icon-wrapper {
        width: 36px;
        height: 28px;
    }
    
    .nav-label {
        font-size: 0.6rem;
    }
}

@media (max-width: 360px) {
    .nav-item {
        padding: 6px 8px;
        min-width: 48px;
    }
    
    .nav-label {
        font-size: 0.55rem;
    }
}
</style>
