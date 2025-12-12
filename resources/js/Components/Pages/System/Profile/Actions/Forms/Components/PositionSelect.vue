<template>
    <v-select
        item-value="name"
        item-title="name"
        label="Position"
        density="compact"
        class="my-1"
        color="primary"
        hide-details="auto"
        :items="positions"
        v-bind="$attrs"
        clearable
    />
</template>

<script setup>
const props = defineProps({
    positions: {
        type: Array,
        required: true,
    },
});
</script>
