<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte por Rango de Fechas</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 10px;
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
        table thead tr th{
            font-size: .75rem;
        }

        tbody tr td{
            font-size: .7rem;
        }

        th, td {
            padding: 5px;
            text-align: center;
        }

        th {
            background-color: #3498db;
            color: white;
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
    <h1>Reporte de Asistencia por Rango de Fechas</h1>
    <h3>Generado el {{ date('d/m/Y H:i') }}</h3>

    <p style="text-align: center; font-style: italic;">
        <strong>Desde:</strong> {{ $fechaInicio }} &nbsp; | &nbsp;
        <strong>Hasta:</strong> {{ $fechaFin }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Empleado</th>
                <th>Fecha</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Tipo Asistencia</th>
                <th>Observaciones</th>
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
                        <td colspan="6" class="no-data-head">No hay datos disponibles</td>
                    </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Este reporte cubre el rango solicitado. Para más detalles, contacte al área de gestión de personal.
    </div>
</body>
</html>
