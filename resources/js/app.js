import '../css/app.css';
import '../css/pixar-worlds.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import SaveQueuePanel from './Components/SaveQueuePanel.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        // SaveQueuePanel sits beside the Inertia App so background saves stay visible across page visits.
        return createApp({ render: () => [h(App, props), h(SaveQueuePanel)] })
            .use(plugin)
            .mount(el);
    },
    progress: {
        delay: 180,
        color: '#4F46E5',
        includeCSS: true,
        showSpinner: false,
    },
});
