<!-- Only allow number input, no negative numbers -->
<template>
    <c-text-field-outlined
        v-bind="$attrs"
        :model-value="modelValue"
        placeholder="Decimal"
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

// Watch for changes in modelValue
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

// Function to validate and clean the input value (convert to decimal, no negative numbers)
const validateNumberInput = (input) => {
    // Allow only digits and a single decimal point
    let value = input.toString();

    value = value.replace(/[^0-9.]/g, "");

    // Ensure only one decimal point exists
    const parts = value.split(".");
    if (parts.length > 2) {
        value = parts[0] + "." + parts[1]; // Ignore extra decimals
    }

    // Limit to 2 decimal places
    if (parts.length === 2) {
        parts[1] = parts[1].slice(0, 2);
        value = parts[0] + "." + parts[1];
    }

    // Prevent negative values
    const numericValue = parseFloat(value);
    return numericValue < 0 ? "0" : value;
};
</script>
