<template>
    <v-menu location="bottom">
        <template v-slot:activator="{ props }">
            <v-btn
                icon="mdi-cog"
                v-bind="props"
                v-tooltip:bottom="'Settings'"
                class="mr-2 ml-3"
            >
            </v-btn>
        </template>

        <v-list>
            <!-- <c-list-item
                v-for="(item, index) in items"
                :key="index"
                :value="index"
            >
                <c-list-item-title>{{ item.title }}</c-list-item-title>
            </c-list-item> -->

            <v-list-item
                v-if="showChangePassword"
                @click="handleChangePassword"
                class="settings-hover"
            >
                <template v-slot:prepend>
                    <v-icon
                        size="small"
                        :color="
                            theme.global.current.value.dark
                                ? 'grey-lighten-2'
                                : 'primary'
                        "
                        icon="mdi-lock-reset"
                    />
                </template>
                Change Password
            </v-list-item>

            <v-list-item @click="handleLogout" class="settings-hover">
                <template v-slot:prepend>
                    <v-icon
                        size="small"
                        color="error"
                        icon="mdi-logout"
                    ></v-icon>
                </template>
                Logout
            </v-list-item>
        </v-list>
    </v-menu>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

import { useTheme } from "vuetify";

const theme = useTheme();

defineProps({
    showChangePassword: {
        type: Boolean,
        default: true,
    },
    errors: Object,
    flash: Object,
    can: Array,
});

// const items = [{ title: "Change Password" }];

const changePasswordRef = ref(null);
const handleChangePassword = () => {
    if (!changePasswordRef.value) {
        console.error("Change Password component is not available.");
        return;
    }

    changePasswordRef.value.toggleDialog();
};

const handleLogout = () => {
    router.post(
        "/logout",
        {},
        {
            onFinish: () => {
                localStorage.removeItem("token");
            },
        }
    );
};
</script>
