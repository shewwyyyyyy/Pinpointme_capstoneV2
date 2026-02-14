<template>
    <v-app class="chat-viewport bg-user-gradient-light">
        <!-- Navigation Drawer (Desktop only) -->
        <RescuerMenu v-model="drawer" />

        <!-- Fixed Header -->
        <div class="messages-header">
            <div class="header-content">
                <v-btn icon variant="text" @click="drawer = true" class="menu-btn desktop-only">
                    <v-icon>mdi-menu</v-icon>
                </v-btn>
                <div class="header-title">
                    <div class="title-with-icon">
                        <v-icon size="24" class="mr-2">mdi-message-text</v-icon>
                        <h1>Messages</h1>
                    </div>
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

        <!-- Scrollable Content Area -->
        <div class="messages-content">
            <!-- Restriction Warning -->
            <v-alert
                v-if="isRescuerRestricted"
                type="warning"
                variant="tonal"
                prominent
                border="start"
                class="mx-4 mt-4 mb-2"
            >
                <template v-slot:prepend>
                    <v-icon size="28">mdi-lock</v-icon>
                </template>
                <div class="text-subtitle-2 font-weight-bold mb-1">Access Restricted</div>
                <div class="text-caption">
                    Your status is set to <strong>{{ rescuerStatus }}</strong> by an administrator. 
                    You cannot access messages until your status is changed.
                </div>
            </v-alert>

            <!-- Show messages only if not restricted -->
            <template v-if="!isRescuerRestricted">
                <!-- Shared Inbox List -->
                <InboxList
                    ref="inboxListRef"
                    :chats="normalizedChats"
                    :loading="loading"
                    :refreshing="refreshing"
                    empty-message="Messages from rescue operations will appear here"
                    scroll-storage-key="rescuer_chats_scroll_position"
                    @open-chat="openChat"
                />
            </template>
            
            <!-- Restriction empty state -->
            <div v-else-if="!loading" class="restriction-empty-state">
                <v-icon size="80" color="grey-lighten-2">mdi-message-lock</v-icon>
                <p class="text-h6 text-grey mt-4">Messages Unavailable</p>
                <p class="text-body-2 text-grey px-6 text-center">
                    Contact an administrator to change your status to access messages.
                </p>
            </div>
        </div>

        <!-- Fixed Bottom Navigation -->
        <div class="bottom-nav-container">
            <RescuerBottomNav :notification-count="0" :message-count="unreadCount" />
        </div>

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
import InboxList from '@/Pages/Shared/InboxList.vue';

// State
const drawer = ref(false);
const loading = ref(true);
const refreshing = ref(false);
const conversations = ref([]);
const currentUserId = ref(null);
const pollingInterval = ref(null);
const consecutiveErrors = ref(0);
const MAX_CONSECUTIVE_ERRORS = 3;
const inboxListRef = ref(null);
const rescuerStatus = ref('available');

// Check if rescuer is restricted
const isRescuerRestricted = computed(() => {
    const status = rescuerStatus.value?.toLowerCase();
    return status === 'off_duty' || status === 'unavailable';
});

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

const snackbar = ref({
    show: false,
    message: '',
    color: 'success',
});

// Computed
const unreadCount = computed(() => {
    return conversations.value.reduce((acc, c) => acc + (c.unread_count || 0), 0);
});

// Normalize conversations for the shared InboxList component
const normalizedChats = computed(() => {
    const result = conversations.value.map(conv => ({
        id: conv.id,
        name: getConversationName(conv),
        picture: getParticipantPicture(conv),
        avatarColor: getAvatarColor(conv),
        lastMessage: getLastMessagePreview(conv),
        lastMessageTime: conv.last_message?.timestamp || conv.last_message?.created_at,
        unreadCount: conv.unread_count || 0,
        isOwnMessage: conv.last_message?.sender_id === currentUserId.value,
        raw: conv,
    }));

    // Sort by last message time (most recent first)
    return result.sort((a, b) => {
        const timeA = a.lastMessageTime ? new Date(a.lastMessageTime) : new Date(0);
        const timeB = b.lastMessageTime ? new Date(b.lastMessageTime) : new Date(0);
        return timeB - timeA;
    });
});

// Handle notification click
const handleNotificationClick = () => {
    popupAlert.value.show = false;
    if (pendingConversationClick.value) {
        openChat(pendingConversationClick.value);
        pendingConversationClick.value = null;
    }
};

// Trigger notification for new messages
const triggerNewMessageNotification = (conversation) => {
    const senderName = getConversationName(conversation);
    
    playNotificationSound('message');
    vibrate('message');
    
    pendingConversationClick.value = { id: conversation.id, raw: conversation };
    
    popupAlert.value = {
        show: true,
        title: '\uD83D\uDCAC New Message',
        message: `${senderName}: ${getLastMessagePreview(conversation)}`,
        type: 'info',
        icon: 'mdi-message-text'
    };
    
    notify({
        title: `New message from ${senderName}`,
        body: getLastMessagePreview(conversation),
        icon: '/icons/icon-192x192.png'
    });
    
    setTimeout(() => {
        popupAlert.value.show = false;
    }, 5000);
};

// Methods
const fetchConversations = async (showErrorToast = false) => {
    try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        currentUserId.value = userData.id;
        rescuerStatus.value = userData.status || 'available';

        if (!currentUserId.value) {
            router.visit('/login');
            return;
        }

        // Don't fetch conversations if rescuer is restricted
        if (isRescuerRestricted.value) {
            console.log('[Chats] Rescuer is restricted, skipping conversation fetch');
            conversations.value = [];
            loading.value = false;
            refreshing.value = false;
            return;
        }

        const previousUnreadCounts = {};
        conversations.value.forEach(c => {
            previousUnreadCounts[c.id] = c.unread_count || 0;
        });
        const wasEmpty = conversations.value.length === 0;

        const response = await getConversations(currentUserId.value);
        const data = response.data || response;

        consecutiveErrors.value = 0;

        if (data) {
            const newConversations = Array.isArray(data) ? data : data.data || [];
            
            if (!wasEmpty && !loading.value) {
                for (const conv of newConversations) {
                    const prevCount = previousUnreadCounts[conv.id] || 0;
                    const newCount = conv.unread_count || 0;
                    
                    if (newCount > prevCount) {
                        triggerNewMessageNotification(conv);
                        break;
                    }
                }
            }
            
            conversations.value = newConversations;
        }
    } catch (error) {
        console.error('Error fetching conversations:', error);
        consecutiveErrors.value++;
        
        if (showErrorToast || consecutiveErrors.value >= MAX_CONSECUTIVE_ERRORS) {
            showSnackbar('Failed to load messages', 'error');
            consecutiveErrors.value = 0;
        }
    } finally {
        loading.value = false;
        refreshing.value = false;
    }
};

const openChat = (chat) => {
    // Prevent opening chat if rescuer is restricted
    if (isRescuerRestricted.value) {
        showSnackbar('You cannot access messages while your status is restricted', 'warning');
        return;
    }
    
    inboxListRef.value?.saveScrollPosition();
    router.visit(`/rescuer/chat/${chat.id}?from=rescuer-chats`);
};

// Helper methods
const getConversationName = (conversation) => {
    if (conversation.rescue_request?.requester) {
        const user = conversation.rescue_request.requester;
        return `${user.first_name || ''} ${user.last_name || ''}`.trim() || 'User';
    }
    
    if (conversation.rescue_request?.user) {
        const user = conversation.rescue_request.user;
        return `${user.first_name || ''} ${user.last_name || ''}`.trim() || 'User';
    }
    
    const otherParticipant = conversation.participants?.find(p => String(p.user_id) !== String(currentUserId.value));
    if (otherParticipant?.user) {
        return `${otherParticipant.user.first_name || ''} ${otherParticipant.user.last_name || ''}`.trim() || 'User';
    }

    return conversation.title || 'Unknown';
};

const getParticipantPicture = (conversation) => {
    if (conversation.rescue_request?.requester?.profile_picture) {
        return getProfilePictureUrl(conversation.rescue_request.requester.profile_picture);
    }
    
    if (conversation.rescue_request?.user?.profile_picture) {
        return getProfilePictureUrl(conversation.rescue_request.user.profile_picture);
    }
    
    const otherParticipant = conversation.participants?.find(p => String(p.user_id) !== String(currentUserId.value));
    if (otherParticipant?.user?.profile_picture) {
        return getProfilePictureUrl(otherParticipant.user.profile_picture);
    }
    
    return null;
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
    
    if (message.message_type === 'audio') return '\uD83C\uDFA4 Voice message';
    if (message.message_type === 'image') return '\uD83D\uDCF7 Image';
    if (message.message_type === 'location') return '\uD83D\uDCCD Location';
    
    const content = message.content || '';
    return content.length > 40 ? content.substring(0, 40) + '...' : content;
};

const showSnackbar = (message, color = 'success') => {
    snackbar.value = { show: true, message, color };
};

const refreshConversations = async () => {
    refreshing.value = true;
    await fetchConversations(true);
};

// Lifecycle
onMounted(() => {
    // Load rescuer status from localStorage on mount
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    rescuerStatus.value = userData.status || 'available';
    
    fetchConversations(true);
    pollingInterval.value = setInterval(() => fetchConversations(false), 10000);
    
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

const handleVisibilityChange = () => {
    if (!document.hidden) {
        fetchConversations(false);
    }
};

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>

<style scoped>
/* Restriction Empty State */
.restriction-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px 24px;
    min-height: 60vh;
}

/* Fixed Viewport Layout */
.chat-viewport {
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Fixed Header */
.messages-header {
    flex-shrink: 0;
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
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.title-with-icon {
    display: flex;
    align-items: center;
}

.title-with-icon .v-icon {
    color: white;
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
    padding-left: 32px;
}

/* Scrollable Content Area */
.messages-content {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    padding: 16px 0;
}

/* Fixed Bottom Navigation */
.bottom-nav-container {
    flex-shrink: 0;
    z-index: 90;
}

/* Desktop only visibility */
.desktop-only {
    display: flex;
}

@media (max-width: 1023px) {
    .desktop-only {
        display: none !important;
    }
    
    .bottom-nav-container {
        display: block;
    }
}

@media (min-width: 1024px) {
    .desktop-only {
        display: flex;
    }
    
    .bottom-nav-container {
        display: none;
    }
}
</style>
