<template>
    <v-breadcrumbs :items="items" density="compact" v-bind="$attrs">
        <slot />
    </v-breadcrumbs>
</template>

<script setup>
defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});
</script>
