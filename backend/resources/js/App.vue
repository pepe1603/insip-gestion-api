<!-- resources/js/App.vue-->
<template>

      <header>
        <h1>Gestión de Asistencias y Vacaciones</h1>
        <nav>
          <router-link to="/">Dashboard</router-link>
          <router-link to="/vacaciones">Vacaciones</router-link>
        </nav>
      </header>
      <main>
        <router-view></router-view>  <!-- Aquí se cargan las páginas -->
      </main>

  </template>

  <script>
  export default {
    name: 'App',
  };
  </script>

  <style scoped>
  /* Estilos generales */
  #app {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
  }

  header {
    background-color: #282c34;
    color: white;
    padding: 10px;
    text-align: center;
  }

  nav {
    margin-top: 10px;
  }

  nav a {
    margin: 0 10px;
    color: #61dafb;
    text-decoration: none;
  }
  </style>
