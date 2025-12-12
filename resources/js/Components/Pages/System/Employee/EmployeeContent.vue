<template>
    <v-container fluid>
        <v-breadcrumbs
            :items="['Home', 'Employees']"
        ></v-breadcrumbs>
        <v-card class="pa-4" elevation="4">
            <v-row class="mt-1" justify="space-between">
                <v-col cols="12" sm="8" md="6" lg="6" xl="6" xxl="6">
                    <v-card-title>
                        Employees
                        <AddEmployee
                            :departments="departments"
                            :positions="positions"
                            :properties="properties"
                            :locations="locations"
                            :errors="errors"
                            :flash="flash"
                            :can="can"
                            ref="addEmployeeRef"
                        />
                    </v-card-title>
                </v-col>
                <v-col cols="12" sm="4">
                    <c-search-field v-model="filters.search" clearable />
                </v-col>
            </v-row>
            <TableEmployee
                :departments="departments"
                :positions="positions"
                :properties="properties"
                :locations="locations"
                :errors="errors"
                :flash="flash"
                :can="can"
                ref="tableDataRef"
            />
        </v-card>
    </v-container>
</template>

<script setup>
import { useDebouncedWatch } from "@/Composables/useDebouncedWatch";
import { ref, watch } from "vue";
import { useDisplay } from "vuetify/lib/framework.mjs";


import AddEmployee from "./Actions/AddEmployee.vue";
import TableEmployee from "./Tables/TableEmployee.vue";

// Define props
const props = defineProps({
    departments: Array,
    positions: Array,
    properties: Array,
    locations: Array,
    errors: Object,
    flash: Object,
    can: Array,
});

const tableDataRef = ref(null);
const toggleLoadData = (value = {}) => {
    if (tableDataRef.value) {
        tableDataRef.value.toggleLoadData(value);
    }
};

// handle search input
const filters = ref({
    search: null,
    // Add other filters as needed
});

useDebouncedWatch(
    filters,
    (value) => {
        toggleLoadData(value);
    },
    undefined,
    { deep: true }
);

// reload data when flash message changes
watch(
    () => props.flash,
    () => {
        toggleLoadData(filters.value);
    },
    { immediate: true }
);

const { mobile } = useDisplay();

// show normal add button on desktop and hide on mobile
const showAddBtn = ref(true);
watch(
    () => mobile.value,
    (newVal) => {
        showAddBtn.value = !newVal; // Hide the button on mobile
    },
    { immediate: true }
);
</script>
