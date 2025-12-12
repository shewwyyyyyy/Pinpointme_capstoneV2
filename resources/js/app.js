import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import Layout from "@/App.vue";
import vuetify from "./vuetify";

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob("./Pages/**/*.vue", { eager: true });
        const page = pages[`./Pages/${name}.vue`];

        // only pages that has no layout defined in the component will render the layout
        if (page.default.layout === undefined) {
            page.default.layout = Layout;
        }

        return page;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // ✅ Auto-register all components from ./Components/** (recursively)
        const components = import.meta.glob("./Components/Customs/**/*.vue", {
            eager: true,
        });

        Object.entries(components).forEach(([path, definition]) => {
            const filename = path.split("/").pop().replace(".vue", "");
            // Choose between these:
            app.component(filename, definition.default); // Use PascalCase (e.g., CDataTableServer)
            // OR if you prefer kebab-case:
            // app.component(toKebabCase(filename), definition.default);
        });

        app.use(plugin).use(vuetify).mount(el);
    },
});
