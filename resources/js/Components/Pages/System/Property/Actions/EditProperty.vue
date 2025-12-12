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
        {{ property.name }}
    </v-btn>
    <v-chip v-else variant="tonal" rounded>
        <v-tooltip
            class="ma-1"
            activator="parent"
            location="bottom"
            text="Unauthorized to edit"
        ></v-tooltip>
        {{ property.name.toUpperCase() }}
    </v-chip>
    <v-dialog v-model="dialog" width="1000" @close="toggleDialog">
        <v-card>
            <v-card-title>
                <v-icon class="mr-2">mdi-pencil-circle</v-icon>
                Edit Property
            </v-card-title>
            <v-card-text>
                <FromProperty
                    :property="property"
                    :errors="errors"
                    :flash="flash"
                    :can="can"
                    @formValues="getFormPropertyValue"
                    ref="formPropertyRef"
                />
            </v-card-text>
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

import FromProperty from "./Forms/FormProperty.vue";
import SnackBar from "@/Components/Utilities/SnackBar.vue";

const dialog = ref(false);
const toggleDialog = () => {
    dialog.value = !dialog.value;
};

const props = defineProps({
    property: {
        type: Object,
        required: true,
    },
    showBtn: {
        type: Boolean,
        default: true,
    },
    errors: Object,
    flash: Object,
    can: Array,
});

const form = ref({});

// Property form
const getFormPropertyValue = (value) => {
    Object.assign(form.value, value);
};

const formPropertyRef = ref(null);
const toggleFormPropertyRef = () => {
    if (formPropertyRef.value) {
        formPropertyRef.value.emitFormData();
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
    toggleFormPropertyRef();

    // submission here
    router.post(
        `/properties/${props.property.id}`,
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
