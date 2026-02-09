import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import Layout from "@/App.vue";
import vuetify from "./vuetify";
import { registerServiceWorker } from "./Utilities/pushNotifications";

// Register service workers immediately for push notifications
// This needs to happen early so push notifications work even when app is closed
if ('serviceWorker' in navigator) {
    // Register the main push notification service worker
    registerServiceWorker()
        .then(registration => {
            if (registration) {
                console.log('[App] Main service worker registered for push notifications');
            }
        })
        .catch(error => {
            console.error('[App] Main service worker registration failed:', error);
        });
    
    // Register Firebase messaging service worker
    navigator.serviceWorker.register('/firebase-messaging-sw.js')
        .then(registration => {
            console.log('[App] Firebase messaging service worker registered');
        })
        .catch(error => {
            console.error('[App] Firebase messaging service worker registration failed:', error);
        });
}

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