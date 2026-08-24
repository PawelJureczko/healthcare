import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
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
            injectRegister: false,
            includeAssets: ['pwa-icons/icon-192.png', 'pwa-icons/icon-512.png'],
            manifest: {
                name: 'Centrum',
                short_name: 'Centrum',
                description: 'Osobiste centrum dowodzenia zdrowiem i treningiem',
                start_url: '/dashboard',
                scope: '/',
                display: 'standalone',
                background_color: '#ffffff',
                theme_color: '#4f46e5',
                icons: [
                    { src: '/pwa-icons/icon-192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/pwa-icons/icon-512.png', sizes: '512x512', type: 'image/png' },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,png,svg}'],
            },
        }),
    ],
});
