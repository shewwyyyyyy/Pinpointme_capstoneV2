<template>
    <v-autocomplete
        item-value="id"
        item-title="name"
        label="Department"
        density="compact"
        class="my-1"
        color="primary"
        clearable
        hide-details="auto"
        :items="computedDepartments"
        v-bind="$attrs"
    />
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    departments: {
        type: Array,
        required: true,
    },
});

const computedDepartments = computed(() => {
    return props.departments.map((department) => ({
        id: department.id,
        name: department.name,
    }));
});
</script>
