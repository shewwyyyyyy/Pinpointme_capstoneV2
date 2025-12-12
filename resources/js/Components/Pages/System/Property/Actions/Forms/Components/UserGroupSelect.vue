<template>
    <v-select
        item-value="id"
        item-title="name"
        label="User Group"
        :items="computedUserGroups"
        v-bind="$attrs"
    />
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    user_groups: {
        type: Array,
        required: true,
    },
});

const computedUserGroups = computed(() => {
    return props.user_groups.map((group) => ({
        id: group.id,
        name: group.name,
    }));
});
</script>
