<template>
    <v-form>
        <v-container>
            <v-row>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <v-text-field
                        variant="outlined"
                        density="compact"
                        label="First Name"
                        hide-details="auto"
                        v-model="form.first_name"
                        :error-messages="formErrors.first_name"
                    />
                </v-col>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <v-text-field
                        hide-details="auto"
                        variant="outlined"
                        density="compact"
                        label="Middle Name"
                        v-model="form.middle_name"
                        :error-messages="formErrors.middle_name"
                    />
                </v-col>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <v-text-field
                        hide-details="auto"
                        variant="outlined"
                        density="compact"
                        label="Last Name"
                        v-model="form.last_name"
                        :error-messages="formErrors.last_name"
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
                    />
                </v-col>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <v-text-field
                        hide-details="auto"
                        variant="outlined"
                        density="compact"
                        label="Email"
                        type="email"
                        v-model="form.email"
                        :error-messages="formErrors.email"
                    />
                </v-col>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <PositionSelect
                        label="Position"
                        v-model="form.position"
                        :positions="positions"
                        :error-messages="formErrors.position"
                        required
                    />
                </v-col>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <v-text-field
                        hide-details="auto"
                        variant="outlined"
                        density="compact"
                        label="Meal Entitlement"
                        v-model="form.meal_entitlement"
                        :error-messages="formErrors.meal_entitlement"
                    />
                </v-col>
                <v-col
                    v-if="isOjt"
                    cols="12"
                    md="6"
                    lg="6"
                    xl="6"
                    xxl="6"
                >
                    <v-text-field
                        hide-details="auto"
                        variant="outlined"
                        density="compact"
                        label="Start Date"
                        type="date"
                        v-model="form.start_date"
                        :error-messages="formErrors.start_date"
                    />
                </v-col>
                <v-col
                    v-if="isOjt"
                    cols="12"
                    md="6"
                    lg="6"
                    xl="6"
                    xxl="6"
                >
                    <v-text-field
                        hide-details="auto"
                        variant="outlined"
                        density="compact"
                        label="End Date"
                        type="date"
                        v-model="form.end_date"
                        :error-messages="formErrors.end_date"
                    />
                </v-col>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <DepartmentAutoComplete
                        label="Department"
                        v-model="form.department_id"
                        :departments="departments"
                        :error-messages="formErrors.department_id"
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
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <LocationSelect
                        label="Location"
                        v-model="form.location_id"
                        :locations="filteredLocations"
                        :error-messages="formErrors.location_id"
                    />
                </v-col>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <v-checkbox
                        hide-details="auto"
                        variant="outlined"
                        density="compact"
                        color="primary"
                        label="Is able to login?"
                        v-model="form.is_able_to_login"
                        :error-messages="formErrors.is_able_to_login"
                    />
                </v-col>
            </v-row>
        </v-container>
    </v-form>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import DepartmentAutoComplete from "./Components/DepartmentAutoComplete.vue";
import PositionSelect from "./Components/PositionSelect.vue";
import PropertySelect from "./Components/PropertySelect.vue";
import LocationSelect from "./Components/LocationSelect.vue";

// Define props

const props = defineProps({
    profile: Object,
    departments: Array,
    positions: Array,
    properties: Array,
    locations: Array,
    errors: Object,
    flash: Object,
    can: Array,
});

// Computed property to filter locations based on selected property
const filteredLocations = computed(() => {
    if (!form.value.property_id || !props.locations) {
        return props.locations || [];
    }
    return props.locations.filter(location => location.property_id === form.value.property_id);
});

const form = ref({
    id: null,
    first_name: null,
    middle_name: null,
    last_name: null,
    unique_identifier: null,
    username: null,
    email: null,
    position: null,
    meal_entitlement: null,
    start_date: null,
    end_date: null,
    property_id: null,
    location_id: null,
    is_able_to_login: null,
    department_id: null,
});

watch(
    () => props.profile,
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

// Watch for property changes and reset location selection
watch(
    () => form.value.property_id,
    (newPropertyId, oldPropertyId) => {
        // Reset location selection when property changes (but not on initial load)
        if (oldPropertyId !== undefined && oldPropertyId !== null && newPropertyId !== oldPropertyId) {
            form.value.location_id = null;
        }
    }
);

// Computed property to check if position is OJT
const isOjt = computed(() => form.value.position === 'OJT');

// Watch for position changes and clear dates when not OJT
watch(
    () => form.value.position,
    (newValue, oldValue) => {
        if (oldValue && newValue !== 'OJT') {
            form.value.start_date = null;
            form.value.end_date = null;
        }
    }
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

defineExpose({
    emitFormData,
});
</script>
