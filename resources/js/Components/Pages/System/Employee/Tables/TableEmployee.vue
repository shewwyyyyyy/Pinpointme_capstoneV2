<template>
    <c-data-table-server
        :headers="headers"
        :hover="true"
        module="employees"
        ref="tableDataRef"
    >
        <template #item.unique_identifier="{ item }">
            <EditEmployee
                :profile="item"
                :departments="departments"
                :positions="positions"
                :properties="properties"
                :locations="locations"
                :errors="props.errors"
                :flash="props.flash"
                :can="props.can"
            />
        </template>
        <template #item.department_id="{ item }">
            {{ departmentName(item.department_id) }}
        </template>
        <template #item.is_able_to_login="{ item }">
            {{ item.is_able_to_login ? "Yes" : "No" }}
        </template>
    </c-data-table-server>
</template>

<script setup>
import { ref } from "vue";

import EditEmployee from "../Actions/EditEmployee.vue";
// import ResetPassword from "../Actions/ResetPassword.vue";
// import SetAccountStatus from "../Actions/SetAccountStatus.vue";

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

const headers = ref([
    {
        title: "Unique Identifier",
        align: "start",
        sortable: false,
        key: "unique_identifier",
    },
    {
        title: "Name",
        align: "start",
        sortable: false,
        key: "full_name",
    },
    {
        title: "Position",
        align: "start",
        sortable: false,
        key: "position",
    },
    {
        title: "Department",
        align: "start",
        sortable: false,
        key: "department_id",
    },
    {
        title: "Created At",
        align: "start",
        sortable: false,
        key: "created_at",
    },
]);

const departmentName = (departmentId) => {
    if (props.departments) {
        const department = props.departments.find(
            (department) => department.id === departmentId
        );
        return department ? department.name : "";
    }
    return "";
};

const tableDataRef = ref(null);
const toggleLoadData = (value = {}) => {
    if (tableDataRef.value) {
        tableDataRef.value.loadData(value);
    }
};

defineExpose({
    toggleLoadData,
});
</script>
