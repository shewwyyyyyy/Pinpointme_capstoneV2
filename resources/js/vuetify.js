// Vuetify
import "vuetify/styles";
import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
import { md3, md1 } from "vuetify/blueprints";
import "@mdi/font/css/materialdesignicons.css";
import { VDateInput } from "vuetify/labs/VDateInput";
import { light, dark } from "./Themes/DefaultTheme.js";

const vuetify = createVuetify({
    // components,
    components: {
        ...components, // Spread standard components
        //VDateInput, // Add VDateInput explicitly from Labs
    },
    directives,
    blueprint: md3,
    icons: {
        defaultSet: "mdi",
    },
    theme: {
        defaultTheme: "light",
        themes: {
            light,
            dark,
        },
    },
    display: {
        mobileBreakpoint: "md",
    },
});

export default vuetify;
