<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Reservas</title>
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
    <h1>Reporte de Reservas</h1>
    <p class="fecha">Generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha ingreso</th>
                <th>Fecha salida</th>
                <th>Costo total</th>
                <th>Trabajador</th>
                <th>Cliente</th>
                <th>Habitación</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($datos as $dato)
            <tr>
                <td>{{ $dato['id'] }}</td>
                <td>{{ $dato['fecha_inicio'] }}</td>
                <td>{{ $dato['fecha_fin'] }}</td>
                <td>{{ $dato['costo_total'] }}</td>
                <td>{{ isset($dato['trabajador']['nombre']) ? $dato['trabajador']['nombre'].' '.$dato['trabajador']['apellido'] : '' }}</td>
                <td>{{ isset($dato['cliente']['nombre']) ? $dato['cliente']['nombre'].' '.$dato['cliente']['apellido'] : '' }}</td>
                <td>
                    @if(isset($dato['habitaciones']) && count($dato['habitaciones']) > 0)
                        {{ 'Habitación ' . ($dato['habitaciones'][0]['numero_habitacion'] ?? $dato['habitaciones'][0]['id']) }}
                    @endif
                </td>
                <td>{{ $dato['estado'] == 1 ? 'Completado' : 'Cancelado' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
