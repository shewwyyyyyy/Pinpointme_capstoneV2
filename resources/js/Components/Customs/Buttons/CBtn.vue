<template>
    <v-btn class="ma-1" :size="size" v-bind="$attrs">
        <slot />
    </v-btn>
</template>

<script setup>
const props = defineProps({
    size: {
        type: String,
        default: "small",
    },
});
</script>
