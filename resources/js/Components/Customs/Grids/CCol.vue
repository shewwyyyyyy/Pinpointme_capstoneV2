<template>
    <v-col
        v-bind="$attrs"
        :cols="cols"
        :sm="sm"
        :md="md"
        :lg="lg"
        :xl="xl"
        :xxl="xxl"
    >
        <slot />
    </v-col>
</template>

<script setup>
defineProps({
    cols: {
        type: [Number, String],
        default: 12,
        validator: (value) => Number(value) >= 1 && Number(value) <= 12,
    },
    sm: {
        type: [Number, String],
        default: 12,
        validator: (value) => Number(value) >= 1 && Number(value) <= 12,
    },
    md: {
        type: [Number, String],
        default: 6,
        validator: (value) => Number(value) >= 1 && Number(value) <= 12,
    },
    lg: {
        type: [Number, String],
        default: 6,
        validator: (value) => Number(value) >= 1 && Number(value) <= 12,
    },
    xl: {
        type: [Number, String],
        default: 6,
        validator: (value) => Number(value) >= 1 && Number(value) <= 12,
    },
    xxl: {
        type: [Number, String],
        default: 6,
        validator: (value) => Number(value) >= 1 && Number(value) <= 12,
    },
});
</script>
