<?php

namespace App\Http\Middleware;

use App\Models\DetalleRol;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RolMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard($request->session()->get('auth_guard'))->user();
        if(!$user){
            return redirect()->route('login');
        }

        $permisos = DetalleRol::where('id_rol', $user->id_rol)->select('id_permiso')->get();

        foreach ($permisos as $permiso) {        
            if($permiso->id_permiso == 1){  // Cambiado de "1" a 1 para consistencia con tipos
                return $next($request);
            }
        }         
        return back()->with('autorizacion','No tiene permiso para ingresar');             
    }
}
