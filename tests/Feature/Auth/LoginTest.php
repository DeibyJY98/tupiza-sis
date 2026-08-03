<?php

use Illuminate\Support\Facades\Auth;

it('autentica con el guard correspondiente al rol y guarda ese guard en la sesión', function () {
    $rolAdmin = crearRol('administrador'); // id 1 => guard "administrador" en AuthController
    [$user, $password] = crearUsuarioConRol($rolAdmin, 'admin1');

    $response = $this->post('/login', [
        'username' => $user->username,
        'password' => $password,
    ]);

    $response->assertRedirect(route('mostrar.reserva'));
    expect(session('auth_guard'))->toBe('administrador');
    expect(Auth::guard('administrador')->check())->toBeTrue();
    expect(Auth::guard('administrador')->id())->toBe($user->id);

    // Los demás guards no deben quedar autenticados con este login.
    expect(Auth::guard('cliente')->check())->toBeFalse();
    expect(Auth::guard('recepcionista')->check())->toBeFalse();
});

it('rechaza una contraseña incorrecta sin autenticar ningún guard', function () {
    $rolAdmin = crearRol('administrador');
    [$user] = crearUsuarioConRol($rolAdmin, 'admin1');

    $response = $this->post('/login', [
        'username' => $user->username,
        'password' => 'contraseña-incorrecta',
    ]);

    $response->assertSessionHas('password');
    expect(session('auth_guard'))->toBeNull();
    expect(Auth::guard('administrador')->check())->toBeFalse();
});

it('rechaza un usuario que no existe', function () {
    $response = $this->post('/login', [
        'username' => 'no-existe',
        'password' => 'lo-que-sea',
    ]);

    $response->assertSessionHas('errorUser');
});

it('logout cierra la sesión del guard activo', function () {
    $rolAdmin = crearRol('administrador');
    [$user, $password] = crearUsuarioConRol($rolAdmin, 'admin1');

    $this->post('/login', ['username' => $user->username, 'password' => $password]);
    expect(Auth::guard('administrador')->check())->toBeTrue();

    $this->get('/logout');

    expect(Auth::guard('administrador')->check())->toBeFalse();
    expect(session('auth_guard'))->toBeNull();
});
