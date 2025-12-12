<template>
    <c-data-table-server
        :headers="headers"
        :hover="true"
        module="properties"
        ref="tableDataRef"
    >
        <template #item.name="{ item }">
            <EditProperty
                :property="item"
                :errors="props.errors"
                :flash="props.flash"
                :can="props.can"
            />
        </template>
    </c-data-table-server>
</template>

<script setup>
import { ref } from "vue";

import EditProperty from "../Actions/EditProperty.vue";
// import DeleteProperty from "../Actions/DeleteProperty.vue";

// Define props
const props = defineProps({
    errors: Object,
    flash: Object,
    can: Array,
});

const headers = ref([
    {
        title: " Name",
        align: "start",
        sortable: false,
        key: "name",
    },
    {
        title: "Code",
        align: "start",
        sortable: false,
        key: "code",
    },
    {
        title: "Description",
        align: "start",
        sortable: false,
        key: "description",
    },
    {
        title: "Created At",
        align: "start",
        sortable: false,
        key: "created_at",
    },
    {
        title: "Actions",
        align: "start",
        sortable: false,
        key: "actions",
    },
]);

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
