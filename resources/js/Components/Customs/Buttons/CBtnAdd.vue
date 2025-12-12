<template>
    <c-btn
        prepend-icon="mdi-plus-circle"
        v-tooltip:bottom="label"
        v-bind="$attrs"
    >
        {{ label }}
    </c-btn>
</template>

<script setup>
import CBtn from "./CBtn.vue";

const props = defineProps({
    label: {
        type: String,
        default: "Add",
    },
});
</script>
