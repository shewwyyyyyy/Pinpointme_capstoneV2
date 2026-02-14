<template>
    <Teleport to="body">
        <!-- Full-screen overlay -->
        <Transition name="wizard-fade">
            <div v-if="isVisible" class="wizard-overlay" @click.self="handleOverlayClick">
                <!-- Spotlight highlight on target element -->
                <div 
                    v-if="spotlightStyle" 
                    class="wizard-spotlight" 
                    :style="spotlightStyle"
                ></div>

                <!-- Arrow pointer connecting card to spotlight -->
                <div 
                    v-if="arrowStyle" 
                    class="wizard-arrow"
                    :class="arrowDirection"
                    :style="arrowStyle"
                ></div>

                <!-- Wizard Card -->
                <div 
                    ref="cardRef"
                    class="wizard-card" 
                    :class="{ 'card-centered': !spotlightRect }"
                    :style="cardPositionStyle"
                >
                    <!-- Progress bar -->
                    <div class="wizard-progress">
                        <div class="wizard-progress-bar" :style="{ width: progressPercent + '%' }"></div>
                    </div>

                    <!-- Header -->
                    <div class="wizard-header">
                        <div class="wizard-step-badge">
                            <span class="step-current">{{ currentStep + 1 }}</span>
                            <span class="step-divider">/</span>
                            <span class="step-total">{{ steps.length }}</span>
                        </div>
                        <button class="wizard-skip-btn" @click="skipWizard">
                            <v-icon size="16" class="mr-1">mdi-close</v-icon>
                            Skip Tour
                        </button>
                    </div>

                    <!-- Step Icon -->
                    <div class="wizard-icon-wrapper">
                        <div class="wizard-icon-circle" :style="{ background: currentStepData.color || 'linear-gradient(135deg, #3674B5 0%, #2D5F96 100%)' }">
                            <v-icon size="32" color="white">{{ currentStepData.icon }}</v-icon>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="wizard-content">
                        <h3 class="wizard-title">{{ currentStepData.title }}</h3>
                        <p class="wizard-description">{{ currentStepData.description }}</p>
                        
                        <!-- Tip box -->
                        <div v-if="currentStepData.tip" class="wizard-tip">
                            <v-icon size="16" color="#DFA92C" class="mr-1">mdi-lightbulb-on</v-icon>
                            <span>{{ currentStepData.tip }}</span>
                        </div>
                    </div>

                    <!-- Step dots -->
                    <div class="wizard-dots">
                        <button 
                            v-for="(step, i) in steps" 
                            :key="i" 
                            class="wizard-dot" 
                            :class="{ active: i === currentStep, completed: i < currentStep }"
                            @click="goToStep(i)"
                        ></button>
                    </div>

                    <!-- Actions -->
                    <div class="wizard-actions">
                        <button 
                            v-if="currentStep > 0" 
                            class="wizard-btn wizard-btn-back" 
                            @click="prevStep"
                        >
                            <v-icon size="18" class="mr-1">mdi-chevron-left</v-icon>
                            Back
                        </button>
                        <div v-else></div>

                        <button 
                            class="wizard-btn wizard-btn-next" 
                            @click="nextStep"
                        >
                            {{ isLastStep ? 'Get Started! 🚀' : 'Next' }}
                            <v-icon v-if="!isLastStep" size="18" class="ml-1">mdi-chevron-right</v-icon>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';

const WIZARD_COMPLETED_KEY = 'pinpointme_wizard_completed';
const WIZARD_VERSION = '2'; // Increment to show wizard again after major updates - v2 adds profile info requirement

const props = defineProps({
    /** Override to force-show the wizard (e.g. from help button) */
    forceShow: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['complete', 'skip', 'close']);

// Wizard steps - each step points at the actual UI element it describes
const steps = [
    {
        icon: 'mdi-hand-wave',
        title: 'Welcome to PinPointMe! 🎉',
        description: 'Your trusted campus emergency rescue system. Let us give you a quick tour of the app so you know exactly what to do in an emergency.',
        tip: 'This tour only shows once. You can replay it anytime from the Help button.',
        color: 'linear-gradient(135deg, #3674B5 0%, #13294B 100%)',
        target: null, // Centered, no highlight
    },
    {
        icon: 'mdi-account-edit',
        title: 'Update Your Information First! ⚠️',
        description: 'Before using the rescue system, please complete your Personal, Medical, and Emergency Contact information in your profile. This helps rescuers identify you and provide appropriate assistance during emergencies.',
        tip: 'Go to your Profile → Edit your Personal Info, Medical Info, and Emergency Contacts before creating a rescue report.',
        color: 'linear-gradient(135deg, #E65100 0%, #FF9800 100%)',
        target: '[data-wizard="profile"]',
    },
    {
        icon: 'mdi-view-dashboard',
        title: 'Dashboard',
        description: 'This is your home base. You can see your current scanned location, emergency form, and quick access to all features from here.',
        tip: 'Tap the Dashboard icon anytime to return here.',
        color: 'linear-gradient(135deg, #3674B5 0%, #4A90E2 100%)',
        target: '[data-wizard="dashboard"]',
    },
    {
        icon: 'mdi-qrcode-scan',
        title: 'Scan QR Code',
        description: 'Tap this Scan button to scan a QR code posted in your room. This instantly detects your building, floor, and room location.',
        tip: 'Long-press for Voice Input or Manual Input options!',
        color: 'linear-gradient(135deg, #3674B5 0%, #4A90E2 100%)',
        target: '[data-wizard="scan"]',
    },
    {
        icon: 'mdi-ambulance',
        title: 'Request Rescue',
        description: 'After your location is set, scroll down to this emergency form — fill in the details, urgency level, and injuries — then tap "Request Rescue" to alert the team.',
        tip: 'Include as much detail as possible to help rescuers prepare.',
        color: 'linear-gradient(135deg, #b71c1c 0%, #e53935 100%)',
        target: '#emergency-form-section',
    },
    {
        icon: 'mdi-message-text',
        title: 'Messages',
        description: 'Once a rescuer is assigned, a chat opens automatically. Tap this Messages button to view all your conversations, share photos, and send voice notes.',
        tip: 'A badge appears here when you have unread messages.',
        color: 'linear-gradient(135deg, #1565C0 0%, #42A5F5 100%)',
        target: '[data-wizard="messages"]',
    },
    {
        icon: 'mdi-bell',
        title: 'Notifications',
        description: 'Tap the bell icon to see your notifications — rescue status updates, new messages, and important alerts all appear here.',
        tip: 'Allow browser notifications for sound alerts even when you\'re on a different page.',
        color: 'linear-gradient(135deg, #DFA92C 0%, #FBC02D 100%)',
        target: '[data-wizard="notifications"]',
    },
    {
        icon: 'mdi-shield-check',
        title: 'Safety Tips',
        description: 'Tap Safety for preventive measures, emergency guidelines, and first-aid tips. Being prepared is the best defense!',
        tip: 'Review safety tips regularly — being prepared saves lives!',
        color: 'linear-gradient(135deg, #185D33 0%, #4CAF50 100%)',
        target: '[data-wizard="safety"]',
    },
    {
        icon: 'mdi-history',
        title: 'History',
        description: 'Tap History to view your past location scans and rescue records. Keep track of everything that happened.',
        tip: 'Use this to review your past emergency requests.',
        color: 'linear-gradient(135deg, #6A1B9A 0%, #AB47BC 100%)',
        target: '[data-wizard="history"]',
    },
    {
        icon: 'mdi-account-circle',
        title: 'Your Profile',
        description: 'Tap your avatar and name to access your profile settings. Here you can update your Personal Info, Medical Info, Emergency Contacts, and account settings.',
        tip: 'Keeping your information updated ensures rescuers can assist you effectively.',
        color: 'linear-gradient(135deg, #13294B 0%, #3674B5 100%)',
        target: '[data-wizard="profile"]',
    },
    {
        icon: 'mdi-rocket-launch',
        title: 'You\'re All Set!',
        description: 'You now know how to use PinPointMe. In an emergency, stay calm, scan or enter your location, and request rescue. Help is always on the way!',
        tip: null,
        color: 'linear-gradient(135deg, #3674B5 0%, #13294B 100%)',
        target: null, // Centered, no highlight
    },
];

const isVisible = ref(false);
const currentStep = ref(0);
const spotlightRect = ref(null);
const cardRef = ref(null);
const cardPlacement = ref('center'); // 'above', 'below', 'center'

const currentStepData = computed(() => steps[currentStep.value]);
const isLastStep = computed(() => currentStep.value === steps.length - 1);
const progressPercent = computed(() => ((currentStep.value + 1) / steps.length) * 100);

// Spotlight style for highlighting target elements
const spotlightStyle = computed(() => {
    if (!spotlightRect.value) return null;
    const r = spotlightRect.value;
    const padding = 10;
    return {
        top: `${r.top - padding}px`,
        left: `${r.left - padding}px`,
        width: `${r.width + padding * 2}px`,
        height: `${r.height + padding * 2}px`,
    };
});

// Arrow direction for CSS class
const arrowDirection = computed(() => {
    return `arrow-${cardPlacement.value}`;
});

// Arrow style - position it between the card and the spotlight
const arrowStyle = computed(() => {
    if (!spotlightRect.value || !cardRef.value) return null;
    const sr = spotlightRect.value;
    const spotCenterX = sr.left + sr.width / 2;

    if (cardPlacement.value === 'above') {
        // Arrow points DOWN toward the target, sits at bottom of card
        return {
            left: `${Math.min(Math.max(spotCenterX - 12, 20), window.innerWidth - 44)}px`,
            top: `${sr.top - 22}px`,
        };
    } else if (cardPlacement.value === 'below') {
        // Arrow points UP toward the target, sits at top of card
        return {
            left: `${Math.min(Math.max(spotCenterX - 12, 20), window.innerWidth - 44)}px`,
            top: `${sr.bottom + 12}px`,
        };
    }
    return null;
});

// Card position - smartly positions near the spotlight target
const cardPositionStyle = computed(() => {
    if (!spotlightRect.value) {
        return {}; // Centered via CSS class
    }
    
    const r = spotlightRect.value;
    const padding = 10;
    const cardWidth = Math.min(340, window.innerWidth - 32);
    const cardEstHeight = 380; // Reduced estimate for better fit
    const gap = 24; // gap between spotlight and card (includes arrow space)
    const viewH = window.innerHeight;
    const viewW = window.innerWidth;
    const minTopMargin = 60; // Minimum margin from top of screen
    
    const spaceAbove = r.top - padding - minTopMargin;
    const spaceBelow = viewH - r.bottom - padding;
    
    const style = {};
    
    // Decide above or below - prefer below when space is tight
    if (spaceBelow > cardEstHeight + gap) {
        // Place card BELOW the target
        style.top = `${Math.min(r.bottom + gap, viewH - cardEstHeight - 20)}px`;
        cardPlacement.value = 'below';
    } else if (spaceAbove > cardEstHeight + gap) {
        // Place card ABOVE the target - but ensure it doesn't go off screen
        const bottomPos = viewH - r.top + gap;
        const calculatedTop = viewH - bottomPos - cardEstHeight;
        if (calculatedTop < minTopMargin) {
            // Card would be too high, use top positioning instead
            style.top = `${minTopMargin}px`;
        } else {
            style.bottom = `${bottomPos}px`;
        }
        cardPlacement.value = 'above';
    } else {
        // Not enough space either way — center the card on screen
        style.top = '50%';
        style.transform = 'translateY(-50%)';
        cardPlacement.value = 'center';
    }
    
    // Horizontal: center card on target, but clamp to viewport
    const spotCenterX = r.left + r.width / 2;
    let cardLeft = spotCenterX - cardWidth / 2;
    cardLeft = Math.max(16, Math.min(cardLeft, viewW - cardWidth - 16));
    style.left = `${cardLeft}px`;
    style.right = 'auto';
    style.position = 'fixed';
    style.maxWidth = `${cardWidth}px`;
    
    return style;
});

// Check if wizard should be shown
const shouldShowWizard = () => {
    try {
        const completed = localStorage.getItem(WIZARD_COMPLETED_KEY);
        if (completed) {
            const data = JSON.parse(completed);
            return data.version !== WIZARD_VERSION;
        }
        return true; // Never completed
    } catch {
        return true;
    }
};

// Mark wizard as completed
const markCompleted = () => {
    try {
        localStorage.setItem(WIZARD_COMPLETED_KEY, JSON.stringify({
            version: WIZARD_VERSION,
            completedAt: new Date().toISOString(),
        }));
    } catch { /* non-critical */ }
};

// Scroll target element into view if needed & update spotlight position
const updateSpotlight = async () => {
    await nextTick();
    const target = currentStepData.value.target;
    if (!target) {
        spotlightRect.value = null;
        cardPlacement.value = 'center';
        return;
    }

    // Small delay to let the DOM settle
    await new Promise(r => setTimeout(r, 80));
    
    const el = document.querySelector(target);
    if (el) {
        // Check if element is actually visible (not hidden by CSS like display:none on desktop)
        const rect = el.getBoundingClientRect();
        if (rect.width === 0 && rect.height === 0) {
            // Element exists but is hidden (e.g., bottom nav on web view)
            spotlightRect.value = null;
            cardPlacement.value = 'center';
            return;
        }

        // Scroll element into view if it's not visible
        const viewH = window.innerHeight;
        if (rect.top < 0 || rect.bottom > viewH) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // Wait for scroll to finish
            await new Promise(r => setTimeout(r, 400));
        }
        
        // Now get the final position
        const finalRect = el.getBoundingClientRect();
        spotlightRect.value = {
            top: finalRect.top,
            left: finalRect.left,
            width: finalRect.width,
            height: finalRect.height,
            bottom: finalRect.bottom,
        };
    } else {
        spotlightRect.value = null;
        cardPlacement.value = 'center';
    }
};

const nextStep = () => {
    if (isLastStep.value) {
        completeWizard();
    } else {
        currentStep.value++;
        updateSpotlight();
    }
};

const prevStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
        updateSpotlight();
    }
};

const goToStep = (index) => {
    currentStep.value = index;
    updateSpotlight();
};

const skipWizard = () => {
    markCompleted();
    isVisible.value = false;
    emit('skip');
    emit('close');
};

const completeWizard = () => {
    markCompleted();
    isVisible.value = false;
    emit('complete');
    emit('close');
};

const handleOverlayClick = () => {
    // Don't close on overlay click — user must use skip or navigate through steps
};

// Public method to show wizard manually
const showWizard = () => {
    currentStep.value = 0;
    isVisible.value = true;
    updateSpotlight();
};

// Watch forceShow prop
watch(() => props.forceShow, (val) => {
    if (val) {
        showWizard();
    }
});

// Handle resize — update spotlight
let resizeHandler = null;
onMounted(() => {
    // Show on first visit for new users only
    if (shouldShowWizard()) {
        // Small delay to let the page render first
        setTimeout(() => {
            isVisible.value = true;
            updateSpotlight();
        }, 1000);
    }
    
    resizeHandler = () => {
        if (isVisible.value) updateSpotlight();
    };
    window.addEventListener('resize', resizeHandler);
});

onUnmounted(() => {
    if (resizeHandler) window.removeEventListener('resize', resizeHandler);
});

// Expose method for parent
defineExpose({ showWizard });
</script>

<style scoped>
/* Overlay */
.wizard-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(24, 36, 60, 0.25); /* much lighter, more transparent */
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    overflow-y: auto;
    backdrop-filter: blur(2px); /* lighter blur for better background visibility */
}

/* Spotlight */
.wizard-spotlight {
    position: fixed;
    border-radius: 16px;
    box-shadow: 0 0 0 9999px rgba(10, 20, 40, 0.55); /* lighter shadow for better visibility */
    z-index: 100000;
    border: 2.5px solid rgba(223, 169, 44, 0.85);
    transition: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
    animation: spotlight-pulse 2s ease-in-out infinite;
    pointer-events: none;
}

@keyframes spotlight-pulse {
    0%, 100% { 
        border-color: rgba(223, 169, 44, 0.85); 
        box-shadow: 0 0 0 9999px rgba(10, 20, 40, 0.55), 0 0 16px 4px rgba(223, 169, 44, 0.2);
    }
    50% { 
        border-color: rgba(223, 169, 44, 1); 
        box-shadow: 0 0 0 9999px rgba(10, 20, 40, 0.55), 0 0 24px 8px rgba(223, 169, 44, 0.35);
    }
}

/* Arrow pointer between card and spotlight */
.wizard-arrow {
    position: fixed;
    z-index: 100002;
    width: 0;
    height: 0;
    pointer-events: none;
    transition: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

/* Arrow pointing DOWN (card is above, arrow at bottom of card pointing to target) */
.wizard-arrow.arrow-above {
    border-left: 14px solid transparent;
    border-right: 14px solid transparent;
    border-top: 16px solid white;
}

/* Arrow pointing UP (card is below, arrow at top of card pointing to target) */
.wizard-arrow.arrow-below {
    border-left: 14px solid transparent;
    border-right: 14px solid transparent;
    border-bottom: 16px solid white;
}

/* Card */
.wizard-card {
    z-index: 100001;
    background: white;
    border-radius: 20px;
    width: 100%;
    max-width: 340px;
    max-height: calc(100vh - 100px);
    overflow-y: auto;
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1);
    animation: wizard-card-enter 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* When card has a target, it's positioned absolutely. When centered, let flexbox handle it. */
.wizard-card:not(.card-centered) {
    position: fixed;
}

.wizard-card.card-centered {
    position: relative;
    margin: auto;
}

@keyframes wizard-card-enter {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Progress bar */
.wizard-progress {
    height: 4px;
    background: #e8edf2;
    overflow: hidden;
}

.wizard-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #3674B5 0%, #DFA92C 100%);
    transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 0 4px 4px 0;
}

/* Header */
.wizard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px 0;
}

.wizard-step-badge {
    display: flex;
    align-items: baseline;
    gap: 2px;
    background: rgba(54, 116, 181, 0.08);
    padding: 3px 10px;
    border-radius: 16px;
}

.step-current {
    font-size: 0.9rem;
    font-weight: 800;
    color: #3674B5;
}

.step-divider {
    font-size: 0.75rem;
    color: #94a3b8;
    margin: 0 1px;
}

.step-total {
    font-size: 0.75rem;
    font-weight: 600;
    color: #94a3b8;
}

.wizard-skip-btn {
    display: flex;
    align-items: center;
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    padding: 5px 8px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.wizard-skip-btn:hover {
    background: #f1f5f9;
    color: #64748b;
}

/* Icon */
.wizard-icon-wrapper {
    display: flex;
    justify-content: center;
    padding: 12px 0 8px;
}

.wizard-icon-circle {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 20px rgba(54, 116, 181, 0.3);
    animation: icon-float 3s ease-in-out infinite;
}

.wizard-icon-circle .v-icon {
    font-size: 28px !important;
}

@keyframes icon-float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}

/* Content */
.wizard-content {
    padding: 0 20px 6px;
    text-align: center;
}

.wizard-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #13294B;
    margin-bottom: 6px;
    line-height: 1.3;
}

.wizard-description {
    font-size: 0.82rem;
    color: #4a5568;
    line-height: 1.5;
    margin-bottom: 8px;
}

/* Tip box */
.wizard-tip {
    display: flex;
    align-items: flex-start;
    gap: 5px;
    background: linear-gradient(135deg, rgba(223, 169, 44, 0.08) 0%, rgba(223, 169, 44, 0.04) 100%);  
    border: 1px solid rgba(223, 169, 44, 0.2);
    border-radius: 10px;
    padding: 8px 12px;
    text-align: left;
}

.wizard-tip span {
    font-size: 0.72rem;
    color: #78610f;
    line-height: 1.45;
}

/* Step dots */
.wizard-dots {
    display: flex;
    justify-content: center;
    gap: 4px;
    padding: 8px 0 6px;
}

.wizard-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    border: none;
    background: #e2e8f0;
    cursor: pointer;
    padding: 0;
    transition: all 0.3s ease;
}

.wizard-dot.active {
    width: 18px;
    border-radius: 4px;
    background: linear-gradient(90deg, #3674B5 0%, #4A90E2 100%);
}

.wizard-dot.completed {
    background: #3674B5;
}

/* Actions */
.wizard-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 16px 14px;
    gap: 10px;
}

.wizard-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 12px;
    padding: 10px 18px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
}

.wizard-btn-back {
    background: #f1f5f9;
    color: #475569;
}

.wizard-btn-back:hover {
    background: #e2e8f0;
}

.wizard-btn-next {
    background: linear-gradient(135deg, #3674B5 0%, #2D5F96 100%);
    color: white;
    flex: 1;
    max-width: 180px;
    box-shadow: 0 4px 12px rgba(54, 116, 181, 0.35);
}

.wizard-btn-next:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(54, 116, 181, 0.45);
}

.wizard-btn-next:active {
    transform: translateY(0);
}

/* Fade transition */
.wizard-fade-enter-active {
    transition: opacity 0.35s ease;
}

.wizard-fade-leave-active {
    transition: opacity 0.25s ease;
}

.wizard-fade-enter-from,
.wizard-fade-leave-to {
    opacity: 0;
}

/* Medium screens */
@media (max-width: 500px) {
    .wizard-card {
        max-width: 340px;
        max-height: calc(100vh - 100px);
    }
}

/* Small screens — slightly smaller card */
@media (max-width: 420px) {
    .wizard-overlay {
        padding: 12px;
    }

    .wizard-card {
        border-radius: 20px;
        max-width: 320px;
        max-height: calc(100vh - 80px);
    }

    .wizard-content {
        padding: 0 16px 6px;
    }

    .wizard-title {
        font-size: 1rem;
    }

    .wizard-description {
        font-size: 0.8rem;
        line-height: 1.5;
        margin-bottom: 8px;
    }

    .wizard-icon-circle {
        width: 52px;
        height: 52px;
        border-radius: 14px;
    }

    .wizard-icon-wrapper {
        padding: 10px 0 6px;
    }

    .wizard-actions {
        padding: 4px 14px 12px;
    }

    .wizard-btn {
        padding: 9px 16px;
        font-size: 0.82rem;
    }

    .wizard-tip {
        padding: 8px 12px;
    }

    .wizard-tip span {
        font-size: 0.72rem;
    }

    .wizard-dots {
        padding: 8px 0 6px;
    }

    .wizard-header {
        padding: 12px 16px 0;
    }

    .wizard-step-badge {
        padding: 3px 10px;
    }

    .step-current {
        font-size: 0.9rem;
    }

    .step-total, .step-divider {
        font-size: 0.75rem;
    }

    .wizard-skip-btn {
        font-size: 0.75rem;
        padding: 5px 8px;
    }
}

/* Extra small screens */
@media (max-width: 360px) {
    .wizard-overlay {
        padding: 8px;
    }

    .wizard-card {
        border-radius: 18px;
        max-width: 300px;
        max-height: calc(100vh - 60px);
    }

    .wizard-content {
        padding: 0 14px 4px;
    }

    .wizard-title {
        font-size: 0.92rem;
    }

    .wizard-description {
        font-size: 0.75rem;
        line-height: 1.45;
    }

    .wizard-icon-circle {
        width: 46px;
        height: 46px;
        border-radius: 12px;
    }

    .wizard-icon-circle .v-icon {
        font-size: 24px !important;
    }

    .wizard-icon-wrapper {
        padding: 8px 0 4px;
    }

    .wizard-actions {
        padding: 4px 12px 10px;
        gap: 8px;
    }

    .wizard-btn {
        padding: 8px 12px;
        font-size: 0.78rem;
        border-radius: 10px;
    }

    .wizard-btn-next {
        max-width: 160px;
    }

    .wizard-tip {
        padding: 6px 10px;
    }

    .wizard-tip span {
        font-size: 0.68rem;
    }

    .wizard-dots {
        padding: 6px 0 4px;
        gap: 4px;
    }

    .wizard-dot {
        width: 6px;
        height: 6px;
    }

    .wizard-dot.active {
        width: 16px;
    }

    .wizard-header {
        padding: 10px 12px 0;
    }

    .wizard-progress {
        height: 3px;
    }
}

/* Very small screens / landscape mode */
@media (max-height: 600px) {
    .wizard-card {
        max-height: calc(100vh - 40px);
    }

    .wizard-icon-wrapper {
        padding: 6px 0 4px;
    }

    .wizard-icon-circle {
        width: 44px;
        height: 44px;
    }

    .wizard-content {
        padding: 0 14px 4px;
    }

    .wizard-description {
        margin-bottom: 6px;
    }

    .wizard-dots {
        padding: 6px 0 4px;
    }

    .wizard-actions {
        padding: 4px 14px 10px;
    }
}
</style>
