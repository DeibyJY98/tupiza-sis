<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Pagos</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #222; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 4px; }
        p.fecha { text-align: center; color: #666; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #999; padding: 6px 8px; text-align: left; }
        th { background-color: #eee; }
    </style>
</head>
<body>
    <h1>Reporte de Pagos</h1>
    <p class="fecha">Generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Reserva</th>
                <th>Cliente</th>
                <th>Cédula</th>
                <th>Monto</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($datos as $dato)
            <tr>
                <td>{{ $dato['fecha'] ?? '' }}</td>
                <td>RES-{{ $dato['reserva']['id'] ?? '' }}</td>
                <td>{{ isset($dato['cliente']['nombre']) ? $dato['cliente']['nombre'].' '.$dato['cliente']['apellido'] : '' }}</td>
                <td>{{ $dato['cliente']['cedula'] ?? '' }}</td>
                <td>{{ $dato['monto'] ?? ($dato['reserva']['costo_total'] ?? '') }}</td>
                <td>{{ $dato['estado'] == 1 ? 'Completado' : 'Cancelado' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
