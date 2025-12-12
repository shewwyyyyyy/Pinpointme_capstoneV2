<template>
    <v-fab v-bind="$attrs" icon="mdi-home" rounded="pill">
        <!-- Forward Vuetify's named slots -->
        <template
            v-for="(_, name) in $slots"
            :key="name"
            v-slot:[name]="slotProps"
        >
            <slot :name="name" v-bind="slotProps" />
        </template>
    </v-fab>
</template>

<script setup></script>
