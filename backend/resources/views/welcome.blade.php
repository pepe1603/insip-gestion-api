<!-- resources/views/welcome.blade.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Asistencias y Vacaciones</title>
    @vite(['resources/js/app.js'])  <!-- Aquí se carga el archivo JS de Vue -->
    @vite(['resources/css/app.css'])  <!-- Aquí se carga el archivo JS de Vue -->
</head>
<body>
    <div class="container mx-auto border border-gray-200 rounded-b-lg rounded-br-lg p-4 text-center text-gray-600 hover:shadow">
        <h1 class="font-semibold text-xl mb-4 text-black">Hola, Bienvenido a esta API</h1>
        <p class="text-base mb-1">Lorem, ipsum dolor sit amet consectetur adipisicing elit. Rerum, nihil optio? Perspiciatis ullam dicta odio.</p>
        <span class="text-blue-500 hover:underline ">click here to redirect App-web</span>
    </div>
  <!-- Vue montará la aplicación aquí -->
</body>
</html>
