<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte por Rango de Fechas</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 20px;
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
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #3498db;
            color: white;
        }
        tr{
            font-size: 12px;
        }

        tr:nth-child(even) {
            background-color: #ecf0f1;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            text-align: center;
            color: #7f8c8d;
        }
    </style>
</head>
<body>

    <img src="{{ public_path('images/nsip-logo_opt.png') }}" alt="Logo" class="logo">
    <h1>Reporte de Departamentos</h1>
    <h3>Generado el {{ date('d/m/Y H:i') }}</h3>



    <p style="text-align: center;">
        <strong>Departamentos Disponibles</strong>
    </p>

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
                            <td> {{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
            @else
                    <tr>
                        <td colspan="6" class="no-data-head">No hay datos de departamentos disponibles</td>
                    </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Este reporte consulta todos los departamentos disponibles. Para más detalles, contacte al área de gestión de personal.
    </div>
</body>
</html>
