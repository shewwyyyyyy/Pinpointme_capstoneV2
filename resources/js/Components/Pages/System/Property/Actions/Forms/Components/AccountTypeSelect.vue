<template>
    <v-select label="Account Type" :items="account_types" v-bind="$attrs" />
</template>

<script setup>
const props = defineProps({
    account_types: {
        type: Array,
        required: true,
    },
});
</script>
