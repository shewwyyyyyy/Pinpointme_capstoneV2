<template>
    <Head :title="`Login — ${appName}`" />
    <empty-layout>
        <c-row fluid>
            <c-col
                style="height: 100vh"
                cols="12"
                sm="12"
                md="6"
                lg="6"
                class="hidden-sm hidden-xs"
            >
                <c-container
                    pa-0
                    class="bg-primary d-flex justify-center align-center flex-column"
                    style="height: 100vh; position: relative"
                >
                    <!-- Background pattern -->
                    <div
                        class="foreground-image"
                        style="
                            position: absolute;
                            top: 0;
                            left: 0;
                            z-index: 1;
                            width: 100%;
                            height: 100%;
                            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 50%);
                        "
                    />

                    <!-- Centered logo -->
                    <c-container
                        class="d-flex justify-center align-center"
                        style="z-index: 2"
                    >
                         <v-img
                            src="/images/logos/pinpointme.png"
                            max-height="250"
                            max-width="250"
                            contain
                        />
                    </c-container>

                    <div
                        class="font-weight-medium text-center mb-2 text-white"
                        style="z-index: 2; font-size: clamp(1.5rem, 4vw, 3rem)"
                    >
                        PinPointMe <br />Emergency Rescue System
                        <br />
                    </div>
                </c-container>
            </c-col>
            <!-- Mobile -->
            <c-col cols="12" sm="12" md="6" lg="6" class="py-5 overflow-hidden">
                <c-container class="pa-5 mt-10">
                    <div
                        class="d-lg-none d-md-none d-flex justify-center align-center flex-column"
                    >
                        <c-container
                            class="pa-0 d-flex justify-center align-center"
                        >
                            <div
                                style="
                                    position: absolute;
                                    margin-right: 0px;
                                    top: 0px;
                                    bottom: 0px;
                                    right: 0px;
                                    height: 100%;
                                    max-width: 100%;
                                    opacity: 0.1;
                                    background: linear-gradient(135deg, var(--v-theme-primary) 0%, transparent 50%);
                                "
                            />

                            <v-icon
                                size="100"
                                color="primary"
                                style="display: block; margin: 0 auto"
                            >mdi-map-marker-radius</v-icon>
                        </c-container>
                    </div>
                    <div
                        class="mt-6 mb-1 text-h4 text-center font-weight-bold"
                        style="position: relative; z-index: 9999"
                    >
                        Welcome!
                    </div>

                    <c-form @submit.prevent="handleFormSubmission">
                        <c-row class="justify-center">
                            <c-col
                                cols="12"
                                sm="12"
                                md="8"
                                lg="8"
                                xl="7"
                                xxl="7"
                            >
                                <c-alert-system-error v-if="props.errors.error">
                                    {{ props.errors.error }}
                                </c-alert-system-error>
                                <c-alert-success
                                    elevation="0"
                                    v-if="props.flash.success"
                                    :text="props.flash.success"
                                />
                            </c-col>

                            <c-col
                                cols="12"
                                sm="12"
                                md="8"
                                lg="8"
                                xl="7"
                                xxl="7"
                            >
                                <c-text-field
                                    v-model="form.username"
                                    :loading="btnDisabled"
                                    label="Username"
                                    class="mt-2"
                                    variant="outlined"
                                    autocomplete="username"
                                    :error-messages="errors.username"
                                    @input="handleNameInput($event, 'username')"
                                />
                            </c-col>

                            <c-col
                                cols="12"
                                sm="12"
                                md="8"
                                lg="8"
                                xl="7"
                                xxl="7"
                            >
                                <c-password-field
                                    v-model="form.password"
                                    :loading="btnDisabled"
                                    class="mt-2"
                                    variant="outlined"
                                    autocomplete="pasword"
                                    :error-messages="errors.password"
                                    @input="handleNameInput($event, 'password')"
                                />
                            </c-col>
                        </c-row>

                        <c-row class="justify-center">
                            <c-col
                                style="padding: 0; margin: 0"
                                class="pa-0 ma-0"
                                cols="12"
                                sm="12"
                                md="8"
                                lg="8"
                                xl="7"
                                xxl="7"
                            >
                            </c-col>
                        </c-row>

                        <c-row class="justify-center">
                            <c-col
                                cols="12"
                                sm="12"
                                md="8"
                                lg="8"
                                xl="7"
                                xxl="7"
                            >
                                <c-btn-submit
                                    prepend-icon=""
                                    :loading="btnDisabled"
                                    label="Login"
                                    size="default"
                                    block
                                />
                            </c-col>
                        </c-row>
                    </c-form>
                    <v-footer
                        class="bg-transparent d-flex flex-column text-center mt-5"
                    >
                        <hr class="mb-5" style="width: 40%" />

                        <div>
                            <strong>{{ `${appName} v${appVersion}` }}</strong>
                            <br />
                            <strong>{{ appDeveloper }} &copy;</strong> —
                            {{ new Date().getFullYear() }}
                        </div>
                    </v-footer>
                </c-container>
            </c-col>
        </c-row>
        <c-fab-lower-right
            @click="isDarkMode = !isDarkMode"
            :icon="isDarkMode ? 'mdi-weather-sunny' : 'mdi-weather-night'"
            style="bottom: 20px"
        />
    </empty-layout>
</template>
<script setup>
import { router, Head, usePage } from "@inertiajs/vue3";
import { computed, ref, onMounted, watch } from "vue";
import { useTheme } from "vuetify";

// layout
import EmptyLayout from "@/Layouts/EmptyLayout.vue";
// import ForgotPasswordDialog from "../Components/Pages/System/Login/Actions/ForgotPasswordDialog.vue";

const props = defineProps({
    flash: Object,
    errors: Object,
});

const form = ref({
    username: null,
    password: null,
});

const visible = ref(false);

const page = usePage();

const appName = computed(() => {
    return page.props.appName ?? "App Name";
});

const appDeveloper = computed(() => {
    return page.props.appDeveloper ?? "Developer";
});

const theme = useTheme();
const isDarkMode = ref(false);

const appVersion = computed(() => {
    return page.props.appVersion;
});

// Sync Vuetify theme and localStorage based on isDarkMode
watch(isDarkMode, (enabled) => {
    const newTheme = enabled ? "dark" : "light";
    // theme.global.name.value = newTheme;
    theme.change(newTheme);
    localStorage.setItem("theme", newTheme);
});

// On mount, read theme from localStorage and apply
onMounted(() => {
    const savedTheme = localStorage.getItem("theme") || "light";
    // theme.global.name.value = savedTheme;
    theme.change(savedTheme);
    isDarkMode.value = savedTheme === "dark";
});

const btnDisabled = ref(false);
const handleFormSubmission = async () => {
    router.post("/login", form.value, {
        onSuccess: ({ props }) => {
            // sessionStorage.setItem("token", props.token);
        },
        onError: () => {
            //
        },
        onBefore: () => {
            btnDisabled.value = true;
        },
        onFinish: () => {
            btnDisabled.value = false;
        },
    });
};

/**
 * Filters input for form.name to allow only alphanumeric, numeric, and special characters,
 * and disallow emojis.
 */
const handleNameInput = (event, field) => {
    // Remove emoji characters using a regex that matches surrogate pairs and certain unicode ranges
    const emojiRegex =
        /([\u2700-\u27BF]|[\uE000-\uF8FF]|[\uD83C-\uDBFF][\uDC00-\uDFFF]|\uFE0F|\u200D)/g;
    // Remove spaces as well
    let value = event.target.value.replace(emojiRegex, "").replace(/\s+/g, "");
    form.value[field] = value;
};
</script>
