<template>
    <v-btn
        class="ma-1"
        variant="tonal"
        :size="size"
        :color="computedColor"
        v-bind="$attrs"
    >
        <slot />
    </v-btn>
</template>

<script setup>
import { computed, ref, onMounted } from "vue";
import { useTheme } from "vuetify";

const props = defineProps({
    color: {
        type: String,
        default: null, // no color passed
    },
    size: {
        type: String,
        default: "small",
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
