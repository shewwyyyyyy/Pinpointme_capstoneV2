<template>
    <c-text-field-solo
        placeholder="Search"
        prepend-inner-icon="mdi-magnify"
        v-bind="$attrs"
        :model-value="modelValue"
        @update:modelValue="$emit('update:modelValue', $event)"
        rounded="pill"
        bg-color="search-bg"
        @input="handleNameInput($event)"
    >
        <slot />
    </c-text-field-solo>
</template>

<script setup>
import { watch, computed } from "vue";
import CTextFieldSolo from "./CTextFieldSolo.vue";

// Define props
const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: null,
    },
});

const emit = defineEmits();

/**
 * Filters input for form.name to allow only alphanumeric, numeric, and special characters,
 * and disallow emojis.
 */
const handleNameInput = (event) => {
    // Remove emoji characters using a regex that matches surrogate pairs and certain unicode ranges
    const emojiRegex = /([\u2700-\u27BF]|[\uE000-\uF8FF]|[\uD83C-\uDBFF][\uDC00-\uDFFF]|\uFE0F|\u200D)/g;
    let value = event.target.value.replace(emojiRegex, "");
    return emit("update:modelValue", value);
};

</script>
