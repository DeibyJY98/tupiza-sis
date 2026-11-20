<?php

use App\Models\HabitacionServicioExtra;
use App\Models\Reserva;
use App\Models\ServicioExtra;
use Carbon\Carbon;

// Pruebas de los tres puntos P0 de md/GUIA-MEJORAS.md, siguiendo exactamente los pasos
// de "Cómo validarlo" de cada uno: solapamiento en el límite del check-out (P0.1),
// costo recalculado en servidor pese a un valor manipulado (P0.2), y ausencia de
// reservas huérfanas cuando falla un paso intermedio del registro (P0.3).
// loguearComoTrabajador() viene de TrabajadorAutomaticoTest.php: loguea a un usuario
// vinculado a un trabajador real, así el id_trabajador se resuelve solo y estos
// tests no necesitan preocuparse por ese campo.
beforeEach(function () {
    loguearComoTrabajador('recep_' . uniqid());
});

it('P0.1: permite una reserva que empieza el mismo día del check-out de otra (se cobra por noches)', function () {
    $habitacion = crearHabitacionDePrueba();
    crearReservaActiva($habitacion, Carbon::today()->addDays(10), Carbon::today()->addDays(12));
    [$cliente] = crearClienteYTrabajadorDePrueba();

    // El día 12 solo es de salida de la primera reserva (check-out 11:00), el nuevo huésped
    // ingresa desde las 14:00: no comparten ninguna noche, así que debe permitirse.
    $response = $this->post('/reserva', [
        'costo_total' => 999, // valor irrelevante, el servidor lo ignora igual
        'fecha_inicio' => Carbon::today()->addDays(12)->toDateString(),
        'fecha_fin' => Carbon::today()->addDays(14)->toDateString(),
        'estado' => 1,
        'id_cliente' => $cliente->id,
        'id_habitacion' => $habitacion->id,
    ]);

    $response->assertRedirect(route('mostrar.reserva'));
    expect(Reserva::count())->toBe(2);
});

it('P0.1: rechaza una reserva que comparte alguna noche con otra activa', function () {
    $habitacion = crearHabitacionDePrueba();
    crearReservaActiva($habitacion, Carbon::today()->addDays(10), Carbon::today()->addDays(12));
    [$cliente] = crearClienteYTrabajadorDePrueba();

    // La noche del 11 ya está ocupada: debe rechazar.
    $response = $this->post('/reserva', [
        'fecha_inicio' => Carbon::today()->addDays(11)->toDateString(),
        'fecha_fin' => Carbon::today()->addDays(14)->toDateString(),
        'estado' => 1,
        'id_cliente' => $cliente->id,
        'id_habitacion' => $habitacion->id,
    ]);

    $response->assertSessionHas('error');
    expect(Reserva::count())->toBe(1); // solo la reserva original, la nueva no se creó
});

it('P0.1: permite reservar después del día de check-out', function () {
    $habitacion = crearHabitacionDePrueba();
    crearReservaActiva($habitacion, Carbon::today()->addDays(10), Carbon::today()->addDays(12));
    [$cliente] = crearClienteYTrabajadorDePrueba();

    $response = $this->post('/reserva', [
        'fecha_inicio' => Carbon::today()->addDays(13)->toDateString(),
        'fecha_fin' => Carbon::today()->addDays(15)->toDateString(),
        'estado' => 1,
        'id_cliente' => $cliente->id,
        'id_habitacion' => $habitacion->id,
    ]);

    $response->assertRedirect(route('mostrar.reserva'));
    expect(Reserva::count())->toBe(2);
});

it('P0.2: el costo total se recalcula en el servidor e ignora el valor manipulado del formulario', function () {
    $habitacion = crearHabitacionDePrueba(); // TipoHabitacion con precio 100
    [$cliente] = crearClienteYTrabajadorDePrueba();
    $extra = ServicioExtra::create(['nombre' => 'Desayuno', 'descripcion' => 'Buffet', 'precio' => 50, 'estado' => 1]);

    // 2 noches (inicio hoy+1, fin hoy+3) x 100 + 50 del extra = 250.
    // El formulario intenta mandar 0.01 (manipulado desde el navegador).
    $this->post('/reserva', [
        'costo_total' => 0.01,
        'fecha_inicio' => Carbon::today()->addDay()->toDateString(),
        'fecha_fin' => Carbon::today()->addDays(3)->toDateString(),
        'estado' => 1,
        'id_cliente' => $cliente->id,
        'id_habitacion' => $habitacion->id,
        'servicios_extra' => [$extra->id],
    ])->assertRedirect(route('mostrar.reserva'));

    $reserva = Reserva::latest('id')->firstOrFail();
    expect((float) $reserva->costo_total)->toBe(250.0);
});

it('P0.2: al editar fechas u habitación, el costo se vuelve a calcular en el servidor', function () {
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today()->addDay(), Carbon::today()->addDays(2));

    // Pasa de 1 a 3 noches; el cliente manda un costo inventado que debe ser ignorado.
    $this->post('/reserva/editar', [
        'id' => $reserva->id,
        'fecha_inicio' => Carbon::today()->addDay()->toDateString(),
        'fecha_fin' => Carbon::today()->addDays(4)->toDateString(),
        'id_habitacion' => $habitacion->id,
        'costo_total' => 1,
    ], ['X-Requested-With' => 'XMLHttpRequest'])->assertJson(['success' => true]);

    expect((float) $reserva->fresh()->costo_total)->toBe(300.0); // 3 noches x 100
});

it('P0.3: si falla la asociación de un servicio extra, no queda una reserva huérfana', function () {
    $habitacion = crearHabitacionDePrueba();
    [$cliente] = crearClienteYTrabajadorDePrueba();
    $extra = ServicioExtra::create(['nombre' => 'Spa', 'descripcion' => 'Masaje', 'precio' => 80, 'estado' => 1]);

    $reservasAntes = Reserva::count();

    // Simula un fallo real a mitad del registro (ej. un error de base de datos)
    // justo al intentar asociar el servicio extra, cuando la reserva ya se creó.
    HabitacionServicioExtra::creating(function () {
        throw new \Exception('Fallo simulado para probar que no queda una reserva huérfana');
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
    expect(Reserva::count())->toBe($reservasAntes); // el rollback deshizo la reserva creada
});
