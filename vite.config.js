// backend/vite.config.js
// Este archivo es la configuración de Vite para el proyecto backend.
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
    // --- NUEVA CONFIGURACIÓN PARA ELIMINAR CONSOLE.LOGS EN PRODUCCIÓN ---
    build: {
      minify: 'terser',
      terserOptions: {
        compress: {
          drop_console: true,
          drop_debugger: true,
        },
      },
    },
    // --- FIN DE LA NUEVA CONFIGURACIÓN ---
});