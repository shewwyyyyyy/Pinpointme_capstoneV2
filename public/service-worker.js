// PinPointMe Service Worker for Push Notifications
const CACHE_VERSION = 'v2';

// Install event - immediately activate
self.addEventListener('install', (event) => {
    console.log('[Service Worker] Installing version:', CACHE_VERSION);
    // Force the waiting service worker to become the active service worker
    event.waitUntil(self.skipWaiting());
});

// Activate event - take control of all clients
self.addEventListener('activate', (event) => {
    console.log('[Service Worker] Activating version:', CACHE_VERSION);
    // Take control of all pages immediately
    event.waitUntil(
        Promise.all([
            self.clients.claim(),
            // Clean up old caches if needed
        ])
    );
});

// Push notification event - THIS FIRES EVEN WHEN APP IS CLOSED
self.addEventListener('push', (event) => {
    console.log('[Service Worker] Push event received');
    
    // Default notification data
    let notificationData = {
        title: 'PinPointMe Alert',
        body: 'You have a new notification',
        icon: '/images/logos/pinpointme.png',
        badge: '/images/logos/pinpointme.png',
        tag: 'pinpointme-' + Date.now(),
        requireInteraction: true,
        renotify: true,
        data: {
            type: 'general',
            timestamp: Date.now()
        }
    };

    // Try to parse the push data
    if (event.data) {
        try {
            const payload = event.data.json();
            console.log('[Service Worker] Push payload:', payload);
            
            notificationData = {
                title: payload.title || notificationData.title,
                body: payload.body || notificationData.body,
                icon: payload.icon || notificationData.icon,
                badge: payload.badge || notificationData.badge,
                tag: payload.tag || notificationData.tag,
                requireInteraction: payload.requireInteraction !== false,
                renotify: true,
                data: {
                    ...notificationData.data,
                    ...payload.data,
                    type: payload.type || payload.data?.type || 'general'
                }
            };
        } catch (e) {
            console.error('[Service Worker] Error parsing push data:', e);
            // Try as text if JSON parsing fails
            try {
                notificationData.body = event.data.text();
            } catch (textError) {
                console.error('[Service Worker] Error getting text data:', textError);
            }
        }
    }

    // Build notification options
    const options = {
        body: notificationData.body,
        icon: notificationData.icon,
        badge: notificationData.badge,
        tag: notificationData.tag,
        requireInteraction: notificationData.requireInteraction,
        renotify: notificationData.renotify,
        vibrate: [300, 100, 300, 100, 300],
        data: notificationData.data,
        silent: false
    };

    // Add actions for rescue requests
    if (notificationData.data?.type === 'rescue_request') {
        options.actions = [
            { action: 'view', title: '👁️ View', icon: '/images/logos/pinpointme.png' },
            { action: 'dismiss', title: '❌ Dismiss' }
        ];
        options.tag = 'rescue-' + (notificationData.data.rescue_code || Date.now());
        options.requireInteraction = true;
    }

    console.log('[Service Worker] Showing notification:', notificationData.title, options);

    // IMPORTANT: Use waitUntil to keep service worker alive until notification is shown
    event.waitUntil(
        self.registration.showNotification(notificationData.title, options)
            .then(() => {
                console.log('[Service Worker] Notification displayed successfully');
            })
            .catch((error) => {
                console.error('[Service Worker] Error showing notification:', error);
            })
    );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
    console.log('[Service Worker] Notification clicked:', event.action);
    
    event.notification.close();

    const data = event.notification.data || {};
    let targetUrl = '/';

    // Determine where to navigate based on notification type
    if (data.type === 'rescue_request') {
        targetUrl = '/rescuer/dashboard';
    } else if (data.url) {
        targetUrl = data.url;
    }

    // Handle specific actions
    if (event.action === 'view') {
        if (data.rescue_code) {
            targetUrl = `/rescuer/dashboard?rescue=${data.rescue_code}`;
        }
    } else if (event.action === 'dismiss') {
        return; // Just close the notification
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Try to focus an existing window
                for (const client of clientList) {
                    if (client.url.includes(self.location.origin)) {
                        client.focus();
                        client.postMessage({
                            type: 'NOTIFICATION_CLICK',
                            data: data,
                            url: targetUrl
                        });
                        return;
                    }
                }
                // Open a new window if none exists
                return clients.openWindow(targetUrl);
            })
    );
});

// Notification close event
self.addEventListener('notificationclose', (event) => {
    console.log('[Service Worker] Notification closed');
});

// Message event - for communication with main app
self.addEventListener('message', (event) => {
    console.log('[Service Worker] Message received:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
