<template>
    <v-btn-icon
        :icon="iconName"
        :color="btnColor"
        :class="!hasAccess ? 'opacity-20' : ''"
        class="mx-2"
        size="small"
        @click="toggleDialog"
        :description="description"
    />

    <v-dialog
        v-model="dialog"
        width="350"
        title="Notice"
        prepend-icon="mdi-alert-circle"
        :btnDisabled="btnDisabled"
        @close="toggleDialog"
        @submit="handleSubmit"
        submitName="confirm"
    >
        <v-container>
            <p>
                {{ description }}
                <strong> {{ profile.name }} </strong>'s account?
            </p></v-container
        >
    </v-dialog>

    <SnackBar ref="snackBarRef" />
</template>

<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";

import SnackBar from "@/Components/Utilities/SnackBar.vue";

const dialog = ref(false);
const toggleDialog = () => {
    if (!props.can.includes("update")) {
        return;
    }

    dialog.value = !dialog.value;
};

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

const isActive = computed(() => {
    return props.profile.status;
});

const btnColor = computed(() => {
    return isActive.value === "inactive" ? "accent" : "error";
});

const iconName = computed(() => {
    return isActive.value === "inactive" ? "mdi-circle" : "mdi-minus-circle";
});

const hasAccess = computed(() => {
    return props.can.includes("update");
});

const description = computed(() => {
    return isActive.value === "inactive" ? "Activate" : "Deactivate";
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
        `/set-user-status/${props.profile.user_id}`,
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
