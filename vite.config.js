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
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // Separar vendor libraries grandes
                    'react-vendor': ['react', 'react-dom', 'react-router-dom'],
                    'map-vendor': ['leaflet', 'react-leaflet'],
                    'ionic-vendor': ['@ionic/react', 'ionicons'],
                    'capacitor-vendor': ['@capacitor/core', '@capacitor/camera', '@capacitor/local-notifications']
                }
            }
        },
        chunkSizeWarningLimit: 600, // Aumentar límite de advertencia a 600kb
        minify: 'terser', // Mejor minificación
        terserOptions: {
            compress: {
                drop_console: true, // Eliminar console.log en producción
                drop_debugger: true
            }
        }
    },
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
        cors: true
    },
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: './tests/setup.js',
        css: true,
    },
});
