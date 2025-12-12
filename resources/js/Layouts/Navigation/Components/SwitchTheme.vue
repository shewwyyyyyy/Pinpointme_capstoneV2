<template>
    <v-switch
        class="d-flex justify-center"
        v-model="isDarkMode"
        :color="isDarkMode ? 'white' : 'primary'"
        true-icon="mdi-weather-night"
        false-icon="mdi-weather-sunny"
        v-tooltip:start="isDarkMode ? 'Light Mode' : 'Dark Mode'"
    />
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import { useTheme } from "vuetify";

const theme = useTheme();
const isDarkMode = ref(false);

// Sync Vuetify theme and localStorage based on isDarkMode
watch(isDarkMode, (enabled) => {
    const newTheme = enabled ? "dark" : "light";
    //theme.global.name.value = newTheme;
    theme.change(newTheme);
    localStorage.setItem("theme", newTheme);
});

// On mount, read theme from localStorage and apply
onMounted(() => {
    const savedTheme = localStorage.getItem("theme") || "light";
    //theme.global.name.value = savedTheme;
    theme.change(savedTheme);
    isDarkMode.value = savedTheme === "dark";
});
</script>
