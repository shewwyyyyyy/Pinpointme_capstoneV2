/**
 * Push Notification Utility for PinPointMe
 * Handles service worker registration, notification permissions, and push subscriptions
 */

// Register the service worker
export async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        console.warn('Service workers are not supported');
        return null;
    }

    try {
        const registration = await navigator.serviceWorker.register('/service-worker.js', {
            scope: '/'
        });
        console.log('[Push] Service worker registered:', registration.scope);
        return registration;
    } catch (error) {
        console.error('[Push] Service worker registration failed:', error);
        return null;
    }
}

// Get the current service worker registration
export async function getServiceWorkerRegistration() {
    if (!('serviceWorker' in navigator)) {
        return null;
    }
    return navigator.serviceWorker.ready;
}

// Check if push notifications are supported
export function isPushSupported() {
    return 'PushManager' in window && 'serviceWorker' in navigator && 'Notification' in window;
}

// Get current notification permission status
export function getNotificationPermission() {
    if (!('Notification' in window)) {
        return 'unsupported';
    }
    return Notification.permission;
}

// Request notification permission
export async function requestNotificationPermission() {
    if (!('Notification' in window)) {
        console.warn('Notifications are not supported');
        return 'unsupported';
    }

    if (Notification.permission === 'granted') {
        return 'granted';
    }

    if (Notification.permission === 'denied') {
        console.warn('Notification permission was previously denied');
        return 'denied';
    }

    try {
        const permission = await Notification.requestPermission();
        console.log('[Push] Notification permission:', permission);
        return permission;
    } catch (error) {
        console.error('[Push] Error requesting notification permission:', error);
        return 'denied';
    }
}

// Get the VAPID public key from the server
export async function getVapidPublicKey() {
    try {
        const response = await fetch('/api/push/vapid-public-key');
        const data = await response.json();
        return data.publicKey;
    } catch (error) {
        console.error('[Push] Error fetching VAPID public key:', error);
        return null;
    }
}

// Convert base64 string to Uint8Array (for VAPID key)
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}

// Subscribe to push notifications
export async function subscribeToPush(registration = null) {
    try {
        if (!registration) {
            registration = await getServiceWorkerRegistration();
        }

        if (!registration) {
            console.error('[Push] No service worker registration available');
            return null;
        }

        // Check if already subscribed
        let subscription = await registration.pushManager.getSubscription();
        
        if (subscription) {
            console.log('[Push] Already subscribed, updating server...');
            await sendSubscriptionToServer(subscription);
            return subscription;
        }

        // Get the VAPID public key
        const vapidPublicKey = await getVapidPublicKey();
        
        if (!vapidPublicKey) {
            console.error('[Push] VAPID public key not available');
            return null;
        }

        // Subscribe to push notifications
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
        });

        console.log('[Push] Push subscription created:', subscription);

        // Send subscription to server
        await sendSubscriptionToServer(subscription);

        return subscription;
    } catch (error) {
        console.error('[Push] Error subscribing to push notifications:', error);
        return null;
    }
}

// Send subscription to the server
export async function sendSubscriptionToServer(subscription) {
    try {
        // Convert ArrayBuffer to base64url (required for Web Push)
        const arrayBufferToBase64Url = (buffer) => {
            const bytes = new Uint8Array(buffer);
            let binary = '';
            for (let i = 0; i < bytes.byteLength; i++) {
                binary += String.fromCharCode(bytes[i]);
            }
            // Convert to base64, then convert to base64url
            return btoa(binary)
                .replace(/\+/g, '-')
                .replace(/\//g, '_')
                .replace(/=+$/, '');
        };

        const p256dhKey = subscription.getKey('p256dh');
        const authKey = subscription.getKey('auth');

        if (!p256dhKey || !authKey) {
            console.error('[Push] Missing subscription keys');
            return false;
        }

        const response = await fetch('/api/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                endpoint: subscription.endpoint,
                keys: {
                    p256dh: arrayBufferToBase64Url(p256dhKey),
                    auth: arrayBufferToBase64Url(authKey),
                },
                contentEncoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0],
            }),
            credentials: 'same-origin',
        });

        const data = await response.json();
        
        if (data.success) {
            console.log('[Push] Subscription saved to server');
        } else {
            console.error('[Push] Failed to save subscription:', data.message);
        }

        return data.success;
    } catch (error) {
        console.error('[Push] Error sending subscription to server:', error);
        return false;
    }
}

// Unsubscribe from push notifications
export async function unsubscribeFromPush() {
    try {
        const registration = await getServiceWorkerRegistration();
        
        if (!registration) {
            return true;
        }

        const subscription = await registration.pushManager.getSubscription();
        
        if (!subscription) {
            return true;
        }

        // Notify server
        await fetch('/api/push/unsubscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                endpoint: subscription.endpoint,
            }),
            credentials: 'same-origin',
        });

        // Unsubscribe locally
        await subscription.unsubscribe();
        console.log('[Push] Unsubscribed from push notifications');

        return true;
    } catch (error) {
        console.error('[Push] Error unsubscribing:', error);
        return false;
    }
}

// Initialize push notifications - call this after login
export async function initializePushNotifications() {
    console.log('[Push] Initializing push notifications...');

    if (!isPushSupported()) {
        console.warn('[Push] Push notifications are not supported in this browser');
        return { success: false, reason: 'unsupported' };
    }

    // Register service worker
    const registration = await registerServiceWorker();
    
    if (!registration) {
        return { success: false, reason: 'service_worker_failed' };
    }

    // Request permission
    const permission = await requestNotificationPermission();
    
    if (permission !== 'granted') {
        console.warn('[Push] Notification permission not granted:', permission);
        return { success: false, reason: 'permission_denied', permission };
    }

    // Subscribe to push
    const subscription = await subscribeToPush(registration);
    
    if (!subscription) {
        return { success: false, reason: 'subscription_failed' };
    }

    console.log('[Push] Push notifications initialized successfully');
    return { success: true, subscription };
}

// Show a local test notification
export function showTestNotification(title = 'PinPointMe', body = 'Notifications are working!') {
    if (Notification.permission === 'granted') {
        new Notification(title, {
            body,
            icon: '/images/logos/pinpointme.png',
            badge: '/images/logos/pinpointme.png',
        });
    }
}
