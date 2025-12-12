<template>
    <Head :title="title" />
    <MainLayout :errors="errors" :flash="flash" :can="can">
        <PropertyContent :flash="flash" :errors="errors" :can="can" />
    </MainLayout>
</template>

<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
//components
import MainLayout from "../../Layouts/MainLayout.vue";
import PropertyContent from "../../Components/Pages/System/Property/PropertyContent.vue";
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
    return `Properties — ${appName.value}`;
});

//initial commit
</script>
