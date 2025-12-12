<template>
    <c-data-table-server
        :headers="headers"
        :hover="true"
        module="profiles"
        ref="tableDataRef"
    >
        <template #item.unique_identifier="{ item }">
            <EditProfile
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

import EditProfile from "../Actions/EditProfile.vue";
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
        title: "Full Name",
        align: "start",
        sortable: false,
        key: "full_name",
    },
    {
        title: "Username",
        align: "start",
        sortable: false,
        key: "username",
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
        key: "property_id",
    },
    {
        title: "Department",
        align: "start",
        sortable: false,
        key: "department_id",
    },
    {
        title: "Is Able to Login",
        align: "start",
        sortable: false,
        key: "is_able_to_login",
    },
    {
        title: "Last Login",
        align: "start",
        sortable: false,
        key: "last_login_at",
    },
    {
        title: "Created At",
        align: "start",
        sortable: false,
        key: "created_at",
    },
    {
        title: "Meal Entitlement",
        align: "start",
        sortable: false,
        key: "meal_entitlement",
    },
    {
        title: "Actions",
        align: "start",
        sortable: false,
        key: "actions",
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
