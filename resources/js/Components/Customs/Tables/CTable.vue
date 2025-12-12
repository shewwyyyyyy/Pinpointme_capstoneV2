<template>
    <v-table :density="density" :hover="hover" v-bind="$attrs">
        <slot />
    </v-table>
</template>
<script setup>
defineProps({
    density: {
        type: String,
        default: "comfortable",
    },
    hover: {
        type: Boolean,
        default: true,
    },
});
</script>
