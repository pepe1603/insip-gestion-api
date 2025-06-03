// resources/js/router/index.js
import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from '../pages/Dashboard.vue';
import Vacaciones from '../pages/Vacaciones.vue';

const routes = [
    { path: '/', component: Dashboard },
    { path: '/vacaciones', component: Vacaciones },
];

const router = createRouter({
    history: createWebHistory(),  // Asegúrate de usar createWebHistory para no usar el hash (#)
    routes,
});

export default router;
