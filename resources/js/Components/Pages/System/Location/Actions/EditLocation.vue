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
        {{ location.name }}
    </v-btn>

    <v-chip v-else variant="tonal" rounded>
        <v-tooltip
            class="ma-1"
            activator="parent"
            location="bottom"
            text="Unauthorized to edit"
        ></v-tooltip>
        {{ location.name.toUpperCase() }}
    </v-chip>

    <v-dialog v-model="dialog" width="750" persistent>
        <v-card
            width="auto"
            max-width="1000"
            prepend-icon="mdi-pencil"
            title="Edit Location"
        >
            <v-container>
                <FormLocation
                    :location="location"
                    :properties="properties"
                    :errors="errors"
                    :flash="flash"
                    :can="can"
                    @formValues="getFormLocationValue"
                    ref="formLocationRef"
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
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";

import FormLocation from "./Forms/Formlocation.vue";
import SnackBar from "@/Components/Utilities/SnackBar.vue";

const dialog = ref(false);
const toggleDialog = () => {
    dialog.value = !dialog.value;
};

const props = defineProps({
    location: {
        type: Object,
        required: true,
    },
    showBtn: {
        type: Boolean,
        default: true,
    },
    properties: Array,
    errors: Object,
    flash: Object,
    can: Array,
});

const form = ref({});

// Location form
const getFormLocationValue = (value) => {
    Object.assign(form.value, value);
};

const formLocationRef = ref(null);
const toggleFormLocationRef = () => {
    if (formLocationRef.value) {
        formLocationRef.value.emitFormData();
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
    toggleFormLocationRef();

    // Add a small delay to ensure form data is properly captured
    setTimeout(() => {
        // submission here
        router.post(
            `/locations/${props.location.id}`,
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
                onError: (errors) => {
                    console.log('Validation errors:', errors);
                    toggleSnackBar("Some fields have errors. Please check the form.", "error");
                },
                onBefore: () => {
                    btnDisabled.value = true;
                },
                onFinish: () => {
                    btnDisabled.value = false;
                },
            }
        );
    }, 100);
};

defineExpose({
    toggleDialog,
});
</script>
