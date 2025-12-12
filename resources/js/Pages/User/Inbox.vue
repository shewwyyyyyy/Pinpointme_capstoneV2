<template>
    <v-app>
        <v-app-bar color="primary" density="compact">
            <v-app-bar-nav-icon @click="drawer = !drawer" />
            <v-app-bar-title>Inbox</v-app-bar-title>
            <v-spacer />
            <v-btn icon @click="fetchChats">
                <v-icon>mdi-refresh</v-icon>
            </v-btn>
        </v-app-bar>

        <!-- Navigation Drawer -->
        <UserMenu v-model="drawer" />

        <v-main class="bg-user-gradient-light">
            <!-- Search Bar -->
            <v-container fluid class="pa-4">
                <v-text-field
                    v-model="searchText"
                    placeholder="Search conversations..."
                    variant="outlined"
                    density="compact"
                    prepend-inner-icon="mdi-magnify"
                    clearable
                    hide-details
                    class="mb-4"
                />

                <!-- Loading State -->
                <div v-if="loading" class="d-flex justify-center align-center py-8">
                    <v-progress-circular indeterminate color="primary" size="48" />
                </div>

                <!-- Empty State -->
                <v-card v-else-if="filteredChats.length === 0" class="text-center pa-8" elevation="2" rounded="lg">
                    <v-icon size="64" color="grey">mdi-message-text-outline</v-icon>
                    <h3 class="text-h6 mt-4">No Conversations</h3>
                    <p class="text-grey mt-2">Your chat conversations will appear here when a rescuer is assigned to your rescue request</p>
                </v-card>

                <!-- Chat List -->
                <v-list v-else class="bg-transparent pa-0">
                    <v-list-item
                        v-for="chat in filteredChats"
                        :key="chat.id"
                        class="mb-2 bg-white rounded-lg cursor-pointer"
                        elevation="1"
                        @click="openChat(chat)"
                    >
                        <template v-slot:prepend>
                            <v-avatar 
                                :color="!chat.other_user_picture ? 'primary' : undefined" 
                                size="48"
                            >
                                <v-img 
                                    v-if="chat.other_user_picture"
                                    :src="chat.other_user_picture"
                                    cover
                                />
                                <span v-else class="text-h6 text-white">
                                    {{ getInitials(chat.other_user_name) }}
                                </span>
                            </v-avatar>
                        </template>

                        <v-list-item-title class="font-weight-bold">
                            {{ chat.other_user_name }}
                            <v-chip 
                                v-if="chat.rescue_status" 
                                size="x-small" 
                                :color="getStatusColor(chat.rescue_status)"
                                class="ml-2"
                            >
                                {{ formatStatus(chat.rescue_status) }}
                            </v-chip>
                        </v-list-item-title>
                        <v-list-item-subtitle class="text-truncate">
                            {{ chat.last_message }}
                        </v-list-item-subtitle>

                        <template v-slot:append>
                            <div class="text-right">
                                <span class="text-caption text-grey d-block">
                                    {{ formatTime(chat.last_message_time) }}
                                </span>
                                <v-badge
                                    v-if="chat.unread_count > 0"
                                    :content="chat.unread_count"
                                    color="error"
                                    inline
                                />
                            </div>
                        </template>
                    </v-list-item>
                </v-list>
            </v-container>

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
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { getConversations, getProfilePictureUrl } from '@/Composables/useApi';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import UserMenu from '@/Components/Pages/User/Menu/UserMenu.vue';
import NotificationPopup from '@/Components/NotificationPopup.vue';

// State
const drawer = ref(false);
const chats = ref([]);
const loading = ref(true);
const searchText = ref('');
const pollingInterval = ref(null);

// Notification Alert
const { playNotificationSound, vibrate, notify } = useNotificationAlert();
const popupAlert = ref({
    show: false,
    title: '',
    message: '',
    type: 'info',
    icon: 'mdi-information',
});
const pendingChatClick = ref(null);

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
    if (!searchText.value) return chats.value;
    return chats.value.filter((chat) =>
        chat.other_user_name.toLowerCase().includes(searchText.value.toLowerCase())
    );
});

// Handle notification click - navigate to chat
const handleNotificationClick = () => {
    popupAlert.value.show = false;
    if (pendingChatClick.value) {
        openChat(pendingChatClick.value);
        pendingChatClick.value = null;
    }
};

// Trigger notification for new messages
const triggerNewMessageNotification = (chat) => {
    // Play sound and vibrate
    playNotificationSound('message');
    vibrate('message');
    
    // Set the chat to navigate to on click
    pendingChatClick.value = chat;
    
    // Show popup
    popupAlert.value = {
        show: true,
        title: '💬 New Message from Rescuer',
        message: `${chat.other_user_name}: ${chat.last_message}`,
        type: 'info',
        icon: 'mdi-message-text'
    };
    
    // Also show browser notification
    notify({
        title: `New message from ${chat.other_user_name}`,
        body: chat.last_message,
        icon: '/icons/icon-192x192.png'
    });
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        popupAlert.value.show = false;
    }, 5000);
};

onMounted(async () => {
    await fetchChats();
    
    // Poll for new messages every 8 seconds
    pollingInterval.value = setInterval(fetchChats, 8000);
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
});

const fetchChats = async () => {
    const isInitialLoad = loading.value;
    if (isInitialLoad) loading.value = true;
    
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

        const newChats = conversations.map((conv) => {
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
                unread_count: otherParticipant?.unread_count || 0,
                rescue_status: rescueRequest?.status,
                created_at: conv.created_at,
            };
        });
        
        // Check for new messages (increased unread count)
        if (!wasEmpty && !isInitialLoad) {
            for (const chat of newChats) {
                const prevCount = previousUnreadCounts[chat.id] || 0;
                const newCount = chat.unread_count || 0;
                
                if (newCount > prevCount) {
                    // New messages detected!
                    triggerNewMessageNotification(chat);
                    break; // Only show one notification at a time
                }
            }
        }
        
        chats.value = newChats;
    } catch (err) {
        console.error('Error fetching chats:', err);
        if (loading.value) {
            toastMessage.value = 'Failed to load chats. Please try again.';
            toastColor.value = 'error';
            showToast.value = true;
        }
    } finally {
        loading.value = false;
    }
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
</script>

<style scoped>
/* Component-specific styles only - background is now global */
</style>
