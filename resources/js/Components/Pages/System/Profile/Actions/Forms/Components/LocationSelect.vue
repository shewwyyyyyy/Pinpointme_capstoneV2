<template>
    <v-select
        item-value="id"
        item-title="name"
        label="Location"
        density="compact"
        class="my-1"
        color="primary"
        hide-details="auto"
        :items="locations"
        v-bind="$attrs"
        clearable
    />
</template>

<script setup>
const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
});
</script>
