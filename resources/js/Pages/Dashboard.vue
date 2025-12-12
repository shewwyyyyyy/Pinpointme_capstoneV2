<template>
    <v-app class="bg-user-gradient-light">
        <v-app-bar color="primary">
            <v-app-bar-title>PinPointMe Admin</v-app-bar-title>
            <v-spacer />
            <v-btn icon @click="logout">
                <v-icon>mdi-logout</v-icon>
            </v-btn>
        </v-app-bar>

        <v-main>
            <v-container class="py-8">
                <h1 class="text-h4 mb-6">Dashboard</h1>

                <v-row>
                    <v-col cols="12" md="4">
                        <v-card color="primary" variant="flat" class="text-white">
                            <v-card-item>
                                <v-card-title class="text-h4">{{ stats.buildings }}</v-card-title>
                                <v-card-subtitle class="text-white">Buildings</v-card-subtitle>
                            </v-card-item>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-card color="success" variant="flat" class="text-white">
                            <v-card-item>
                                <v-card-title class="text-h4">{{ stats.rescues }}</v-card-title>
                                <v-card-subtitle class="text-white">Total Rescues</v-card-subtitle>
                            </v-card-item>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-card color="warning" variant="flat" class="text-white">
                            <v-card-item>
                                <v-card-title class="text-h4">{{ stats.pending }}</v-card-title>
                                <v-card-subtitle class="text-white">Pending Requests</v-card-subtitle>
                            </v-card-item>
                        </v-card>
                    </v-col>
                </v-row>

                <v-row class="mt-6">
                    <v-col cols="12">
                        <v-card>
                            <v-card-title>Quick Links</v-card-title>
                            <v-card-text>
                                <v-btn color="primary" class="mr-2" href="/admin/dashboard">Admin Dashboard</v-btn>
                                <v-btn color="primary" class="mr-2" href="/admin/users">Manage Users</v-btn>
                                <v-btn color="primary" class="mr-2" href="/admin/rescuers">Manage Rescuers</v-btn>
                                <v-btn color="primary" class="mr-2" href="/admin/buildings">Manage Buildings</v-btn>
                                <v-btn color="primary" class="mr-2" href="/admin/reports">Reports</v-btn>
                                <v-btn color="primary" href="/audit-trails">Audit Trails</v-btn>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>
        </v-main>
    </v-app>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const stats = ref({
    buildings: 0,
    rescues: 0,
    pending: 0,
});

const logout = () => {
    router.post('/logout');
};

onMounted(async () => {
    // Fetch stats if needed
});
</script>
