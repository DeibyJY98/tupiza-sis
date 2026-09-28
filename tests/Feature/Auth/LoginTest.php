<?php

use Illuminate\Support\Facades\Auth;

it('autentica con el guard correspondiente al rol y guarda ese guard en la sesión', function () {
    $rolAdmin = crearRol('administrador'); // id 1 => guard "administrador" en AuthController
    [$user, $password] = crearUsuarioConRol($rolAdmin, 'admin1');

    $response = $this->post('/login', [
        'username' => $user->username,
        'password' => $password,
    ]);

    $response->assertRedirect(route('dashboard'));
    expect(session('auth_guard'))->toBe('administrador');
    expect(Auth::guard('administrador')->check())->toBeTrue();
    expect(Auth::guard('administrador')->id())->toBe($user->id);

    // Los demás guards no deben quedar autenticados con este login.
    expect(Auth::guard('cliente')->check())->toBeFalse();
    expect(Auth::guard('recepcionista')->check())->toBeFalse();
});

it('un rol nuevo sin guard propio (creado desde Gestión de Roles) puede iniciar sesión igual, con el guard genérico "web"', function () {
    // administrador=1, recepcionista=2, cliente=3 ya existen en el orden de creación
    // de este test; el 4to rol creado (cualquier nombre, ej. "Gerente") cae en el
    // default del switch de AuthController::login() y antes de este fix era
    // rechazado con "Rol de usuario no válido".
    crearRol('administrador');
    crearRol('recepcionista');
    crearRol('cliente');
    $rolNuevo = crearRol('Gerente');
    [$user, $password] = crearUsuarioConRol($rolNuevo, 'gerente1');

    $response = $this->post('/login', [
        'username' => $user->username,
        'password' => $password,
    ]);

    $response->assertRedirect(route('dashboard'));
    expect(session('auth_guard'))->toBe('web');
    expect(Auth::guard('web')->check())->toBeTrue();
    expect(Auth::guard('web')->id())->toBe($user->id);
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
