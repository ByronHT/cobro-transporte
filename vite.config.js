import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        react(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.jsx',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '192.168.1.2',
        hmr: {
            host: '192.168.1.2:8000',
        },
        origin: 'http://192.168.1.2:5173',
        cors: true
    },
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: './tests/setup.js',
        css: true,
    },
});
