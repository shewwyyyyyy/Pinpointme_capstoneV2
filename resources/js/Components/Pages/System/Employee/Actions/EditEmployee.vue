<template>
    <v-btn
        v-if="showBtn"
        class="ma-1"
        variant="tonal"
        color="primary"
        size="small"
        prepend-icon="mdi-pencil-circle"
        v-tooltip:bottom="'Edit'"
        @click="toggleDialog"
    >
        {{ profile.unique_identifier }}
    </v-btn>

    <v-chip v-else="" variant="tonal" rounded>
        <v-tooltip
            class="ma-1"
            activator="parent"
            location="bottom"
            text="Unauthorized to edit"
        ></v-tooltip>
        {{ profile.unique_identifier.toUpperCase() }}
    </v-chip>

    <v-dialog v-model="dialog" width="750" persistent>
        <v-card
            width="auto"
            max-width="1000"
            prepend-icon="mdi-plus"
            title="Edit Employee"
        >
            <v-container>
                <FormEmployee
                    :profile="profile"
                    :departments="departments"
                    :positions="positions"
                    :properties="properties"
                    :locations="locations"
                    :errors="errors"
                    :flash="flash"
                    :can="can"
                    @formValues="getFormProfileValue"
                    ref="formProfileRef"
                />
            </v-container>

            <template v-slot:actions>
                <v-btn :disabled="btnDisabled" @click="toggleDialog">
                    close
                </v-btn>
                <v-btn
                    :disabled="btnDisabled"
                    :loading="btnDisabled"
                    variant="elevated"
                    prepend-icon="mdi-check-circle"
                    @click="handleSubmit"
                >
                    save
                </v-btn>
            </template>
        </v-card>
    </v-dialog>

    <SnackBar ref="snackBarRef" />
</template>

<script setup>
import {ref} from "vue";
import { router } from "@inertiajs/vue3";

import FormEmployee from "./Forms/FormEmployee.vue";
import SnackBar from "@/Components/Utilities/SnackBar.vue";

const dialog = ref(false);
const toggleDialog = () => {
    dialog.value = !dialog.value;
};

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
    showBtn: {
        type: Boolean,
        default: true,
    },
    departments: Array,
    positions: Array,
    properties: Array,
    locations: Array,
    errors: Object,
    flash: Object,
    can: Array,
});

const form = ref({});

// User group form
const getFormProfileValue = (value) => {
    Object.assign(form.value, value);
};

const formProfileRef = ref(null);
const toggleFormProfileRef = () => {
    if (formProfileRef.value) {
        formProfileRef.value.emitFormData();
    }
};

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
    toggleFormProfileRef();

    // submission here
    router.post(
        `/profiles/${props.profile.id}`,
        {
            _method: "PUT",
            forceFormData: true,
            ...form.value,
        },
        {
            onSuccess: ({ props }) => {
                dialog.value = false;

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
