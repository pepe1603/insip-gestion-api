<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Empleados</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">

    <style>
        body {
            height: 100%;
            width: 100%;
            padding: .25rem;
            font-family: DejaVu Sans, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
            font-size: 15px;
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
            overflow-x: scroll;
        }

        table, th, td {
            border: 1px solid #ddd;
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

        tr{
            font-size: 12px;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
            font-size: 12px;
        }

        tr:hover {
            background-color: #ddd;
        }

        .footer {
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

        .header p {
            text-align: center;
            font-size: 14px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="header">
        <!-- Logo de la empresa -->
        <img src="{{ public_path('images/nsip-logo_opt.png') }}" alt="Logo" class="logo">
        <h1>Reporte de Empleados</h1>
        <p>Generado el {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nombre Completo</th>
                <th>Fecha de Ingreso</th>
                <th>Puesto</th>
                <th>Departamento</th>
                <th>Status</th>
                <th>Tipo de Contrato</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $empleado)
                <tr>
                    <td>{{ $empleado['nombre_completo'] }}</td>
                    <td>{{ $empleado['fecha_ingreso'] }}</td>
                    <td>{{ $empleado['puesto'] }}</td>
                    <td>{{ $empleado['departamento'] }}</td>
                    <td>{{ $empleado['status'] }}</td>
                    <td>{{ $empleado['tipo_contrato'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Este reporte es generado automáticamente. Para más información, contacte al departamento de recursos humanos.</p>
    </div>
</body>
</html>
