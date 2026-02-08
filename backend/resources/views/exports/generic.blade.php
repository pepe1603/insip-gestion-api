<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Generico</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
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

        table tbody tr, td {
            font-family: 'Roboto', sans-serif;
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

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #ddd;
        }

        .footer {
            bottom: 0;
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
        <h1>Reporte</h1>
        <p style="text-align: center; font-weight: 500; font-family: DejaVu Sans, sans-serif; letter-spacing: 1px;">Generado el {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                @if(count($data) > 0)
                    @foreach(array_keys($data[0]) as $header)
                        <th>{{ ucfirst(str_replace('_', ' ', $header)) }}</th>
                    @endforeach
                @else
                    <th colspan="100%" class="no-data-head">No hay datos disponibles para mostrar</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @if(count($data) > 0)
                @foreach($data as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="100%" class="no-data-body">No hay datos disponibles para mostrar</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <p>Este reporte es generado automáticamente. Para más información, contacte al departamento de recursos humanos.</p>
    </div>
</body>
</html>
