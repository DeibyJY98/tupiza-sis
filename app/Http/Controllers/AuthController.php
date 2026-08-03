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
                return back()->with('errorUser', 'Rol de usuario no válido');
        }
        //dd( $user, $guard);

        $credentials = $request->only('username', 'password');

        if (Auth::guard($guard)->attempt($credentials)){
            // Recordar con qué guard inició sesión, para poder resolverlo en middlewares/vistas
            $request->session()->put('auth_guard', $guard);

            return redirect()->route('mostrar.reserva');
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
