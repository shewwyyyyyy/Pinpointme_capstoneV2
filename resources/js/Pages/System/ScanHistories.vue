<template>
    <MainLayout>
        <ScanHistoryContent :errors="errors" :flash="flash" :can="can" />
    </MainLayout>
</template>

<script setup>
import MainLayout from "@/Layouts/MainLayout.vue";
import ScanHistoryContent from "@/Components/Pages/System/ScanHistory/ScanHistoryContent.vue";

defineProps({
    errors: Object,
    flash: Object,
    can: Array,
});
</script>