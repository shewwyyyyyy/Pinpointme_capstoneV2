<template>
    <v-app-bar color="primary">
        <v-app-bar-nav-icon v-if="hasDrawer" @click="toggleDrawer" />

        <v-app-bar-title>
            <img
                src="/images/logos/APZ_s_w.png"
                alt="Achi Logo"
                class="mt-1"
                style="height: 40px"
            />
        </v-app-bar-title>
        <template v-slot:append v-if="true">
            <switch-theme />
            <menu-settings
                :errors="errors"
                :flash="flash"
                :can="can"
                :showChangePassword="false"
            />
        </template>
    </v-app-bar>
</template>

<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

import SwitchTheme from "./Components/SwitchTheme.vue";
import MenuSettings from "./Components/MenuSettings.vue";

const page = usePage();
const appName = computed(() => {
    return page.props.appName ?? "App Name";
});

const props = defineProps({
    hasDrawer: {
        type: Boolean,
        default: false,
    },
    errors: Object,
    flash: Object,
    can: Array,
});

const emits = defineEmits(["toggleDrawer"]);
const toggleDrawer = () => {
    emits("toggleDrawer");
};
</script>
