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
                        :positions="computedFilterPositions"
                        :error-messages="formErrors.position"
                    />
                </v-col>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <DepartmentAutoComplete
                        label="Department"
                        v-model="form.department_id"
                        :departments="departments"
                        :error-messages="formErrors.department_id"
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

const form = ref({
    id: null,
    first_name: null,
    middle_name: null,
    last_name: null,
    unique_identifier: null,
    username: null,
    email: null,
    position: null,
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

const computedFilterPositions = computed(() => {
    return props.positions.filter((position) => (position !== "Manager"));
});

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
