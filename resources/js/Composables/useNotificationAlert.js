import { ref, onUnmounted } from 'vue';

/**
 * Composable for handling push-style notifications with real audio sounds and vibration.
 *
 * Sound mapping:
 *  - emergency / rescue request  →  /sounds/send-request-alarm.m4a
 *  - message / chat              →  /sounds/message-notification.m4a
 *  - force-alert (unstoppable)   →  /sounds/infinity-ringtone.m4a  (loops until stopped)
 *  - success / default           →  Web Audio API beep fallback
 */
export function useNotificationAlert() {
    const showAlert = ref(false);
    const alertMessage = ref('');
    const alertTitle = ref('');
    const alertType = ref('info');
    const alertIcon = ref('mdi-bell');
    const alertCallback = ref(null);
    const isForceAlertPlaying = ref(false);

    // ── Audio instances (lazy-loaded) ──────────────────────────
    let emergencyAudio = null;
    let messageAudio = null;
    let forceAlertAudio = null;

    const getEmergencyAudio = () => {
        if (!emergencyAudio) {
            emergencyAudio = new Audio('/sounds/send-request-alarm.m4a');
            emergencyAudio.preload = 'auto';
        }
        return emergencyAudio;
    };

    const getMessageAudio = () => {
        if (!messageAudio) {
            messageAudio = new Audio('/sounds/message-notification.m4a');
            messageAudio.preload = 'auto';
        }
        return messageAudio;
    };

    const getForceAlertAudio = () => {
        if (!forceAlertAudio) {
            forceAlertAudio = new Audio('/sounds/infinity-ringtone.m4a');
            forceAlertAudio.preload = 'auto';
            forceAlertAudio.loop = true; // loops infinitely until stopped
        }
        return forceAlertAudio;
    };

    // ── Web Audio API fallback beep ────────────────────────────
    const playBeep = (frequency = 800, duration = 200, volume = 0.5, type = 'sine') => {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = frequency;
            osc.type = type;
            gain.gain.value = volume;
            osc.start();
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + duration / 1000);
            osc.stop(ctx.currentTime + duration / 1000);
        } catch (e) {
            console.warn('Could not play beep:', e);
        }
    };

    // ── Play notification sound ────────────────────────────────
    const playNotificationSound = (type = 'notification') => {
        try {
            switch (type) {
                case 'emergency':
                case 'rescue': {
                    const audio = getEmergencyAudio();
                    audio.currentTime = 0;
                    audio.volume = 1.0;
                    audio.play().catch(e => {
                        console.warn('Emergency audio blocked, using beep fallback:', e);
                        playBeep(600, 150, 0.6, 'square');
                        setTimeout(() => playBeep(800, 150, 0.6, 'square'), 200);
                        setTimeout(() => playBeep(1000, 200, 0.7, 'square'), 400);
                    });
                    break;
                }
                case 'message': {
                    const audio = getMessageAudio();
                    audio.currentTime = 0;
                    audio.volume = 0.8;
                    audio.play().catch(e => {
                        console.warn('Message audio blocked, using beep fallback:', e);
                        playBeep(800, 100, 0.3, 'sine');
                        setTimeout(() => playBeep(1000, 100, 0.3, 'sine'), 150);
                    });
                    break;
                }
                case 'force-alert': {
                    const audio = getForceAlertAudio();
                    audio.currentTime = 0;
                    audio.volume = 1.0;
                    audio.loop = true;
                    isForceAlertPlaying.value = true;
                    audio.play().catch(e => {
                        console.warn('Force-alert audio blocked:', e);
                        startFallbackForceAlert();
                    });
                    break;
                }
                case 'success':
                    playBeep(523, 100, 0.3, 'sine');
                    setTimeout(() => playBeep(659, 100, 0.3, 'sine'), 120);
                    setTimeout(() => playBeep(784, 150, 0.4, 'sine'), 240);
                    break;
                default: {
                    const audio = getMessageAudio();
                    audio.currentTime = 0;
                    audio.volume = 0.5;
                    audio.play().catch(() => playBeep(800, 200, 0.4, 'sine'));
                }
            }
        } catch (e) {
            console.warn('playNotificationSound error:', e);
        }
    };

    // ── Force alert fallback (repeating beeps if audio blocked) ──
    let fallbackForceAlertTimer = null;
    const startFallbackForceAlert = () => {
        isForceAlertPlaying.value = true;
        const beepLoop = () => {
            if (!isForceAlertPlaying.value) return;
            playBeep(900, 300, 0.8, 'square');
            setTimeout(() => playBeep(700, 300, 0.8, 'square'), 400);
            fallbackForceAlertTimer = setTimeout(beepLoop, 1000);
        };
        beepLoop();
    };

    // ── Stop the force-alert ringtone ──────────────────────────
    const stopForceAlert = () => {
        isForceAlertPlaying.value = false;
        if (forceAlertAudio) {
            forceAlertAudio.pause();
            forceAlertAudio.currentTime = 0;
        }
        if (fallbackForceAlertTimer) {
            clearTimeout(fallbackForceAlertTimer);
            fallbackForceAlertTimer = null;
        }
        if ('vibrate' in navigator) {
            navigator.vibrate(0);
        }
    };

    // ── Vibration ──────────────────────────────────────────────
    const vibrate = (pattern = [200, 100, 200]) => {
        if ('vibrate' in navigator) {
            try {
                navigator.vibrate(pattern);
            } catch (e) {
                console.warn('Vibration not supported:', e);
            }
        }
    };

    const vibrateForType = (type) => {
        switch (type) {
            case 'emergency':
            case 'rescue':
                vibrate([300, 100, 300, 100, 300, 100, 300]);
                break;
            case 'force-alert':
                vibrate([500, 200, 500, 200, 500, 200, 500, 200, 500]);
                break;
            case 'message':
                vibrate([100, 50, 100]);
                break;
            case 'success':
                vibrate([100]);
                break;
            default:
                vibrate([200, 100, 200]);
        }
    };

    // ── Main notify function ───────────────────────────────────
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

        if (sound) {
            playNotificationSound(soundType);
        }

        if (vibration) {
            vibrateForType(soundType);
        }

        // Auto-hide (force-alert never auto-hides)
        if (duration > 0 && soundType !== 'force-alert') {
            setTimeout(() => hideAlert(), duration);
        }

        requestNotificationPermission();

        if (document.hidden) {
            showBrowserNotification(title, message, icon);
        }
    };

    const hideAlert = () => {
        showAlert.value = false;
        alertCallback.value = null;
    };

    const handleAlertClick = () => {
        if (alertCallback.value) {
            alertCallback.value();
        }
        hideAlert();
    };

    // ── Browser notification API ───────────────────────────────
    const requestNotificationPermission = async () => {
        if ('Notification' in window && Notification.permission === 'default') {
            try { await Notification.requestPermission(); } catch (e) { /* ignore */ }
        }
    };

    const showBrowserNotification = (title, body) => {
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

    // ── Convenience methods ────────────────────────────────────
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

    const notifyForceAlert = (title, message, onClick = null) => {
        notify({
            title,
            message,
            type: 'error',
            icon: 'mdi-alarm-light',
            soundType: 'force-alert',
            duration: 0,
            onClick,
        });
    };

    return {
        // State
        showAlert,
        alertTitle,
        alertMessage,
        alertType,
        alertIcon,
        isForceAlertPlaying,

        // Methods
        notify,
        notifyEmergency,
        notifyMessage,
        notifySuccess,
        notifyWarning,
        notifyForceAlert,
        hideAlert,
        handleAlertClick,
        playNotificationSound,
        stopForceAlert,
        vibrate,
        requestNotificationPermission,
    };
}

export default useNotificationAlert;
