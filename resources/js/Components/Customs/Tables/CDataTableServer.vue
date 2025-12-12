<template>
    <v-data-table-server
        v-bind="$attrs"
        :items="items"
        :item-value="itemValue"
        :items-length="total"
        :items-per-page="perPage"
        :items-per-page-options="itemsPerPageOption"
        :hover="hover"
        :density="density"
        :loading="isLoading"
        @update:options="handleTableFilter"
    >
        <template #item.num="{ index }">
            {{ indexCount + index }}
        </template>

        <!-- Forward Vuetify's named slots -->
        <template
            v-for="(_, name) in $slots"
            :key="name"
            v-slot:[name]="slotProps"
        >
            <slot :name="name" v-bind="slotProps" />
        </template>
    </v-data-table-server>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import axiosInstance from "@/Utilities/axios";

const props = defineProps({
    module: {
        type: String,
        required: true,
    },
    hover: {
        type: Boolean,
        default: true,
    },
    itemValue: {
        type: String,
        default: "id",
    },
    itemsPerPageOption: {
        type: Array,
        default: () => [10, 25, 50, 100],
    },
    density: {
        type: String,
        default: "comfortable",
    },
});

// Define state variables
const items = ref([]);
const currentPage = ref(1);
const lastPage = ref(1);
const perPage = ref(10);
const sortBy = ref(null);
const sortDirection = ref(null);
const total = ref(0);
const isLoading = ref(true);

//compute row count of an item in the table
const indexCount = computed(() => {
    return 1 + (perPage.value * currentPage.value - perPage.value);
});

const loadData = async (setFilters = {}) => {
    try {
        isLoading.value = true; // Set loading to true before making the request

        const searchParams = new URLSearchParams();

        const filters = {
            current_page: currentPage.value ?? 1,
            per_page: perPage.value ?? 10,
            sort_by: sortBy.value ?? "id",
            sort_direction: sortDirection.value ?? "desc",
        };

        // Add default filters
        Object.entries(filters).forEach(([key, value]) => {
            searchParams.append(key, value ?? "");
        });

        // Add extra filters if passed
        if (
            setFilters &&
            typeof setFilters === "object" &&
            Object.keys(setFilters).length > 0
        ) {
            Object.entries(setFilters).forEach(([key, value]) => {
                searchParams.append(key, value ?? "");
            });
        }

        if (props.module && props.module.length > 0) {
            const response = await axiosInstance.get(
                `/${props.module}?` + searchParams.toString()
            );

            const { data } = response.data; // Extract data directly from the response
            items.value = data; // Set items

            const {
                current_page,
                last_page,
                per_page,
                sort_by,
                sort_direction,
                total: totalCount,
            } = response.data;

            // Set pagination and sorting data
            currentPage.value = current_page;
            lastPage.value = last_page;
            perPage.value = per_page;
            sortBy.value = sort_by;
            sortDirection.value = sort_direction;
            total.value = totalCount;
        }
    } catch (error) {
        console.error("Error fetching data:", error);
        // Optionally, you could set an error state here
    } finally {
        // Always set loading to false, regardless of success or failure
        isLoading.value = false;
    }
};

const handleTableFilter = async ({
    page,
    itemsPerPage,
    sortBy: sortParams,
}) => {
    currentPage.value = page;
    perPage.value = itemsPerPage;

    // get the first value
    if (sortParams[0]) {
        sortBy.value = sortParams[0].key;
        sortDirection.value = sortParams[0].order;
    } else {
        // return to default
        sortBy.value = null;
        sortDirection.value = null;
    }

    isLoading.value = true;

    await loadData();
};

defineExpose({
    loadData,
});
</script>
