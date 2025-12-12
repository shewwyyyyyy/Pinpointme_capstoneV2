<template>
    <v-alert
        icon="mdi-alert"
        color="error"
        class="my-2"
        elevation="2"
        v-bind="$attrs"
    >
        <slot />
    </v-alert>
</template>

<script setup></script>
