<template>
    <v-btn class="ma-1" prepend-icon="mdi-plus" @click="toggleDialog">
        add
    </v-btn>

    <v-dialog v-model="dialog" width="750" persistent>
        <v-card
            width="auto"
            max-width="1000"
            prepend-icon="mdi-plus"
            title="Add Property"
        >
            <v-container>
                <FormProperty
                    :errors="errors"
                    :flash="flash"
                    :can="can"
                    @formValues="getformPropertyValue"
                    ref="formPropertyRef"
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
import FormProperty from "./Forms/FormProperty.vue";
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
})
const form = ref({});

// Property form
const getformPropertyValue = (value) => {
    Object.assign(form.value, value);
};

const formPropertyRef = ref(null);
const toggleFromPropertyRef = () => {
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

// handle submmission
const btnDisabled = ref(false);
const handleSubmit = () => {
    toggleFromPropertyRef();

    // submission here
    router.post("/properties", form.value, {
        forceFormData: true,
        onSuccess: ({ props }) => {
            toggleDialog();

            form.value = {};

            toggleSnackBar(props.flash.success, "success");
        },
        onError: () => {
            toggleSnackBar("Please check the form for errors", "error");
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
