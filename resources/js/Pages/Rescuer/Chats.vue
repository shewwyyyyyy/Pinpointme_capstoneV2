<template>
    <v-app class="bg-user-gradient-light">
        <!-- Header -->
        <div class="messages-header">
            <div class="header-content">
                <v-btn icon variant="text" @click="drawer = true" class="menu-btn desktop-only">
                    <v-icon>mdi-menu</v-icon>
                </v-btn>
                <div class="header-title">
                    <h1>Messages</h1>
                    <p v-if="unreadCount > 0">{{ unreadCount }} unread message{{ unreadCount !== 1 ? 's' : '' }}</p>
                </div>
                <v-btn 
                    icon 
                    variant="text" 
                    class="refresh-btn"
                    :loading="refreshing"
                    @click="refreshConversations"
                >
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
            </div>
        </div>

        <!-- Navigation Drawer (Desktop only) -->
        <RescuerMenu v-model="drawer" />

        <!-- Main Content -->
        <v-main class="messages-main">
            <div v-if="loading" class="d-flex justify-center align-center" style="min-height: 60vh;">
                <v-progress-circular indeterminate color="primary" size="64" />
            </div>

            <div v-else-if="conversations.length === 0" class="pa-4">
                <v-card variant="tonal" color="grey-lighten-3" class="text-center pa-8">
                    <v-icon size="64" color="grey">mdi-message-text-outline</v-icon>
                    <div class="text-h6 mt-4 text-grey-darken-1">No Messages</div>
                    <div class="text-body-2 text-grey mt-2">
                        Messages from rescue operations will appear here
                    </div>
                </v-card>
            </div>

            <v-list v-else class="pa-0">
                <template v-for="(conversation, index) in conversations" :key="conversation.id">
                    <v-list-item
                        class="py-3"
                        @click="openChat(conversation)"
                    >
                        <template v-slot:prepend>
                            <v-badge
                                :model-value="conversation.unread_count > 0"
                                color="error"
                                dot
                                location="bottom end"
                                offset-x="3"
                                offset-y="3"
                            >
                                <v-avatar
                                    :color="!getParticipantPicture(conversation) ? getAvatarColor(conversation) : undefined"
                                    size="48"
                                >
                                    <v-img 
                                        v-if="getParticipantPicture(conversation)"
                                        :src="getParticipantPicture(conversation)"
                                        cover
                                    />
                                    <span v-else class="text-white">{{ getInitials(conversation) }}</span>
                                </v-avatar>
                            </v-badge>
                        </template>

                        <v-list-item-title class="font-weight-medium">
                            {{ getConversationName(conversation) }}
                        </v-list-item-title>
                        <v-list-item-subtitle class="d-flex align-center">
                            <v-icon v-if="conversation.last_message?.sender_id === currentUserId" size="14" class="mr-1">
                                mdi-check-all
                            </v-icon>
                            <span :class="{ 'font-weight-medium': conversation.unread_count > 0 }">
                                {{ getLastMessagePreview(conversation) }}
                            </span>
                        </v-list-item-subtitle>

                        <template v-slot:append>
                            <div class="text-right">
                                <div class="text-caption text-grey mb-1">
                                    {{ formatTime(conversation.last_message?.timestamp || conversation.last_message?.created_at) }}
                                </div>
                                <v-chip
                                    v-if="conversation.rescue_request"
                                    size="x-small"
                                    :color="getStatusColor(conversation.rescue_request.status)"
                                    variant="tonal"
                                >
                                    {{ formatStatus(conversation.rescue_request.status) }}
                                </v-chip>
                                <v-badge
                                    v-else-if="conversation.unread_count > 0"
                                    :content="conversation.unread_count"
                                    color="primary"
                                    inline
                                />
                            </div>
                        </template>
                    </v-list-item>
                    <v-divider v-if="index < conversations.length - 1" />
                </template>
            </v-list>

            <!-- Pull to refresh indicator -->
            <div v-if="refreshing" class="d-flex justify-center py-4">
                <v-progress-circular indeterminate color="primary" size="24" />
            </div>
        </v-main>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000">
            {{ snackbar.message }}
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
        
        <!-- Bottom Navigation (Mobile/Tablet only) -->
        <RescuerBottomNav :notification-count="0" :message-count="unreadCount" />
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { getConversations, getProfilePictureUrl } from '@/Composables/useApi';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import RescuerMenu from '@/Components/Pages/Rescuer/Menu/RescuerMenu.vue';
import RescuerBottomNav from '@/Components/Pages/Rescuer/Menu/RescuerBottomNav.vue';
import NotificationPopup from '@/Components/NotificationPopup.vue';

// State
const drawer = ref(false);
const loading = ref(true);
const refreshing = ref(false);
const conversations = ref([]);
const currentUserId = ref(null);
const pollingInterval = ref(null);
const consecutiveErrors = ref(0);
const MAX_CONSECUTIVE_ERRORS = 3; // Only show error after 3 consecutive failures

// Notification Alert
const { playNotificationSound, vibrate, notify } = useNotificationAlert();
const popupAlert = ref({
    show: false,
    title: '',
    message: '',
    type: 'info',
    icon: 'mdi-information',
});
const pendingConversationClick = ref(null);

// Track previous unread count for change detection
const previousUnreadCount = ref(0);

const snackbar = ref({
    show: false,
    message: '',
    color: 'success',
});

// Computed
const unreadCount = computed(() => {
    return conversations.value.reduce((acc, c) => acc + (c.unread_count || 0), 0);
});

// Handle notification click - navigate to conversation
const handleNotificationClick = () => {
    popupAlert.value.show = false;
    if (pendingConversationClick.value) {
        openChat(pendingConversationClick.value);
        pendingConversationClick.value = null;
    }
};

// Trigger notification for new messages
const triggerNewMessageNotification = (conversation, newMessages) => {
    const senderName = getConversationName(conversation);
    
    // Play sound and vibrate
    playNotificationSound('message');
    vibrate('message');
    
    // Set the conversation to navigate to on click
    pendingConversationClick.value = conversation;
    
    // Show popup
    popupAlert.value = {
        show: true,
        title: '💬 New Message',
        message: `${senderName}: ${getLastMessagePreview(conversation)}`,
        type: 'info',
        icon: 'mdi-message-text'
    };
    
    // Also show browser notification
    notify({
        title: `New message from ${senderName}`,
        body: getLastMessagePreview(conversation),
        icon: '/icons/icon-192x192.png'
    });
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        popupAlert.value.show = false;
    }, 5000);
};

// Methods
const fetchConversations = async (showErrorToast = false) => {
    try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        currentUserId.value = userData.id;

        if (!currentUserId.value) {
            router.visit('/login');
            return;
        }

        // Store previous unread counts for comparison
        const previousUnreadCounts = {};
        conversations.value.forEach(c => {
            previousUnreadCounts[c.id] = c.unread_count || 0;
        });
        const wasEmpty = conversations.value.length === 0;

        const response = await getConversations(currentUserId.value);
        const data = response.data || response;

        // Reset error count on success
        consecutiveErrors.value = 0;

        if (data) {
            const newConversations = Array.isArray(data) ? data : data.data || [];
            
            // Check for new messages (increased unread count)
            if (!wasEmpty && !loading.value) {
                for (const conv of newConversations) {
                    const prevCount = previousUnreadCounts[conv.id] || 0;
                    const newCount = conv.unread_count || 0;
                    
                    if (newCount > prevCount) {
                        // New messages detected!
                        triggerNewMessageNotification(conv, newCount - prevCount);
                        break; // Only show one notification at a time
                    }
                }
            }
            
            conversations.value = newConversations;
            
            // Sort by last message time
            conversations.value.sort((a, b) => {
                const timeA = a.last_message?.timestamp || a.last_message?.created_at 
                    ? new Date(a.last_message?.timestamp || a.last_message?.created_at) 
                    : new Date(0);
                const timeB = b.last_message?.timestamp || b.last_message?.created_at 
                    ? new Date(b.last_message?.timestamp || b.last_message?.created_at) 
                    : new Date(0);
                return timeB - timeA;
            });
        }
    } catch (error) {
        console.error('Error fetching conversations:', error);
        consecutiveErrors.value++;
        
        // Only show error toast if explicitly requested (manual refresh) or after multiple consecutive failures
        if (showErrorToast || consecutiveErrors.value >= MAX_CONSECUTIVE_ERRORS) {
            showSnackbar('Failed to load messages', 'error');
            consecutiveErrors.value = 0; // Reset after showing error
        }
    } finally {
        loading.value = false;
        refreshing.value = false;
    }
};

const openChat = (conversation) => {
    router.visit(`/rescuer/chat/${conversation.id}`);
};

// Helper methods
const getConversationName = (conversation) => {
    // For rescue-related conversations, show the requester's name
    if (conversation.rescue_request?.requester) {
        const user = conversation.rescue_request.requester;
        return `${user.first_name || ''} ${user.last_name || ''}`.trim() || 'User';
    }
    
    // Fallback to user from rescue request
    if (conversation.rescue_request?.user) {
        const user = conversation.rescue_request.user;
        return `${user.first_name || ''} ${user.last_name || ''}`.trim() || 'User';
    }
    
    // For other conversations, find the other participant
    const otherParticipant = conversation.participants?.find(p => String(p.user_id) !== String(currentUserId.value));
    if (otherParticipant?.user) {
        return `${otherParticipant.user.first_name || ''} ${otherParticipant.user.last_name || ''}`.trim() || 'User';
    }

    return conversation.title || 'Unknown';
};

const getParticipantPicture = (conversation) => {
    // For rescue-related conversations, get requester's picture
    if (conversation.rescue_request?.requester?.profile_picture) {
        return getProfilePictureUrl(conversation.rescue_request.requester.profile_picture);
    }
    
    if (conversation.rescue_request?.user?.profile_picture) {
        return getProfilePictureUrl(conversation.rescue_request.user.profile_picture);
    }
    
    // For other conversations, find the other participant
    const otherParticipant = conversation.participants?.find(p => String(p.user_id) !== String(currentUserId.value));
    if (otherParticipant?.user?.profile_picture) {
        return getProfilePictureUrl(otherParticipant.user.profile_picture);
    }
    
    return null;
};

const getInitials = (conversation) => {
    const name = getConversationName(conversation);
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return `${parts[0][0] || ''}${parts[1][0] || ''}`.toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const getAvatarColor = (conversation) => {
    if (conversation.rescue_request) {
        return getEmergencyColor(conversation.rescue_request.emergency_type);
    }
    return 'primary';
};

const getEmergencyColor = (type) => {
    const colors = {
        'Fire': 'deep-orange',
        'Medical': 'red',
        'Earthquake': 'brown',
        'Flood': 'blue',
        'Violence': 'purple',
        'Other': 'grey',
    };
    return colors[type] || 'grey';
};

const getLastMessagePreview = (conversation) => {
    const message = conversation.last_message;
    if (!message) return 'No messages yet';
    
    if (message.message_type === 'audio') {
        return '🎤 Voice message';
    }
    if (message.message_type === 'image') {
        return '📷 Image';
    }
    if (message.message_type === 'location') {
        return '📍 Location';
    }
    
    const content = message.content || '';
    return content.length > 40 ? content.substring(0, 40) + '...' : content;
};

const getStatusColor = (status) => {
    const colors = {
        'pending': 'warning',
        'accepted': 'info',
        'en_route': 'primary',
        'on_scene': 'success',
        'rescued': 'success',
        'cancelled': 'error',
    };
    return colors[status] || 'grey';
};

const formatStatus = (status) => {
    const labels = {
        'pending': 'Pending',
        'accepted': 'Active',
        'en_route': 'En Route',
        'on_scene': 'On Scene',
        'rescued': 'Done',
        'cancelled': 'Cancelled',
    };
    return labels[status] || status;
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    if (diffDays === 1) {
        return 'Yesterday';
    }
    if (diffDays < 7) {
        return date.toLocaleDateString([], { weekday: 'short' });
    }
    return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
};

const showSnackbar = (message, color = 'success') => {
    snackbar.value = { show: true, message, color };
};

// Manual refresh with error toast
const refreshConversations = async () => {
    refreshing.value = true;
    await fetchConversations(true); // Show error toast on manual refresh
};

// Lifecycle
onMounted(() => {
    fetchConversations(true); // Show error on initial load
    
    // Poll for new messages every 10 seconds
    pollingInterval.value = setInterval(() => fetchConversations(false), 10000);
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
});
</script>

<style scoped>
/* Header */
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
    max-width: 800px;
    margin: 0 auto;
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
    background: #f8fafb;
    min-height: 100vh;
}

/* Desktop only visibility */
.desktop-only {
    display: flex;
}

@media (max-width: 1023px) {
    .desktop-only {
        display: none !important;
    }
    
    /* Mobile: adequate padding for bottom nav */
    .messages-main {
        padding-bottom: 100px !important;
    }
}

@media (min-width: 1024px) {
    /* Show menu button on desktop, no margin needed since drawer is temporary */
    .desktop-only {
        display: flex;
    }
    
    /* Desktop: less bottom padding */
    .messages-main {
        padding-bottom: 40px;
    }
}

/* Constrain list width for better readability */
.v-list {
    max-width: 800px;
    margin: 0 auto;
    background: transparent;
}

.v-list-item {
    min-height: 72px;
    background: white;
    margin: 0 12px 8px;
    border-radius: 12px;
}

.v-list-item:last-child {
    margin-bottom: 16px;
}

/* Empty state and loading centered */
.pa-4 {
    max-width: 800px;
    margin: 0 auto;
    padding-bottom: 100px !important;
}
</style>
