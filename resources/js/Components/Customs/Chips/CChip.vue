<template>
    <v-chip :color="computedColor" :density="density" v-bind="$attrs">
        <slot />
    </v-chip>
</template>

<script setup>
import { computed } from "vue";
import { useTheme } from "vuetify";

const props = defineProps({
    color: {
        type: String,
        default: "primary",
    },
    density: {
        type: String,
        default: "comfortable", // Options: "comfortable", "compact", "default"
    },
});

const theme = useTheme();
const isDarkMode = computed(() => theme.global.name.value === "dark");

const computedColor = computed(() => {
    // If dark mode is active, append "-tonal" or use default "primary-tonal"
    if (isDarkMode.value) {
        return props.color
            ? `${props.color}-tonal-alert`
            : "primary-tonal-alert";
    }

    // If in light mode, return the color or fallback to "primary"
    return props.color ?? "primary";
});
</script>
