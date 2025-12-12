<template>
    <v-text-field
        :model-value="modelValue"
        density="compact"
        hide-details="auto"
        :color="computedColor"
        class="my-1"
        :maxlength="maxlength"
        v-bind="$attrs"
        @update:modelValue="$emit('update:modelValue', $event)"
    >
        <slot />
    </v-text-field>
</template>

<script setup>
import { computed, watch } from "vue";
import { useTheme } from "vuetify";

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: null,
    },
    color: {
        type: String,
        default: null,
    },
    maxlength: {
        type: [Number, String],
        default: 50,
    },
});

const theme = useTheme();
const isDarkMode = computed(() => theme.global.name.value === "dark");

const computedColor = computed(() => {
    if (isDarkMode.value) {
        return props.color ? `${props.color}-tonal` : "primary-tonal";
    }
    return props.color ?? "primary";
});
</script>
