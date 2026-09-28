<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AuthController extends Controller
{
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
        
        $user = User::with('rol')->where('username', $request->username)->first(); 
        
        if(!$user){
            return back()->with('errorUser', 'El usuario no existe');
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
            $request->session()->put('auth_guard', $guard);

            return redirect()->route('dashboard');
        }
        else
        {
            return back()->with('password', 'la constraseña es incorrecta');
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
