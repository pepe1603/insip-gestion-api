<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Días de Vacaciones Tomados por Mes</title>
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
            width: 90%;
            text-align: center;
            font-size: 12px;
            color: #555;
            margin: 0 auto;
            margin-top: 10px;
        }
        .header-more-info strong {
            color: #333;
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
        <h1>Días de Vacaciones Tomados por Mes</h1>
        <p class="header-info">Reporte generado el {{ date('d/m/Y H:i') }} para el Año:  {{  $año }} </p>

        <p class="header-more-info">
            Este reporte solo muestra los días de vacaciones tomados por mes para el año especificado.
            <br>
            <span>            solo se incluyen los meses que tienen días tomados.Es decir que las vaciones fueron solicitadas y aprobadas.</span>
            <br>
            <strong>Nota:</strong> Si un mes no tiene días tomados, no aparecerá en el reporte.
            <br>
            El reporte se ordena por mes, comenzando desde enero hasta diciembre.
            <br>
        </p>
    </div>

    @if(count($data) > 0)
        <table>
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>Días Tomados</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $meses = [
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                    ];
                @endphp
                @foreach($data as $row)
                    <tr>
                        <td>{{ $meses[$row['mes']] ?? $row['mes'] }}</td>
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
