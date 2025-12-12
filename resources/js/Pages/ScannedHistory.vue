<template>
    <Head title="Scanned History" />
    <MainLayout>
        <ScannedHistoryContent 
            :errors="$page.props.errors"
            :flash="$page.props.flash"
            :can="$page.props.can"
        />
    </MainLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import ScannedHistoryContent from '@/Components/Pages/ScannedHistory/ScannedHistoryContent.vue';

defineProps({
    errors: Object,
    flash: Object,
    can: Array,
});
</script>