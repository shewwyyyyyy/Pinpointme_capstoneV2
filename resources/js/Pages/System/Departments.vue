<template>
    <Head :title="title" />
    <MainLayout :errors="errors" :flash="flash" :can="can">
        <DepartmentContent :flash="flash" :errors="errors" :can="can" />
    </MainLayout>
</template>
<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

//components
import MainLayout from "../../Layouts/MainLayout.vue";
import DepartmentContent from "../../Components/Pages/System/Department/DepartmentContent.vue";

defineProps({
    errors: Object,
    flash: Object,
    can: Array,
});

const page = usePage();
const appName = computed(() => {
    return page.props.appName ?? "App Name";
});

const title = computed(() => {
    return `Departments — ${appName.value}`;
});
</script>
