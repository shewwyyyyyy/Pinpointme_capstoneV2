<template>
    <v-footer
        app
        color="transparent"
        :absolute="true"
        class="d-flex justify-center"
        height="54"
    >
        <span class="white--text"
            >&copy; {{ `${new Date().getFullYear()} ${appName}` }}</span
        >
    </v-footer>
</template>

<script setup>
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();
const appName = computed(() => {
    return page.props.appName ?? "App Name";
});
</script>
