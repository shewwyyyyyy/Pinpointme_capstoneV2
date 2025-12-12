import { ref, onUnmounted } from 'vue';

/**
 * Composable for handling push-style notifications with sound and vibration
 */
export function useNotificationAlert() {
    const showAlert = ref(false);
    const alertMessage = ref('');
    const alertTitle = ref('');
    const alertType = ref('info'); // info, success, warning, error
    const alertIcon = ref('mdi-bell');
    const alertCallback = ref(null);
    
    // Sound URLs - using data URIs for notification sounds
    const sounds = {
        notification: null,
        emergency: null,
        message: null,
    };
    
    // Create audio context and sounds
    const initSounds = () => {
        // Create a simple beep sound using Web Audio API
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                sounds.audioContext = new AudioContext();
            }
        } catch (e) {
            console.warn('Web Audio API not supported');
        }
    };
    
    // Play a beep sound using Web Audio API
    const playBeep = (frequency = 800, duration = 200, volume = 0.5, type = 'sine') => {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            const audioContext = new AudioContext();
            
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = frequency;
            oscillator.type = type;
            gainNode.gain.value = volume;
            
            oscillator.start();
            
            // Fade out
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration / 1000);
            
            oscillator.stop(audioContext.currentTime + duration / 1000);
        } catch (e) {
            console.warn('Could not play sound:', e);
        }
    };
    
    // Play notification sound pattern
    const playNotificationSound = (type = 'notification') => {
        switch (type) {
            case 'emergency':
                // Urgent alert - three ascending beeps
                playBeep(600, 150, 0.6, 'square');
                setTimeout(() => playBeep(800, 150, 0.6, 'square'), 200);
                setTimeout(() => playBeep(1000, 200, 0.7, 'square'), 400);
                break;
            case 'message':
                // Chat message - soft double beep
                playBeep(800, 100, 0.3, 'sine');
                setTimeout(() => playBeep(1000, 100, 0.3, 'sine'), 150);
                break;
            case 'success':
                // Success - pleasant ascending
                playBeep(523, 100, 0.3, 'sine');
                setTimeout(() => playBeep(659, 100, 0.3, 'sine'), 120);
                setTimeout(() => playBeep(784, 150, 0.4, 'sine'), 240);
                break;
            default:
                // Standard notification - single beep
                playBeep(800, 200, 0.4, 'sine');
        }
    };
    
    // Vibrate device
    const vibrate = (pattern = [200, 100, 200]) => {
        if ('vibrate' in navigator) {
            try {
                navigator.vibrate(pattern);
            } catch (e) {
                console.warn('Vibration not supported:', e);
            }
        }
    };
    
    // Vibration patterns for different types
    const vibrateForType = (type) => {
        switch (type) {
            case 'emergency':
                vibrate([300, 100, 300, 100, 300]); // Long urgent pattern
                break;
            case 'message':
                vibrate([100, 50, 100]); // Short double vibration
                break;
            case 'success':
                vibrate([100]); // Single short vibration
                break;
            default:
                vibrate([200, 100, 200]); // Standard pattern
        }
    };
    
    // Show notification alert
    const notify = ({
        title = 'Notification',
        message = '',
        type = 'info',
        icon = 'mdi-bell',
        sound = true,
        vibration = true,
        duration = 5000,
        onClick = null,
        soundType = 'notification',
    }) => {
        alertTitle.value = title;
        alertMessage.value = message;
        alertType.value = type;
        alertIcon.value = icon;
        alertCallback.value = onClick;
        showAlert.value = true;
        
        // Play sound
        if (sound) {
            playNotificationSound(soundType);
        }
        
        // Vibrate
        if (vibration) {
            vibrateForType(soundType);
        }
        
        // Auto hide after duration
        if (duration > 0) {
            setTimeout(() => {
                hideAlert();
            }, duration);
        }
        
        // Request browser notification permission if not granted
        requestNotificationPermission();
        
        // Also show browser notification if page is not visible
        if (document.hidden) {
            showBrowserNotification(title, message, icon);
        }
    };
    
    // Hide alert
    const hideAlert = () => {
        showAlert.value = false;
        alertCallback.value = null;
    };
    
    // Handle alert click
    const handleAlertClick = () => {
        if (alertCallback.value) {
            alertCallback.value();
        }
        hideAlert();
    };
    
    // Request browser notification permission
    const requestNotificationPermission = async () => {
        if ('Notification' in window && Notification.permission === 'default') {
            try {
                await Notification.requestPermission();
            } catch (e) {
                console.warn('Notification permission request failed:', e);
            }
        }
    };
    
    // Show browser notification
    const showBrowserNotification = (title, body, icon) => {
        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                new Notification(title, {
                    body,
                    icon: '/favicon.ico',
                    badge: '/favicon.ico',
                    vibrate: [200, 100, 200],
                    tag: 'pinpointme-notification',
                    renotify: true,
                });
            } catch (e) {
                console.warn('Browser notification failed:', e);
            }
        }
    };
    
    // Convenience methods for different notification types
    const notifyEmergency = (title, message, onClick = null) => {
        notify({
            title,
            message,
            type: 'error',
            icon: 'mdi-alert-circle',
            soundType: 'emergency',
            duration: 10000,
            onClick,
        });
    };
    
    const notifyMessage = (title, message, onClick = null) => {
        notify({
            title,
            message,
            type: 'info',
            icon: 'mdi-message',
            soundType: 'message',
            duration: 5000,
            onClick,
        });
    };
    
    const notifySuccess = (title, message, onClick = null) => {
        notify({
            title,
            message,
            type: 'success',
            icon: 'mdi-check-circle',
            soundType: 'success',
            duration: 4000,
            onClick,
        });
    };
    
    const notifyWarning = (title, message, onClick = null) => {
        notify({
            title,
            message,
            type: 'warning',
            icon: 'mdi-alert',
            soundType: 'notification',
            duration: 6000,
            onClick,
        });
    };
    
    // Initialize sounds on mount
    initSounds();
    
    return {
        // State
        showAlert,
        alertTitle,
        alertMessage,
        alertType,
        alertIcon,
        
        // Methods
        notify,
        notifyEmergency,
        notifyMessage,
        notifySuccess,
        notifyWarning,
        hideAlert,
        handleAlertClick,
        playNotificationSound,
        vibrate,
        requestNotificationPermission,
    };
}

export default useNotificationAlert;
