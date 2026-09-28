<?php

use App\Models\Pago;
use App\Models\Reserva;

// Reutiliza crearReservaConCosto() y otorgarPermisoAlUsuarioActual() (Pest carga
// todos los archivos de test antes de correr, así que las funciones globales de
// PagoValidacionTest.php y CheckInCheckOutTest.php ya están disponibles aquí).

it('en el formulario de crear pago, lista las reservas de más reciente a más antigua', function () {
    loguearComoTrabajador('recep_' . uniqid());
    otorgarPermisoAlUsuarioActual('pago');

    $reservaVieja = crearReservaConCosto(100);
    $reservaNueva = crearReservaConCosto(100);

    $response = $this->get('/pago');

    $response->assertViewHas('reservasDisponibles', function ($reservas) use ($reservaVieja, $reservaNueva) {
        $ids = $reservas->pluck('id')->values()->all();
        $posicionNueva = array_search($reservaNueva->id, $ids);
        $posicionVieja = array_search($reservaVieja->id, $ids);

        return $posicionNueva !== false && $posicionVieja !== false && $posicionNueva < $posicionVieja;
    });
});

it('en el formulario de crear pago, excluye una reserva ya pagada por completo', function () {
    loguearComoTrabajador('recep_' . uniqid());
    otorgarPermisoAlUsuarioActual('pago');

    $reservaPagada = crearReservaConCosto(200);
    Pago::create([
        'fecha' => now()->toDateString(),
        'monto' => 200,
        'comprobante' => 'x.jpg',
        'estado' => 1,
        'id_cliente' => $reservaPagada->id_cliente,
        'id_reserva' => $reservaPagada->id,
    ]);

    $reservaConSaldo = crearReservaConCosto(200);
    Pago::create([
        'fecha' => now()->toDateString(),
        'monto' => 50,
        'comprobante' => 'x.jpg',
        'estado' => 1,
        'id_cliente' => $reservaConSaldo->id_cliente,
        'id_reserva' => $reservaConSaldo->id,
    ]);

    $response = $this->get('/pago');

    $response->assertViewHas('reservasDisponibles', function ($reservas) use ($reservaPagada, $reservaConSaldo) {
        return ! $reservas->contains('id', $reservaPagada->id)
            && $reservas->contains('id', $reservaConSaldo->id);
    });
});

it('ignora los pagos cancelados al decidir si una reserva ya está totalmente pagada', function () {
    loguearComoTrabajador('recep_' . uniqid());
    otorgarPermisoAlUsuarioActual('pago');

    $reserva = crearReservaConCosto(150);
    Pago::create([
        'fecha' => now()->toDateString(),
        'monto' => 150,
        'comprobante' => 'x.jpg',
        'estado' => 0, // cancelado: no cuenta como saldo cubierto
        'id_cliente' => $reserva->id_cliente,
        'id_reserva' => $reserva->id,
    ]);

    $response = $this->get('/pago');

    $response->assertViewHas('reservasDisponibles', fn ($reservas) => $reservas->contains('id', $reserva->id));
});

it('en el modal de editar sigue apareciendo una reserva ya pagada por completo (no se filtra ahí)', function () {
    loguearComoTrabajador('recep_' . uniqid());
    otorgarPermisoAlUsuarioActual('pago');

    $reservaPagada = crearReservaConCosto(300);
    Pago::create([
        'fecha' => now()->toDateString(),
        'monto' => 300,
        'comprobante' => 'x.jpg',
        'estado' => 1,
        'id_cliente' => $reservaPagada->id_cliente,
        'id_reserva' => $reservaPagada->id,
    ]);

    $response = $this->get('/pago');

    $response->assertViewHas('reservas', fn ($reservas) => $reservas->contains('id', $reservaPagada->id));
});

it('Pago::toShow() incluye el detalle de la reserva (fechas, habitaciones y estadía) para la acción Ver', function () {
    $reserva = crearReservaConCosto(400);
    $pago = Pago::create([
        'fecha' => now()->toDateString(),
        'monto' => 400,
        'comprobante' => 'storage/comprobante/RES-1-123.jpg',
        'estado' => 1,
        'id_cliente' => $reserva->id_cliente,
        'id_reserva' => $reserva->id,
    ]);

    $show = $pago->toShow();
    $reserva = $reserva->fresh(); // estado_estadia toma su valor default de la migración recién al releer de la BD

    expect($show['reserva']['fecha_inicio'])->toBe(\Carbon\Carbon::parse($reserva->fecha_inicio)->format('Y-m-d'))
        ->and($show['reserva']['fecha_fin'])->toBe(\Carbon\Carbon::parse($reserva->fecha_fin)->format('Y-m-d'))
        ->and($show['reserva']['estado_estadia'])->toBe($reserva->estado_estadia)
        ->and($show['reserva']['habitaciones'])->toBe($reserva->habitaciones->pluck('numero_habitacion')->implode(', '))
        ->and($show['comprobante'])->toBe('storage/comprobante/RES-1-123.jpg');
});
