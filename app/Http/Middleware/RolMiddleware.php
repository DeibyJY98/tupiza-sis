<?php

namespace App\Http\Middleware;

use App\Models\DetalleRol;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $user = Cache::get('persona');     
        if(!$user){
            return redirect()->route('login');
        }   

        // Asegurarnos de que tenemos un objeto User válido
        if (!$user instanceof \App\Models\User) {
            Cache::forget('persona');
            return redirect()->route('login')->with('error', 'Sesión inválida, por favor inicie sesión nuevamente');
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
