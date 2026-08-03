<?php

namespace App\Http\Controllers\Concerns;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

trait ExportaPdf
{
    /**
     * Genera la descarga de un reporte PDF tabular usando la plantilla genérica
     * resources/views/pdf/reporte.blade.php.
     *
     * @param  array<int, string>  $columnas  Encabezados de columna, en orden.
     * @param  array<int, array<int, string>>  $filas  Cada fila es un array de valores en el mismo orden que $columnas.
     */
    protected function generarPdf(string $titulo, array $columnas, array $filas, string $nombreArchivo): Response
    {
        $pdf = Pdf::loadView('pdf.reporte', [
            'titulo' => $titulo,
            'columnas' => $columnas,
            'filas' => $filas,
            'generadoEl' => now()->format('d/m/Y H:i'),
        ])->setPaper('letter', 'landscape');

        return $pdf->download($nombreArchivo);
    }
}
