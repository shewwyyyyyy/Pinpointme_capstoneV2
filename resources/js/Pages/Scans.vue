<template>
    <Head :title="title" />
    <BlankLayout :errors="errors" :flash="flash" :can="can">
        <v-container>
            <ScanContent :flash="flash" :errors="errors" />
        </v-container>
    </BlankLayout>
</template>
<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import ScanContent from "../Components/Pages/Scan/ScanContent.vue";
import BlankLayout from "../Layouts/BlankLayout.vue";

const props = defineProps({
    errors: Object,
    flash: Object,
    can: Array,
});

const page = usePage();
const appName = computed(() => {
    return page.props.appName ?? "App Name";
});

const title = computed(() => {
    return `QR Scanner — ${appName.value}`;
});
</script>
