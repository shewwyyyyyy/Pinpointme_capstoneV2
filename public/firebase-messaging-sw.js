// Firebase Messaging Service Worker for PinPointMe
// This file must be at the root of your domain for Firebase Cloud Messaging

// Import Firebase scripts for service workers
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

// Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyC29IDagVxBaw91vOVwgx0u78BSOOwG-w8",
    authDomain: "ppm-fcm.firebaseapp.com",
    projectId: "ppm-fcm",
    storageBucket: "ppm-fcm.firebasestorage.app",
    messagingSenderId: "792412354648",
    appId: "1:792412354648:web:686e84c46bfb35d49af22d"
};

// Initialize Firebase in the service worker
firebase.initializeApp(firebaseConfig);

// Retrieve an instance of Firebase Messaging
const messaging = firebase.messaging();

// Handle push events directly - this gives us more control over notifications
self.addEventListener('push', (event) => {
    console.log('[firebase-messaging-sw.js] Push event received');
    
    let payload = {};
    let title = 'PinPointMe Alert';
    let body = 'You have a new notification';
    
    if (event.data) {
        try {
            payload = event.data.json();
            console.log('[firebase-messaging-sw.js] Push payload (raw JSON):', JSON.stringify(payload, null, 2));
            
            // FCM data-only messages put everything in the data field
            // The payload structure is: { data: { title, body, ... } }
            const data = payload.data || payload;
            
            title = data.title || payload.notification?.title || title;
            body = data.body || payload.notification?.body || body;
            
            console.log('[firebase-messaging-sw.js] Extracted - Title:', title, 'Body:', body);
        } catch (e) {
            console.error('[firebase-messaging-sw.js] Error parsing push data:', e);
            // Try as text
            try {
                const textData = event.data.text();
                console.log('[firebase-messaging-sw.js] Push data as text:', textData);
                body = textData || body;
            } catch (textError) {
                console.error('[firebase-messaging-sw.js] Error getting text data:', textError);
            }
        }
    }
    
    // Get the data object for additional fields
    const data = payload.data || payload || {};
    
    const notificationOptions = {
        body: body,
        icon: data.icon || '/images/logos/pinpointme.png',
        badge: '/images/logos/pinpointme.png',
        tag: data.tag || 'pinpointme-' + Date.now(),
        requireInteraction: true,
        renotify: true,
        vibrate: [300, 100, 300, 100, 300],
        data: {
            ...data,
            type: data.type || 'general',
            timestamp: Date.now(),
            click_action: data.click_action || '/'
        }
    };

    // Add actions for rescue requests
    if (data.type === 'rescue_request') {
        notificationOptions.actions = [
            { action: 'view', title: '👁️ View', icon: '/images/logos/pinpointme.png' },
            { action: 'dismiss', title: '❌ Dismiss' }
        ];
        notificationOptions.tag = 'rescue-' + (data.rescue_code || Date.now());
        notificationOptions.requireInteraction = true;
    }

    console.log('[firebase-messaging-sw.js] Showing notification:', title, notificationOptions);
    
    event.waitUntil(
        self.registration.showNotification(title, notificationOptions)
    );
});

// Handle background messages (fallback for Firebase SDK)
messaging.onBackgroundMessage((payload) => {
    console.log('[firebase-messaging-sw.js] Received background message:', payload);
    
    // Customize notification here
    const notificationTitle = payload.notification?.title || payload.data?.title || 'PinPointMe Alert';
    const notificationOptions = {
        body: payload.notification?.body || payload.data?.body || 'You have a new notification',
        icon: payload.notification?.icon || '/images/logos/pinpointme.png',
        badge: '/images/logos/pinpointme.png',
        tag: payload.data?.tag || 'pinpointme-' + Date.now(),
        requireInteraction: true,
        renotify: true,
        vibrate: [300, 100, 300, 100, 300],
        data: {
            ...payload.data,
            type: payload.data?.type || 'general',
            timestamp: Date.now(),
            click_action: payload.data?.click_action || '/'
        }
    };

    // Add actions for rescue requests
    if (payload.data?.type === 'rescue_request') {
        notificationOptions.actions = [
            { action: 'view', title: '👁️ View', icon: '/images/logos/pinpointme.png' },
            { action: 'dismiss', title: '❌ Dismiss' }
        ];
        notificationOptions.tag = 'rescue-' + (payload.data.rescue_code || Date.now());
        notificationOptions.requireInteraction = true;
    }

    return self.registration.showNotification(notificationTitle, notificationOptions);
});

// Handle notification click
self.addEventListener('notificationclick', (event) => {
    console.log('[firebase-messaging-sw.js] Notification clicked:', event.action);
    
    event.notification.close();

    const data = event.notification.data || {};
    let targetUrl = data.click_action || '/';

    // Determine where to navigate based on notification type
    if (data.type === 'rescue_request') {
        targetUrl = '/rescuer/dashboard';
        if (event.action === 'view' && data.rescue_code) {
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

console.log('[firebase-messaging-sw.js] Firebase Messaging Service Worker initialized');
