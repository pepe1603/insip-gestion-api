// resources/js/store/index.js
import { defineStore } from 'pinia';

export const useVacacionesStore = defineStore('vacaciones', {
    state: () => ({
        vacaciones: [],
    }),
    actions: {
        async fetchVacaciones() {
            const response = await fetch('/api/vacaciones');
            this.vacaciones = await response.json();
        },
    },
});
