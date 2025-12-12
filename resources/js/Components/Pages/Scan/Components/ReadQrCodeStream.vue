<template>
    <v-card>
        <qrcode-stream
            :paused="paused"
            @detect="onDetect"
            @error="onError"
            @camera-on="resetValidationState"
        >
            <!-- <div v-if="validationFailure" class="validation-failure">
                This is NOT a URL!
            </div>

            <div v-if="validationPending" class="validation-pending">
                Long validation in progress...
            </div> -->
        </qrcode-stream>
    </v-card>
    <SnackBarTop ref="snackBarRef" />
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { QrcodeStream } from "vue-qrcode-reader";
import axiosInstance from "@/Utilities/axios";
import SnackBarTop from "@/Components/Utilities/SnackBarTop.vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const isValid = ref(undefined);
const paused = ref(false);
const result = ref(null);

const timeout = (ms) => {
    return new Promise((resolve) => {
        window.setTimeout(resolve, ms);
    });
};

const resetValidationState = () => {
    isValid.value = undefined;
    result.value = null;
};

const onError = console.error;

const items = ref(null);

// Expose items to parent component
defineExpose({
    items,
});

// notification
const snackBarRef = ref(null);
const toggleSnackBar = (message, color) => {
    if (!snackBarRef.value) {
        return;
    }

    snackBarRef.value.showNotification(message, color);
};

const loadData = async () => {
    try {
        const response = await axiosInstance.post(
            `/profiles/${result.value}?property_id=${page.props.auth.user.property_id}`,
            {}
        );

        items.value = response.data; // Set items
    } catch (error) {
        console.error("Error fetching data:", error);
    }
};

const onDetect = async ([firstDetectedCode]) => {
    result.value = firstDetectedCode.rawValue;
    paused.value = true;

    await loadData();

    isValid.value = true;

    await timeout(2000);

    paused.value = false;
};

const validationPending = computed(() => {
    return isValid.value === undefined && paused.value;
});

const validationSuccess = computed(() => {
    return isValid.value === true;
});

const validationFailure = computed(() => {
    return isValid.value === false;
});

watch(
    items,
    (value) => {
        if (value.data) {
            return toggleSnackBar(value.message, "accent");
        }

        toggleSnackBar(value.message, "error");
    },
    {
        deep: true,
    }
);
</script>
