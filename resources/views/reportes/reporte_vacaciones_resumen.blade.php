<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de Vacaciones Solicitadas</title>
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
        .summary-box {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
            text-align: center;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #fff;
            border-radius: 8px;
        }
        .summary-item { margin: 0 15px; }
        .summary-item h3 { color: #4CAF50; margin-bottom: 5px; }
        .summary-item p { font-size: 20px; font-weight: bold; color: #333; }
        .logo { max-width: 100px; display: block; margin: 0 auto 20px; }
        .footer { text-align: center; font-size: 10px; margin-top: 50px; color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/nsip-logo_opt.png') }}" alt="Logo" class="logo">
        <h1>Resumen de Vacaciones Solicitadas</h1>
        <p class="header-info">Reporte generado el {{ date('d/m/Y H:i') }}</p>
    </div>

    @if(isset($data[0]))
        <div class="summary-box">
            <div class="summary-item">
                <h3>Total Solicitudes</h3>
                <p>{{ $data[0]['total'] }}</p>
            </div>
            <div class="summary-item">
                <h3>Aprobadas</h3>
                <p style="color: #28a745;">{{ $data[0]['aprobadas'] }}</p>
            </div>
            <div class="summary-item">
                <h3>Rechazadas</h3>
                <p style="color: #dc3545;">{{ $data[0]['rechazadas'] }}</p>
            </div>
            <div class="summary-item">
                <h3>Pendientes</h3>
                <p style="color: #ffc107;">{{ $data[0]['pendientes'] }}</p>
            </div>
        </div>
    @else
        <p style="text-align: center; color: #888;">No hay datos para el resumen de vacaciones.</p>
    @endif

    <div class="footer">
        <p>Este reporte es generado automáticamente.</p>
    </div>
</body>
</html>
