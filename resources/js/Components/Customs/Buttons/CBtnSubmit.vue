<template>
    <c-btn
        prepend-icon="mdi-check-circle"
        type="submit"
        variant="elevated"
        v-bind="$attrs"
    >
        {{ label }}</c-btn
    >
</template>

<script setup>
import CBtn from "./CBtn.vue";

const props = defineProps({
    label: {
        type: String,
        default: "Submit",
    },
});
</script>
