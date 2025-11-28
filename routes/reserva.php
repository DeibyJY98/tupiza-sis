<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservaController;

// Añadir esta ruta junto con las demás rutas de reserva
Route::get('/reserva/fechas-ocupadas/{habitacion_id}', [ReservaController::class, 'getFechasOcupadas'])
    ->name('reserva.fechas-ocupadas');