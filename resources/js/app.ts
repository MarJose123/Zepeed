import { createInertiaApp, router } from "@inertiajs/vue3";
import { echo } from "@laravel/echo-vue";
import { configureEcho } from "@laravel/echo-vue";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import type { DefineComponent } from "vue";
import { createApp, h } from "vue";
import { ZiggyVue } from "ziggy-js";
import "../css/app.css";
import { initializeTheme } from "./composables/useAppearance";

configureEcho({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? "mt1",
    wsHost: import.meta.env.VITE_PUSHER_HOST ?? "localhost",
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 6001,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 6001,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? "https") === "https",
    disableStats: true,
    enabledTransports: ["ws", "wss"],
});

// @see: https://laravel.com/docs/12.x/broadcasting#only-to-others-configuration
router.on("before", (event) => {
    const socketId: string | undefined = echo().socketId();

    if (socketId) {
        event.detail.visit.headers["X-Socket-ID"] = socketId;
    }
});

const appName = import.meta.env.VITE_APP_NAME || "Zepeed";

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>("./pages/**/*.vue"),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});

// This will set light / dark mode on page load...
initializeTheme();
