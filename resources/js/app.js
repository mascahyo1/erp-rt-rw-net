import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { initFlowbite } from 'flowbite';

const appName = import.meta.env.VITE_APP_NAME || 'ERP RT/RW Net';

createInertiaApp({
    title: (title) => `${title} — ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        // Initialize Flowbite on mount
        vueApp.mixin({
            mounted() {
                initFlowbite();
            },
        });

        return vueApp.mount(el);
    },
    progress: {
        color: '#0ea5e9',
    },
});
