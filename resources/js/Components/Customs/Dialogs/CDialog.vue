<template>
    <v-dialog v-bind="$attrs" :width="width">
        <v-card
            width="auto"
            max-width="1000"
            :prepend-icon="prependIcon"
            :title="title"
            :text="text"
        >
            <slot />
            <template v-slot:actions>
                <c-btn-text
                    prepend-icon="mdi-close"
                    @click="$emit('close')"
                    :disabled="btnDisabled"
                >
                    Close
                </c-btn-text>
                <template v-if="showSubmit">
                    <c-btn-submit
                        @click="$emit('submit')"
                        :disabled="btnDisabled"
                        :loading="btnDisabled"
                        :label="submitName"
                    />
                </template>
            </template>
        </v-card>
    </v-dialog>
</template>

<script setup>
import CBtnText from "@/Components/Customs/Buttons/CBtnText.vue";
import CBtnSubmit from "@/Components/Customs/Buttons/CBtnSubmit.vue";

defineProps({
    width: {
        type: [String, Number],
        default: "auto",
    },
    title: {
        type: String,
        default: "Dialog",
    },
    text: String,
    prependIcon: {
        type: String,
        default: "mdi-update",
    },
    btnDisabled: {
        type: Boolean,
        default: false,
    },
    submitName: {
        type: String,
        default: "Submit",
    },
    showSubmit: {
        type: Boolean,
        default: true,
    },
});

defineEmits(["close", "submit"]);
</script>
