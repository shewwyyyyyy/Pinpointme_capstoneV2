<template>
    <v-select
        item-value="id"
        item-title="name"
        label="Property"
        density="compact"
        class="my-1"
        color="primary"
        hide-details="auto"
        :items="properties"
        v-bind="$attrs"
        clearable
    />
</template>

<script setup>
const props = defineProps({
    properties: {
        type: Array,
        required: true,
    },
});
</script>
