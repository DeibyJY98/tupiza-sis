<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Crea un rol de prueba. El orden de creación dentro de un test determina su id
 * autoincremental, que es lo que AuthController::login usa para resolver el guard
 * (1 => administrador, 2 => recepcionista, 3 => cliente).
 */
function crearRol(string $nombre): \App\Models\Rol
{
    return \App\Models\Rol::create(['nombre' => $nombre, 'estado' => 1]);
}

/**
 * Crea una Persona + User asociados a un rol dado.
 *
 * @return array{0: \App\Models\User, 1: string} [usuario, contraseña en texto plano]
 */
function crearUsuarioConRol(\App\Models\Rol $rol, string $username): array
{
    $persona = \App\Models\Persona::create([
        'nombre' => 'Test',
        'apellido' => 'Usuario',
        'cedula' => fake()->unique()->numerify('########'),
        'celular' => fake()->numerify('########'),
        'correo' => $username.'@test.com',
        'estado' => 1,
    ]);

    $password = 'secret123';

    $user = \App\Models\User::create([
        'username' => $username,
        'email' => $username.'@test.com',
        'password' => $password,
        'estado' => 1,
        'id_rol' => $rol->id,
        'id_persona' => $persona->id,
    ]);

    return [$user, $password];
}
