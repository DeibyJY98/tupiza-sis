<?php

use Carbon\Carbon;

// P2.1: endpoint que devuelve las habitaciones libres para un rango de fechas dado,
// independiente de si hoy están "ocupadas" o no (Habitacion.estado es solo para hoy).
beforeEach(function () {
    loguearComoTrabajador('recep_' . uniqid());
});

it('devuelve solo las habitaciones sin reservas activas que se solapen con el rango pedido', function () {
    $habitacionOcupada = crearHabitacionDePrueba();
    crearReservaActiva($habitacionOcupada, Carbon::today()->addDays(5), Carbon::today()->addDays(8));

    $habitacionLibre = crearHabitacionDePrueba();

    $response = $this->getJson('/reserva/habitaciones-disponibles?desde=' . Carbon::today()->addDays(6)->toDateString() . '&hasta=' . Carbon::today()->addDays(7)->toDateString());

    $response->assertOk();
    $ids = collect($response->json('habitaciones'))->pluck('id');

    expect($ids)->not->toContain($habitacionOcupada->id)
        ->and($ids)->toContain($habitacionLibre->id);
});

it('una habitación ocupada en un rango vuelve a aparecer disponible fuera de ese rango', function () {
    $habitacion = crearHabitacionDePrueba();
    crearReservaActiva($habitacion, Carbon::today()->addDays(5), Carbon::today()->addDays(8));

    // Pide un rango que no se solapa con la reserva existente (empieza el día siguiente al check-out).
    $response = $this->getJson('/reserva/habitaciones-disponibles?desde=' . Carbon::today()->addDays(9)->toDateString() . '&hasta=' . Carbon::today()->addDays(10)->toDateString());

    $ids = collect($response->json('habitaciones'))->pluck('id');
    expect($ids)->toContain($habitacion->id);
});

it('excluye del solapamiento la reserva indicada en excluir_reserva (para editar sin bloquearse a sí misma)', function () {
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today()->addDays(5), Carbon::today()->addDays(8));

    $response = $this->getJson('/reserva/habitaciones-disponibles?desde=' . Carbon::today()->addDays(6)->toDateString()
        . '&hasta=' . Carbon::today()->addDays(7)->toDateString()
        . '&excluir_reserva=' . $reserva->id);

    $ids = collect($response->json('habitaciones'))->pluck('id');
    expect($ids)->toContain($habitacion->id);
});

it('exige desde y hasta', function () {
    $response = $this->getJson('/reserva/habitaciones-disponibles');

    $response->assertStatus(422);
});

it('exige permiso sobre el módulo reserva', function () {
    auth()->guard(session('auth_guard'))->logout();

    $response = $this->getJson('/reserva/habitaciones-disponibles?desde=' . Carbon::today()->toDateString() . '&hasta=' . Carbon::today()->addDay()->toDateString());

    $response->assertRedirect(route('login'));
});
