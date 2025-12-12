<template>
    <c-chip prepend-icon="mdi-account-circle" v-bind="$attrs">
        <slot />
    </c-chip>
</template>

<script setup>
import CChip from "./CChip.vue";
</script>
