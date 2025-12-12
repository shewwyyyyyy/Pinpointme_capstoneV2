<template>
    <v-pagination v-bind="$attrs" :length="length"></v-pagination>
</template>

<script setup>
defineProps({
    length: {
        type: Number,
        default: 1,
    },
});
</script>
