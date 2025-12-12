<template>
    <v-btn class="ma-1" prepend-icon="mdi-plus" @click="toggleDialog">
        add
    </v-btn>

    <v-dialog v-model="dialog" width="750" persistent>
        <v-card
            width="auto"
            max-width="1000"
            prepend-icon="mdi-plus"
            title="Add Department"
        >
            <v-container>
                <FormDepartment
                    :errors="errors"
                    :flash="flash"
                    :can="can"
                    @formValues="getFormDepartmentValue"
                    ref="formDepartmentRef"
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
import FormDepartment from "./Forms/FormDepartment.vue";
import SnackBar from "@/Components/Utilities/SnackBar.vue";

const dialog = ref(false);
const toggleDialog = () => {
    dialog.value = !dialog.value;
};

defineProps({
    showBtn: {
        type: Boolean,
        default: true,
    },
    errors: Object,
    flash: Object,
    can: Array,
});
const form = ref({});

// Department form
const getFormDepartmentValue = (value) => {
    Object.assign(form.value, value);
};

const formDepartmentRef = ref(null);
const toggleFormDepartmentRef = () => {
    if (formDepartmentRef.value) {
        formDepartmentRef.value.emitFormData();
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
    toggleFormDepartmentRef();

    // submission here
    router.post("/departments", form.value, {
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
