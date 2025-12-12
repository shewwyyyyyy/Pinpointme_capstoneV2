<template>
    <v-dialog transition="dialog-bottom-transition" fullscreen v-bind="$attrs">
        <v-card>
            <v-toolbar color="primary">
                <v-btn icon="mdi-close" @click="$emit('close')"></v-btn>

                <v-toolbar-title>{{ title }}</v-toolbar-title>

                <v-toolbar-items>
                    <v-btn
                        text="Save"
                        variant="text"
                        @click="$emit('submit')"
                    ></v-btn>
                </v-toolbar-items>
            </v-toolbar>

            <slot />
        </v-card>
    </v-dialog>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        default: "Dialog",
    },
});

defineEmits(["close", "submit"]);
</script>
