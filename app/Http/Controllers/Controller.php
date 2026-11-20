<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    /**
     * Vuelve a la página anterior dejando el error DENTRO del modal que se estaba usando
     * ('crear' o 'editar') en vez de cerrarlo: se reenvían los datos escritos y se avisa a la
     * vista (session 'modal') para que reabra el modal. El script de layout/navbar.blade.php
     * se encarga de reabrirlo, rellenarlo y mostrar el error.
     *
     * Nunca se devuelven contraseñas ni archivos: por seguridad, y porque un navegador no
     * permite rellenar un input de archivo.
     */
    protected function errorEnModal(Request $request, ValidationException|string $error, string $modal): RedirectResponse
    {
        $mensaje = $error instanceof ValidationException
            ? collect($error->errors())->flatten()->join(' ')
            : $error;

        return back()
            ->withInput($request->except(['password', 'foto', 'comprobante']))
            ->with('error', $mensaje)
            ->with('modal', $modal);
    }
}
