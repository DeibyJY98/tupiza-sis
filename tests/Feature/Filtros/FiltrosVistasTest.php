<?php

// Verifica que las 12 vistas con filtros (búsqueda/estado/fecha) rendericen sin errores
// de Blade y expongan los atributos data-filtro-* que consume public/js/navbar.js.
// Usa los seeders reales del proyecto para tener datos representativos en cada tabla.

beforeEach(function () {
    $this->seed();

    $this->post('/login', ['username' => 'DeibyJY', 'password' => '12345'])
        ->assertRedirect(route('mostrar.reserva'));
});

it('Persona: filtro de texto y estado, sin filtro de fecha', function () {
    $response = $this->get('/persona');
    $response->assertOk();
    $response->assertSee('data-filtro-texto', false);
    $response->assertSee('id="estado"', false);
    $response->assertDontSee('id="fecha_inicio"', false);
});

it('User: filtro de texto y estado, sin filtro de fecha', function () {
    $response = $this->get('/usuario');
    $response->assertOk();
    $response->assertSee('data-filtro-texto', false);
    $response->assertDontSee('id="fecha_inicio"', false);
});

it('Rol: filtro de texto y estado, sin filtro de fecha', function () {
    $response = $this->get('/rol');
    $response->assertOk();
    $response->assertSee('data-filtro-texto', false);
    $response->assertDontSee('id="fecha_inicio"', false);
});

it('Habitacion: filtro de texto y estado, conserva su propio filtro de disponibilidad por fecha', function () {
    $response = $this->get('/habitacion');
    $response->assertOk();
    $response->assertSee('data-filtro-texto', false);
    $response->assertSee('id="filtroFechaInicio"', false);
});

it('Reserva: filtro de texto, estado y fecha', function () {
    $response = $this->get('/reserva');
    $response->assertOk();
    $response->assertSee('data-filtro-texto', false);
    $response->assertSee('data-filtro-fecha', false);
});

it('Pago: filtro de texto, estado y fecha', function () {
    $response = $this->get('/pago');
    $response->assertOk();
    $response->assertSee('data-filtro-texto', false);
    $response->assertSee('data-filtro-fecha', false);
});

it('Cliente: filtro de texto y estado', function () {
    $response = $this->get('/cliente');
    $response->assertOk();
    $response->assertSee('data-filtro-texto', false);
    $response->assertSee('id="estado"', false);
});

it('Trabajador: filtro de texto y estado', function () {
    $response = $this->get('/trabajador');
    $response->assertOk();
    $response->assertSee('data-filtro-texto', false);
    $response->assertSee('id="estado"', false);
});

it('ServicioExtra: filtro de texto y estado', function () {
    $response = $this->get('/servicio_extra');
    $response->assertOk();
    $response->assertSee('data-filtro-texto', false);
    $response->assertSee('id="estado"', false);
});

it('Caracteristica: filtro de texto y estado', function () {
    $response = $this->get('/caracteristica');
    $response->assertOk();
    $response->assertSee('data-filtro-texto', false);
    $response->assertSee('id="estado"', false);
});

it('TipoHabitacion: filtro de texto y estado', function () {
    $response = $this->get('/tipo_habitacion');
    $response->assertOk();
    $response->assertSee('data-filtro-texto', false);
    $response->assertSee('id="estado"', false);
});

it('Permiso: solo filtro de texto, sin estado ni fecha', function () {
    $response = $this->get('/permiso');
    $response->assertOk();
    $response->assertSee('data-filtro-texto', false);
    $response->assertDontSee('id="estado"', false);
    $response->assertDontSee('id="fecha_inicio"', false);
});
