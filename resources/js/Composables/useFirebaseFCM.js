/**
 * Composable for Firebase Cloud Messaging integration
 * Handles FCM token initialization and storage in Firestore after user login
 */

import { ref, computed, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { 
    initializeFCMForUser, 
    onForegroundMessage, 
    initializeFirebase,
    removeUserFCMToken 
} from '@/Utilities/firebase';

// Global state to track if FCM has been initialized
const fcmInitialized = ref(false);
const fcmToken = ref(null);
const fcmError = ref(null);

export function useFirebaseFCM() {
    const page = usePage();
    
    // Get authenticated user from page props
    const authUser = computed(() => page.props.auth?.user || null);
    const userId = computed(() => authUser.value?.id || null);
    
    /**
     * Initialize Firebase Cloud Messaging for the current user
     * This stores/updates the FCM token in Firestore
     */
    const initializeFCM = async () => {
        // Don't initialize if no authenticated user
        if (!userId.value) {
            console.log('[useFirebaseFCM] No authenticated user, skipping FCM initialization');
            return { success: false, error: 'No authenticated user' };
        }

        // Don't re-initialize if already done for this session
        if (fcmInitialized.value && fcmToken.value) {
            console.log('[useFirebaseFCM] FCM already initialized for this session');
            return { success: true, token: fcmToken.value };
        }

        console.log('[useFirebaseFCM] Initializing FCM for user:', userId.value);

        try {
            // Initialize Firebase and get FCM token, store in Firestore
            const result = await initializeFCMForUser(userId.value);
            
            if (result.success) {
                fcmToken.value = result.token;
                fcmInitialized.value = true;
                fcmError.value = null;
                
                // Set up foreground message handler
                onForegroundMessage((payload) => {
                    console.log('[useFirebaseFCM] Foreground message received:', payload);
                });
                
                console.log('[useFirebaseFCM] FCM initialized successfully');
            } else {
                fcmError.value = result.error;
                console.warn('[useFirebaseFCM] FCM initialization failed:', result.error);
            }
            
            return result;
        } catch (error) {
            fcmError.value = error.message;
            console.error('[useFirebaseFCM] Error initializing FCM:', error);
            return { success: false, error: error.message };
        }
    };

    /**
     * Clear FCM token on logout
     */
    const clearFCMOnLogout = async () => {
        if (userId.value) {
            try {
                await removeUserFCMToken(userId.value);
            } catch (error) {
                console.error('[useFirebaseFCM] Error clearing FCM token:', error);
            }
        }
        fcmToken.value = null;
        fcmInitialized.value = false;
        fcmError.value = null;
    };

    // Auto-initialize on mount if user is authenticated
    onMounted(async () => {
        if (userId.value) {
            // Small delay to ensure page is fully loaded
            setTimeout(() => {
                initializeFCM();
            }, 1000);
        }
    });

    // Watch for user changes (e.g., login/logout)
    watch(userId, async (newUserId, oldUserId) => {
        if (newUserId && newUserId !== oldUserId) {
            // User logged in or changed
            await initializeFCM();
        } else if (!newUserId && oldUserId) {
            // User logged out
            await clearFCMOnLogout();
        }
    });

    return {
        authUser,
        userId,
        fcmInitialized,
        fcmToken,
        fcmError,
        initializeFCM,
        clearFCMOnLogout
    };
}

export default useFirebaseFCM;
