<!-- NOTE:to get only the first & last date, use emit("dateRangeValues") -->
<template>
    <v-date-input
        prepend-icon=""
        density="compact"
        hide-details="auto"
        class="my-1"
        multiple="range"
        v-bind="$attrs"
        :model-value="inputRange"
        input-format="yyyy-mm-dd"
        @update:modelValue="onInputChange"
    >
        <slot />
    </v-date-input>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
        validator: (value) => {
            if (value === null) return true;
            if (!Array.isArray(value)) return false;
            return value.every((v) => !isNaN(new Date(v)));
        },
    },
});

const emit = defineEmits(["update:modelValue", "dateRangeValues"]);

// Local state for date input (must only hold [start, end])
const inputRange = ref([]);

// Utility: Generate full date range with preserved boundaries
function generateDateRangeWithBoundaries(startISO, endISO) {
    const startDate = new Date(startISO);
    const endDate = new Date(endISO);
    if (startDate > endDate) return [];

    const dates = [];

    // Push start date exactly
    dates.push(startDate.toISOString());

    // Intermediate days at 16:00:00Z
    const current = new Date(startDate);
    current.setUTCHours(16, 0, 0, 0);
    if (startDate.getUTCHours() >= 16) {
        current.setUTCDate(current.getUTCDate() + 1);
    }

    while (current < endDate) {
        if (current > startDate && current < endDate) {
            dates.push(current.toISOString());
        }
        current.setUTCDate(current.getUTCDate() + 1);
    }

    // Push end date exactly
    dates.push(endDate.toISOString());

    return dates;
}

const formatYMD = (date) => {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
};

// User interaction
const onInputChange = (value) => {
    if (Array.isArray(value) && value.length >= 2) {
        const cleaned = value
            .map((v) => new Date(v))
            .filter((d) => !isNaN(d))
            .sort((a, b) => a - b);

        if (cleaned.length >= 2) {
            const start = cleaned[0];
            const end = cleaned[cleaned.length - 1];

            const fullRange = generateDateRangeWithBoundaries(start, end);

            //generate dates
            inputRange.value = generateDateRangeWithBoundaries(start, end);

            emit("update:modelValue", [start, end]); // Only emit [start, end]

            // Emit only the first and last date from the generated range
            const result = [
                formatYMD(fullRange[0]),
                formatYMD(fullRange[fullRange.length - 1]),
            ];

            emit("dateRangeValues", result);

            return;
        }
    }

    // Handle fallback: empty or single-date selections
    inputRange.value = value;
    emit("update:modelValue", value);
    emit("dateRangeValues", []);
};

// Sync props → input
watch(
    () => props.modelValue,
    (newVal) => {
        inputRange.value = newVal || [];

        if (Array.isArray(newVal) && newVal.length > 0) {
            //generate dates
            inputRange.value = generateDateRangeWithBoundaries(
                newVal[0],
                newVal[newVal.length - 1]
            );

            const cleaned = newVal
                .map((v) => new Date(v))
                .filter((d) => !isNaN(d))
                .sort((a, b) => a - b);

            if (cleaned.length >= 2) {
                const fullRange = generateDateRangeWithBoundaries(
                    cleaned[0],
                    cleaned[cleaned.length - 1]
                );

                emit("dateRangeValues", [
                    formatYMD(fullRange[0]),
                    formatYMD(fullRange[fullRange.length - 1]),
                ]);
                return;
            }
        }

        emit("dateRangeValues", []);
    },
    { immediate: true }
);
</script>
