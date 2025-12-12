<template>
    <v-btn-icon
        icon="mdi-lock-reset"
        :disabled="btnDisabled"
        :class="!hasAccess ? 'opacity-20' : ''"
        class="mx-2"
        size="small"
        @click="toggleDialog"
        description="Reset Password"
    />

    <v-dialog
        v-model="dialog"
        width="350"
        title="Reset Password"
        prependIcon="mdi-lock-reset"
        :btnDisabled="btnDisabled"
        @close="toggleDialog"
        @submit="handleSubmit"
        submitName="Confirm"
    >
        <v-container>
            <p>
                Are you sure you want to reset the password of
                <strong>{{ profile.name }}</strong
                >?
            </p>
        </v-container>
    </v-dialog>

    <SnackBar ref="snackBarRef" />
</template>

<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";

import SnackBar from "@/Components/Utilities/SnackBar.vue";

const props = defineProps({
    showBtn: {
        type: Boolean,
        default: true,
    },
    profile: Object,
    errors: Object,
    flash: Object,
    can: Array,
});

const dialog = ref(false);
const toggleDialog = () => {
    if (!props.can.includes("update")) {
        return;
    }

    dialog.value = !dialog.value;
};

const hasAccess = computed(() => {
    return props.can.includes("update");
});

// notification
const snackBarRef = ref(null);
const toggleSnackBar = (message, color) => {
    if (!snackBarRef.value) {
        return;
    }

    snackBarRef.value.showNotification(message, color);
};

// handle submission
const btnDisabled = ref(false);

const handleSubmit = () => {
    router.post(
        `/reset-password/${props.profile.user_id}`,
        {
            _method: "PUT",
            forceFormData: true,
        },
        {
            onSuccess: ({ props }) => {
                toggleDialog();

                toggleSnackBar(props.flash.success, "accent");
            },
            onError: () => {
                toggleSnackBar("Some fields has an error.", "error");
            },
            onBefore: () => {
                btnDisabled.value = true;
            },
            onFinish: () => {
                btnDisabled.value = false;
            },
        }
    );
};

defineExpose({
    toggleDialog,
});
</script>
