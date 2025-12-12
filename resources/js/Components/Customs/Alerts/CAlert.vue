<template>
    <v-alert
        border="start"
        class="my-2"
        :border-color="computedColor"
        :color="computedColor"
        elevation="2"
        v-bind="$attrs"
        variant="tonal"
    >
        <slot />
    </v-alert>
</template>

<script setup>
import { computed, ref, onMounted } from "vue";
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
        return props.color
            ? `${props.color}-tonal-alert`
            : "primary-tonal-alert";
    }

    // If in light mode, return the color or fallback to "primary"
    return props.color ?? "primary";
});
</script>
