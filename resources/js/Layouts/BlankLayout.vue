<template>
    <scan-nav-bar :errors="errors" :flash="flash" :can="can" />

    <v-main>
        <slot />
    </v-main>
</template>

<script setup>
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

import ScanNavBar from "./Navigation/ScanNavBar.vue";

defineProps({
    errors: Object,
    flash: Object,
    can: Array,
});

const page = usePage();
const api_token = computed(() => page.props.token);

// Set token; added to run first in the browser
// This ensures that the token is available in localStorage for subsequent requests
if (typeof window !== "undefined") {
    const token = localStorage.getItem("token");
    if (!token && api_token.value) {
        localStorage.setItem("token", api_token.value);
    }
}
</script>
