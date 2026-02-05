<template>
    <v-app class="bg-user-gradient-light">
        <!-- Header - like User's Inbox -->
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
            <!-- Loading State -->
            <div v-if="loading" class="loading-container">
                <v-progress-circular indeterminate color="primary" size="48" />
                <p class="text-grey mt-4">Loading your messages...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="conversations.length === 0" class="empty-state-container">
                <div class="empty-state-card">
                    <div class="empty-icon-wrapper">
                        <v-icon size="80" color="primary">mdi-message-text-outline</v-icon>
                    </div>
                    <h3>No Messages Yet</h3>
                    <p>Messages from rescue operations will appear here</p>
                </div>
            </div>

            <!-- Chat List -->
            <div v-else class="chat-list-container" ref="chatListContainer">
                <div class="chat-list">
                    <div
                        v-for="conversation in conversations"
                        :key="conversation.id"
                        class="chat-item"
                        @click="openChat(conversation)"
                    >
                        <!-- Avatar with Badge -->
                        <div class="chat-avatar">
                            <v-badge
                                :model-value="conversation.unread_count > 0"
                                color="error"
                                dot
                                location="bottom end"
                                offset-x="4"
                                offset-y="4"
                            >
                                <v-avatar
                                    :color="!getParticipantPicture(conversation) ? getAvatarColor(conversation) : undefined"
                                    size="48"
                                    class="avatar-shadow"
                                >
                                    <v-img 
                                        v-if="getParticipantPicture(conversation)"
                                        :src="getParticipantPicture(conversation)"
                                        cover
                                    />
                                    <span v-else class="text-white font-weight-bold">{{ getInitials(conversation) }}</span>
                                </v-avatar>
                            </v-badge>
                        </div>

                        <!-- Chat Content -->
                        <div class="chat-content">
                            <div class="chat-header">
                                <h4 class="chat-name">{{ getConversationName(conversation) }}</h4>
                                <div class="chat-meta">
                                    <span class="chat-time">{{ formatTime(conversation.last_message?.timestamp || conversation.last_message?.created_at) }}</span>
                                </div>
                            </div>
                            
                            <div class="chat-message">
                                <p :class="{ 'unread-message': conversation.unread_count > 0 }">
                                    <v-icon v-if="conversation.last_message?.sender_id === currentUserId" size="14" class="mr-1">
                                        mdi-check-all
                                    </v-icon>
                                    {{ getLastMessagePreview(conversation) }}
                                </p>
                            </div>
                            
                            <div class="chat-footer" v-if="conversation.rescue_request || conversation.unread_count > 0">
                                <v-chip
                                    v-if="conversation.rescue_request"
                                    size="small"
                                    :color="getStatusColor(conversation.rescue_request.status)"
                                    variant="tonal"
                                    class="status-chip"
                                >
                                    <v-icon start size="12">mdi-circle</v-icon>
                                    {{ formatStatus(conversation.rescue_request.status) }}
                                </v-chip>
                                <v-chip
                                    v-else-if="conversation.unread_count > 0"
                                    size="small"
                                    color="primary"
                                    variant="flat"
                                    class="unread-chip"
                                >
                                    {{ conversation.unread_count }}
                                </v-chip>
                            </div>
                        </div>

                        <!-- Action Arrow -->
                        <div class="chat-action">
                            <v-icon color="grey-darken-1" size="20">mdi-chevron-right</v-icon>
                        </div>
                    </div>
                </div>
            </div>

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
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { getConversations, getProfilePictureUrl } from '@/Composables/useApi';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import RescuerMenu from '@/Components/Pages/Rescuer/Menu/RescuerMenu.vue';
import RescuerBottomNav from '@/Components/Pages/Rescuer/Menu/RescuerBottomNav.vue';
import NotificationPopup from '@/Components/NotificationPopup.vue';

// Scroll position storage key
const SCROLL_POSITION_KEY = 'rescuer_chats_scroll_position';

// State
const drawer = ref(false);
const loading = ref(true);
const refreshing = ref(false);
const conversations = ref([]);
const currentUserId = ref(null);
const pollingInterval = ref(null);
const consecutiveErrors = ref(0);
const MAX_CONSECUTIVE_ERRORS = 3;
const chatListContainer = ref(null);

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

// Save scroll position before leaving
const saveScrollPosition = () => {
    if (chatListContainer.value) {
        const scrollTop = chatListContainer.value.scrollTop;
        sessionStorage.setItem(SCROLL_POSITION_KEY, scrollTop.toString());
    }
};

// Restore scroll position
const restoreScrollPosition = async () => {
    await nextTick();
    const savedPosition = sessionStorage.getItem(SCROLL_POSITION_KEY);
    if (savedPosition && chatListContainer.value) {
        chatListContainer.value.scrollTop = parseInt(savedPosition, 10);
    }
};

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
    
    playNotificationSound('message');
    vibrate('message');
    
    pendingConversationClick.value = conversation;
    
    popupAlert.value = {
        show: true,
        title: '💬 New Message',
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

        if (!currentUserId.value) {
            router.visit('/login');
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
                        triggerNewMessageNotification(conv, newCount - prevCount);
                        break;
                    }
                }
            }
            
            conversations.value = newConversations;
            
            conversations.value.sort((a, b) => {
                const timeA = a.last_message?.timestamp || a.last_message?.created_at 
                    ? new Date(a.last_message?.timestamp || a.last_message?.created_at) 
                    : new Date(0);
                const timeB = b.last_message?.timestamp || b.last_message?.created_at 
                    ? new Date(b.last_message?.timestamp || b.last_message?.created_at) 
                    : new Date(0);
                return timeB - timeA;
            });
            
            // Restore scroll position after data loads
            if (loading.value) {
                await restoreScrollPosition();
            }
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

const openChat = (conversation) => {
    saveScrollPosition();
    router.visit(`/rescuer/chat/${conversation.id}?from=rescuer-chats`);
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

const refreshConversations = async () => {
    refreshing.value = true;
    await fetchConversations(true);
};

// Lifecycle
onMounted(() => {
    fetchConversations(true);
    pollingInterval.value = setInterval(() => fetchConversations(false), 10000);
    
    // Save scroll position before page unload
    window.addEventListener('beforeunload', saveScrollPosition);
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
    window.removeEventListener('beforeunload', saveScrollPosition);
});
</script>

<style scoped>
/* Header - like User's Inbox */
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
    min-height: 100vh;
}

/* Loading State */
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    padding: 2rem;
}

.loading-container p {
    font-size: 1rem;
    font-weight: 500;
}

/* Empty State */
.empty-state-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    padding: 2rem 1rem;
}

.empty-state-card {
    background: white;
    border-radius: 20px;
    padding: 3rem 2rem;
    text-align: center;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    max-width: 400px;
    width: 100%;
    border: 1px solid rgba(54, 116, 181, 0.1);
}

.empty-icon-wrapper {
    background: linear-gradient(135deg, rgba(54, 116, 181, 0.1) 0%, rgba(54, 116, 181, 0.05) 100%);
    border-radius: 50%;
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
}

.empty-state-card h3 {
    color: #1a365d;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.empty-state-card p {
    color: #64748b;
    font-size: 1rem;
    line-height: 1.6;
    margin: 0;
}

/* Chat List Container */
.chat-list-container {
    padding: 1rem;
    max-width: 800px;
    margin: 0 auto;
    overflow-y: auto;
}

.chat-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Chat Item */
.chat-item {
    background: white;
    border-radius: 12px;
    padding: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(54, 116, 181, 0.06);
    position: relative;
    overflow: hidden;
    min-height: 70px;
}

.chat-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    border-color: rgba(54, 116, 181, 0.2);
}

.chat-item:active {
    transform: translateY(0);
}

.chat-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #3674B5 0%, #4A90E2 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.chat-item:hover::before {
    opacity: 1;
}

/* Avatar */
.chat-avatar {
    flex-shrink: 0;
}

.avatar-shadow {
    box-shadow: 0 4px 12px rgba(54, 116, 181, 0.25);
}

/* Chat Content */
.chat-content {
    flex: 1;
    min-width: 0;
}

.chat-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
    gap: 1rem;
}

.chat-name {
    font-size: 1rem;
    font-weight: 600;
    color: #1a365d;
    margin: 0;
    line-height: 1.3;
}

.chat-meta {
    flex-shrink: 0;
    text-align: right;
}

.chat-time {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
}

.chat-message {
    margin-bottom: 0.75rem;
}

.chat-message p {
    font-size: 0.85rem;
    color: #4a5568;
    line-height: 1.4;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: flex;
    align-items: center;
}

.unread-message {
    color: #1a365d !important;
    font-weight: 600 !important;
}

.chat-footer {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-chip {
    font-size: 0.75rem;
    font-weight: 600;
    height: 24px;
}

.unread-chip {
    font-size: 0.75rem;
    font-weight: 700;
    height: 24px;
    min-width: 24px;
    border-radius: 12px;
}

/* Action Arrow */
.chat-action {
    flex-shrink: 0;
    opacity: 0.6;
    transition: opacity 0.3s ease;
}

.chat-item:hover .chat-action {
    opacity: 1;
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
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 120px) !important;
    }
    
    .chat-list-container {
        padding-bottom: 40px !important;
    }
}

@media (max-width: 600px) {
    .messages-main {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 140px) !important;
    }
    
    .chat-list-container {
        padding: 0.75rem;
        padding-bottom: 50px !important;
    }
    
    .chat-item {
        padding: 0.75rem;
        border-radius: 10px;
        min-height: 64px;
    }
    
    .chat-name {
        font-size: 0.95rem;
    }
    
    .chat-message p {
        font-size: 0.85rem;
    }
    
    .avatar-shadow {
        box-shadow: 0 2px 8px rgba(54, 116, 181, 0.2);
    }
    
    .empty-state-card {
        padding: 2rem 1.5rem;
        border-radius: 16px;
    }
    
    .empty-icon-wrapper {
        width: 100px;
        height: 100px;
        margin-bottom: 1rem;
    }
    
    .empty-state-card h3 {
        font-size: 1.25rem;
    }
}

@media (min-width: 1024px) {
    .desktop-only {
        display: flex;
    }
    
    .messages-main {
        padding-bottom: 40px;
    }
    
    .chat-list-container {
        padding: 1.5rem;
    }
    
    .chat-item {
        padding: 1rem;
    }
    
    .chat-name {
        font-size: 1.125rem;
    }
}

/* Constrain list width for better readability */
.chat-list {
    width: 100%;
}

/* Pull to refresh indicator */
.d-flex.justify-center.py-4 {
    max-width: 800px;
    margin: 0 auto;
}

/* Toast and notifications */
.v-snackbar {
    z-index: 9999;
}
</style>
