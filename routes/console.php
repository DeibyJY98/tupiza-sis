<?php

use App\Models\Habitacion;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Se ejecuta a la hora de check-out: notifica las reservas que finalizan hoy y
// recalcula qué habitaciones quedan disponibles/ocupadas.
Schedule::command('app:recalcular-disponibilidad-habitaciones')
    ->dailyAt(Habitacion::HORA_CHECK_OUT);
