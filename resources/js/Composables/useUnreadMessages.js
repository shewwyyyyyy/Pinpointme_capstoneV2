/**
 * Composable for managing unread message count across all user pages.
 * The count persists in localStorage and is updated when fetching conversations.
 * Count is only reset when the actual chat is opened (via markMessagesAsRead).
 */
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { getConversations } from '@/Composables/useApi';

// Shared reactive state (persists across component instances)
const unreadCount = ref(0);
const previousUnreadCount = ref(0);
const conversations = ref([]);
const isLoading = ref(false);
const lastFetchTime = ref(0);
let pollingInterval = null;
let activeInstances = 0;

// Callback for new messages notification
const newMessageCallbacks = new Set();

// Storage key for persistence
const STORAGE_KEY = 'unread_message_count';
const CONVERSATIONS_KEY = 'unread_conversations';

// Initialize from localStorage
const initFromStorage = () => {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            const count = parseInt(stored, 10) || 0;
            unreadCount.value = count;
            previousUnreadCount.value = count;
        }
        const storedConversations = localStorage.getItem(CONVERSATIONS_KEY);
        if (storedConversations) {
            conversations.value = JSON.parse(storedConversations) || [];
        }
    } catch (e) {
        console.error('Error reading from localStorage:', e);
    }
};

// Save to localStorage
const saveToStorage = () => {
    try {
        localStorage.setItem(STORAGE_KEY, String(unreadCount.value));
        localStorage.setItem(CONVERSATIONS_KEY, JSON.stringify(conversations.value));
    } catch (e) {
        console.error('Error saving to localStorage:', e);
    }
};

// Get current user ID from localStorage
const getCurrentUserId = () => {
    try {
        const userData = localStorage.getItem('userData');
        if (userData) {
            return JSON.parse(userData).id;
        }
    } catch (e) {
        console.error('Error getting user ID:', e);
    }
    return null;
};

// Fetch unread messages from API
const fetchUnreadMessages = async (forceRefresh = false) => {
    const userId = getCurrentUserId();
    if (!userId) return;
    
    // Prevent fetching too frequently (minimum 5 seconds between fetches)
    const now = Date.now();
    if (!forceRefresh && now - lastFetchTime.value < 5000) {
        return;
    }
    
    isLoading.value = true;
    lastFetchTime.value = now;
    
    try {
        const response = await getConversations(userId);
        const data = response.data || response;
        const convList = Array.isArray(data) ? data : (data?.data || []);
        
        // Map conversations with unread counts
        const newConversations = convList.map((conv) => {
            // Find the CURRENT user's participant (for unread_count)
            const myParticipant = conv.participants?.find(
                (p) => String(p.user_id) === String(userId)
            );
            
            return {
                id: conv.id,
                unread_count: myParticipant?.unread_count || 0,
            };
        });
        
        // Calculate total unread count
        const totalUnread = newConversations.reduce((acc, c) => acc + (c.unread_count || 0), 0);
        
        // Check if there are new messages and trigger callbacks
        if (totalUnread > previousUnreadCount.value && previousUnreadCount.value >= 0) {
            const newCount = totalUnread - previousUnreadCount.value;
            // Trigger all registered callbacks
            newMessageCallbacks.forEach(callback => {
                try {
                    callback(newCount, totalUnread);
                } catch (e) {
                    console.error('Error in new message callback:', e);
                }
            });
        }
        
        previousUnreadCount.value = totalUnread;
        unreadCount.value = totalUnread;
        conversations.value = newConversations;
        
        // Persist to localStorage
        saveToStorage();
    } catch (err) {
        console.error('Error fetching unread messages:', err);
    } finally {
        isLoading.value = false;
    }
};

// Start polling for updates
const startPolling = () => {
    if (pollingInterval) return;
    
    // Poll every 10 seconds for more responsive message updates
    pollingInterval = setInterval(() => {
        fetchUnreadMessages();
    }, 10000);
};

// Stop polling
const stopPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
};

/**
 * Main composable function
 * @returns {Object} - Unread message utilities
 */
export function useUnreadMessages() {
    onMounted(() => {
        activeInstances++;
        
        // Initialize from storage on first mount
        if (activeInstances === 1) {
            initFromStorage();
        }
        
        // Fetch fresh data
        fetchUnreadMessages();
        
        // Start polling if first instance
        if (activeInstances === 1) {
            startPolling();
        }
    });
    
    onUnmounted(() => {
        activeInstances--;
        
        // Stop polling when no active instances
        if (activeInstances === 0) {
            stopPolling();
        }
    });
    
    return {
        /** Current total unread message count */
        unreadCount,
        /** List of conversations with unread counts */
        conversations,
        /** Whether currently loading */
        isLoading,
        /** Manually refresh unread count */
        refresh: () => fetchUnreadMessages(true),
        /** Get the unread count (for non-reactive usage) */
        getCount: () => unreadCount.value,
        /** Register a callback for new messages */
        onNewMessages: (callback) => {
            newMessageCallbacks.add(callback);
            // Return unregister function
            return () => newMessageCallbacks.delete(callback);
        },
    };
}

// Export a simple function to get count without mounting (for quick access)
export function getUnreadMessageCount() {
    initFromStorage();
    return unreadCount.value;
}

// Export function to manually update count (e.g., after opening a chat)
export function updateUnreadCount(newCount) {
    unreadCount.value = newCount;
    saveToStorage();
}
