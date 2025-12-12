<template>
    <nav-side-bar :errors="errors" :flash="flash" :can="can" />
    <img
        src="images/logos/a_sign_in_side_blue.png"
        alt="Sign In Right"
        style="
            position: absolute;
            margin-right: 0px;
            top: 0px;
            bottom: 0px;
            right: 0px;
            height: 100%;
            max-width: 100%;
            object-fit: cover;
            opacity: 0.15; /* 50% transparency */
        "
    />
    <v-main>
        <slot />
    </v-main>

    <app-footer />
</template>

<script setup>
import NavSideBar from "./Navigation/NavSideBar.vue";
import AppFooter from "./Navigation/Components/AppFooter.vue";

import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

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
