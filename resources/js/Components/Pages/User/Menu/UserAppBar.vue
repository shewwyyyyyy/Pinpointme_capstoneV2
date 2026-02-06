<template>
    <div class="user-app-bar">
        <div class="app-bar-content">
            <!-- Left: Back button or Profile Avatar + Name -->
            <div class="bar-left">
                <v-btn v-if="showBack" icon variant="text" @click="goBack" class="bar-btn">
                    <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
                <v-btn v-else icon variant="text" @click="$emit('toggle-drawer')" class="bar-btn desktop-only">
                    <v-icon>mdi-menu</v-icon>
                </v-btn>

                <!-- Profile Avatar + Name -->
                <div class="profile-section" data-wizard="profile" @click="navigateTo('/user/profile')">
                    <div class="avatar-glow-wrapper">
                        <v-avatar 
                            size="42" 
                            :color="!profilePictureUrl ? 'white' : undefined"
                            class="profile-avatar"
                        >
                            <v-img v-if="profilePictureUrl" :src="profilePictureUrl" cover />
                            <span v-else class="profile-initials">{{ userInitials }}</span>
                        </v-avatar>
                        <div class="avatar-status-dot"></div>
                    </div>
                    <div class="profile-info">
                        <span class="profile-greeting">Hello <span class="wave-emoji">👋</span></span>
                        <span class="profile-name">{{ userFullName }}</span>
                    </div>
                </div>
            </div>

            <!-- Right: Actions -->
            <div class="bar-actions">
                <!-- Notification Bell -->
                <v-btn icon variant="text" class="bar-btn" data-wizard="notifications" @click="navigateTo('/user/notifications')">
                    <v-badge 
                        :content="notificationCount" 
                        :model-value="notificationCount > 0" 
                        color="error" 
                        overlap
                        :max="99"
                    >
                        <v-icon>mdi-bell-outline</v-icon>
                    </v-badge>
                </v-btn>

                <!-- Extra action slot (e.g., help) -->
                <slot name="actions"></slot>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { getProfilePictureUrl as getProfilePicUrl } from '@/Composables/useApi';

const props = defineProps({
    title: {
        type: String,
        default: 'PinPointMe',
    },
    subtitle: {
        type: String,
        default: '',
    },
    showBack: {
        type: Boolean,
        default: false,
    },
    notificationCount: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['toggle-drawer', 'go-back']);

const page = usePage();
const authUser = computed(() => page.props?.auth?.user);

// User data - prefer Inertia auth, fallback to localStorage
const userData = ref(null);

const userFirstName = computed(() => {
    if (authUser.value?.first_name) {
        return authUser.value.first_name;
    }
    if (userData.value?.firstName) {
        return userData.value.firstName;
    }
    return 'User';
});

const userFullName = computed(() => {
    const first = authUser.value?.first_name || userData.value?.firstName || '';
    const last = authUser.value?.last_name || userData.value?.lastName || '';
    const full = `${first} ${last}`.trim();
    return full || 'User';
});

const userInitials = computed(() => {
    const first = authUser.value?.first_name?.[0] || userData.value?.firstName?.[0] || '';
    const last = authUser.value?.last_name?.[0] || userData.value?.lastName?.[0] || '';
    return (first + last).toUpperCase() || 'U';
});

const profilePictureUrl = computed(() => {
    const picturePath = authUser.value?.profile_picture || userData.value?.profile_picture || userData.value?.avatar;
    if (!picturePath) return null;
    if (picturePath.startsWith('http') || picturePath.startsWith('data:')) return picturePath;
    return getProfilePicUrl(picturePath);
});

const navigateTo = (path) => {
    router.visit(path);
};

const goBack = () => {
    emit('go-back');
};

onMounted(() => {
    const storedUser = localStorage.getItem('userData');
    if (storedUser) {
        userData.value = JSON.parse(storedUser);
    }
});
</script>

<style scoped>
.user-app-bar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #3674B5;
    padding: env(safe-area-inset-top, 0) 0 0 0;
}

.app-bar-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    gap: 8px;
}

.bar-left {
    display: flex;
    align-items: center;
    gap: 4px;
    flex: 1;
    min-width: 0;
}

.bar-btn {
    color: white;
    flex-shrink: 0;
}

.bar-actions {
    display: flex;
    align-items: center;
    gap: 2px;
    flex-shrink: 0;
}

/* Profile Section - Left side */
.profile-section {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    padding: 6px 14px 6px 6px;
    border-radius: 28px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    min-width: 0;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.profile-section:hover {
    background: rgba(255, 255, 255, 0.18);
    border-color: rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

.profile-section:active {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(0);
}

/* Avatar glow wrapper */
.avatar-glow-wrapper {
    position: relative;
    flex-shrink: 0;
}

.profile-avatar {
    border: 2.5px solid rgba(255, 255, 255, 0.85);
    flex-shrink: 0;
    box-shadow: 0 0 12px rgba(223, 169, 44, 0.35), 0 2px 8px rgba(0, 0, 0, 0.15);
}

.avatar-status-dot {
    position: absolute;
    bottom: 1px;
    right: 1px;
    width: 10px;
    height: 10px;
    background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);
    border-radius: 50%;
    border: 2px solid #3674B5;
    z-index: 2;
}

.profile-initials {
    font-size: 0.85rem;
    font-weight: 800;
    color: #3674B5;
    letter-spacing: 0.5px;
}

.profile-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
    line-height: 1.25;
}

.profile-greeting {
    font-size: 0.72rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.85);
    letter-spacing: 0.3px;
    display: flex;
    align-items: center;
    gap: 3px;
}

.wave-emoji {
    display: inline-block;
    font-size: 0.8rem;
    animation: wave 2.5s ease-in-out infinite;
    transform-origin: 70% 70%;
}

@keyframes wave {
    0%, 100% { transform: rotate(0deg); }
    10% { transform: rotate(14deg); }
    20% { transform: rotate(-8deg); }
    30% { transform: rotate(14deg); }
    40% { transform: rotate(-4deg); }
    50% { transform: rotate(10deg); }
    60% { transform: rotate(0deg); }
}

.profile-name {
    font-size: 1.05rem;
    font-weight: 800;
    color: white;
    white-space: nowrap;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    letter-spacing: 0.3px;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}

/* Desktop only visibility */
.desktop-only {
    display: flex;
}

@media (max-width: 1023px) {
    .desktop-only {
        display: none !important;
    }
}

/* Mobile adjustments */
@media (max-width: 600px) {
    .app-bar-content {
        padding: 8px 8px;
        gap: 4px;
    }
    
    .profile-name {
        max-width: 150px;
        font-size: 1rem;
    }
    
    .profile-section {
        padding: 5px 10px 5px 5px;
        gap: 10px;
    }
    
    .profile-avatar {
        width: 38px !important;
        height: 38px !important;
    }
}

@media (max-width: 359px) {
    .profile-name {
        max-width: 100px;
        font-size: 0.85rem;
    }
    
    .profile-avatar {
        width: 34px !important;
        height: 34px !important;
    }
    
    .profile-greeting {
        font-size: 0.65rem;
    }
    
    .profile-section {
        padding: 4px 8px 4px 4px;
        gap: 8px;
    }
}
</style>
