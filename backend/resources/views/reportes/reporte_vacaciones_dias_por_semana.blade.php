<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Días de Vacaciones Tomados por Semana</title>
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
        table { width: 60%; margin: 0 auto; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: center; font-size: 12px; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .logo { max-width: 100px; display: block; margin: 0 auto 20px; }
        .footer { text-align: center; font-size: 10px; margin-top: 30px; color: #777; }
        .header-more-info {
            text-align: center;
            font-size: 12px;
            color: #555;
            margin-top: 10px;
        }
        .header-more-info strong {
            color: #4CAF50;
        }
        .header-more-info span {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/nsip-logo_opt.png') }}" alt="Logo" class="logo">
        <h1>Días de Vacaciones Tomados por Semana</h1>
        <p class="header-info">Reporte generado el {{ date('d/m/Y H:i') }} para el Año: {{ $año }}</p>

        <p class="header-more-info">
            Este reporte muestra los días de vacaciones tomados por semana para el año especificado.
            <br>
            <strong>Nota:</strong> Solo se incluyen las semanas que tienen días tomados. Es decir, las vacaciones fueron solicitadas y aprobadas.
            <br>
            El reporte se ordena por semana, comenzando desde la primera semana del año hasta la última.
            <br>
            Si una semana no tiene días tomados, no aparecerá en el reporte.
            <br>
            <strong>Nota:</strong> El número de semana se basa en el calendario ISO 8601, donde la semana 1 es la primera semana del año que contiene al menos 4 días.
            <br>
            Si el año no tiene semanas completas, el reporte puede no mostrar todas las semanas del año.
            <br>
            Si el año es bisiesto, se considerarán 53 semanas en lugar de 52.
            <br>
            Si el año no es bisiesto, se considerarán 52 semanas.
            <br>
            Si el año tiene menos de 52 semanas completas, el reporte mostrará solo las semanas con días tomados.
            <br>

        </p>
    </div>

    @if(count($data) > 0)
        <table>
            <thead>
                <tr>
                    <th>Semana</th>
                    <th>Días Tomados</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                    <tr>
                        <td>{{ $row['semana'] }}</td>
                        <td>{{ $row['total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; color: #888;">No se tomaron días de vacaciones en el año especificado.</p>
    @endif

    <div class="footer">
        <p>Este reporte es generado automáticamente.</p>
    </div>
</body>
</html>
