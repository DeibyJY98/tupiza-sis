<?php

namespace App\Http\Middleware;

use App\Models\DetalleRol;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
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
        $permisos = DetalleRol::where('id_rol', $user->id_rol)->select('id_permiso')->get();

        foreach ($permisos as $key => $permiso) {        
            if($permiso->id_permiso == "3"){
                return $next($request);
            }
        }         
        return back()->with('autorizacion','no tiene permiso para ingresar'); 
    }
}
