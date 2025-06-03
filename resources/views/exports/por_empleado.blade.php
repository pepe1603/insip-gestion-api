<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte por Empleado</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            background-color: #f5f5f5;
        }

        h1, h3 {
            text-align: center;
            color: #2c3e50;
        }

        .logo {
            max-width: 100px;
            display: block;
            margin: 0 auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 5px 4px;
            text-align: center;
        }
        tr {
            font-size: 12px;
        }

        th {
            background-color: #e67e22;
            color: white;
            font-size: 14px;

        }

        tr:nth-child(even) {
            background-color: #fdf2e9;

        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            text-align: center;
            color: #7f8c8d;
        }
        .no-data-head {
            text-align: center;
            font-size: 16px;
            color: #ff0000;
            font-weight: bold;
        }
        .no-data-body {
            text-align: center;
            font-size: 15px;
            color: #c1c1c1;
            font-weight: normal;
        }
    </style>
</head>
<body>

<div class="header">
    <img src="{{ public_path('images/nsip-logo_opt.png') }}" alt="Logo" class="logo">
    <h1>Reporte de Asistencia por Empleado</h1>
    <h3>Generado el {{ date('d/m/Y H:i') }}</h3>

    @php
        $empleadoNombre = $data[0]['empleado'] ?? 'No especificado';
    @endphp

    <p style="text-align: center; font-family: 'Robot', sans-serif;"><strong>Empleado:</strong> {{ $empleadoNombre }}</p>

</div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Hora de Entrada</th>
                <th>Hora de Salida</th>
                <th>Tipo de Asistencia</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @if(count($data) > 0)
                @foreach($data as $row)
                    <tr>
                        <td>{{ $row['id'] }}</td>
                        <td>{{ $row['fecha'] }}</td>
                        <td>{{ $row['hora_entrada'] }}</td>
                        <td>{{ $row['hora_salida'] }}</td>
                        <td>{{ $row['tipo_asistencia'] }}</td>
                        <td>{{ $row['observaciones'] }}</td>
                    </tr>
                @endforeach
            @else
                <tr><td colspan="100%" class="no-data-body">No hay datos disponibles para mostrar</td></tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Este reporte es generado automáticamente. Para más información, contacte al departamento de RRHH.
    </div>
</body>
</html>
