/**
 * Firebase Cloud Functions for PinPointMe FCM Notifications
 * 
 * This function sends push notifications to users via Firebase Cloud Messaging.
 * It looks up FCM tokens from the Firestore 'users' collection.
 */

const { initializeApp } = require('firebase-admin/app');
const { getFirestore } = require('firebase-admin/firestore');
const { getMessaging } = require('firebase-admin/messaging');
const { onCall, HttpsError } = require('firebase-functions/v2/https');
const { logger } = require('firebase-functions');

// Initialize Firebase Admin
initializeApp();

const db = getFirestore();
const messaging = getMessaging();

/**
 * Send notifications to multiple users by their user IDs
 * 
 * This is a callable function that accepts:
 * - title: Notification title
 * - body: Notification body  
 * - userIds: Array of user IDs to send notifications to
 * - data: Optional additional data to include in the notification
 * 
 * @param {Object} request - The callable request object
 * @returns {Object} - Result with success count and failures
 */
exports.sendNotifications = onCall({
    cors: true,
    maxInstances: 10,
}, async (request) => {
    const { title, body, userIds, data = {} } = request.data;

    // Validate input
    if (!title || typeof title !== 'string') {
        throw new HttpsError('invalid-argument', 'Title is required and must be a string');
    }
    
    if (!body || typeof body !== 'string') {
        throw new HttpsError('invalid-argument', 'Body is required and must be a string');
    }
    
    if (!userIds || !Array.isArray(userIds) || userIds.length === 0) {
        throw new HttpsError('invalid-argument', 'userIds is required and must be a non-empty array');
    }

    logger.info('Sending notifications', {
        title,
        body,
        userCount: userIds.length,
        userIds
    });

    try {
        // Fetch FCM tokens for all user IDs from Firestore
        const tokens = [];
        const userTokenMap = {};
        
        // Query Firestore for each user ID
        const userPromises = userIds.map(async (userId) => {
            const userDoc = await db.collection('users').doc(String(userId)).get();
            
            if (userDoc.exists) {
                const userData = userDoc.data();
                if (userData.fcmtoken) {
                    tokens.push(userData.fcmtoken);
                    userTokenMap[userData.fcmtoken] = userId;
                    logger.info(`Found FCM token for user ${userId}`);
                } else {
                    logger.warn(`No FCM token found for user ${userId}`);
                }
            } else {
                logger.warn(`User document not found for user ${userId}`);
            }
        });

        await Promise.all(userPromises);

        if (tokens.length === 0) {
            logger.warn('No valid FCM tokens found for any user');
            return {
                success: true,
                message: 'No users with FCM tokens found',
                sentCount: 0,
                failedCount: 0,
                totalUsers: userIds.length
            };
        }

        logger.info(`Found ${tokens.length} FCM tokens, sending notifications...`);

        // Build the message payload
        // NOTE: For web push, we use data-only messages to give the service worker full control
        // When a 'notification' field is present, Chrome may auto-display a generic notification
        const message = {
            data: {
                ...data,
                title: title,
                body: body,
                click_action: data.click_action || '/',
                timestamp: String(Date.now()),
            },
            android: {
                notification: {
                    title: title,
                    body: body,
                    icon: 'ic_notification',
                    color: '#1976D2',
                    sound: 'default',
                    priority: 'high',
                    channelId: 'rescue_alerts',
                },
                priority: 'high',
            },
            apns: {
                payload: {
                    aps: {
                        alert: {
                            title: title,
                            body: body,
                        },
                        sound: 'default',
                        badge: 1,
                        'content-available': 1,
                    },
                },
            },
            webpush: {
                headers: {
                    Urgency: 'high',
                },
                data: {
                    title: title,
                    body: body,
                },
                fcmOptions: {
                    link: data.click_action || '/',
                },
            },
        };

        // Send to all tokens using sendEachForMulticast
        const response = await messaging.sendEachForMulticast({
            tokens: tokens,
            ...message,
        });

        logger.info('Notification send results', {
            successCount: response.successCount,
            failureCount: response.failureCount,
        });

        // Process failed tokens (remove invalid ones from Firestore)
        const failedTokens = [];
        response.responses.forEach((resp, idx) => {
            if (!resp.success) {
                const errorCode = resp.error?.code;
                logger.error(`Failed to send to token ${idx}`, {
                    error: resp.error?.message,
                    code: errorCode
                });
                
                // If token is invalid, mark for removal
                if (errorCode === 'messaging/invalid-registration-token' ||
                    errorCode === 'messaging/registration-token-not-registered') {
                    failedTokens.push(tokens[idx]);
                }
            }
        });

        // Clean up invalid tokens from Firestore
        if (failedTokens.length > 0) {
            logger.info(`Cleaning up ${failedTokens.length} invalid tokens`);
            const cleanupPromises = failedTokens.map(async (token) => {
                const userId = userTokenMap[token];
                if (userId) {
                    await db.collection('users').doc(String(userId)).update({
                        fcmtoken: null,
                        tokenInvalidatedAt: new Date(),
                    });
                }
            });
            await Promise.all(cleanupPromises);
        }

        return {
            success: true,
            message: `Notifications sent successfully`,
            sentCount: response.successCount,
            failedCount: response.failureCount,
            totalUsers: userIds.length,
            tokensFound: tokens.length,
        };
    } catch (error) {
        logger.error('Error sending notifications', { error: error.message });
        throw new HttpsError('internal', `Failed to send notifications: ${error.message}`);
    }
});

/**
 * Send notification to users by role
 * 
 * This function queries the MySQL database via the users collection
 * and sends notifications to all users with a specific role.
 * 
 * @param {Object} request - Contains title, body, role, and optional data
 */
exports.sendNotificationsByRole = onCall({
    cors: true,
    maxInstances: 10,
}, async (request) => {
    const { title, body, role, data = {} } = request.data;

    // Validate input
    if (!title || typeof title !== 'string') {
        throw new HttpsError('invalid-argument', 'Title is required and must be a string');
    }
    
    if (!body || typeof body !== 'string') {
        throw new HttpsError('invalid-argument', 'Body is required and must be a string');
    }
    
    if (!role || typeof role !== 'string') {
        throw new HttpsError('invalid-argument', 'Role is required and must be a string');
    }

    logger.info('Sending notifications by role', { title, body, role });

    try {
        // Query all users collection documents that have fcmtoken set
        const usersSnapshot = await db.collection('users')
            .where('fcmtoken', '!=', null)
            .get();

        if (usersSnapshot.empty) {
            logger.warn('No users with FCM tokens found in Firestore');
            return {
                success: true,
                message: 'No users with FCM tokens found',
                sentCount: 0,
                failedCount: 0,
            };
        }

        const tokens = [];
        usersSnapshot.forEach((doc) => {
            const userData = doc.data();
            if (userData.fcmtoken) {
                tokens.push(userData.fcmtoken);
            }
        });

        if (tokens.length === 0) {
            return {
                success: true,
                message: 'No valid FCM tokens found',
                sentCount: 0,
                failedCount: 0,
            };
        }

        logger.info(`Found ${tokens.length} FCM tokens, sending notifications...`);

        // Build the message payload (data-only for web push control)
        const message = {
            data: {
                ...data,
                title: title,
                body: body,
                role: role,
                timestamp: String(Date.now()),
            },
            webpush: {
                headers: {
                    Urgency: 'high',
                },
                data: {
                    title: title,
                    body: body,
                },
            },
        };

        // Send to all tokens
        const response = await messaging.sendEachForMulticast({
            tokens: tokens,
            ...message,
        });

        logger.info('Notification send results', {
            successCount: response.successCount,
            failureCount: response.failureCount,
        });

        return {
            success: true,
            sentCount: response.successCount,
            failedCount: response.failureCount,
            totalTokens: tokens.length,
        };
    } catch (error) {
        logger.error('Error sending notifications by role', { error: error.message });
        throw new HttpsError('internal', `Failed to send notifications: ${error.message}`);
    }
});
