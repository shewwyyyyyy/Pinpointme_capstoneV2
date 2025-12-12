<template>
    <v-container fluid>
        <v-breadcrumbs
            :items="['Home', 'Scan Histories']"
            density="compact"
        ></v-breadcrumbs>
        <v-card class="pa-4" elevation="4">
            <v-row class="mt-1" justify="space-between">
                <v-col cols="12" sm="8" md="6" lg="6" xl="6" xxl="6">
                    <v-card-title> Scan History Records </v-card-title>
                </v-col>
                <v-col cols="12" sm="2" md="2" lg="2" xl="2" xxl="2">
                    <v-select
                        :items="['OJT', 'Employee']"
                        label="Filter by Position"
                        v-model="selectedPosition"
                        density="compact"
                        variant="outlined"
                        clearable
                    ></v-select>
                </v-col>
                <v-col cols="12" sm="4" md="4" lg="4" xl="4" xxl="4">
                    <c-search-field v-model="filters.search" clearable />
                </v-col>
            </v-row>
            <TableScanHistory
                :errors="props.errors"
                :flash="props.flash"
                :can="props.can"
                ref="tableRef"
            />
        </v-card>
    </v-container>
</template>
<script setup>
const props = defineProps({
    errors: Object,
    flash: Object,
    can: Array,
});

import { ref, watch, onMounted, onUnmounted } from "vue";
import TableScanHistory from "./Tables/TableScanHistory.vue";

const tableRef = ref(null);
const filters = ref({
    search: "",
});

// Position filter state from localStorage
const selectedPosition = ref(localStorage.getItem('selectedPosition') || null);

// Listen for position filter changes from navbar
const handlePositionChange = (event) => {
    selectedPosition.value = event.detail;
    if (tableRef.value) {
        tableRef.value.toggleLoadData({
            search: filters.value.search,
            position: event.detail,
        });
    }
};

onMounted(() => {
    window.addEventListener('position-filter-changed', handlePositionChange);
});

onUnmounted(() => {
    window.removeEventListener('position-filter-changed', handlePositionChange);
});

// Watch for search changes and trigger table reload
watch(
    () => filters.value.search,
    (newValue) => {
        if (tableRef.value) {
            tableRef.value.toggleLoadData({
                search: newValue,
                position: selectedPosition.value,
            });
        }
    }
);

// Watch for position changes and trigger table reload
watch(
    selectedPosition,
    (newValue) => {
        // Update localStorage
        if (newValue) {
            localStorage.setItem('selectedPosition', newValue);
        } else {
            localStorage.removeItem('selectedPosition');
        }
        // Dispatch event for other components
        window.dispatchEvent(new CustomEvent('position-filter-changed', { detail: newValue }));
        // Reload table data
        if (tableRef.value) {
            tableRef.value.toggleLoadData({
                search: filters.value.search,
                position: newValue,
            });
        }
    }
);
</script>
