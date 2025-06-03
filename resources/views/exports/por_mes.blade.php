<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Asistencia - {{ $mes }} / {{ $anio }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }

        h1 {
            text-align: center;
            font-size: 24px;
            color: #4CAF50;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 100px;
            display: block;
            margin: 0 auto;
        }


        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 5px;
            text-align: center;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }
        tr {
            font-size: 12px;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            margin-top: 20px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/nsip-logo_opt.png') }}" alt="Logo" class="logo">
        <h1>Reporte de Asistencia por Mes</h1>
        <p style="text-align: center;">Generado el {{ date('d/m/Y H:i') }}</p>
    </div>
   <p style="text-align: center;">
     <strong style="padding: .25rem; text-align: center; margin: 0; font-style: italic;">Reporte del Mes - {{ $mes }}/{{ $anio }}</strong>
   </p>
    <table>
        <thead>
            <tr>
                <th>Id</th>
                <th>Empleado</th>
                <th>Fecha</th>
                <th>Hora Entrada</th>
                <th>Hora Salida</th>
                <th>Tipo de Asistencia</th>
            </tr>
        </thead>
        <tbody>
            @if(count($data) > 0)
                @foreach($data as $row)
                    <tr>
                        <td>{{ $row['id'] }}</td>
                        <td>{{ $row['empleado'] }}</td>
                        <td>{{ $row['fecha'] }}</td>
                        <td>{{ $row['hora_entrada'] }}</td>
                        <td>{{ $row['hora_salida'] }}</td>
                        <td>{{ $row['tipo_asistencia'] }}</td>
                    </tr>
                @endforeach
            @else
                <tr><td colspan="6" style="text-align: center;">No hay asistencias para este mes.</td></tr>
            @endif
        </tbody>
    </table>
    <div class="footer">
        <p>Este reporte es generado automáticamente.</p>
    </div>
</body>
</html>
