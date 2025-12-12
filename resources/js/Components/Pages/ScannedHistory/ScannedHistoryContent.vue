<template>
    <v-container>
        <v-row class="mb-4">
            <!-- Search Bar -->
            <v-col cols="12" md="8">
                <v-text-field
                    v-model="search"
                    label="Search Employee"
                    prepend-inner-icon="mdi-magnify"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                ></v-text-field>
            </v-col>

            <!-- Filter by Date Button -->
            <v-col cols="12" md="4" class="d-flex align-center">
                <v-btn
                    color="primary"
                    class="text-white"
                    prepend-icon="mdi-calendar"
                    @click="filterDialog = true"
                >
                    Filter by Date
                </v-btn>
            </v-col>

            <!-- Date Picker Dialog -->
            <v-dialog v-model="filterDialog" max-width="400px">
                <v-card>
                    <v-card-title class="text-h6"
                        >Select Date & Time</v-card-title
                    >
                    <v-card-text>
                        <v-date-picker
                            v-model="selectedDate"
                            class="mb-4"
                        ></v-date-picker>
                        <v-time-picker v-model="selectedTime"></v-time-picker>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn text @click="filterDialog = false">Cancel</v-btn>
                        <v-btn color="success" text @click="applyFilter">
                            Apply
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-row>

        <v-row>
            <v-col cols="12" md="12" lg="6" xl="6" xxl="6">
                <v-card class="d-flex align-center pa-4">
                    <v-card-title class="pa-0 d-flex flex-column">
                        <span class="text-h6">QR Scanned</span>
                        <span class="text-body-2">Employee: Allan</span>
                        <span class="text-body-2">2025-09-24 · 3:22 PM</span>
                    </v-card-title>

                    <v-spacer></v-spacer>

                    <v-btn icon color="primary">
                        <v-icon>mdi-qrcode</v-icon>
                    </v-btn>
                </v-card>
            </v-col>
            <v-col cols="12" md="12" lg="6" xl="6" xxl="6">
                <v-card class="d-flex align-center pa-4">
                    <v-card-title class="pa-0 d-flex flex-column">
                        <span class="text-h6">QR Scanned</span>
                        <span class="text-body-2">Employee: Yor</span>
                        <span class="text-body-2">2025-09-24 · 3:22 PM</span>
                    </v-card-title>

                    <v-spacer></v-spacer>

                    <v-btn icon color="primary">
                        <v-icon>mdi-qrcode</v-icon>
                    </v-btn>
                </v-card>
            </v-col>
            <v-col cols="12" md="12" lg="6" xl="6" xxl="6">
                <v-card class="d-flex align-center pa-4">
                    <v-card-title class="pa-0 d-flex flex-column">
                        <span class="text-h6">QR Scanned</span>
                        <span class="text-body-2">Employee: Loid</span>
                        <span class="text-body-2">2025-09-24 · 3:22 PM</span>
                    </v-card-title>

                    <v-spacer></v-spacer>

                    <v-btn icon color="primary">
                        <v-icon>mdi-qrcode</v-icon>
                    </v-btn>
                </v-card>
            </v-col>
            <v-col cols="12" md="12" lg="6" xl="6" xxl="6">
                <v-card class="d-flex align-center pa-4">
                    <v-card-title class="pa-0 d-flex flex-column">
                        <span class="text-h6">QR Scanned</span>
                        <span class="text-body-2">Employee: Anya</span>
                        <span class="text-body-2">2025-09-24 · 3:22 PM</span>
                    </v-card-title>

                    <v-spacer></v-spacer>

                    <v-btn icon color="primary">
                        <v-icon>mdi-qrcode</v-icon>
                    </v-btn>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script setup>
const props = defineProps({
    errors: Object,
    flash: Object,
    can: Array,
});

import { ref } from "vue";

const scanned = ref([
    {
        name: "Employee, Allan",
        code: "dsa-zxc-456",
        time: "2024-07-16 9:29:22 am",
    },
]);

const search = ref('')
const filterDialog = ref(false)
const selectedDate = ref(null)
const selectedTime = ref(null)

function applyFilter() {
  console.log('Filter by:', selectedDate.value, selectedTime.value)
  filterDialog.value = false
}
</script>