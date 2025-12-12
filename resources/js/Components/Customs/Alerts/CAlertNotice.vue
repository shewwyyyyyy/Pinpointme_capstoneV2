<template>
    <c-alert icon="mdi-information" :color="computedColor" v-bind="$attrs">
        <slot />
    </c-alert>
</template>

<script setup>
import { useTheme } from "vuetify";

import CAlert from "./CAlert.vue";
import { computed } from "vue";

const theme = useTheme();
const isDarkMode = computed(() => theme.global.name.value === "dark");

const computedColor = computed(() => {
    // If dark mode is active, append "-tonal" or use default "primary-tonal"
    if (isDarkMode.value) {
        return "primary";
    }

    // If in light mode, return the color or fallback to "primary"
    return "primary";
});
</script>
