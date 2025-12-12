<template>
    <c-text-field
        :type="visible ? 'text' : 'password'"
        :append-inner-icon="visible ? 'mdi-eye-off' : 'mdi-eye'"
        @click:append-inner="visible = !visible"
        label="Password"
        v-bind="$attrs"
    />
</template>

<script setup>
import CTextField from "@/Components/Customs/Inputs/CTextField.vue";

import { ref } from "vue";

const visible = ref(false);
</script>
