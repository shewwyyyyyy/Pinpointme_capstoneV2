<template>
    <c-btn-tonal
        prepend-icon="mdi-pencil-circle"
        v-tooltip:bottom="'Edit'"
        v-bind="$attrs"
    >
        {{ label ? label : "Edit" }}
    </c-btn-tonal>
</template>

<script setup>
import CBtnTonal from "./CBtnTonal.vue";

const props = defineProps({
    label: {
        type: String,
    },
});
</script>
