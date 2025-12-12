<template>
    <Head :title="`Error | ${code}`" />
    <blank-layout
        :errors="errors"
        :flash="flash"
        :code="code"
        :hasChangPassword="false"
    >
        <not-found v-if="code === 404" :text="message" />
        <unauthorized v-else-if="code === 401" :text="message" />
        <forbidden v-else-if="code === 403" :text="message" />
        <server-error v-else-if="code === 500" :text="message" />
        <server-error v-else :text="message" />
    </blank-layout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3";

import BlankLayout from "../Layouts/BlankLayout.vue";

import NotFound from "../Components/Errors/NotFound.vue";
import Unauthorized from "../Components/Errors/Unauthorized.vue";
import ServerError from "../Components/Errors/ServerError.vue";
import Forbidden from "../Components/Errors/Forbidden.vue";

const props = defineProps({
    code: Number,
    message: {
        type: String,
        default: "Something went wrong.",
    },
    errors: Object,
    flash: Object,
});
</script>
