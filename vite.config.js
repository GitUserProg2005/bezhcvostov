import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({

    server: {
        watch: {
            // Иначе Vite + Laravel refresh пытаются смотреть весь репозиторий → ENOSPC на Linux
            ignored: [
                '**/vendor/**',
                '**/storage/**',
                '**/node_modules/**',
                '**/public/build/**',
            ],
        },
    },

    plugins: [

        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),

        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),

        VitePWA({

            registerType: 'autoUpdate',

            // Регистрация SW вручную в resources/js/app.js (Blade, не index.html)
            injectRegister: false,

            includeAssets: [
                'icons/icon-192.png',
                'icons/icon-512.png'
            ],

            manifest: {

                name: 'Wix',
                short_name: 'Wix',

                start_url: '/',

                display: 'standalone',

                background_color: '#0f172a',
                theme_color: '#0f172a',

                orientation: 'portrait',

                icons: [
                    {
                        src: '/icons/wix_logo4.png',
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: '/icons/wix_logo4.png',
                        sizes: '512x512',
                        type: 'image/png'
                    }
                ]
            },

            workbox: {

                // ВАЖНО: отключаем fallback чтобы не ломать SSR
                navigateFallback: null,

                // НЕ кешируем страницы
                runtimeCaching: [],

                // обновление service worker сразу
                clientsClaim: true,
                skipWaiting: true
            },

            devOptions: {
                enabled: false
            }

        })

    ],

});