<template>
    <Teleport to="body">
        <Transition name="notif-slide">
            <div
                v-if="show"
                class="notif-popup"
                :class="[`notif-${type}`, { 'notif-clickable': showViewButton }]"
                @click="showViewButton ? handleClick() : null"
            >
                <!-- Colored accent bar on the left -->
                <div class="notif-accent" :class="`accent-${type}`"></div>

                <!-- Icon circle -->
                <div class="notif-icon-wrap" :class="`icon-bg-${type}`">
                    <v-icon :size="22" color="white">{{ icon || defaultIcon }}</v-icon>
                </div>

                <!-- Content -->
                <div class="notif-body">
                    <div class="notif-title">{{ title }}</div>
                    <div class="notif-message">{{ message }}</div>
                </div>

                <!-- Actions -->
                <div class="notif-actions">
                    <v-btn
                        v-if="showViewButton"
                        variant="tonal"
                        size="small"
                        rounded="lg"
                        class="notif-view-btn"
                        :class="`view-btn-${type}`"
                        @click.stop="handleClick"
                    >
                        VIEW
                    </v-btn>
                    <v-btn
                        icon
                        variant="text"
                        size="x-small"
                        class="notif-close-btn"
                        @click.stop="handleClose"
                    >
                        <v-icon size="18">mdi-close</v-icon>
                    </v-btn>
                </div>
            </div>
        </Transition>
    </Teleport>
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
        default: '',
    },
    showViewButton: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close', 'click']);

const defaultIcon = computed(() => {
    const icons = {
        error: 'mdi-alert-circle',
        warning: 'mdi-alert',
        success: 'mdi-check-circle',
        info: 'mdi-bell',
    };
    return icons[props.type] || 'mdi-bell';
});

const handleClick = () => {
    emit('click');
};

const handleClose = () => {
    emit('close');
};
</script>

<style scoped>
/* ── Popup container ── */
.notif-popup {
    position: fixed;
    top: 16px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10000;
    display: flex;
    align-items: stretch;
    width: 380px;
    max-width: calc(100vw - 32px);
    background: #ffffff;
    border-radius: 14px;
    box-shadow:
        0 8px 30px rgba(54, 116, 181, 0.15),
        0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    animation: notifEnter 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

.notif-popup.notif-error {
    animation: notifEnter 0.35s cubic-bezier(0.22, 1, 0.36, 1), notifShake 0.5s 0.35s ease-in-out;
    box-shadow:
        0 8px 30px rgba(183, 28, 28, 0.18),
        0 2px 8px rgba(0, 0, 0, 0.08);
}

.notif-clickable {
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.notif-clickable:hover {
    transform: translateX(-50%) translateY(-1px);
    box-shadow:
        0 12px 36px rgba(54, 116, 181, 0.2),
        0 4px 12px rgba(0, 0, 0, 0.1);
}

.notif-clickable.notif-error:hover {
    box-shadow:
        0 12px 36px rgba(183, 28, 28, 0.22),
        0 4px 12px rgba(0, 0, 0, 0.1);
}

/* ── Left accent bar ── */
.notif-accent {
    width: 5px;
    flex-shrink: 0;
}

.accent-error   { background: linear-gradient(180deg, #EF5350, #b71c1c); }
.accent-warning { background: linear-gradient(180deg, #FFA726, #E65100); }
.accent-success { background: linear-gradient(180deg, #66BB6A, #185D33); }
.accent-info    { background: linear-gradient(180deg, #42A5F5, #3674B5); }

/* ── Icon ── */
.notif-icon-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    margin: 14px 0 14px 14px;
    flex-shrink: 0;
}

.icon-bg-error   { background: linear-gradient(135deg, #EF5350, #C62828); }
.icon-bg-warning { background: linear-gradient(135deg, #FFA726, #E65100); }
.icon-bg-success { background: linear-gradient(135deg, #66BB6A, #2E7D32); }
.icon-bg-info    { background: linear-gradient(135deg, #42A5F5, #3674B5); }

/* ── Content ── */
.notif-body {
    flex: 1;
    padding: 14px 12px;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 3px;
}

.notif-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #13294B;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.notif-message {
    font-size: 12.5px;
    font-weight: 400;
    color: #546E7A;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* ── Actions ── */
.notif-actions {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 10px 12px 10px 0;
    flex-shrink: 0;
}

.notif-view-btn {
    font-size: 11px !important;
    font-weight: 700 !important;
    letter-spacing: 0.5px;
    min-width: 56px !important;
    height: 28px !important;
    padding: 0 10px !important;
}

.view-btn-error   { color: #C62828 !important; background: rgba(198, 40, 40, 0.1) !important; }
.view-btn-warning { color: #E65100 !important; background: rgba(230, 81, 0, 0.1) !important; }
.view-btn-success { color: #2E7D32 !important; background: rgba(46, 125, 50, 0.1) !important; }
.view-btn-info    { color: #3674B5 !important; background: rgba(54, 116, 181, 0.1) !important; }

.notif-close-btn {
    color: #90A4AE !important;
    width: 26px !important;
    height: 26px !important;
}
.notif-close-btn:hover {
    color: #546E7A !important;
    background: rgba(0, 0, 0, 0.04) !important;
}

/* ── Transitions ── */
.notif-slide-enter-active {
    animation: notifEnter 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}
.notif-slide-leave-active {
    animation: notifLeave 0.25s cubic-bezier(0.55, 0, 1, 0.45) forwards;
}

@keyframes notifEnter {
    from {
        opacity: 0;
        transform: translateX(-50%) translateY(-20px) scale(0.96);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) translateY(0) scale(1);
    }
}

@keyframes notifLeave {
    from {
        opacity: 1;
        transform: translateX(-50%) translateY(0) scale(1);
    }
    to {
        opacity: 0;
        transform: translateX(-50%) translateY(-14px) scale(0.96);
    }
}

@keyframes notifShake {
    0%, 100% { transform: translateX(-50%); }
    15%, 45%, 75% { transform: translateX(calc(-50% + 3px)); }
    30%, 60%, 90% { transform: translateX(calc(-50% - 3px)); }
}

/* ── Mobile adjustments ── */
@media (max-width: 440px) {
    .notif-popup {
        width: calc(100vw - 24px);
        border-radius: 12px;
    }

    .notif-icon-wrap {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        margin: 12px 0 12px 10px;
    }

    .notif-body {
        padding: 12px 10px;
    }

    .notif-title {
        font-size: 13px;
    }

    .notif-message {
        font-size: 12px;
    }
}
</style>
