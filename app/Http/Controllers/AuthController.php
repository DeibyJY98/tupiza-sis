<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // Intentos fallidos permitidos por usuario + IP antes de bloquear temporalmente el login
    private const MAX_INTENTOS = 5;
    private const SEGUNDOS_BLOQUEO = 60;
    private const MENSAJE_CREDENCIALES = 'Usuario o contraseña incorrectos.';

    public function welcome()
    {
        return view('welcome');
    }
    
    public function loginView()
    {
        return view('login');
    }
    
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',           
        ]);
        
        // Límite de intentos (anti fuerza bruta): clave por usuario + IP
        $clave = Str::lower($request->username) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            $segundos = RateLimiter::availableIn($clave);
            return back()->with('password', "Demasiados intentos fallidos. Intente nuevamente en {$segundos} segundos.");
        }

        $user = User::with('rol')->where('username', $request->username)->first();

        // Mensaje único para usuario inexistente o contraseña incorrecta: no revela qué
        // usuarios existen (enumeración de usuarios). Si el usuario no existe se hace igual
        // una comprobación de hash para que el tiempo de respuesta sea similar.
        if (!$user) {
            Hash::check($request->password, Hash::make('contrasena-ficticia'));
            RateLimiter::hit($clave, self::SEGUNDOS_BLOQUEO);
            return back()->with('password', self::MENSAJE_CREDENCIALES);
        }

        switch($user->id_rol)
        {
            case 1:
                $guard = 'administrador';
                break;
            case 2:
                $guard = 'recepcionista';
                break;
            case 3:
                $guard = 'cliente';
                break;
            default:
                // Cualquier rol creado desde la UI (Gestión de Roles) no tiene un guard
                // con su propio nombre en config/auth.php, así que se autentica con el
                // guard genérico "web" en vez de bloquear el login. Ninguno de los guards
                // nombrados tiene lógica propia en el resto de la app (todos comparten el
                // mismo provider "users" y en todos lados solo se usa
                // Auth::guard(session('auth_guard')) de forma genérica), así que esto no
                // cambia nada para los roles que ya tenían su guard dedicado.
                $guard = 'web';
                break;
        }
        //dd( $user, $guard);

        $credentials = $request->only('username', 'password');

        if (Auth::guard($guard)->attempt($credentials)){
            // Recordar con qué guard inició sesión, para poder resolverlo en middlewares/vistas
            RateLimiter::clear($clave);
            $request->session()->put('auth_guard', $guard);

            return redirect()->route('dashboard');
        }
        else
        {
            RateLimiter::hit($clave, self::SEGUNDOS_BLOQUEO);
            return back()->with('password', self::MENSAJE_CREDENCIALES);
        }

    }

    public function logout(Request $request)
    {
        $guard = $request->session()->get('auth_guard', 'web');
        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome');
    }
}
