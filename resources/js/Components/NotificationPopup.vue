<template>
    <!-- Floating Notification Alert -->
    <v-slide-y-transition>
        <v-alert
            v-if="show"
            :type="type"
            :icon="icon"
            variant="elevated"
            class="notification-popup"
            :class="[`notification-${type}`, { 'clickable': !!onClick }]"
            closable
            @click="handleClick"
            @click:close="handleClose"
        >
            <template v-slot:title>
                <span class="font-weight-bold">{{ title }}</span>
            </template>
            {{ message }}
            <template v-slot:append v-if="showViewButton">
                <v-btn
                    variant="text"
                    size="small"
                    @click.stop="handleClick"
                >
                    View
                </v-btn>
            </template>
        </v-alert>
    </v-slide-y-transition>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Notification',
    },
    message: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'info',
        validator: (value) => ['info', 'success', 'warning', 'error'].includes(value),
    },
    icon: {
        type: String,
        default: 'mdi-bell',
    },
    showViewButton: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close', 'click']);

const onClick = computed(() => {
    return props.showViewButton;
});

const handleClick = () => {
    emit('click');
};

const handleClose = () => {
    emit('close');
};
</script>

<style scoped>
.notification-popup {
    position: fixed;
    top: 70px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    min-width: 320px;
    max-width: 90vw;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    border-radius: 12px;
    animation: slideDown 0.3s ease-out, pulse 2s ease-in-out infinite;
}

.notification-popup.clickable {
    cursor: pointer;
}

.notification-popup.clickable:hover {
    transform: translateX(-50%) scale(1.02);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
}

.notification-error {
    background: linear-gradient(135deg, #ff5252, #d32f2f) !important;
    animation: slideDown 0.3s ease-out, shake 0.5s ease-in-out;
}

.notification-warning {
    background: linear-gradient(135deg, #ffc107, #ff9800) !important;
}

.notification-success {
    background: linear-gradient(135deg, #4caf50, #2e7d32) !important;
}

.notification-info {
    background: linear-gradient(135deg, #2196f3, #1565c0) !important;
}

@keyframes slideDown {
    from {
        transform: translateX(-50%) translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    50% {
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), 0 0 20px rgba(var(--v-theme-primary), 0.4);
    }
}

@keyframes shake {
    0%, 100% { transform: translateX(-50%) translateY(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-52%) translateY(0); }
    20%, 40%, 60%, 80% { transform: translateX(-48%) translateY(0); }
}
</style>
