<?php

use App\Models\Cliente;
use App\Models\Habitacion;
use App\Models\HabitacionReserva;
use App\Models\Persona;
use App\Models\Reserva;
use App\Models\Trabajador;
use App\Models\TipoHabitacion;
use App\Services\ReservaService;
use Carbon\Carbon;

// Nota: HabitacionReservaObserver sobreescribe automáticamente este estado inicial en
// cuanto se crea una HabitacionReserva ligada a una reserva activa (0 = ocupada,
// 1 = disponible), así que el valor pasado aquí solo importa antes de esa primera reserva.
function crearHabitacionDePrueba(int $estado = 1): Habitacion
{
    $tipo = TipoHabitacion::create([
        'nombre' => 'Individual',
        'descripcion' => 'Habitación individual',
        'cant_cama' => 1,
        'precio' => 100,
        'estado' => 1,
    ]);

    return Habitacion::create([
        'numero_habitacion' => '101',
        'planta' => '1',
        'estado' => $estado,
        'id_tipo_habitacion' => $tipo->id,
    ]);
}

function crearReservaActiva(Habitacion $habitacion, string $fechaInicio, string $fechaFin, int $estado = 1): Reserva
{
    $persona = Persona::create([
        'nombre' => 'Cliente', 'apellido' => 'Prueba', 'cedula' => fake()->unique()->numerify('########'),
        'celular' => '70000000', 'correo' => fake()->unique()->safeEmail(), 'estado' => 1,
    ]);
    $cliente = Cliente::create(['nit' => 123, 'razon_social' => 'N/A', 'estado' => 1, 'id_persona' => $persona->id]);

    $personaTrabajador = Persona::create([
        'nombre' => 'Trabajador', 'apellido' => 'Prueba', 'cedula' => fake()->unique()->numerify('########'),
        'celular' => '70000001', 'correo' => fake()->unique()->safeEmail(), 'estado' => 1,
    ]);
    // 'salario' no está en $fillable de Trabajador pero la columna es NOT NULL,
    // así que se asigna directo (bypassea la protección de asignación masiva).
    $trabajador = new Trabajador(['cargo' => 'Recepcionista', 'estado' => 1, 'id_persona' => $personaTrabajador->id]);
    $trabajador->salario = '1500';
    $trabajador->save();

    $reserva = Reserva::create([
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
        'costo_total' => 100,
        'estado' => $estado,
        'id_cliente' => $cliente->id,
        'id_trabajador' => $trabajador->id,
    ]);

    HabitacionReserva::create([
        'monto' => 100,
        'id_reserva' => $reserva->id,
        'id_habitacion' => $habitacion->id,
    ]);

    return $reserva;
}

it('rechaza fechas de inicio en el pasado', function () {
    $habitacion = crearHabitacionDePrueba();
    $servicio = new ReservaService();

    $servicio->validarDisponibilidadHabitacion(
        $habitacion->id,
        Carbon::yesterday(),
        Carbon::tomorrow(),
    );
})->throws(Exception::class, 'No se pueden realizar reservas en fechas pasadas.');

it('rechaza fecha fin anterior a fecha inicio', function () {
    $habitacion = crearHabitacionDePrueba();
    $servicio = new ReservaService();

    $servicio->validarDisponibilidadHabitacion(
        $habitacion->id,
        Carbon::today()->addDays(5),
        Carbon::today()->addDays(2),
    );
})->throws(Exception::class, 'La fecha de fin no puede ser anterior a la fecha de inicio.');

it('detecta solapamiento de fechas para la misma habitación', function () {
    $habitacion = crearHabitacionDePrueba();
    crearReservaActiva($habitacion, Carbon::today()->addDays(1), Carbon::today()->addDays(5));

    $servicio = new ReservaService();

    $servicio->validarDisponibilidadHabitacion(
        $habitacion->id,
        Carbon::today()->addDays(3),
        Carbon::today()->addDays(7),
    );
})->throws(Exception::class, 'La habitación no está disponible para las fechas seleccionadas.');

it('permite reservar cuando las fechas no se solapan con ninguna reserva activa', function () {
    $habitacion = crearHabitacionDePrueba();
    crearReservaActiva($habitacion, Carbon::today()->addDays(1), Carbon::today()->addDays(5));

    $servicio = new ReservaService();

    $resultado = $servicio->validarDisponibilidadHabitacion(
        $habitacion->id,
        Carbon::today()->addDays(10),
        Carbon::today()->addDays(12),
    );

    expect($resultado)->toBeTrue();
});

it('ignora reservas canceladas (estado distinto de activo) al validar solapamiento', function () {
    $habitacion = crearHabitacionDePrueba();
    // estado 0 = no activa (cancelada), no debe bloquear el rango de fechas.
    crearReservaActiva($habitacion, Carbon::today()->addDays(1), Carbon::today()->addDays(5), estado: 0);

    $servicio = new ReservaService();

    $resultado = $servicio->validarDisponibilidadHabitacion(
        $habitacion->id,
        Carbon::today()->addDays(2),
        Carbon::today()->addDays(3),
    );

    expect($resultado)->toBeTrue();
});

it('libera la habitación cuando ya no queda ninguna reserva activa que cubra hoy', function () {
    $habitacion = crearHabitacionDePrueba();
    // Cubre la fecha de hoy, por eso el observer marca la habitación como ocupada (0) al crearla.
    $reserva = crearReservaActiva($habitacion, Carbon::today(), Carbon::today()->addDays(3));
    expect($habitacion->fresh()->estado)->toBe(0);

    $servicio = new ReservaService();
    $servicio->liberarHabitacion($reserva->id);

    expect($habitacion->fresh()->estado)->toBe(1); // 1 = disponible
});

it('no libera la habitación si otra reserva activa también cubre hoy', function () {
    $habitacion = crearHabitacionDePrueba();
    $reservaA = crearReservaActiva($habitacion, Carbon::today(), Carbon::today()->addDays(3));
    crearReservaActiva($habitacion, Carbon::today()->subDay(), Carbon::today()->addDays(5));

    $servicio = new ReservaService();
    $servicio->liberarHabitacion($reservaA->id);

    expect($habitacion->fresh()->estado)->toBe(0); // sigue ocupada por la segunda reserva, que también cubre hoy
});

it('no marca como ocupada una reserva activa que todavía no empieza ni una que ya terminó', function () {
    $habitacion = crearHabitacionDePrueba();
    crearReservaActiva($habitacion, Carbon::today()->addDays(5), Carbon::today()->addDays(8)); // futura
    crearReservaActiva($habitacion, Carbon::today()->subDays(10), Carbon::today()->subDays(7)); // pasada

    expect($habitacion->fresh()->estado)->toBe(1); // 1 = disponible: ninguna cubre la fecha de hoy
});
