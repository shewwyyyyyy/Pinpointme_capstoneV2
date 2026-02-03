<template>
    <v-app class="bg-user-gradient-light">
        <!-- Header - like Rescuer's Chats -->
        <div class="messages-header">
            <div class="header-content">
                <v-btn icon variant="text" @click="drawer = true" class="menu-btn desktop-only">
                    <v-icon>mdi-menu</v-icon>
                </v-btn>
                <div class="header-title">
                    <h1>Messages</h1>
                    <p v-if="totalUnreadCount > 0">{{ totalUnreadCount }} unread message{{ totalUnreadCount !== 1 ? 's' : '' }}</p>
                </div>
                <v-btn 
                    icon 
                    variant="text" 
                    class="refresh-btn"
                    :loading="refreshing"
                    @click="refreshChats"
                >
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
            </div>
        </div>

        <!-- Navigation Drawer - Desktop only -->
        <UserMenu v-model="drawer" />

        <v-main class="messages-main">
            <!-- Loading State -->
            <div v-if="loading" class="d-flex justify-center align-center" style="min-height: 60vh;">
                <v-progress-circular indeterminate color="primary" size="64" />
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredChats.length === 0" class="pa-4">
                <v-card variant="tonal" color="grey-lighten-3" class="text-center pa-8">
                    <v-icon size="64" color="grey">mdi-message-text-outline</v-icon>
                    <div class="text-h6 mt-4 text-grey-darken-1">No Messages</div>
                    <div class="text-body-2 text-grey mt-2">
                        Messages from rescuers will appear here when assigned to your rescue request
                    </div>
                </v-card>
            </div>

            <!-- Chat List - like Rescuer's Chats -->
            <v-list v-else class="pa-0">
                <template v-for="(chat, index) in filteredChats" :key="chat.id">
                    <v-list-item
                        class="py-3"
                        @click="openChat(chat)"
                    >
                        <template v-slot:prepend>
                            <v-badge
                                :model-value="chat.unread_count > 0"
                                color="error"
                                dot
                                location="bottom end"
                                offset-x="3"
                                offset-y="3"
                            >
                                <v-avatar
                                    :color="!chat.other_user_picture ? 'primary' : undefined"
                                    size="48"
                                >
                                    <v-img 
                                        v-if="chat.other_user_picture"
                                        :src="chat.other_user_picture"
                                        cover
                                    />
                                    <span v-else class="text-white">{{ getInitials(chat.other_user_name) }}</span>
                                </v-avatar>
                            </v-badge>
                        </template>

                        <v-list-item-title class="font-weight-medium">
                            {{ chat.other_user_name }}
                        </v-list-item-title>
                        <v-list-item-subtitle class="d-flex align-center">
                            <span :class="{ 'font-weight-medium': chat.unread_count > 0 }">
                                {{ chat.last_message }}
                            </span>
                        </v-list-item-subtitle>

                        <template v-slot:append>
                            <div class="text-right">
                                <div class="text-caption text-grey mb-1">
                                    {{ formatTime(chat.last_message_time) }}
                                </div>
                                <v-chip
                                    v-if="chat.rescue_status"
                                    size="x-small"
                                    :color="getStatusColor(chat.rescue_status)"
                                    variant="tonal"
                                >
                                    {{ formatStatus(chat.rescue_status) }}
                                </v-chip>
                                <v-badge
                                    v-else-if="chat.unread_count > 0"
                                    :content="chat.unread_count"
                                    color="primary"
                                    inline
                                />
                            </div>
                        </template>
                    </v-list-item>
                    <v-divider v-if="index < filteredChats.length - 1" />
                </template>
            </v-list>

            <!-- Pull to refresh indicator -->
            <div v-if="refreshing" class="d-flex justify-center py-4">
                <v-progress-circular indeterminate color="primary" size="24" />
            </div>

            <!-- Toast Notification -->
            <v-snackbar v-model="showToast" :color="toastColor" location="top">
                {{ toastMessage }}
                <template v-slot:actions>
                    <v-btn variant="text" @click="showToast = false">Close</v-btn>
                </template>
            </v-snackbar>
            
            <!-- Popup Notification Alert -->
            <NotificationPopup
                :show="popupAlert.show"
                :title="popupAlert.title"
                :message="popupAlert.message"
                :type="popupAlert.type"
                :icon="popupAlert.icon"
                @close="popupAlert.show = false"
                @click="handleNotificationClick"
            />
        </v-main>
        
        <!-- Bottom Navigation for Mobile -->
        <UserBottomNav 
            :notification-count="0" 
            :message-count="totalUnreadCount"
        />
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { getConversations, getProfilePictureUrl } from '@/Composables/useApi';
import UserMenu from '@/Components/Pages/User/Menu/UserMenu.vue';
import UserBottomNav from '@/Components/Pages/User/Menu/UserBottomNav.vue';
import NotificationPopup from '@/Components/NotificationPopup.vue';

// State
const drawer = ref(false);
const chats = ref([]);
const loading = ref(true);
const refreshing = ref(false);
const searchText = ref('');
const pollingInterval = ref(null);
const consecutiveErrors = ref(0);
const MAX_CONSECUTIVE_ERRORS = 3;

// Popup alert state (for notifications from other sources if needed)
const popupAlert = ref({
    show: false,
    title: '',
    message: '',
    type: 'info',
    icon: 'mdi-information',
});

// Toast
const showToast = ref(false);
const toastMessage = ref('');
const toastColor = ref('success');

// Current user
const currentUserId = computed(() => {
    const userData = localStorage.getItem('userData');
    if (userData) {
        return JSON.parse(userData).id;
    }
    return null;
});

// Filtered chats based on search
const filteredChats = computed(() => {
    let result = chats.value;
    if (searchText.value) {
        result = result.filter((chat) =>
            chat.other_user_name.toLowerCase().includes(searchText.value.toLowerCase())
        );
    }
    // Sort by last message time (most recent first)
    return result.sort((a, b) => {
        const timeA = a.last_message_time ? new Date(a.last_message_time) : new Date(0);
        const timeB = b.last_message_time ? new Date(b.last_message_time) : new Date(0);
        return timeB - timeA;
    });
});

// Handle notification click
const handleNotificationClick = () => {
    popupAlert.value.show = false;
};

// Note: Chat notifications are handled within the Chat module itself
// Removed triggerNewMessageNotification to avoid duplicate notifications

onMounted(async () => {
    await fetchChats(true);
    
    // Poll for new messages every 10 seconds
    pollingInterval.value = setInterval(() => fetchChats(false), 10000);
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
});

const fetchChats = async (showErrorToast = false) => {
    const isInitialLoad = loading.value;
    
    try {
        const userId = currentUserId.value;
        
        // Store previous unread counts for comparison
        const previousUnreadCounts = {};
        chats.value.forEach(c => {
            previousUnreadCounts[c.id] = c.unread_count || 0;
        });
        const wasEmpty = chats.value.length === 0;
        
        const response = await getConversations(userId);
        const data = response.data || response;
        const conversations = Array.isArray(data) ? data : (data?.data || []);

        // Reset error count on success
        consecutiveErrors.value = 0;

        const newChats = conversations.map((conv) => {
            // Find the CURRENT user's participant (for unread_count)
            const myParticipant = conv.participants?.find(
                (p) => String(p.user_id) === String(currentUserId.value)
            );
            
            // Find the other participant (the rescuer for user inbox)
            const otherParticipant = conv.participants?.find(
                (p) => String(p.user_id) !== String(currentUserId.value)
            );
            
            const otherUser = otherParticipant?.user;
            const rescueRequest = conv.rescue_request;

            return {
                id: conv.id,
                chat_id: conv.id,
                rescue_request_id: rescueRequest?.id,
                other_user_id: otherParticipant?.user_id || '',
                other_user_name: otherUser
                    ? `${otherUser.first_name || ''} ${otherUser.last_name || ''}`.trim() || 'Rescuer'
                    : 'Rescuer',
                other_user_picture: otherUser?.profile_picture 
                    ? getProfilePictureUrl(otherUser.profile_picture)
                    : null,
                other_user_role: otherUser?.role || 'rescuer',
                last_message: conv.last_message?.content || 'No messages yet',
                last_message_time: conv.last_message?.timestamp || conv.updated_at,
                // Use CURRENT user's unread_count (what I haven't read)
                unread_count: myParticipant?.unread_count || 0,
                rescue_status: rescueRequest?.status,
                created_at: conv.created_at,
            };
        });
        
        // Note: Chat notifications are handled within the Chat module itself
        // We only update the list here, no popup notifications needed
        
        chats.value = newChats;
    } catch (err) {
        console.error('Error fetching chats:', err);
        consecutiveErrors.value++;
        
        // Only show error toast if explicitly requested (manual refresh) or after multiple consecutive failures
        if (showErrorToast || consecutiveErrors.value >= MAX_CONSECUTIVE_ERRORS) {
            toastMessage.value = 'Failed to load messages';
            toastColor.value = 'error';
            showToast.value = true;
            consecutiveErrors.value = 0; // Reset after showing error
        }
    } finally {
        loading.value = false;
        refreshing.value = false;
    }
};

// Manual refresh with loading indicator
const refreshChats = async () => {
    refreshing.value = true;
    await fetchChats(true);
};

const openChat = (chat) => {
    // Navigate to chat using conversation ID
    router.visit(`/user/chat/${chat.id}`);
};

const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const formatTime = (timestamp) => {
    if (!timestamp) return '';

    const now = new Date();
    const messageDate = new Date(timestamp);

    // If today, show time only
    if (messageDate.toDateString() === now.toDateString()) {
        return messageDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // If this year, show month and day
    if (messageDate.getFullYear() === now.getFullYear()) {
        return messageDate.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }

    // Otherwise show full date
    return messageDate.toLocaleDateString();
};

const formatStatus = (status) => {
    const labels = {
        'pending': 'Pending',
        'accepted': 'Active',
        'en_route': 'En Route',
        'on_scene': 'On Scene',
        'rescued': 'Completed',
        'cancelled': 'Cancelled',
    };
    return labels[status] || status;
};

const getStatusColor = (status) => {
    const colors = {
        'pending': 'warning',
        'accepted': 'info',
        'en_route': 'primary',
        'on_scene': 'success',
        'rescued': 'success',
        'cancelled': 'grey',
    };
    return colors[status] || 'grey';
};

// Total unread messages count
const totalUnreadCount = computed(() => {
    return chats.value.reduce((acc, chat) => acc + (chat.unread_count || 0), 0);
});
</script>

<style scoped>
/* Header - like Rescuer's Chats */
.messages-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #3674B5;
    padding: env(safe-area-inset-top, 0) 0 0 0;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    gap: 12px;
}

.menu-btn, .refresh-btn {
    color: white;
}

.header-title {
    flex: 1;
    text-align: center;
}

.header-title h1 {
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
    margin: 0;
}

.header-title p {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
}

/* Main Content */
.messages-main {
    padding-bottom: 80px;
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

@media (min-width: 1024px) {
    /* Show menu button on desktop, no margin needed since drawer is temporary */
    .desktop-only {
        display: flex;
    }
    
    /* No bottom padding needed on desktop */
    .messages-main {
        padding-bottom: 20px;
    }
}

/* Constrain list width for better readability */
.v-list {
    max-width: 800px;
    margin: 0 auto;
}

.v-list-item {
    min-height: 72px;
}

/* Empty state and loading centered */
.pa-4 {
    max-width: 800px;
    margin: 0 auto;
}
</style>
