<template>
    <Head :title="title" />
    <MainLayout :errors="errors" :flash="flash" :can="can">
        <EmployeeContent
            :departments="departments"
            :positions="positions"
            :properties="properties"
            :locations="locations"
            :flash="flash"
            :errors="errors"
            :can="can"
        />
    </MainLayout>
</template>
<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

//components
import MainLayout from "../../Layouts/MainLayout.vue";
import EmployeeContent from "../../Components/Pages/System/Employee/EmployeeContent.vue";

defineProps({
    departments: Array,
    positions: Array,
    properties: Array,
    locations: Array,
    errors: Object,
    flash: Object,
    can: Array,
});

const page = usePage();
const appName = computed(() => {
    return page.props.appName ?? "App Name";
});

const title = computed(() => {
    return `Employees — ${appName.value}`;
});
</script>
