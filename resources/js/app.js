import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import '../css/app.css';
import './bootstrap';

createInertiaApp({
    title: (title) => (title ? `${title} — ${import.meta.env.VITE_APP_NAME || 'FHV Stream'}` : import.meta.env.VITE_APP_NAME || 'FHV Stream'),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#0d9488',
        showSpinner: true,
    },
});