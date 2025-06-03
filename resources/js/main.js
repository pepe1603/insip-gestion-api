// resources/js/main.js
import { createApp } from 'vue';
import App from './App.vue';  // Componente raíz
import router from './router/index.js';  // Router de Vue
import { createPinia } from 'pinia';  // Si usas Pinia para manejar el estado

const app = createApp(App);

// Usar router y Pinia (si lo usas)
app.use(router);
app.use(createPinia());

app.mount('#app');  // Monta la aplicación en el div con id "app"
