<?php

use App\Models\Notificacion;
use Carbon\Carbon;

// Reutiliza crearHabitacionDePrueba() y crearReservaActiva() definidas en
// tests/Feature/Services/ReservaServiceTest.php (funciones globales de Pest).

afterEach(function () {
    Carbon::setTestNow();
});

it('notifica el check-out de una reserva que termina hoy y libera la habitación', function () {
    // La reserva se crea ANTES del check-out (queda ocupada correctamente); luego el
    // reloj avanza sin que ningún evento vuelva a tocar la habitación -justo el hueco
    // que el comando programado tiene que cerrar al mediodía.
    Carbon::setTestNow(Carbon::today()->setTime(8, 0));
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today()->subDays(3), Carbon::today());
    expect($habitacion->fresh()->estado)->toBe(0);

    Carbon::setTestNow(Carbon::today()->setTime(11, 5)); // ya pasó la hora de check-out

    $this->artisan('app:recalcular-disponibilidad-habitaciones')->assertExitCode(0);

    expect(Notificacion::count())->toBe(1);
    $notificacion = Notificacion::first();
    expect($notificacion->tipo)->toBe('checkout');
    expect($notificacion->id_reserva)->toBe($reserva->id);
    expect($notificacion->id_habitacion)->toBe($habitacion->id);
    expect($notificacion->leida)->toBeFalse();
    expect($notificacion->mensaje)->toContain($habitacion->numero_habitacion);

    expect($habitacion->fresh()->estado)->toBe(1); // disponible tras el check-out
});

it('no duplica la notificación de check-out si el comando corre más de una vez', function () {
    Carbon::setTestNow(Carbon::today()->setTime(11, 5));

    $habitacion = crearHabitacionDePrueba();
    crearReservaActiva($habitacion, Carbon::today()->subDays(3), Carbon::today());

    $this->artisan('app:recalcular-disponibilidad-habitaciones');
    $this->artisan('app:recalcular-disponibilidad-habitaciones');

    expect(Notificacion::count())->toBe(1);
});

it('no notifica antes de la hora de check-out aunque la reserva termine hoy', function () {
    Carbon::setTestNow(Carbon::today()->setTime(9, 0)); // antes de las 11:00

    $habitacion = crearHabitacionDePrueba();
    crearReservaActiva($habitacion, Carbon::today()->subDays(3), Carbon::today());

    $this->artisan('app:recalcular-disponibilidad-habitaciones');

    expect(Notificacion::count())->toBe(0);
    expect($habitacion->fresh()->estado)->toBe(0); // sigue ocupada, todavía no es la hora
});

it('no notifica reservas que terminan en el futuro ni reservas canceladas', function () {
    Carbon::setTestNow(Carbon::today()->setTime(11, 5));

    $habitacionFutura = crearHabitacionDePrueba();
    crearReservaActiva($habitacionFutura, Carbon::today(), Carbon::today()->addDays(3));

    $habitacionCancelada = crearHabitacionDePrueba();
    crearReservaActiva($habitacionCancelada, Carbon::today()->subDays(5), Carbon::today(), estado: 0);

    $this->artisan('app:recalcular-disponibilidad-habitaciones');

    expect(Notificacion::count())->toBe(0);
});
