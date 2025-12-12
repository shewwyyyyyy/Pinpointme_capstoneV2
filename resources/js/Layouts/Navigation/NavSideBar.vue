<template>
    <v-navigation-drawer v-model="drawer" app color="primary">
        <v-sheet class="pa-4 d-flex flex-column align-center" color="#000020">
            <v-avatar class="mb-4" color="blue-grey-lighten-2" size="53">
                <v-icon
                    :icon="profileIcon"
                    size="x-large"
                    color="white"
                ></v-icon>
            </v-avatar>
            <div class="user-name">{{ fullName }}</div>
            <div class="user-email">{{ emailAddress }}</div>
        </v-sheet>

        <v-divider></v-divider>

        <v-list nav>
            <Link class="no-underline-v-list-item" href="/">
                <v-list-item
                    link
                    class="sidebar-hover"
                    :class="{ selected: page.url === '/' }"
                    @click="router.visit('/')"
                >
                    <template #prepend>
                        <v-icon
                            icon="mdi-home"
                            color="white"
                            size="default"
                        ></v-icon>
                    </template>
                    <v-list-item-title class="text-body-2">
                        Dashboard
                    </v-list-item-title>
                </v-list-item>
            </Link>

            <Link
                v-for="link in moduleLinks"
                :key="link.module"
                class="no-underline-v-list-item"
                :href="`/${moduleLink(link.module)}`"
            >
                <v-list-item
                    link
                    class="sidebar-hover"
                    :class="{
                        selected: page.url === `/${moduleLink(link.module)}`,
                    }"
                    @click="router.visit(`/${moduleLink(link.module)}`)"
                >
                    <template #prepend>
                        <v-icon
                            :icon="link.icon"
                            color="white"
                            size="default"
                        ></v-icon>
                    </template>
                    <v-list-item-title class="text-body-2">{{
                        moduleName(link.module)
                    }}</v-list-item-title>
                </v-list-item>
            </Link>

            <v-list-group>
                <template v-slot:activator="{ props }">
                    <v-list-item
                        v-bind="props"
                        prepend-icon="mdi-chart-bar"
                        title="Reports"
                        class="sidebar-hover"
                    ></v-list-item>
                </template>

                <Link
                    v-for="link in reportLinks"
                    :key="link.module"
                    class="no-underline-v-list-item"
                    :href="`/${moduleLink(link.module)}`"
                >
                    <v-list-item
                        class="sidebar-hover"
                        :class="{
                            selected:
                                page.url === `/${moduleLink(link.module)}`,
                        }"
                        @click="router.visit(`/${moduleLink(link.module)}`)"
                    >
                        <v-list-item-title class="text-body-2">{{
                            moduleName(link.module)
                        }}</v-list-item-title>
                    </v-list-item>
                </Link>
            </v-list-group>

            <v-list-group>
                <template v-slot:activator="{ props }">
                    <v-list-item
                        v-bind="props"
                        prepend-icon="mdi-tools"
                        title="Systems"
                        class="sidebar-hover"
                    ></v-list-item>
                </template>

                <Link
                    v-for="link in systemLinks"
                    :key="link.module"
                    class="no-underline-v-list-item"
                    :href="`/${moduleLink(link.module)}`"
                >
                    <v-list-item
                        v-bind="props"
                        class="sidebar-hover"
                        :class="{
                            selected:
                                page.url === `/${moduleLink(link.module)}`,
                        }"
                        :key="link.module"
                        @click="router.visit(`/${moduleLink(link.module)}`)"
                    >
                        <v-list-item-title class="text-body-2">{{
                            moduleName(link.module)
                        }}</v-list-item-title>
                    </v-list-item>
                </Link>
            </v-list-group>
        </v-list>

        <template v-slot:append>
            <v-footer class="d-flex flex-column text-center" color="primary">
                <div>
                    <strong style="font-size: 0.99rem">{{ appName }}</strong>

                    <br />
                    <strong>Developer ©</strong> —
                    {{ new Date().getFullYear() }}
                </div>
            </v-footer>
        </template>
    </v-navigation-drawer>

    <nav-bar
        :hasDrawer="true"
        :errors="errors"
        :flash="flash"
        :can="can"
        @toggleDrawer="toggleDrawer"
    />
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import NavBar from "./NavBar.vue";
import { usePage, Link, router } from "@inertiajs/vue3";
import { useDisplay } from "vuetify";

const props = defineProps({
    errors: Object,
    flash: Object,
    can: Array,
});

const drawer = ref(false);

const { mobile } = useDisplay();
onMounted(() => {
    if (!mobile.value) {
        drawer.value = true;
    }
});

const toggleDrawer = () => {
    drawer.value = !drawer.value;
};

const page = usePage();
const appName = computed(() => {
    return page.props?.appName ?? "App Name";
});

const fullName = computed(() => {
    return page.props?.auth?.user?.name ?? "Full Name";
});

const emailAddress = computed(() => {
    return page.props?.auth?.user?.email ?? "Email Address";
});

const profileIcon = computed(() => {
    return page.props?.auth?.user?.isAdmin ? "mdi-account-star" : "mdi-account";
});

const accountType = computed(() => {
    return page.props?.auth?.user?.type;
});

const accessibleModules = computed(() => page.props?.accessibleModules ?? []);

const moduleLinks = [
    {
        module: "buildings",
        icon: "mdi-office-building",
    },
    {
        module: "floors",
        icon: "mdi-layers",
    },
    {
        module: "rooms",
        icon: "mdi-door",
    },
    {
        module: "rescue_requests",
        icon: "mdi-alert-circle",
    },
];

const reportLinks = [
    {
        module: "audit_trails",
        icon: "mdi-clipboard-text-clock",
    },
];

const systemLinks = [
    {
        module: "users",
        icon: "mdi-account-group",
    },
    {
        module: "conversations",
        icon: "mdi-message-text",
    },
];

const moduleLink = (module) => module.replace(/_/g, "-");
const moduleName = (module) => {
    return module
        .replace(/_/g, "-")
        .split("-")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
};
</script>

<style scoped>
.user-name,
.user-email {
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    text-align: center;
    max-width: 100%;
    display: inline-block;
    white-space: normal;
    color: white; /* ✅ Default name/email is white */
}

/* Style for the user's name */
.user-name {
    font-weight: bold;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

/* Style for the user's email */
.user-email {
    font-size: 0.9rem;
}

/* Smaller screens */
@media (max-width: 600px) {
    .user-name {
        font-size: 0.8rem;
    }
    .user-email {
        font-size: 0.8rem;
    }
}

/* Larger screens */
@media (min-width: 1200px) {
    .user-name {
        font-size: 1rem;
    }
    .user-email {
        font-size: 0.9rem;
    }
}

/* Selected state (stays active) */
.sidebar-hover.selected {
    background-color: #000020 !important;
    border-radius: 6px;
}
.sidebar-hover.selected .v-list-item-title,
.sidebar-hover.selected .v-icon {
    color: white !important;
}

/* ✅ Click/press only (temporary, not staying) */
.sidebar-hover:active {
    background-color: #000020 !important;
    border-radius: 6px;
}
.sidebar-hover:active .v-list-item-title,
.sidebar-hover:active .v-icon {
    color: black !important;
}

/* Hover effect */
.sidebar-hover:hover {
    background-color: #808080;
    transition: background-color 0.3s ease, color 0.3s ease;
    border-radius: 6px;
    cursor: pointer;
}
.sidebar-hover:hover .v-list-item-title,
.sidebar-hover:hover .v-icon {
    color: white !important;
}

/* NEW CSS for custom ripple color */
.sidebar-hover :deep(.v-ripple__container) {
    color: white !important;
}

/* Remove underline from Inertia's Link */
.no-underline-v-list-item {
    text-decoration: none !important;
}

/* ✅ Make ALL sidebar text and icons white by default */
.no-underline-v-list-item .v-list-item-title,
.no-underline-v-list-item .v-icon {
    color: white !important;
}
</style>
