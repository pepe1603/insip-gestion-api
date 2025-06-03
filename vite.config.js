import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import Vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';  // Importa el módulo 'path'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        Vue(),
    ],
    resolve: {
        alias: {
          '@': path.resolve(__dirname, './resources/js'), // Esto indica que "@" apunta a "resources/js"
        },
      },
      server: {
        proxy: {
            '/api': process.env.VITE_API_URL || 'http://localhost:8000',  // Redirige a la URL de la API definida en .env
        },
    },
});
