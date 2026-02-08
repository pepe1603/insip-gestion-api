<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Empleados con Más Días de Vacaciones</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
                html{
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
        }
        body { font-family: DejaVu Sans, sans-serif; margin: 20px; background-color: #f5f5f5; }
        h1 { text-align: center; font-size: 24px; color: #4CAF50; margin-bottom: 20px; }
        .header-info { text-align: center; margin-bottom: 20px; font-size: 14px; color: #555; }
        table { width: 70%; margin: 0 auto; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .logo { max-width: 100px; display: block; margin: 0 auto 20px; }
        .footer { text-align: center; font-size: 10px; margin-top: 30px; color: #777; }
        .header-more-info {
            width: 90%;
            text-align: center;
            font-size: 12px;
            color: #555;
            margin: 0 auto;
            margin-top: 10px;
        }

        .header-more-info br {
            margin: 5px 0;
        }
        .header-more-info strong {
            color: #4CAF50;
        }
        .header-more-info span {
            color: #888;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/nsip-logo_opt.png') }}" alt="Logo" class="logo">
        <h1>Top Empleados con Más Días de Vacaciones Tomados</h1>
                <p class="header-info">Reporte generado el {{ date('d/m/Y H:i') }} (Top {{ $limit ?? 5 }} empleados)</p>
                <br>

        <p class="header-more-info">
            Este reporte, muestrra un listado de los empleados que más días de vacaciones han tomado en un periodo determinado.
            <br>
            El listado se ordena de mayor a menor cantidad de días tomados.
            <br>
            El Top puede ser personalizado mediante el parámetro `limit` en la solicitud.
            <br>
            Si no se especifica, el valor por defecto es 5 empleados.
            <br>
            <strong>Nota:</strong> El reporte no incluye empleados que no hayan tomado días de vacaciones.
            <br>
            Si el parámetro `limit` es mayor que la cantidad de empleados con días tomados, se mostrará solo hasta el número disponible.
            <br>
        </p>


    </div>

    @if(count($data) > 0)
        <table>
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Empleado</th>
                    <th>Total Días Tomados</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $vacacion['empleado']['nombre'] ?? 'N/A' }} {{ $vacacion['empleado']['ape_paterno'] ?? '' }} {{ $vacacion['empleado']['ape_materno'] ?? '' }}</td>
                        <td>{{ $row['total_dias'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; color: #888;">No hay datos de empleados para el Top especificado.</p>
    @endif

    <div class="footer">
        <p>Este reporte es generado automáticamente.</p>
    </div>
</body>
</html>
