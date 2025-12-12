<template>
    <v-container fluid class="pa-4 py-2 px-0">
        <!-- Loading Overlay -->
        <v-overlay :model-value="isProcessing" class="align-center justify-center" persistent>
            <v-progress-circular color="primary" size="64" indeterminate />
            <div class="text-center mt-4">
                <v-card class="pa-4" elevation="4">
                    <v-card-text>Processing scan...</v-card-text>
                </v-card>
            </div>
        </v-overlay>

        <v-row>
            <!-- Camera and Recent Scanned -->
            <v-col cols="12" md="6" class="pr-md-2 d-flex flex-column">
                
                <CardQrScanner @scanned="handleScanned" @success="handleScanSuccess" />

                <v-card
                    class="mt-2"
                    elevation="2"
                    rounded="lg"
                >
                    <v-card-title class="text-h6 font-weight-bold pa-2">
                        Recent Scanned:
                    </v-card-title>
                    <v-card-text class="pa-2" style="height: 550px; overflow: hidden;">
                        <!-- Loading state -->
                        <v-progress-circular
                            v-if="loading"
                            indeterminate
                            color="primary"
                            class="mx-auto d-block mt-4"
                        />
                        
                        <!-- Empty state -->
                        <v-alert
                            v-else-if="recentItems.length === 0"
                            type="info"
                            variant="tonal"
                            class="mt-2"
                        >
                            No recent scans found
                        </v-alert>
                        
                        <template v-else>
                            <CardLastScanned
                                v-for="(item, index) in recentItems"
                                :key="`recent-${index}`"
                                :item="item"
                                :index="index"
                                @handleQRClick="handleQRClick"
                            />
                        </template>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Scan History with Pagination - Only visible on desktop -->
            <v-col v-if="mdAndUp" cols="12" md="6" class="pl-md-2 d-flex flex-column">
                <v-card class="d-flex flex-column" elevation="2" rounded="lg" style="flex: 1;">
                    <v-card-title class="text-h6 font-weight-bold pa-2 ml-2">
                        Scan History:
                    </v-card-title>
                    <v-card-text class="pa-2 flex-grow-1 d-flex flex-column" style="overflow: hidden;">
                        <!-- Loading state -->
                        <v-progress-circular
                            v-if="loading"
                            indeterminate
                            color="primary"
                            class="mx-auto d-block mt-4"
                        />
                        
                        <!-- Empty state -->
                        <v-alert
                            v-else-if="paginatedItems.length === 0"
                            type="info"
                            variant="tonal"
                            class="mt-2"
                        >
                            No scan history found
                        </v-alert>
                        
                        <!-- Scan items - Paginated -->
                        <div v-else class="flex-grow-1">
                            <CardLastScanned
                                v-for="(item, index) in paginatedItems"
                                :key="`history-${index}`"
                                :item="item"
                                :index="index"
                                @handleQRClick="handleQRClick"
                            />
                        </div>
                    </v-card-text>

                    <!-- Pagination - Always visible for navigation -->
                    <v-card-actions
                        class="justify-center pa-2 mt-auto"
                        v-if="hasMultiplePages"
                    >
                        <v-pagination
                            v-model="currentPage"
                            :length="totalPages"
                            :total-visible="5"
                            color="primary"
                            size="small"
                        />
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { router } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { useDisplay } from 'vuetify';
import axiosInstance from "@/Utilities/axios";
import CardQrScanner from "./Components/CardQrScanner.vue";
import CardLastScanned from "./Components/CardLastScanned.vue";

const { mdAndUp } = useDisplay();

// Position filter state
const selectedPosition = ref(localStorage.getItem('selectedPosition') || null);

// Listen for position filter changes from navbar
const handlePositionChange = (event) => {
    selectedPosition.value = event.detail;
    fetchScanHistory(); // Refetch when position changes
};

onMounted(() => {
    window.addEventListener('position-filter-changed', handlePositionChange);
});

onUnmounted(() => {
    window.removeEventListener('position-filter-changed', handlePositionChange);
});

const form = ref({
    unique_identifier: null,
});

const isProcessing = ref(false);

const handleScanned = (result) => {
    form.value.unique_identifier = result;
};

const handleScanSuccess = () => {
    // Refresh scan history when scan is successful
    fetchScanHistory();
};

watch(
    form,
    (value) => {
        if (value.unique_identifier !== null) {
            console.log("Scanned from CardQrScanner:", value.unique_identifier);
            handleFormSubmission();
            form.value.unique_identifier = null;
        }
    },
    {
        deep: true,
    }
);

const handleFormSubmission = async () => {
    router.post("/scans", { 
        ...form.value,
        selected_position: selectedPosition.value 
    }, {
        onSuccess: ({ props }) => {
            // Automatically reload scan history after successful scan
            fetchScanHistory();
        },
        onError: () => {
            //
        },
        onBefore: () => {
            isProcessing.value = true;
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};

 
const currentPage = ref(1);
const perPage = ref(8);  

// Scan history data from API
const scannedItems = ref([]);
const loading = ref(false);

// Fetch scan history from API
const fetchScanHistory = async () => {
    try {
        loading.value = true;
        const params = {
            per_page: 50, // Get recent 50 items
            sort_by: 'scanned_at',
            sort_direction: 'desc'
        };
        
        // Add position filter if selected
        if (selectedPosition.value) {
            params.position = selectedPosition.value;
        }
        
        const response = await axiosInstance.get('/scan-histories', { params });
        
        // Transform API data to match CardLastScanned format
        if (response.data && response.data.data) {
            scannedItems.value = response.data.data.map(item => {
                const profile = item.profile || {};
                const firstName = profile.first_name || '';
                const middleName = profile.middle_name || '';
                const lastName = profile.last_name || '';
                
                return {
                    name: `${lastName}, ${firstName} ${middleName}`.trim(),
                    mealType: item.meal_schedule || 'N/A',
                    time: formatDateTime(item.scanned_at),
                    raw: item // Keep raw data for reference
                };
            });
        }
    } catch (error) {
        console.error('Error fetching scan history:', error);
    } finally {
        loading.value = false;
    }
};

// Format date time to Philippine Time
const formatDateTime = (dateTime) => {
    if (!dateTime) return '';
    const date = new Date(dateTime);
    return date.toLocaleString('en-PH', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
        timeZone: 'Asia/Manila'
    });
};

// Fetch data on component mount
onMounted(() => {
    window.addEventListener('position-filter-changed', handlePositionChange);
    fetchScanHistory();
});

onUnmounted(() => {
    window.removeEventListener('position-filter-changed', handlePositionChange);
});

 
const recentItems = computed(() => scannedItems.value.slice(0, 5));

const paginatedItems = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return scannedItems.value.slice(start, start + perPage.value);
});

const totalPages = computed(() => Math.ceil(scannedItems.value.length / perPage.value));
const hasMultiplePages = computed(() => totalPages.value > 1);

 
const addNewScan = (item) => scannedItems.value.unshift(item);
const handleQRClick = (item, index) => console.log('QR clicked:', { item, index });
</script>
