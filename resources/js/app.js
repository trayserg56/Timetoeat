import '../css/app.css';
import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';

const pages = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
    resolve: (name) => {
        const loadPage = pages[`./Pages/${name}.vue`];

        if (! loadPage) {
            throw new Error(`Unknown page: ${name}`);
        }

        return loadPage();
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#dc6b35',
    },
});
