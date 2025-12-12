<template>
    <v-fab
        v-bind="$attrs"
        :color="toggleFab ? 'primary' : '#000020'"
        size="x-large"
        location="right bottom"
        icon
        rounded="circle"
        style="position: fixed; bottom: 80px; right: 25px; z-index: 1000"
        @click="toggleFab = !toggleFab"
    >
        <v-icon>{{ toggleFab ? "mdi-close" : "mdi-cog" }}</v-icon>

        <v-speed-dial
            v-model="toggleFab"
            location="top center"
            transition="slide-y-reverse-transition"
            activator="parent"
        >
            <v-btn
                v-for="(link, index) in buttonLinks"
                :key="index"
                :color="link.color || 'primary'"
                icon
                @click="link.action"
            >
                <v-icon size="24">{{ link.icon }}</v-icon>
            </v-btn>
            <template
                v-for="(_, name) in $slots"
                :key="name"
                v-slot:[name]="slotProps"
            >
                <slot :name="name" v-bind="slotProps" />
            </template>
        </v-speed-dial>
    </v-fab>
</template>

<script setup>
import { ref } from "vue";
import { VFab, VSpeedDial, VBtn, VIcon } from "vuetify/components";

const props = defineProps({
    buttonLinks: {
        type: Array,
        default: () => [],
    },
});

const toggleFab = ref(false);
</script>
