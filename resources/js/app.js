import '../css/app.css';
import './bootstrap';
import './echo';

import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/navigation';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { setupCalendar, Calendar, DatePicker } from 'v-calendar';
import 'v-calendar/style.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
document.documentElement.classList.add('dark');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({
            render: () => h(App, props),
        })
            .use(plugin)
            .use(ZiggyVue)
            .use(setupCalendar, {})
            .component('VDatePicker', DatePicker)
            .component('VCalendar', Calendar)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/build/sw.js')
            .then((reg) => console.log('SW registered', reg.scope))
            .catch((err) => console.error('SW registration failed:', err));
    });
}