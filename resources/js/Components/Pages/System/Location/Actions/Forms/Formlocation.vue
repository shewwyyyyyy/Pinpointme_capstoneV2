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
                    />
                </v-col>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <PropertySelect
                        label="Property"
                        v-model="form.property_id"
                        :properties="properties"
                        :error-messages="formErrors.property_id"
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
                        placeholder="Enter Location description..."
                    />
                </v-col>
            </v-row>
        </v-container>
    </v-form>
</template>

<script setup>
import { ref, watch } from "vue";
import PropertySelect from "../../../Profile/Actions/Forms/Components/PropertySelect.vue";

const props = defineProps({
    location: Object,
    properties: Array,
    errors: Object,
    flash: Object,
    can: Array,
});

const form = ref({
    id: null,
    name: null,
    code: null,
    description: null,
    property_id: null,
});

// Remove API fetching - properties will be passed from parent

// Watch for location changes and populate form
watch(
    () => props.location,
    (newVal) => {
        if (newVal && Object.keys(newVal).length > 0) {
            Object.keys(form.value).forEach((key) => {
                const isNull = form.value[key] === null;
                const isEmptyArray =
                    Array.isArray(form.value[key]) &&
                    form.value[key].length === 0;

                // Only update if the current value is null or an empty array
                if (isNull || isEmptyArray) {
                    if (newVal.hasOwnProperty(key)) {
                        form.value[key] = newVal[key];
                    }
                }
            });
        }
    },
    { immediate: true, deep: true }
);

// Properties are now passed from parent, so no additional watching needed

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

defineExpose({
    emitFormData,
});
</script>