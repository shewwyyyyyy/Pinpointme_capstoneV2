<template>
    <c-data-table-server
        :headers="headers"
        :hover="true"
        module="scan-histories"
        ref="tableDataRef"
        :item-class="getRowClass"
    >
        <template #item.unique_identifier="{ item }">
            {{ item.profile?.unique_identifier || "N/A" }}
        </template>

        <template #item.date="{ item }">
            {{ formatDate(item.scanned_at) }}
        </template>

        <template #item.time="{ item }">
            {{ formatTime(item.scanned_at) }}
        </template>

        <template #item.employee_name="{ item }">
            {{ getEmployeeName(item) }}
        </template>

        <template #item.department="{ item }">
            {{ item.profile?.department?.name || "N/A" }}
        </template>

        <template #item.position="{ item }">
            {{ item.profile?.position || "N/A" }}
        </template>

        <template #item.property="{ item }">
            {{ item.property?.name || "N/A" }}
        </template>

        <template #item.start_date="{ item }">
            {{ item.start_date || "N/A" }}
        </template>

        <template #item.end_date="{ item }">
            {{ item.end_date || "N/A" }}
        </template>
        <template #item.meal_type="{ item }">
            {{ item.meal_schedule || "N/A" }}
        </template>
        <template #item.meal_count="{ item }">
            <span :class="{ 'text-red': isMealCountExceeded(item) }">
                {{ item.meal_count || "N/A" }}
            </span>
        </template>
    </c-data-table-server>
</template>

<script setup>
import { ref } from "vue";

// Define props
const props = defineProps({
    errors: Object,
    flash: Object,
    can: Array,
});

const headers = ref([
    {
        title: "Unique Identifier",
        align: "start",
        sortable: true,
        key: "unique_identifier",
    },
    {
        title: "Name",
        align: "start",
        sortable: true,
        key: "employee_name",
    },
    {
        title: "Department",
        align: "start",
        sortable: false,
        key: "department",
    },
    {
        title: "Position",
        align: "start",
        sortable: false,
        key: "position",
    },
    {
        title: "Property",
        align: "start",
        sortable: false,
        key: "property",
    },
    {
        title: "Date Created",
        align: "start",
        sortable: true,
        key: "date",
    },
    {
        title: "Time",
        align: "start",
        sortable: true,
        key: "time",
    },
    {
        title: "Meal Type",
        align: "start",
        sortable: true,
        key: "meal_type",
    },
    {
        title: "Meal Count",
        align: "start",
        sortable: true,
        key: "meal_count",
    },
    {
        title: "Start Date",
        align: "start",
        sortable: false,
        key: "start_date",
    },
    {
        title: "End Date",
        align: "start",
        sortable: false,
        key: "end_date",
    },
]);

const tableDataRef = ref(null);

const toggleLoadData = (value = {}) => {
    if (tableDataRef.value) {
        tableDataRef.value.loadData(value);
    }
};

const getEmployeeName = (item) => {
    if (!item.profile) return "N/A";
    const firstName = item.profile.first_name || "";
    const middleName = item.profile.middle_name || "";
    const lastName = item.profile.last_name || "";

    // Format: Last Name, First Name Middle Name
    return `${lastName}, ${firstName} ${middleName}`.trim();
};

const formatDate = (dateTime) => {
    if (!dateTime) return "";
    // Convert to Philippine Time (GMT+8)
    const date = new Date(dateTime);
    return date.toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        timeZone: "Asia/Manila",
    });
};

const formatTime = (dateTime) => {
    if (!dateTime) return "";
    // Convert to Philippine Time (GMT+8)
    const date = new Date(dateTime);
    return date.toLocaleTimeString("en-PH", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: true,
        timeZone: "Asia/Manila",
    });
};

const isMealCountExceeded = (item) => {
    if (!item.meal_count) {
        return false;
    }
    // Default to 1 for Employee position if meal_entitlement is not set
    const mealEntitlement = item.profile?.meal_entitlement 
        ? parseInt(item.profile.meal_entitlement)
        : (item.profile?.position === 'Employee' ? 1 : 0);
    const mealCount = parseInt(item.meal_count);
    return mealCount > mealEntitlement;
};

const getRowClass = (item) => {
    return isMealCountExceeded(item) ? "meal-exceeded-row" : "";
};

defineExpose({
    toggleLoadData,
});
</script>

<style scoped>
:deep(.meal-exceeded-row) {
    background-color: #ffebee !important;
}

:deep(.meal-exceeded-row:hover) {
    background-color: #ffcdd2 !important;
}

.text-red {
    color: #d32f2f;
    font-weight: bold;
}
</style>
