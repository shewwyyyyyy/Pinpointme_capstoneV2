<template>
    <Head :title="title" />
    <MainLayout :errors="errors" :flash="flash" :can="can">
        <LocationContent :properties="properties" :flash="flash" :errors="errors" :can="can" />
    </MainLayout>
</template>
<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

//components
import MainLayout from "../../Layouts/MainLayout.vue";
import LocationContent from "../../Components/Pages/System/Location/LocationContent.vue";

const props = defineProps({
    properties: Array,
    errors: Object,
    flash: Object,
    can: Array,
});

const page = usePage();
const appName = computed(() => {
    return page.props.appName ?? "App Name";
});

const title = computed(() => {
    return `Locations — ${appName.value}`;
});
</script>
