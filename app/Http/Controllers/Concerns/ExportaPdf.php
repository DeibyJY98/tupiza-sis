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
     * @param  array<int, array{0: string, 1: string}>  $resumen  Líneas opcionales al final del reporte, como [etiqueta, valor] (p. ej. un total).
     */
    protected function generarPdf(string $titulo, array $columnas, array $filas, string $nombreArchivo, array $resumen = []): Response
    {
        $pdf = Pdf::loadView('pdf.reporte', [
            'titulo' => $titulo,
            'columnas' => $columnas,
            'filas' => $filas,
            'resumen' => $resumen,
            'generadoEl' => now()->format('d/m/Y H:i'),
        ])->setPaper('letter', 'landscape');

        return $pdf->download($nombreArchivo);
    }
}
