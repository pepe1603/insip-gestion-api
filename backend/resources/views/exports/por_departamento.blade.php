<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Asistencia - Departamento</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }

        h1 {
            text-align: center;
            font-size: 24px;
            color: #4CAF50;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        tr{
            font-size: 12px;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #ddd;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            margin-top: 20px;
            color: #777;
        }

        .logo {
            max-width: 100px;
            display: block;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/nsip-logo_opt.png') }}" alt="Logo" class="logo">
        <h1>Reporte de Asistencia por Departamento</h1>
        <p style="text-align: center;">Generado el {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Empleado</th>
                <th>Departamento</th>
                <th>Fecha</th>
                <th>Hora Entrada</th>
                <th>Hora Salida</th>
                <th>Tipo Asistencia</th>
                <th>Observaciones</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $asistencia)
                <tr>
                    <td>{{ $asistencia['empleado'] ?? 'N/A' }}</td>
                    <td>{{ $asistencia['departamento'] ?? 'N/A' }}</td>
                    <td>{{ $asistencia['fecha'] ?? 'N/A' }}</td>
                    <td>{{ $asistencia['hora_entrada'] ?? 'N/A' }}</td>
                    <td>{{ $asistencia['hora_salida'] ?? 'N/A' }}</td>
                    <td>{{ $asistencia['tipo_asistencia'] ?? 'N/A' }}</td>
                    <td>{{ $asistencia['observaciones'] ?? 'Sin observaciones' }}</td>
                    <td>{{ $asistencia['status'] ?? 'Desconocido' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Reporte generado automáticamente. Contacte al área de RRHH para más información.</p>
    </div>
</body>
</html>
