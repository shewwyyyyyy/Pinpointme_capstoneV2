<template>
  <SnackBarTop ref="snackBarRef" />
  
  <v-card
    elevation="9"
    rounded="lg"
    variant="outlined"
    
    class="mx-full justify-center pa-3 bg-white "
   >
     <!-- Fixed height container to prevent card resizing -->
     <v-container class="camera-container pa-0"  style="height: 100%; overflow: hidden; position: relative; display: flex; flex-direction: column; max-width: 100%;">
       <!-- Camera content wrapper -->
       <v-container class="pa-0" fluid style="flex: 1; position: relative; max-width: 100%;">
         <v-sheet 
           v-if="scannerActive"
           style="height: 100%; width: 100%;"
           class="pa-0"
         >
           <ReadQrCodeStream ref="qrStreamRef" />
         </v-sheet>

         <!-- Camera Closed - Show Reopen Button -->
         <v-container v-else class="d-flex flex-column align-center justify-center pa-8" style="height: 100%;">
           <v-icon size="80" color="warning" class="mb-4">mdi-camera-off</v-icon>
           <v-card-title class="text-h6 mb-2 text-center">Camera Timeout</v-card-title>
           <v-card-subtitle class="mb-4 text-center">No QR code detected for 20 seconds</v-card-subtitle>
           <v-btn 
             color="primary" 
             size="large"
             @click="restartScanner"
             prepend-icon="mdi-camera"
           >
             Open Camera Again
           </v-btn>
         </v-container>
       </v-container>

       <!-- Timer Display (inside fixed container, always at bottom) -->
       <v-container class="text-center pa-2 ma-0" style="min-height: 20px; display: flex !important; align-items: center; justify-content: center; width: 100%; max-width: none !important;">
         <v-chip v-if="scannerActive" color="info" variant="outlined" prepend-icon="mdi-timer-outline">
           Time remaining: {{ remainingTime }}s
         </v-chip>
       </v-container>
     </v-container>

    <!-- Status feedback at the bottom of the card -->
    <v-card-actions v-if="statusMessage">
      <v-alert
        :type="statusType"
        variant="tonal"
        density="compact"
        class="flex-grow-1"
      >
        {{ statusMessage }}
      </v-alert>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import ReadQrCodeStream from './ReadQrCodeStream.vue';
import SnackBarTop from "@/Components/Utilities/SnackBarTop.vue";
 
// Define props that will be passed from parent component
const props = defineProps({
  errors: Object,
  flash: Object,
  btnDisabled: {
    type: Boolean,
    default: false
  }
});

// Define emits to pass scanned data to parent
const emits = defineEmits(['scanned', 'success', 'error']);

// Scanner state management
const scannerActive = ref(true);
const statusMessage = ref('');
const statusType = ref('info');
const remainingTime = ref(20);
const qrStreamRef = ref(null);

// Snackbar refs
const snackBarRef = ref(null);

let cameraTimer = null;
let countdownInterval = null;

// Snackbar toggle function
const toggleSnackBar = (message, type = 'accent') => {
    if (snackBarRef.value) {
        snackBarRef.value.snackbarText = message;
        snackBarRef.value.snackbarType = type;
        snackBarRef.value.snackbar = true;
    }
};

// Timer functions
const startCameraTimer = () => {
  // Clear any existing timers
  clearCameraTimers();
  
  // Reset remaining time
  remainingTime.value = 20;
  
  // Start countdown interval (updates every second)
  countdownInterval = setInterval(() => {
    remainingTime.value -= 1;
    if (remainingTime.value <= 0) {
      clearInterval(countdownInterval);
    }
  }, 1000);
  
  // Set camera timeout (20 seconds)
  cameraTimer = setTimeout(() => {
    clearInterval(countdownInterval);
    handleTimeout();
  }, 20000);
};

const clearCameraTimers = () => {
  if (cameraTimer) {
    clearTimeout(cameraTimer);
    cameraTimer = null;
  }
  if (countdownInterval) {
    clearInterval(countdownInterval);
    countdownInterval = null;
  }
};

const handleScanned = (scannedData) => {
  console.log("QR Code scanned:", scannedData);
  emits('scanned', scannedData);
  
  // Clear and restart timer on successful scan
  clearCameraTimers();
  startCameraTimer();
  
  // Update status
  statusMessage.value = `Scanned: ${scannedData.substring(0, 30)}${scannedData.length > 30 ? '...' : ''}`;
  statusType.value = 'success';
  
  // Clear status after 3 seconds
  setTimeout(() => {
    statusMessage.value = '';
  }, 3000);
};

const handleTimeout = () => {
  console.log("Scanner timeout - no QR detected for 20 seconds");
  clearCameraTimers();
  scannerActive.value = false;
  statusMessage.value = '';
};

const handleSuccess = (message) => {
  console.log("Scanner success:", message);
  emits('success', message);
  
  // Show brief success status
  if (message.includes('Camera ready')) {
    statusMessage.value = 'Camera ready - Point at QR code';
    statusType.value = 'info';
    
    // Clear after 2 seconds
    setTimeout(() => {
      statusMessage.value = '';
    }, 2000);
  }
};

const handleError = (errorMessage) => {
  console.error("Scanner error:", errorMessage);
  emits('error', errorMessage);
  
  // Show error status
  statusMessage.value = errorMessage;
  statusType.value = 'error';
  
  // Clear after 5 seconds
  setTimeout(() => {
    statusMessage.value = '';
  }, 5000);
};

const restartScanner = () => {
  console.log("Restarting scanner");
  scannerActive.value = true;
  statusMessage.value = '';
  
  // Start timer after component re-renders
  nextTick(() => {
    startCameraTimer();
  });
};

const flipCamera = () => {
  console.log("Camera flipped!")
}

// Watch for when ReadQrCodeStream component mounts/detects QR
watch(() => qrStreamRef.value, (newVal) => {
  if (newVal && scannerActive.value) {
    // Start timer when component is ready
    nextTick(() => {
      startCameraTimer();
    });
  }
});

// Watch for items changes from ReadQrCodeStream to handle errors
watch(
  () => qrStreamRef.value?.items,
  (value) => {
    if (!value) return;
    
    console.log("QR Scan detected, restarting timer...", value);
    
    // Restart timer on any scan (success or error)
    clearCameraTimers();
    startCameraTimer();
    
    // Check if it's an error (409 or other errors)
    if (value.status === 'error') {
      toggleSnackBar(value.message, 'error');
      // Don't stop camera for errors, just show message
      return;
    }
    
    // If it's successful data, show success message
    if (value.data) {
      toggleSnackBar(value.message, 'accent');
      // Emit success event to parent to refresh history
      emits('success', value.message);
    }
  },
  {
    deep: true,
  }
);

// Monitor items from ReadQrCodeStream for scan detection
onMounted(() => {
  // Set up mutation observer to detect QR scans from ReadQrCodeStream
  const checkForScans = setInterval(() => {
    try {
      if (qrStreamRef.value && qrStreamRef.value.$el) {
        const el = qrStreamRef.value.$el;
        // Check if querySelector exists and is a function
        if (el && typeof el.querySelector === 'function') {
          const resultElement = el.querySelector('.decode-result b');
          if (resultElement && resultElement.textContent && resultElement.textContent !== 'null') {
            const scannedData = resultElement.textContent;
            if (scannedData) {
              handleScanned(scannedData);
            }
          }
        }
      }
    } catch (error) {
      // Silently handle any errors during scan detection
      console.debug('Scan check error:', error);
    }
  }, 500);
  
  // Store interval for cleanup
  window.scanCheckInterval = checkForScans;
});

// Cleanup on component unmount
onBeforeUnmount(() => {
  clearCameraTimers();
  if (window.scanCheckInterval) {
    clearInterval(window.scanCheckInterval);
  }
});
</script>
