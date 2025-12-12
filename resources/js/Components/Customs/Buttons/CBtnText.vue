<template>
    <v-btn
        class="ma-1"
        variant="text"
        size="small"
        :color="computedColor"
        :prepend-icon="prependIcon"
        v-bind="$attrs"
    >
        <template v-if="prependIcon" v-slot:prepend>
            <v-icon :color="isDarkMode ? 'white' : 'primary'"></v-icon>
        </template>
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
    prependIcon: {
        type: String,
        default: undefined,
    },
});

const theme = useTheme();
const isDarkMode = computed(() => theme.global.name.value === "dark");

const computedColor = computed(() => {
    // If dark mode is active, append "-tonal" or use default "primary-tonal"
    if (isDarkMode.value) {
        return props.color ? `${props.color}-tonal` : "white";
    }

    // If in light mode, return the color or fallback to "primary"
    return props.color ?? "primary";
});
</script>
