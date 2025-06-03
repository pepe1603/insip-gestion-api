<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Vacaciones por Departamento</title>
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
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .logo { max-width: 100px; display: block; margin: 0 auto 20px; }
        .footer { text-align: center; font-size: 10px; margin-top: 30px; color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/nsip-logo_opt.png') }}" alt="Logo" class="logo">
        <h1>Reporte de Vacaciones por Departamento</h1>
        <p class="header-info">
            Reporte generado el {{ date('d/m/Y H:i') }}
            @if(isset($extraParams['departamento_id']))
                <br>Departamento ID: **{{ $extraParams['departamento_id'] }}**
            @endif
        </p>
    </div>

    @if(count($data) > 0)
        <table>
            <thead>
                <tr>
                    <th>ID Solicitud</th>
                    <th>Empleado</th>
                    <th>Departamento</th>
                    <th>Ciclo Servicio</th>
                    <th>Fecha Solicitud</th>
                    <th>Días Solicitados</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $vacacion)
                    <tr>
                        <td>{{ $vacacion['id'] }}</td>
                        <td>{{ $vacacion['empleado']['nombre'] ?? 'N/A' }} {{ $vacacion['empleado']['ape_paterno'] ?? '' }} {{ $vacacion['empleado']['ape_materno'] ?? '' }}</td>
                        <td>{{ $vacacion['empleado']['departamento']['nombre'] ?? 'N/A' }}</td>
                        <td>{{ $vacacion['ciclo_servicio']['anio'] ?? $vacacion['ciclo_servicio_id'] ?? 'N/A' }}</td>
                        <td>{{ \Carbon\Carbon::parse($vacacion['fecha_solicitud'])->format('d/m/Y') }}</td>
                        <td>{{ $vacacion['dias_vacaciones_solicitados'] }}</td>
                        <td>{{ $vacacion['estado_solicitud']['estado'] ?? 'Desconocido' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; color: #888;">No se encontraron vacaciones para el departamento especificado.</p>
    @endif

    <div class="footer">
        <p>Este reporte es generado automáticamente.</p>
    </div>
</body>
</html>
