<template>
    <v-empty-state
        :headline="`Whoops, ${code}`"
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

defineProps({
    code: {
        type: Number,
        default: 500,
    },
    title: {
        type: String,
        default: "Server Error",
    },
    text: {
        type: String,
        default: "Something went wrong. Please try again later.",
    },
});

const theme = useTheme();
const isDarkMode = computed(() => theme.global.name.value === "dark");

const computedImage = computed(() => {
    const defaultErrors = [401, 404, 500];
    if (defaultErrors.includes(props.code)) {
        return isDarkMode.value
            ? `/images/errors/error_${props.code}_dark.svg`
            : `/images/errors/error_${props.code}_light.svg`;
    }

    return isDarkMode.value
        ? "/images/errors/error_500_dark.svg"
        : "/images/errors/error_500_light.svg";
});
</script>
