<template>
    <div class="bottom-nav">
        <button 
            :class="['nav-item', isActive('/rescuer/dashboard') ? 'active' : '']" 
            @click="navigateTo('/rescuer/dashboard')"
        >
            <v-icon size="24">mdi-home</v-icon>
            <span class="nav-label">Home</span>
        </button>
        <button 
            :class="['nav-item', isActive('/rescuer/chats') ? 'active' : '']" 
            @click="navigateTo('/rescuer/chats')"
        >
            <v-icon size="24">mdi-email-outline</v-icon>
            <span class="nav-label">Messages</span>
        </button>
        <button 
            class="nav-item" 
            @click.stop="$emit('open-notifications')"
        >
            <v-badge
                :content="notificationCount"
                :model-value="notificationCount > 0"
                color="error"
                offset-x="-2"
                offset-y="2"
            >
                <v-icon size="24">mdi-bell-outline</v-icon>
            </v-badge>
            <span class="nav-label">Alerts</span>
        </button>
        <button 
            :class="['nav-item', isActive('/rescuer/profile') ? 'active' : '']" 
            @click="navigateTo('/rescuer/profile')"
        >
            <v-icon size="24">mdi-account-outline</v-icon>
            <span class="nav-label">Profile</span>
        </button>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

defineProps({
    notificationCount: {
        type: Number,
        default: 0,
    },
});

defineEmits(['open-notifications']);

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
    height: 64px;
    background: linear-gradient(135deg, #37474f 0%, #455a64 100%);
    border-radius: 20px 20px 0 0;
    display: none; /* Hidden by default, shown via media query */
    justify-content: space-around;
    align-items: center;
    padding: 0 8px;
    padding-bottom: env(safe-area-inset-bottom, 0);
    box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
    z-index: 1000;
    touch-action: none;
}

.nav-item {
    background: none;
    border: none;
    color: rgba(255,255,255,0.6);
    cursor: pointer;
    padding: 8px 16px;
    border-radius: 12px;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.nav-item:hover {
    color: white;
    background: rgba(255,255,255,0.1);
}

.nav-item.active {
    color: white;
    background: rgba(255,255,255,0.15);
}

.nav-label {
    font-size: 0.65rem;
    font-weight: 500;
    margin-top: 2px;
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
        height: 60px;
        border-radius: 16px 16px 0 0;
    }
    
    .nav-item {
        padding: 6px 12px;
    }
    
    .nav-label {
        font-size: 0.6rem;
    }
}

@media (max-width: 360px) {
    .nav-item {
        padding: 6px 8px;
    }
    
    .nav-label {
        font-size: 0.55rem;
    }
}
</style>
