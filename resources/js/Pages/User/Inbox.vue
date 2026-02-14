<template>
    <v-app class="bg-user-gradient-light">
        <!-- App Bar -->
        <UserAppBar 
            title="Messages" 
            :subtitle="totalUnreadCount > 0 ? totalUnreadCount + ' unread message' + (totalUnreadCount !== 1 ? 's' : '') : ''"
            :notification-count="0"
            @toggle-drawer="drawer = true"
        >
            <template #actions>
                <v-btn icon variant="text" class="bar-btn" style="color: white;" :loading="refreshing" @click="refreshChats">
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
            </template>
        </UserAppBar>

        <!-- Navigation Drawer - Desktop only -->
        <UserMenu v-model="drawer" />

        <v-main class="messages-main">
            <!-- Shared Inbox List -->
            <InboxList
                ref="inboxListRef"
                :chats="normalizedChats"
                :loading="loading"
                :refreshing="refreshing"
                empty-message="Messages from rescuers will appear here when you're assigned to a rescue request"
                scroll-storage-key="user_inbox_scroll_position"
                @open-chat="openChat"
            />

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
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import UserMenu from '@/Components/Pages/User/Menu/UserMenu.vue';
import UserAppBar from '@/Components/Pages/User/Menu/UserAppBar.vue';
import UserBottomNav from '@/Components/Pages/User/Menu/UserBottomNav.vue';
import NotificationPopup from '@/Components/NotificationPopup.vue';
import InboxList from '@/Pages/Shared/InboxList.vue';

// Notification Alert System
const { playNotificationSound, vibrate, notify } = useNotificationAlert();

// State
const drawer = ref(false);
const chats = ref([]);
const loading = ref(true);
const refreshing = ref(false);
const pollingInterval = ref(null);
const consecutiveErrors = ref(0);
const MAX_CONSECUTIVE_ERRORS = 3;
const inboxListRef = ref(null);

// Popup alert state
const popupAlert = ref({
    show: false,
    title: '',
    message: '',
    type: 'info',
    icon: 'mdi-information',
});
const pendingConversationClick = ref(null);

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

// Normalize chats for the shared InboxList component
const normalizedChats = computed(() => {
    let result = chats.value.map(chat => ({
        id: chat.id,
        name: chat.other_user_name,
        picture: chat.other_user_picture,
        avatarColor: 'primary',
        lastMessage: chat.last_message,
        lastMessageTime: chat.last_message_time,
        unreadCount: chat.unread_count,
        isOwnMessage: false,
        raw: chat,
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

onMounted(async () => {
    await fetchChats(true);
    
    // Poll for new messages every 10 seconds
    pollingInterval.value = setInterval(() => fetchChats(false), 10000);
    
    // Refresh chats when page becomes visible
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

const handleVisibilityChange = () => {
    if (!document.hidden) {
        fetchChats(false);
    }
};

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});

const fetchChats = async (showErrorToast = false) => {
    const isInitialLoad = loading.value;
    
    try {
        const userId = currentUserId.value;
        
        // Store previous unread counts
        const previousUnreadCounts = {};
        chats.value.forEach(c => {
            previousUnreadCounts[c.id] = c.unread_count || 0;
        });
        
        const response = await getConversations(userId);
        const data = response.data || response;
        const conversations = Array.isArray(data) ? data : (data?.data || []);

        consecutiveErrors.value = 0;

        const newChats = conversations.map((conv) => {
            const myParticipant = conv.participants?.find(
                (p) => String(p.user_id) === String(currentUserId.value)
            );
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
                last_message: getMessagePreview(conv.last_message),
                last_message_time: conv.last_message?.timestamp || conv.updated_at,
                unread_count: myParticipant?.unread_count || 0,
                rescue_status: rescueRequest?.status,
            };
        });
        
        // Detect new messages and trigger notification
        if (!isInitialLoad && newChats.length > 0) {
            for (const chat of newChats) {
                const prevCount = previousUnreadCounts[chat.id] || 0;
                const newCount = chat.unread_count || 0;
                
                if (newCount > prevCount) {
                    playNotificationSound('message');
                    vibrate('message');
                    
                    pendingConversationClick.value = { id: chat.id, raw: chat };
                    
                    popupAlert.value = {
                        show: true,
                        title: '\uD83D\uDCAC New Message',
                        message: `${chat.other_user_name}: ${chat.last_message || 'New message'}`,
                        type: 'info',
                        icon: 'mdi-message-text',
                    };
                    
                    notify({
                        title: `New message from ${chat.other_user_name}`,
                        body: chat.last_message || 'New message',
                        icon: '/icons/icon-192x192.png'
                    });
                    
                    setTimeout(() => { popupAlert.value.show = false; }, 5000);
                    break;
                }
            }
        }
        
        chats.value = newChats;
    } catch (err) {
        console.error('Error fetching chats:', err);
        consecutiveErrors.value++;
        
        if (showErrorToast || consecutiveErrors.value >= MAX_CONSECUTIVE_ERRORS) {
            toastMessage.value = 'Failed to load messages';
            toastColor.value = 'error';
            showToast.value = true;
            consecutiveErrors.value = 0;
        }
    } finally {
        loading.value = false;
        refreshing.value = false;
    }
};

const getMessagePreview = (message) => {
    if (!message) return 'No messages yet';
    if (message.message_type === 'audio') return '\uD83C\uDFA4 Voice message';
    if (message.message_type === 'image') return '\uD83D\uDCF7 Image';
    if (message.message_type === 'location') return '\uD83D\uDCCD Location';
    const content = message.content || '';
    return content.length > 40 ? content.substring(0, 40) + '...' : content;
};

const refreshChats = async () => {
    refreshing.value = true;
    await fetchChats(true);
};

const openChat = (chat) => {
    inboxListRef.value?.saveScrollPosition();
    router.visit(`/user/chat/${chat.id}?from=user-inbox`);
};

// Total unread messages count
const totalUnreadCount = computed(() => {
    return chats.value.reduce((acc, chat) => acc + (chat.unread_count || 0), 0);
});
</script>

<style scoped>
/* Main Content */
.messages-main {
    min-height: 100vh;
}

@media (max-width: 1023px) {
    .messages-main {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 120px) !important;
    }
}

@media (max-width: 600px) {
    .messages-main {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 140px) !important;
    }
}

@media (min-width: 1024px) {
    .messages-main {
        padding-bottom: 40px;
    }
}
</style>
