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
            <v-select
                v-model="selectedPosition"
                :items="positionOptions"
                density="compact"
                variant="outlined"
                style="max-width: 300px;"
                class="mr-2"
            ></v-select>
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
import { ref, computed, watch } from "vue";
import { usePage } from "@inertiajs/vue3";

import SwitchTheme from "./Components/SwitchTheme.vue";
import MenuSettings from "./Components/MenuSettings.vue";

const page = usePage();
const appName = computed(() => {
    return page.props.appName ?? "App Name";
});

// Position filter state
const selectedPosition = ref(localStorage.getItem('selectedPosition') || null);
const positionOptions = ['OJT', 'Employee'];

// Watch for changes and persist to localStorage
watch(selectedPosition, (newValue) => {
    if (newValue) {
        localStorage.setItem('selectedPosition', newValue);
    } else {
        localStorage.removeItem('selectedPosition');
    }
    // Dispatch custom event for other components to listen
    window.dispatchEvent(new CustomEvent('position-filter-changed', { detail: newValue }));
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
