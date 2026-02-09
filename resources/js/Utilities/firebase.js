/**
 * Firebase Configuration and Utilities for PinPointMe
 * Handles Firebase initialization, FCM token generation, and Firestore user token storage
 */

import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, onMessage, deleteToken } from 'firebase/messaging';
import { getFirestore, doc, setDoc, getDoc, serverTimestamp, collection, query, where, getDocs } from 'firebase/firestore';
import { getFunctions, httpsCallable } from 'firebase/functions';

// Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyC29IDagVxBaw91vOVwgx0u78BSOOwG-w8",
    authDomain: "ppm-fcm.firebaseapp.com",
    projectId: "ppm-fcm",
    storageBucket: "ppm-fcm.firebasestorage.app",
    messagingSenderId: "792412354648",
    appId: "1:792412354648:web:686e84c46bfb35d49af22d"
};

// VAPID key for FCM web push (generated in Firebase Console)
const VAPID_KEY = 'BHp1SB_4ynWNV2cv7nKcI2UEvO61LcYfltRiBkT60ZD_0iU738iFn-ylPy9ETIcd8u4LvyeLckMQ_Reacmz0C5o';

// Initialize Firebase
let app = null;
let messaging = null;
let db = null;
let functions = null;

/**
 * Initialize Firebase app and services
 * @returns {Object} - Firebase app instance
 */
export function initializeFirebase() {
    if (!app) {
        app = initializeApp(firebaseConfig);
        console.log('[Firebase] App initialized');
    }
    return app;
}

/**
 * Get Firebase Functions instance
 * @returns {Object} - Firebase Functions instance
 */
export function getFirebaseFunctions() {
    if (!functions) {
        const firebaseApp = initializeFirebase();
        functions = getFunctions(firebaseApp);
        console.log('[Firebase] Functions initialized');
    }
    return functions;
}

/**
 * Get Firebase Messaging instance
 * @returns {Object|null} - Firebase Messaging instance or null if not supported
 */
export function getFirebaseMessaging() {
    if (!messaging) {
        try {
            const firebaseApp = initializeFirebase();
            messaging = getMessaging(firebaseApp);
            console.log('[Firebase] Messaging initialized');
        } catch (error) {
            console.error('[Firebase] Error initializing messaging:', error);
            return null;
        }
    }
    return messaging;
}

/**
 * Get Firestore instance
 * @returns {Object} - Firestore instance
 */
export function getFirestoreDb() {
    if (!db) {
        const firebaseApp = initializeFirebase();
        db = getFirestore(firebaseApp);
        console.log('[Firebase] Firestore initialized');
    }
    return db;
}

/**
 * Request FCM token from Firebase (with force refresh option)
 * @param {boolean} forceRefresh - If true, deletes existing token and gets a new one
 * @returns {Promise<string|null>} - FCM token or null if failed
 */
export async function requestFCMToken(forceRefresh = false) {
    try {
        const fcmMessaging = getFirebaseMessaging();
        
        if (!fcmMessaging) {
            console.warn('[Firebase] Messaging not available');
            return null;
        }

        // Check notification permission
        if (Notification.permission === 'denied') {
            console.warn('[Firebase] Notification permission denied');
            return null;
        }

        // Request permission if not granted
        if (Notification.permission !== 'granted') {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                console.warn('[Firebase] Notification permission was not granted');
                return null;
            }
        }

        // Get or register Firebase messaging service worker
        let serviceWorkerRegistration = null;
        if ('serviceWorker' in navigator) {
            try {
                // Unregister existing service workers first if force refreshing
                if (forceRefresh) {
                    const existingRegistrations = await navigator.serviceWorker.getRegistrations();
                    for (const reg of existingRegistrations) {
                        if (reg.active?.scriptURL?.includes('firebase-messaging-sw.js')) {
                            console.log('[Firebase] Unregistering old messaging service worker');
                            await reg.unregister();
                        }
                    }
                }
                
                // Register firebase-messaging-sw.js
                serviceWorkerRegistration = await navigator.serviceWorker.register('/firebase-messaging-sw.js', {
                    scope: '/'
                });
                
                // Wait for the service worker to be ready
                await navigator.serviceWorker.ready;
                console.log('[Firebase] Messaging service worker registered');
            } catch (swError) {
                console.warn('[Firebase] Could not register messaging service worker:', swError);
                serviceWorkerRegistration = await navigator.serviceWorker.ready;
            }
        }

        // Delete existing token if force refreshing
        if (forceRefresh) {
            try {
                console.log('[Firebase] Deleting existing FCM token...');
                await deleteToken(fcmMessaging);
                console.log('[Firebase] Old FCM token deleted');
            } catch (deleteError) {
                console.warn('[Firebase] Could not delete old token (may not exist):', deleteError.message);
            }
        }

        // Get FCM token
        const token = await getToken(fcmMessaging, {
            vapidKey: VAPID_KEY,
            serviceWorkerRegistration: serviceWorkerRegistration
        });

        if (token) {
            console.log('[Firebase] FCM token obtained:', token.substring(0, 20) + '...');
            return token;
        } else {
            console.warn('[Firebase] No FCM token available');
            return null;
        }
    } catch (error) {
        console.error('[Firebase] Error getting FCM token:', error);
        return null;
    }
}

/**
 * Store or update user FCM token in Firestore
 * @param {number|string} userId - User ID from MySQL database
 * @param {string} fcmToken - FCM token to store
 * @returns {Promise<boolean>} - true if successful, false otherwise
 */
export async function storeUserFCMToken(userId, fcmToken) {
    try {
        const firestore = getFirestoreDb();
        const userDocRef = doc(firestore, 'users', String(userId));
        
        // Check if document exists and update, or create new
        // Also set isActive to true when storing FCM token (user is logging in)
        await setDoc(userDocRef, {
            userid: userId,
            fcmtoken: fcmToken,
            isActive: true,
            updatedAt: serverTimestamp()
        }, { merge: true });

        console.log('[Firebase] User FCM token stored/updated in Firestore for user:', userId);
        return true;
    } catch (error) {
        console.error('[Firebase] Error storing FCM token in Firestore:', error);
        return false;
    }
}

/**
 * Get user FCM token from Firestore
 * @param {number|string} userId - User ID from MySQL database
 * @returns {Promise<string|null>} - FCM token or null if not found
 */
export async function getUserFCMToken(userId) {
    try {
        const firestore = getFirestoreDb();
        const userDocRef = doc(firestore, 'users', String(userId));
        const docSnap = await getDoc(userDocRef);
        
        if (docSnap.exists()) {
            return docSnap.data().fcmtoken || null;
        }
        return null;
    } catch (error) {
        console.error('[Firebase] Error getting FCM token from Firestore:', error);
        return null;
    }
}

/**
 * Initialize FCM and store token for logged-in user
 * This should be called after successful login
 * @param {number|string} userId - User ID from the MySQL database
 * @param {boolean} forceRefresh - If true, forces regeneration of FCM token
 * @returns {Promise<{success: boolean, token?: string, error?: string}>}
 */
export async function initializeFCMForUser(userId, forceRefresh = true) {
    console.log('[Firebase] Initializing FCM for user:', userId, '| forceRefresh:', forceRefresh);
    
    try {
        // Initialize Firebase first
        initializeFirebase();
        
        // Get FCM token (force refresh by default to ensure valid token)
        const fcmToken = await requestFCMToken(forceRefresh);
        
        if (!fcmToken) {
            return { 
                success: false, 
                error: 'Failed to obtain FCM token. Please ensure notifications are enabled.' 
            };
        }

        // Store token in Firestore
        const stored = await storeUserFCMToken(userId, fcmToken);
        
        if (!stored) {
            return { 
                success: false, 
                error: 'Failed to store FCM token in database.' 
            };
        }

        return { 
            success: true, 
            token: fcmToken 
        };
    } catch (error) {
        console.error('[Firebase] Error initializing FCM for user:', error);
        return { 
            success: false, 
            error: error.message || 'Unknown error occurred' 
        };
    }
}

/**
 * Set up foreground message handler
 * @param {Function} callback - Callback function to handle incoming messages
 */
export function onForegroundMessage(callback) {
    const fcmMessaging = getFirebaseMessaging();
    
    if (!fcmMessaging) {
        console.warn('[Firebase] Messaging not available for foreground handler');
        return;
    }
    
    onMessage(fcmMessaging, (payload) => {
        console.log('[Firebase] Foreground message received:', payload);
        
        if (callback && typeof callback === 'function') {
            callback(payload);
        }
        
        // Show notification manually if in foreground
        if (Notification.permission === 'granted' && payload.notification) {
            const { title, body, icon } = payload.notification;
            new Notification(title || 'PinPointMe', {
                body: body || 'You have a new notification',
                icon: icon || '/images/logos/pinpointme.png',
                badge: '/images/logos/pinpointme.png',
            });
        }
    });
}

/**
 * Remove user FCM token from Firestore (for logout)
 * Also sets isActive to false
 * @param {number|string} userId - User ID from MySQL database
 * @returns {Promise<boolean>}
 */
export async function removeUserFCMToken(userId) {
    try {
        const firestore = getFirestoreDb();
        const userDocRef = doc(firestore, 'users', String(userId));
        
        await setDoc(userDocRef, {
            userid: userId,
            fcmtoken: null,
            isActive: false,
            updatedAt: serverTimestamp()
        }, { merge: true });

        console.log('[Firebase] User FCM token removed and isActive set to false for user:', userId);
        return true;
    } catch (error) {
        console.error('[Firebase] Error removing FCM token from Firestore:', error);
        return false;
    }
}

/**
 * Set user's active status in Firestore
 * @param {number|string} userId - User ID from MySQL database
 * @param {boolean} isActive - Whether the user is active/online
 * @returns {Promise<boolean>}
 */
export async function setUserActiveStatus(userId, isActive) {
    try {
        const firestore = getFirestoreDb();
        const userDocRef = doc(firestore, 'users', String(userId));
        
        await setDoc(userDocRef, {
            userid: userId,
            isActive: isActive,
            updatedAt: serverTimestamp()
        }, { merge: true });

        console.log(`[Firebase] User ${userId} isActive set to ${isActive}`);
        return true;
    } catch (error) {
        console.error('[Firebase] Error setting user active status:', error);
        return false;
    }
}

/**
 * Check if a user is currently active
 * @param {number|string} userId - User ID from MySQL database
 * @returns {Promise<boolean>} - true if user is active, false otherwise
 */
export async function isUserActive(userId) {
    try {
        const firestore = getFirestoreDb();
        const userDocRef = doc(firestore, 'users', String(userId));
        const docSnap = await getDoc(userDocRef);
        
        if (docSnap.exists()) {
            return docSnap.data().isActive === true;
        }
        return false;
    } catch (error) {
        console.error('[Firebase] Error checking user active status:', error);
        return false;
    }
}

/**
 * Get user data from Firestore (including isActive and fcmtoken)
 * @param {number|string} userId - User ID from MySQL database
 * @returns {Promise<{isActive: boolean, fcmtoken: string|null}|null>}
 */
export async function getUserFirestoreData(userId) {
    try {
        const firestore = getFirestoreDb();
        const userDocRef = doc(firestore, 'users', String(userId));
        const docSnap = await getDoc(userDocRef);
        
        if (docSnap.exists()) {
            const data = docSnap.data();
            return {
                isActive: data.isActive === true,
                fcmtoken: data.fcmtoken || null,
                userid: data.userid
            };
        }
        return null;
    } catch (error) {
        console.error('[Firebase] Error getting user Firestore data:', error);
        return null;
    }
}

/**
 * Send notification to a user only if they are inactive (not currently using the app)
 * This is useful for chat messages - only notify if recipient is not actively using the app
 * @param {number|string} userId - User ID to potentially notify
 * @param {string} title - Notification title
 * @param {string} body - Notification body
 * @param {Object} data - Optional additional data for the notification
 * @returns {Promise<{success: boolean, notified: boolean, reason?: string}>}
 */
export async function notifyIfUserInactive(userId, title, body, data = {}) {
    console.log('[Firebase] Checking if user', userId, 'needs notification...');
    
    try {
        const userData = await getUserFirestoreData(userId);
        
        if (!userData) {
            console.log('[Firebase] User not found in Firestore');
            return { success: false, notified: false, reason: 'user_not_found' };
        }
        
        // If user is active, don't send notification
        if (userData.isActive) {
            console.log('[Firebase] User is active, skipping notification');
            return { success: true, notified: false, reason: 'user_is_active' };
        }
        
        // User is inactive, check if they have an FCM token
        if (!userData.fcmtoken) {
            console.log('[Firebase] User has no FCM token');
            return { success: false, notified: false, reason: 'no_fcm_token' };
        }
        
        // Send notification to the inactive user
        console.log('[Firebase] User is inactive, sending notification...');
        const result = await sendNotifications(title, body, [userId], data);
        
        return {
            success: result.success,
            notified: result.success,
            reason: result.success ? 'notification_sent' : 'send_failed'
        };
    } catch (error) {
        console.error('[Firebase] Error in notifyIfUserInactive:', error);
        return { success: false, notified: false, reason: error.message };
    }
}

/**
 * Send notifications to multiple users via Firebase Cloud Function
 * @param {string} title - Notification title
 * @param {string} body - Notification body
 * @param {Array<number|string>} userIds - Array of user IDs to notify
 * @param {Object} data - Optional additional data for the notification
 * @returns {Promise<{success: boolean, sentCount?: number, failedCount?: number, error?: string}>}
 */
export async function sendNotifications(title, body, userIds, data = {}) {
    console.log('[Firebase] Sending notifications', { title, body, userIds, data });
    
    try {
        const functionsInstance = getFirebaseFunctions();
        const sendNotificationsFunc = httpsCallable(functionsInstance, 'sendNotifications');
        
        const result = await sendNotificationsFunc({
            title,
            body,
            userIds,
            data
        });
        
        console.log('[Firebase] Notifications sent successfully', result.data);
        return result.data;
    } catch (error) {
        console.error('[Firebase] Error sending notifications:', error);
        return {
            success: false,
            error: error.message || 'Failed to send notifications'
        };
    }
}
''
/**
 * Send notifications to all users with FCM tokens in Firestore
 * This function queries Firestore directly and calls the Cloud Function
 * @param {string} title - Notification title
 * @param {string} body - Notification body
 * @param {Object} data - Optional additional data for the notification
 * @returns {Promise<{success: boolean, sentCount?: number, error?: string}>}
 */
export async function sendNotificationsToAllUsers(title, body, data = {}) {
    console.log('[Firebase] Sending notifications to all users with FCM tokens');
    
    try {
        const firestore = getFirestoreDb();
        const usersRef = collection(firestore, 'users');
        const q = query(usersRef, where('fcmtoken', '!=', null));
        const querySnapshot = await getDocs(q);
        
        const userIds = [];
        querySnapshot.forEach((doc) => {
            const userData = doc.data();
            if (userData.userid) {
                userIds.push(userData.userid);
            }
        });
        
        if (userIds.length === 0) {
            console.log('[Firebase] No users with FCM tokens found');
            return {
                success: true,
                sentCount: 0,
                message: 'No users with FCM tokens found'
            };
        }
        
        console.log(`[Firebase] Found ${userIds.length} users with FCM tokens`);
        return await sendNotifications(title, body, userIds, data);
    } catch (error) {
        console.error('[Firebase] Error sending notifications to all users:', error);
        return {
            success: false,
            error: error.message || 'Failed to send notifications'
        };
    }
}

/**
 * Send rescue request notifications to specified user IDs
 * This is a convenience function specifically for rescue requests
 * @param {Object} rescueData - Rescue request data
 * @param {Array<number|string>} rescuerIds - Array of rescuer user IDs
 * @returns {Promise<{success: boolean, sentCount?: number, error?: string}>}
 */
export async function sendRescueNotifications(rescueData, rescuerIds) {
    const { rescueCode, location, urgencyLevel } = rescueData;
    
    // Build notification title based on urgency
    let urgencyPrefix = '';
    if (urgencyLevel === 'critical') {
        urgencyPrefix = '🚨 CRITICAL: ';
    } else if (urgencyLevel === 'high') {
        urgencyPrefix = '⚠️ URGENT: ';
    }
    
    const title = `${urgencyPrefix}New Rescue Request`;
    const body = `Location: ${location || 'Unknown'}\nCode: ${rescueCode}`;
    
    const data = {
        type: 'rescue_request',
        rescue_code: rescueCode,
        urgency_level: urgencyLevel || 'normal',
        click_action: `/rescuer/dashboard?rescue=${rescueCode}`,
        tag: `rescue-${rescueCode}`
    };
    
    return await sendNotifications(title, body, rescuerIds, data);
}

export default {
    initializeFirebase,
    getFirebaseMessaging,
    getFirebaseFunctions,
    getFirestoreDb,
    requestFCMToken,
    storeUserFCMToken,
    getUserFCMToken,
    initializeFCMForUser,
    onForegroundMessage,
    removeUserFCMToken,
    setUserActiveStatus,
    isUserActive,
    getUserFirestoreData,
    notifyIfUserInactive,
    sendNotifications,
    sendNotificationsToAllUsers,
    sendRescueNotifications,
};
