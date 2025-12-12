<template>
    <v-textarea
        rows="2"
        variant="outlined"
        density="compact"
        hide-details="auto"
        :color="computedColor"
        class="my-1"
        v-bind="$attrs"
    ></v-textarea>
</template>

<script setup>
import { computed } from "vue";
import { useTheme } from "vuetify";

const props = defineProps({
    color: {
        type: String,
        default: null, // no color passed
    },
});

const theme = useTheme();
const isDarkMode = computed(() => theme.global.name.value === "dark");

const computedColor = computed(() => {
    // If dark mode is active, append "-tonal" or use default "primary-tonal"
    if (isDarkMode.value) {
        return props.color ? `${props.color}-tonal` : "primary-tonal";
    }

    // If in light mode, return the color or fallback to "primary"
    return props.color ?? "primary";
});
</script>
