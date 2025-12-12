<template>
    <v-checkbox color="primary" v-bind="$attrs" hide-details>
        <!-- Forward Vuetify's named slots -->
        <template
            v-for="(_, name) in $slots"
            :key="name"
            v-slot:[name]="slotProps"
        >
            <slot :name="name" v-bind="slotProps" />
        </template>
    </v-checkbox>
</template>

<script setup></script>
