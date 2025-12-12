<template>
    <v-empty-state
        headline="Whoops, 500"
        :title="title"
        :text="text"
        :image="computedImage"
    >
        <Link href="/">
            <c-btn prepend-icon="mdi-home">Home</c-btn>
        </Link>
    </v-empty-state>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";
import CBtn from "../Customs/Buttons/CBtn.vue";
import { useTheme } from "vuetify";
import { computed } from "vue";

defineProps({
    title: {
        type: String,
        default: "Server Error",
    },
    text: {
        type: String,
        default: "Contact the administrator or try again later.",
    },
});

const theme = useTheme();
const isDarkMode = computed(() => theme.global.name.value === "dark");

const computedImage = computed(() => {
    return isDarkMode.value
        ? "/images/errors/error_500_dark.svg"
        : "/images/errors/error_500_light.svg";
});
</script>
