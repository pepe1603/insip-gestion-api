// resources/js/services/api.js
import axios from 'axios';

// Aquí usamos la variable de entorno que Vite nos provee
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,  // Utilizamos la variable de entorno para la URL base de la API
  headers: {
    'Content-Type': 'application/json',
  },
});

export const getVacaciones = () => api.get('/api/vacaciones');
