import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import Layout from "@/App.vue";
import vuetify from "./vuetify";
import { registerSW } from 'virtual:pwa-register';

// Register Service Worker
const updateSW = registerSW({
    onNeedRefresh() {
        if (confirm('New content available. Reload?')) {
            updateSW(true);
        }
    },
    onOfflineReady() {
        console.log('App ready to work offline');
    },
});

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob("./Pages/**/*.vue", { eager: true });
        const page = pages[`./Pages/${name}.vue`];

        if (page.default.layout === undefined) {
            page.default.layout = Layout;
        }

        return page;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        const components = import.meta.glob("./Components/Customs/**/*.vue", {
            eager: true,
        });

        Object.entries(components).forEach(([path, definition]) => {
            const filename = path.split("/").pop().replace(".vue", "");
            app.component(filename, definition.default);
        });

        app.use(plugin).use(vuetify).mount(el);
    }
});