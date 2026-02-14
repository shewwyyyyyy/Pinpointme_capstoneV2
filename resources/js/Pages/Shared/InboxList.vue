<template>
    <div class="inbox-wrapper">
        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <v-progress-circular indeterminate color="primary" size="48" />
            <p class="text-grey mt-4">Loading your messages...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="chats.length === 0" class="empty-state-container">
            <div class="empty-state-card">
                <div class="empty-icon-wrapper">
                    <v-icon size="80" color="primary">mdi-message-text-outline</v-icon>
                </div>
                <h3>No Messages Yet</h3>
                <p>{{ emptyMessage }}</p>
            </div>
        </div>

        <!-- Chat List -->
        <div v-else class="chat-list-container" ref="chatListContainer">
            <div class="chat-list">
                <div
                    v-for="chat in chats"
                    :key="chat.id"
                    class="chat-item"
                    @click="$emit('open-chat', chat)"
                >
                    <!-- Avatar with Badge -->
                    <div class="chat-avatar">
                        <v-badge
                            :model-value="chat.unreadCount > 0"
                            color="error"
                            dot
                            location="bottom end"
                            offset-x="4"
                            offset-y="4"
                        >
                            <v-avatar
                                :color="!chat.picture ? (chat.avatarColor || 'primary') : undefined"
                                size="48"
                                class="avatar-shadow"
                            >
                                <v-img 
                                    v-if="chat.picture"
                                    :src="chat.picture"
                                    cover
                                />
                                <span v-else class="text-white font-weight-bold">{{ getInitials(chat.name) }}</span>
                            </v-avatar>
                        </v-badge>
                    </div>

                    <!-- Chat Content -->
                    <div class="chat-content">
                        <div class="chat-header">
                            <h4 class="chat-name">{{ chat.name }}</h4>
                            <div class="chat-meta">
                                <span class="chat-time">{{ formatTime(chat.lastMessageTime) }}</span>
                            </div>
                        </div>
                        
                        <div class="chat-message">
                            <p :class="{ 'unread-message': chat.unreadCount > 0 }">
                                <v-icon v-if="chat.isOwnMessage" size="14" class="mr-1" color="grey">
                                    mdi-check-all
                                </v-icon>
                                {{ chat.lastMessage }}
                            </p>
                        </div>
                    </div>

                    <!-- Unread Count or Arrow -->
                    <div class="chat-action">
                        <v-chip
                            v-if="chat.unreadCount > 0"
                            size="small"
                            color="#3674B5"
                            variant="flat"
                            class="unread-chip"
                        >
                            {{ chat.unreadCount }}
                        </v-chip>
                        <v-icon v-else color="grey-darken-1" size="20">mdi-chevron-right</v-icon>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pull to refresh indicator -->
        <div v-if="refreshing" class="d-flex justify-center py-4">
            <v-progress-circular indeterminate color="primary" size="24" />
        </div>
    </div>
</template>

<script setup>
import { ref, watch, nextTick, onMounted } from 'vue';

const props = defineProps({
    chats: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    refreshing: {
        type: Boolean,
        default: false,
    },
    emptyMessage: {
        type: String,
        default: 'Messages will appear here',
    },
    scrollStorageKey: {
        type: String,
        default: 'inbox_scroll_position',
    },
});

defineEmits(['open-chat']);

const chatListContainer = ref(null);

// Scroll position management
const saveScrollPosition = () => {
    if (chatListContainer.value) {
        sessionStorage.setItem(props.scrollStorageKey, chatListContainer.value.scrollTop.toString());
    }
};

const restoreScrollPosition = async () => {
    await nextTick();
    const saved = sessionStorage.getItem(props.scrollStorageKey);
    if (saved && chatListContainer.value) {
        chatListContainer.value.scrollTop = parseInt(saved, 10);
    }
};

// Restore scroll when chats load for the first time
watch(() => props.loading, (newVal, oldVal) => {
    if (oldVal && !newVal) {
        restoreScrollPosition();
    }
});

onMounted(() => {
    window.addEventListener('beforeunload', saveScrollPosition);
});

// Expose save for parent to call before navigation
defineExpose({ saveScrollPosition, restoreScrollPosition });

// Helper functions
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

    // If today, show time only (e.g. 10:30 AM)
    if (messageDate.toDateString() === now.toDateString()) {
        return messageDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // If yesterday
    const yesterday = new Date(now);
    yesterday.setDate(yesterday.getDate() - 1);
    if (messageDate.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    }

    // If this week, show day name
    const diffDays = Math.floor((now - messageDate) / 86400000);
    if (diffDays < 7) {
        return messageDate.toLocaleDateString([], { weekday: 'short' });
    }

    // If this year, show month and day
    if (messageDate.getFullYear() === now.getFullYear()) {
        return messageDate.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }

    // Otherwise show full date
    return messageDate.toLocaleDateString();
};
</script>

<style scoped>
.inbox-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* Loading State */
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
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
    flex: 1;
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
    width: 100%;
}

.chat-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    width: 100%;
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
}

.chat-action .v-icon {
    opacity: 0.6;
    transition: opacity 0.3s ease;
}

.chat-item:hover .chat-action .v-icon {
    opacity: 1;
}

/* Responsive */
@media (max-width: 600px) {
    .chat-list-container {
        padding: 0.75rem;
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

@media (max-width: 480px) {
    .chat-list-container {
        padding: 0.5rem;
    }
    
    .chat-item {
        padding: 12px 16px;
        width: 100%;
        box-sizing: border-box;
    }
    
    .chat-content {
        min-width: 0;
        flex: 1;
    }
    
    .chat-name {
        font-size: 0.9rem;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    
    .chat-message p {
        font-size: 0.8rem;
        line-height: 1.4;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
}

@media (min-width: 1024px) {
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
</style>
