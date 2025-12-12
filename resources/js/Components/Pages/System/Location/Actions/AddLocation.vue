<template>
    <v-btn class="ma-1" prepend-icon="mdi-plus" @click="toggleDialog">
        add
    </v-btn>

    <v-dialog v-model="dialog" width="750" persistent>
        <v-card
            width="auto"
            max-width="1000"
            prepend-icon="mdi-plus"
            title="Add Location"
        >
            <v-container>
                <FormLocation
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
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

// Importing custom components
import FormLocation from "./Forms/Formlocation.vue";
import SnackBar from "@/Components/Utilities/SnackBar.vue";

const dialog = ref(false);
const toggleDialog = () => {
    dialog.value = !dialog.value;
};

const props = defineProps({
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

    // submission here
    router.post("/locations", form.value, {
        forceFormData: true,
        onSuccess: ({ props }) => {
            toggleDialog();

            // reset form value
            form.value = {};

            toggleSnackBar(props.flash.success, "accent");
        },
        onError: () => {
            // emits("notification", "Some fields has an error.", "error");
            toggleSnackBar("Some fields has an error.", "error");
        },
        onBefore: () => {
            btnDisabled.value = true;
        },
        onFinish: () => {
            btnDisabled.value = false;
        },
    });
};

defineExpose({
    toggleDialog,
});
</script>
