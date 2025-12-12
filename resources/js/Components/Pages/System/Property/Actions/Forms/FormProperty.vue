<template>
    <v-form>
        <v-container>
            <v-row>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <v-text-field
                        variant="outlined"
                        density="compact"
                        label="Name"
                        hide-details="auto"
                        v-model="form.name"
                        :error-messages="formErrors.name"
                        required
                    />
                </v-col>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <v-text-field
                        hide-details="auto"
                        variant="outlined"
                        density="compact"
                        label="Code"
                        v-model="form.code"
                        :error-messages="formErrors.code"
                        required
                        placeholder="e.g., IT, HR, FIN"
                    />
                </v-col>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <v-text-field
                        hide-details="auto"
                        variant="outlined"
                        density="compact"
                        label="Username"
                        v-model="form.username"
                        :error-messages="formErrors.username"
                        placeholder="Enter username"
                    />
                </v-col>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <v-text-field
                        hide-details="auto"
                        variant="outlined"
                        density="compact"
                        label="Unique Identifier"
                        v-model="form.unique_identifier"
                        :error-messages="formErrors.unique_identifier"
                        placeholder="Enter unique identifier"
                    />
                </v-col>
                <v-col cols="12" md="12" lg="12" xl="12" xxl="12">
                    <v-textarea
                        hide-details="auto"
                        variant="outlined"
                        density="compact"
                        label="Description"
                        v-model="form.description"
                        :error-messages="formErrors.description"
                        rows="3"
                        placeholder="Enter Property description..."
                    />
                </v-col>
            </v-row>

            <TableMealSchedule
                :form="form"
                :existingSchedule="form.schedule"
                @update:schedule="updateSchedule"
            />
        </v-container>
    </v-form>
</template>

<script setup>
import { ref, watch } from "vue";
import TableMealSchedule from "../../Tables/TableMealSchedule.vue";

const props = defineProps({
    property: Object,
    errors: Object,
    flash: Object,
    can: Array,
});

const form = ref({
    id: null,
    name: null,
    code: null,
    description: null,
    username: null,
    unique_identifier: null,
    schedule: null,
});

watch(
    () => props.property,
    (newVal) => {
        if (newVal && Object.keys(newVal).length > 0) {
            // Update all form fields from property data
            form.value.id = newVal.id ?? form.value.id;
            form.value.name = newVal.name ?? form.value.name;
            form.value.code = newVal.code ?? form.value.code;
            form.value.description = newVal.description ?? form.value.description;
            form.value.username = newVal.username ?? form.value.username;
            form.value.unique_identifier = newVal.unique_identifier ?? form.value.unique_identifier;
            form.value.schedule = newVal.schedule ?? form.value.schedule;
        }
    },
    { immediate: true, deep: true }
);

// set error start
const formErrors = ref({});
watch(
    () => props.errors,
    (newValue) => {
        formErrors.value = Object.assign({}, newValue);
    },
    { deep: true }
);
// set error end

const emits = defineEmits(["formValues"]);

const emitFormData = () => {
    emits("formValues", form.value);
};

const updateSchedule = (scheduleData) => {
    form.value.schedule = scheduleData;
};

defineExpose({
    emitFormData,
});
</script>
