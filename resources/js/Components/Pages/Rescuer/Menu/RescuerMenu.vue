<template>
    <v-navigation-drawer v-model="drawerModel" temporary>
        <v-list-item class="pa-4">
            <template v-slot:prepend>
                <v-avatar color="primary" size="48">
                    <v-img v-if="profilePictureUrl" :src="profilePictureUrl" cover />
                    <span v-else class="text-h6 text-white">{{ getInitials(userName) }}</span>
                </v-avatar>
            </template>
            <v-list-item-title class="font-weight-bold">{{ userName }}</v-list-item-title>
            <v-list-item-subtitle>Rescuer</v-list-item-subtitle>
        </v-list-item>

        <v-divider />

        <v-list density="compact" nav>
            <v-list-item
                prepend-icon="mdi-view-dashboard"
                title="Dashboard"
                @click="navigateTo('/rescuer/dashboard')"
            />
            <v-list-item
                prepend-icon="mdi-message-text"
                title="Messages"
                @click="navigateTo('/rescuer/chats')"
            />
            <v-list-item
                prepend-icon="mdi-account"
                title="Profile"
                @click="navigateTo('/rescuer/profile')"
            />
        </v-list>

        <template v-slot:append>
            <div class="pa-4">
                <v-btn
                    block
                    color="error"
                    variant="tonal"
                    prepend-icon="mdi-logout"
                    @click="handleLogout"
                >
                    Logout
                </v-btn>
            </div>
        </template>
    </v-navigation-drawer>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { getProfilePictureUrl } from '@/Composables/useApi';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const page = usePage();

// Get authenticated user from Inertia shared data
const authUser = computed(() => page.props?.auth?.user);

const drawerModel = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

// User data - prefer Inertia auth, fallback to localStorage
const userData = ref(null);

const userName = computed(() => {
    // Use Inertia auth first
    if (authUser.value?.first_name) {
        return `${authUser.value.first_name} ${authUser.value.last_name || ''}`.trim();
    }
    if (userData.value?.firstName) {
        return `${userData.value.firstName} ${userData.value.lastName || ''}`.trim();
    }
    return userData.value?.name || 'Rescuer';
});

const profilePictureUrl = computed(() => {
    const picturePath = authUser.value?.profile_picture || userData.value?.profile_picture || userData.value?.avatar;
    if (!picturePath) return null;
    if (picturePath.startsWith('http') || picturePath.startsWith('data:')) return picturePath;
    return getProfilePictureUrl(picturePath);
});

const getInitials = (name) => {
    if (!name) return 'R';
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const navigateTo = (path) => {
    drawerModel.value = false;
    router.visit(path);
};

onMounted(() => {
    // Check if user is authenticated via Inertia
    if (!authUser.value) {
        // No authenticated user, redirect to login
        router.visit('/login');
        return;
    }
    
    // Also keep localStorage in sync for components that need it
    const storedData = localStorage.getItem('userData');
    if (storedData) {
        userData.value = JSON.parse(storedData);
    } else if (authUser.value) {
        // Populate localStorage from Inertia auth
        const user = authUser.value;
        const data = {
            id: user.id,
            email: user.email,
            firstName: user.first_name || '',
            lastName: user.last_name || '',
            role: user.role || 'rescuer',
            isAdmin: user.isAdmin === true || user.isAdmin === 1 || user.role === 'admin',
            profile_picture: user.profile_picture || null,
            contact_number: user.contact_number || '',
        };
        localStorage.setItem('userData', JSON.stringify(data));
        userData.value = data;
    }
});

const handleLogout = async () => {
    // Clear all localStorage data
    localStorage.removeItem('userData');
    localStorage.removeItem('authToken');
    localStorage.removeItem('token');
    localStorage.removeItem('lastRescueCode');
    localStorage.removeItem('lastRescueRequestId');
    localStorage.removeItem('lastRescueRequestTime');
    localStorage.removeItem('conversationId');
    localStorage.removeItem('chatId');

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        await fetch('/logout', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            credentials: 'include'
        });
    } catch (e) {
        console.error('Logout error:', e);
    }
    
    // Force redirect to login
    window.location.href = '/login';
};
</script>
