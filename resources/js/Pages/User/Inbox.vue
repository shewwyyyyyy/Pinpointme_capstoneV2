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
            <div v-if="loading" class="loading-container">
                <v-progress-circular indeterminate color="primary" size="48" />
                <p class="text-grey mt-4">Loading your messages...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredChats.length === 0" class="empty-state-container">
                <div class="empty-state-card">
                    <div class="empty-icon-wrapper">
                        <v-icon size="80" color="primary">mdi-message-text-outline</v-icon>
                    </div>
                    <h3>No Messages Yet</h3>
                    <p>Messages from rescuers will appear here when you're assigned to a rescue request</p>
                </div>
            </div>

            <!-- Chat List -->
            <div v-else class="chat-list-container" ref="chatListContainer">
                <div class="chat-list">
                    <div
                        v-for="(chat, index) in filteredChats"
                        :key="chat.id"
                        class="chat-item"
                        @click="openChat(chat)"
                    >
                        <!-- Avatar with Badge -->
                        <div class="chat-avatar">
                            <v-badge
                                :model-value="chat.unread_count > 0"
                                color="error"
                                dot
                                location="bottom end"
                                offset-x="4"
                                offset-y="4"
                            >
                                <v-avatar
                                    :color="!chat.other_user_picture ? 'primary' : undefined"
                                    size="48"
                                    class="avatar-shadow"
                                >
                                    <v-img 
                                        v-if="chat.other_user_picture"
                                        :src="chat.other_user_picture"
                                        cover
                                    />
                                    <span v-else class="text-white font-weight-bold">{{ getInitials(chat.other_user_name) }}</span>
                                </v-avatar>
                            </v-badge>
                        </div>

                        <!-- Chat Content -->
                        <div class="chat-content">
                            <div class="chat-header">
                                <h4 class="chat-name">{{ chat.other_user_name }}</h4>
                                <div class="chat-meta">
                                    <span class="chat-time">{{ formatTime(chat.last_message_time) }}</span>
                                </div>
                            </div>
                            
                            <div class="chat-message">
                                <p :class="{ 'unread-message': chat.unread_count > 0 }">
                                    {{ chat.last_message }}
                                </p>
                            </div>
                            
                            <div class="chat-footer" v-if="chat.rescue_status || chat.unread_count > 0">
                                <v-chip
                                    v-if="chat.rescue_status"
                                    size="small"
                                    :color="getStatusColor(chat.rescue_status)"
                                    variant="tonal"
                                    class="status-chip"
                                >
                                    <v-icon start size="12">mdi-circle</v-icon>
                                    {{ formatStatus(chat.rescue_status) }}
                                </v-chip>
                                <v-chip
                                    v-else-if="chat.unread_count > 0"
                                    size="small"
                                    color="primary"
                                    variant="flat"
                                    class="unread-chip"
                                >
                                    {{ chat.unread_count }}
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
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { getConversations, getProfilePictureUrl } from '@/Composables/useApi';
import UserMenu from '@/Components/Pages/User/Menu/UserMenu.vue';
import UserBottomNav from '@/Components/Pages/User/Menu/UserBottomNav.vue';
import NotificationPopup from '@/Components/NotificationPopup.vue';

// Scroll position storage key
const SCROLL_POSITION_KEY = 'user_inbox_scroll_position';

// State
const drawer = ref(false);
const chats = ref([]);
const loading = ref(true);
const refreshing = ref(false);
const searchText = ref('');
const pollingInterval = ref(null);
const consecutiveErrors = ref(0);
const MAX_CONSECUTIVE_ERRORS = 3;
const chatListContainer = ref(null);

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

// Note: Chat notifications are handled within the Chat module itself
// Removed triggerNewMessageNotification to avoid duplicate notifications

onMounted(async () => {
    await fetchChats(true);
    
    // Restore scroll position after data loads
    await restoreScrollPosition();
    
    // Poll for new messages every 10 seconds
    pollingInterval.value = setInterval(() => fetchChats(false), 10000);
    
    // Save scroll position before page unload
    window.addEventListener('beforeunload', saveScrollPosition);
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
    window.removeEventListener('beforeunload', saveScrollPosition);
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
    // Save scroll position before navigating
    saveScrollPosition();
    // Navigate to chat using conversation ID
    router.visit(`/user/chat/${chat.id}?from=user-inbox`);
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
    /* Show menu button on desktop, no margin needed since drawer is temporary */
    .desktop-only {
        display: flex;
    }
    
    /* Desktop: less bottom padding */
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
