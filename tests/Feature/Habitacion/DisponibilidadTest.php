<?php

use App\Models\Habitacion;
use Carbon\Carbon;

// Reutiliza crearHabitacionDePrueba() y crearReservaActiva() definidas en
// tests/Feature/Services/ReservaServiceTest.php (funciones globales de Pest).

afterEach(function () {
    Carbon::setTestNow();
});

it('mantiene la habitación ocupada antes de la hora de check-out el día que termina la reserva', function () {
    Carbon::setTestNow(Carbon::today()->setTime(9, 0));

    $habitacion = crearHabitacionDePrueba();
    crearReservaActiva($habitacion, Carbon::today()->subDays(2), Carbon::today());

    expect($habitacion->fresh()->estado)->toBe(0); // 0 = ocupada, aún no es hora de check-out (11:00)
});

it('libera la habitación automáticamente después de la hora de check-out el día que termina la reserva', function () {
    Carbon::setTestNow(Carbon::today()->setTime(9, 0));
    $habitacion = crearHabitacionDePrueba();
    crearReservaActiva($habitacion, Carbon::today()->subDays(2), Carbon::today());
    expect($habitacion->fresh()->estado)->toBe(0);

    Carbon::setTestNow(Carbon::today()->setTime(11, 30)); // pasó la hora de check-out
    $habitacion->actualizarDisponibilidad();

    expect($habitacion->fresh()->estado)->toBe(1); // 1 = disponible
});

it('respeta la constante Habitacion::HORA_CHECK_OUT usada por el schedule', function () {
    expect(Habitacion::HORA_CHECK_OUT)->toBe('11:00');
});
