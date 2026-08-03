<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1f2229; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .meta { color: #666; font-size: 10px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; word-break: break-word; }
        th { background-color: #1f2229; color: #fff; }
        tr:nth-child(even) td { background-color: #f5f5f5; }
    </style>
</head>
<body>
    <h1>TupizaSis &middot; {{ $titulo }}</h1>
    <p class="meta">Generado el {{ $generadoEl }} &middot; {{ count($filas) }} registro(s)</p>

    <table>
        <thead>
            <tr>
                @foreach ($columnas as $columna)
                    <th>{{ $columna }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $fila)
                <tr>
                    @foreach ($fila as $valor)
                        <td>{{ $valor }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columnas) }}">Sin registros para mostrar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
