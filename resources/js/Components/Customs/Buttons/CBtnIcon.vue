<template>
    <v-btn
        class="ma-1"
        :icon="computedIcon"
        variant="text"
        density="compact"
        v-tooltip:bottom="computedDescription"
        v-bind="$attrs"
    >
        <!-- Forward Vuetify's named slots -->
        <template
            v-for="(_, name) in $slots"
            :key="name"
            v-slot:[name]="slotProps"
        >
            <slot :name="name" v-bind="slotProps" />
        </template>
    </v-btn>
</template>

<script setup>
import { computed } from "vue";
const props = defineProps({
    icon: String,
    description: String,
});

const computedIcon = computed(() => {
    return props.icon || "mdi-home";
});

const computedDescription = computed(() => {
    return props.description || "Home";
});
</script>
