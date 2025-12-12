<template>
    <v-container class="d-flex align-center justify-center">
        <div id="reader" style="width: 300px"></div>
    </v-container>
</template>

<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted } from "vue";
import { Html5Qrcode, Html5QrcodeScannerState } from "html5-qrcode";

const props = defineProps({
    errors: Object,
    flash: Object,
    btnDisabled: Boolean,
});

let html5QrCode;
let timeoutId;
let resumeTimeoutId; // New timeout ID for the 5-second resume delay

const emits = defineEmits(["scanned", "timeout", "success", "error"]);

// UI Feedback state
const showFeedback = ref(false);
const feedbackMessage = ref("");
const feedbackType = ref("success");

// QR Code tracking for duplicate prevention
const scannedQRCodes = ref(new Map()); // Map to store QR code with timestamp

const isQRCodeRecentlyScanned = (qrCode) => {
    const now = Date.now();
    const oneHour = 60 * 60 * 1000; // 1 hour in milliseconds

    if (scannedQRCodes.value.has(qrCode)) {
        const lastScanTime = scannedQRCodes.value.get(qrCode);
        return now - lastScanTime < oneHour;
    }
    return false;
};

const addQRCodeToHistory = (qrCode) => {
    const now = Date.now();
    scannedQRCodes.value.set(qrCode, now);

    // Clean up old entries (older than 1 hour)
    const oneHour = 60 * 60 * 1000;
    for (const [code, timestamp] of scannedQRCodes.value.entries()) {
        if (now - timestamp >= oneHour) {
            scannedQRCodes.value.delete(code);
        }
    }
};

const showSuccessMessage = (message) => {
    feedbackMessage.value = message;
    feedbackType.value = "success";
    showFeedback.value = true;
    console.log("Success:", message);

    // Auto-hide after 3 seconds
    setTimeout(() => {
        showFeedback.value = false;
    }, 3000);
};

const showErrorMessage = (message) => {
    feedbackMessage.value = message;
    feedbackType.value = "error";
    showFeedback.value = true;
    console.error("Error:", message);

    // Auto-hide after 5 seconds for errors
    setTimeout(() => {
        showFeedback.value = false;
    }, 5000);
};

const scanned = ref(null);

// Timer duration set to 20 seconds
const INACTIVITY_TIMEOUT = 20000;
// New: Duration for the pause after a successful scan (5 seconds)
const RESUME_DELAY = 5000;

// --- Timer Management Functions ---

const resetInactivityTimer = () => {
    // 1. Clear the existing inactivity timeout (reset the clock)
    if (timeoutId) {
        clearTimeout(timeoutId);
    }

    // 2. Set a new inactivity timeout
    timeoutId = setTimeout(() => {
        // Only stop if a scan hasn't already happened
        if (scanned.value === null) {
            stopScannerAndClearResources(); // Use the resource clearing function
            console.log(
                "Scanner stopped automatically after 20 seconds of inactivity."
            );
        }
    }, INACTIVITY_TIMEOUT);
    // console.log("Inactivity timer started/reset.");
};

// --- Scanner Control Functions ---

const startScanner = async () => {
    await nextTick(); // Wait until #reader is in the DOM

    html5QrCode = new Html5Qrcode("reader");

    html5QrCode
        .start(
            { facingMode: "environment" },
            { fps: 1, qrbox: { width: 250, height: 250 } },
            (decodedText) => {
                // When a scan is successful, set the value, which triggers the watch
                scanned.value = decodedText;
                console.log("Scanned:", decodedText);
            }
        )
        .catch((err) => {
            console.error("Start failed:", err);
        });

    // Start the 20-second inactivity timer immediately after the scanner starts
    resetInactivityTimer();
};

/** Pauses the camera but keeps resources loaded. */
const pauseScanner = async (value) => {
    if (
        html5QrCode &&
        html5QrCode.getState() === Html5QrcodeScannerState.SCANNING
    ) {
        await html5QrCode.pause();
        console.log("Scanner paused.");
        emits("scanned", value);
    }
};

/** Resumes the camera after a pause. */
const resumeScanner = async () => {
    if (
        html5QrCode &&
        html5QrCode.getState() === Html5QrcodeScannerState.PAUSED
    ) {
        // Clear the scanned value to allow a new scan to be detected
        scanned.value = null;
        await html5QrCode.resume();
        // console.log("Scanner resumed. Ready for next scan.");
        // Restart the inactivity timer
        resetInactivityTimer();
    }
};

/** Stops the camera and clears all resources (used for timeout or unmount). */
const stopScannerAndClearResources = async () => {
    // Clear ALL related timeouts
    if (timeoutId) clearTimeout(timeoutId);
    if (resumeTimeoutId) clearTimeout(resumeTimeoutId);

    if (
        html5QrCode &&
        (html5QrCode.getState() === Html5QrcodeScannerState.SCANNING ||
            html5QrCode.getState() === Html5QrcodeScannerState.PAUSED)
    ) {
        await html5QrCode.stop();
        // Emits the scanned value. If null, it means it timed out.
        emits("scanned", scanned.value);
        await html5QrCode.clear();
        console.log("Scanner stopped and resources cleared.");
        scanned.value = null; // Reset for next time startScanner is called
    }
};

// --- Vue Watcher and Lifecycle Hooks ---

watch(
    scanned,
    (value) => {
        if (value !== null) {
            // A successful scan occurred.
            // 1. Immediately cancel the 20-second inactivity timer.
            if (timeoutId) {
                clearTimeout(timeoutId);
            }

            // 2. Pause the scanner.
            pauseScanner(value);

            // 3. Emit the scanned result *now* (or wait for resume, depending on app logic)
            // We'll emit immediately so the parent can process the data.

            // 4. Set a 5-second timer to automatically resume scanning.
            // console.log(
            //     `Scan successful. Waiting ${
            //         RESUME_DELAY / 1000
            //     } seconds before resuming...`
            // );

            // Clear any existing resume timer before setting a new one
            if (resumeTimeoutId) {
                clearTimeout(resumeTimeoutId);
            }

            resumeTimeoutId = setTimeout(() => {
                resumeScanner();
            }, RESUME_DELAY);
        } else if (
            html5QrCode &&
            html5QrCode.getState() === Html5QrcodeScannerState.SCANNING
        ) {
            // If `scanned.value` is reset to null while the scanner is running,
            // restart the inactivity timer to give the user another 20 seconds.
            resetInactivityTimer();
        }
    },
    {
        deep: true,
    }
);

onMounted(() => {
    startScanner();
});

onUnmounted(() => {
    // Clear all timeouts and stop the scanner when the component is unmounted
    clearTimeout(timeoutId);
    clearTimeout(resumeTimeoutId);
    stopScannerAndClearResources();
});
</script>

<style scoped>
.qr-reader-container {
    width: 100%; /* Take full width of parent container */
    max-width: 400px; /* Limit the max size for better display */
    /* Add a padding hack to force a 1:1 aspect ratio (or define height) */
    padding-top: 100%; /* This is the key for a fixed aspect ratio */
    position: relative;
}
/* Crucial: Target the video element the library creates to fit it */
.qr-reader-container video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover; /* Ensures the video covers the container */
}
</style>
