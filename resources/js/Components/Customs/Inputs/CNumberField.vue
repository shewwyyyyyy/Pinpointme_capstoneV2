<!-- Only allow number input, no negative numbers, and no decimal points -->
<template>
    <c-text-field-outlined
        v-bind="$attrs"
        :model-value="modelValue"
        placeholder="Number"
        @update:modelValue="$emit('update:modelValue', $event)"
    ></c-text-field-outlined>
</template>

<script setup>
import { watch } from "vue";
import CTextFieldOutlined from "./CTextFieldOutlined.vue";

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: null,
    },
});

const emit = defineEmits();
watch(
    () => props.modelValue,
    (newValue) => {
        // Only proceed if there's a new value to check
        if (newValue !== null && newValue !== undefined) {
            const validatedValue = validateNumberInput(newValue);
            emit("update:modelValue", validatedValue);
        }
    }
);

// Function to validate and clean the input value (no decimals allowed)
const validateNumberInput = (input) => {
    // Convert input to string if it's a number
    let value = input.toString();

    // Remove any non-digit characters (no decimals)
    value = value.replace(/[^0-9]/g, ""); // Remove everything that is not a digit

    return value;
};
</script>
