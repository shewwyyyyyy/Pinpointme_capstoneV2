<template>
    <v-empty-state
        headline="Whoops, 404"
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
        default: "Not Found",
    },
    text: {
        type: String,
        default: "Attachment not found",
    },
});

const theme = useTheme();
const isDarkMode = computed(() => theme.global.name.value === "dark");

const computedImage = computed(() => {
    return isDarkMode.value
        ? "/images/errors/error_404_dark.svg"
        : "/images/errors/error_404_light.svg";
});
</script>
