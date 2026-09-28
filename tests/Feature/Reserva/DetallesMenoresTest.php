<?php

use App\Models\HabitacionReserva;
use App\Models\HabitacionServicioExtra;
use App\Models\Reserva;
use App\Models\ServicioExtra;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

// Detalles menores encontrados al revisar los P0 (ver conversación): editar la fecha
// de salida de una reserva ya en curso, editar solo los servicios extras, y que los
// mensajes de error no filtren excepciones internas (SQL, etc.) al usuario.
beforeEach(function () {
    loguearComoTrabajador('recep_' . uniqid());
});

it('permite extender la fecha de salida de una reserva cuya llegada ya pasó', function () {
    $habitacion = crearHabitacionDePrueba();
    // La reserva ya empezó ayer (huésped ya hizo check-in) y hoy se le extiende la estadía.
    $reserva = crearReservaActiva($habitacion, Carbon::yesterday(), Carbon::today());

    $response = $this->post('/reserva/editar', [
        'id' => $reserva->id,
        'fecha_fin' => Carbon::today()->addDays(2)->toDateString(),
        // fecha_inicio no se reenvía: sigue siendo "ayer", ya pasada.
    ], ['X-Requested-With' => 'XMLHttpRequest']);

    $response->assertJson(['success' => true]);
    expect($reserva->fresh()->fecha_fin)->toBe(Carbon::today()->addDays(2)->toDateString());
});

it('rechaza mover fecha_inicio de una reserva hacia el pasado (la regla sigue aplicando si de verdad cambia)', function () {
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today(), Carbon::today()->addDays(3));

    $response = $this->post('/reserva/editar', [
        'id' => $reserva->id,
        'fecha_inicio' => Carbon::yesterday()->toDateString(),
        'fecha_fin' => Carbon::today()->addDays(3)->toDateString(),
    ], ['X-Requested-With' => 'XMLHttpRequest']);

    $response->assertJson(['success' => false]);
    expect($response->json('message'))->toContain('fechas pasadas');
});

it('editar solo los servicios extras actualiza el monto del pivot y sincroniza la asociación real', function () {
    $habitacion = crearHabitacionDePrueba(); // precio 100/noche
    $reserva = crearReservaActiva($habitacion, Carbon::today()->addDay(), Carbon::today()->addDays(2)); // 2 noches

    $desayuno = ServicioExtra::create(['nombre' => 'Desayuno', 'descripcion' => 'Buffet', 'precio' => 30, 'estado' => 1]);
    $spa = ServicioExtra::create(['nombre' => 'Spa', 'descripcion' => 'Masaje', 'precio' => 70, 'estado' => 1]);

    $habitacionReserva = HabitacionReserva::where('id_reserva', $reserva->id)->firstOrFail();

    $response = $this->post('/reserva/editar', [
        'id' => $reserva->id,
        'servicios_extra' => [$desayuno->id, $spa->id],
    ], ['X-Requested-With' => 'XMLHttpRequest']);

    $response->assertJson(['success' => true]);

    // 2 noches x 100 + 30 + 70 = 300
    expect((float) $reserva->fresh()->costo_total)->toBe(300.0)
        ->and((float) $habitacionReserva->fresh()->monto)->toBe(300.0);

    $idsAsociados = HabitacionServicioExtra::where('id_habitacion_reserva', $habitacionReserva->id)
        ->pluck('id_servicio_extra')
        ->sort()
        ->values()
        ->all();
    expect($idsAsociados)->toBe(collect([$desayuno->id, $spa->id])->sort()->values()->all());
});

it('editar la habitación de una reserva varias veces no duplica la habitación asociada (soft delete de habitacion_reservas)', function () {
    $habitacionA = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacionA, Carbon::today()->addDay(), Carbon::today()->addDays(3));

    $habitacionB = crearHabitacionDePrueba();

    // update() borra (soft delete) la fila de habitacion_reservas y crea una nueva
    // cada vez que se envía id_habitacion, incluso si vuelve a ser la misma. Editar
    // dos veces deja 2 filas soft-eliminadas + 1 activa para la misma reserva.
    $this->post('/reserva/editar', [
        'id' => $reserva->id,
        'id_habitacion' => $habitacionB->id,
    ], ['X-Requested-With' => 'XMLHttpRequest'])->assertJson(['success' => true]);

    $this->post('/reserva/editar', [
        'id' => $reserva->id,
        'id_habitacion' => $habitacionB->id,
    ], ['X-Requested-With' => 'XMLHttpRequest'])->assertJson(['success' => true]);

    expect(HabitacionReserva::withTrashed()->where('id_reserva', $reserva->id)->count())->toBe(3);

    // belongsToMany debe ignorar las filas soft-eliminadas del pivot: solo 1 habitación.
    $habitaciones = $reserva->fresh()->habitaciones;
    expect($habitaciones)->toHaveCount(1);
    expect($habitaciones->first()->id)->toBe($habitacionB->id);
});

it('un error inesperado no filtra el mensaje interno al usuario y queda registrado en el log', function () {
    Log::spy();

    $habitacion = crearHabitacionDePrueba();
    [$cliente] = crearClienteYTrabajadorDePrueba();
    $extra = ServicioExtra::create(['nombre' => 'Late check-out', 'descripcion' => '', 'precio' => 20, 'estado' => 1]);

    HabitacionServicioExtra::creating(function () {
        throw new \RuntimeException('SQLSTATE[23000]: detalle interno que no debería verse');
    });

    $response = $this->post('/reserva', [
        'fecha_inicio' => Carbon::today()->addDay()->toDateString(),
        'fecha_fin' => Carbon::today()->addDays(2)->toDateString(),
        'estado' => 1,
        'id_cliente' => $cliente->id,
        'id_habitacion' => $habitacion->id,
        'servicios_extra' => [$extra->id],
    ]);

    $response->assertSessionHas('error');
    expect(session('error'))
        ->not->toContain('SQLSTATE')
        ->toBe('Ocurrió un error al registrar la reserva. Intenta nuevamente.');

    Log::shouldHaveReceived('error')->once();
});
