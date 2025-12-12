<template>
    <v-data-table
        v-bind="$attrs"
        :items-per-page="perPage"
        :items-per-page-options="itemsPerPageOption"
    >
        <template #item.num="{ index }">
            {{ indexCount + index }}
        </template>

        <!-- Forward Vuetify's named slots -->
        <template
            v-for="(_, name) in $slots"
            :key="name"
            v-slot:[name]="slotProps"
        >
            <slot :name="name" v-bind="slotProps" />
        </template>
    </v-data-table>
</template>

<script setup>
import { computed, ref, watch } from "vue";

const props = defineProps({
    hover: {
        type: Boolean,
        default: true,
    },
    itemValue: {
        type: String,
        default: "id",
    },
    itemsPerPageOption: {
        type: Array,
        default: () => [5, 10, 20],
    },
    density: {
        type: String,
        default: "comfortable",
    },
});

// Define state variables
const currentPage = ref(1);
const perPage = ref(5);

//compute row count of an item in the table
const indexCount = computed(() => {
    return 1 + (perPage.value * currentPage.value - perPage.value);
});
</script>
