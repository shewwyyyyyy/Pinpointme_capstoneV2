<template>
    <v-date-input
        prepend-icon=""
        density="compact"
        hide-details="auto"
        class="my-1"
        v-bind="$attrs"
        :model-value="modelValue"
        @update:modelValue="onInputChange"
    >
        <slot />
    </v-date-input>
</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: [String, Number, Date],
        default: null,
        validator: (value) => {
            // Allow null
            if (value === null) return true;

            // Allow valid Date object
            if (value instanceof Date) return !isNaN(value);

            // Allow numbers
            if (typeof value === "number") return !isNaN(value);

            // Allow valid date strings
            const parsed = new Date(value);
            return !isNaN(parsed);
        },
    },
});

const emit = defineEmits(["update:modelValue"]);

const onInputChange = (value) => {
    if (value instanceof Date && !isNaN(value)) {
        emit("update:modelValue", value.toISOString().substring(0, 10));
    } else {
        const date = new Date(value);
        emit(
            "update:modelValue",
            !isNaN(date) ? date.toISOString().substring(0, 10) : null
        );
    }
};
</script>
