<?php

namespace App\Http\Middleware;

use App\Models\DetalleRol;
use App\Models\Permiso;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermisoModuloMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        $user = Auth::guard($request->session()->get('auth_guard'))->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $idPermiso = Permiso::where('nombre', $modulo)->value('id');

        $tienePermiso = $idPermiso && DetalleRol::where('id_rol', $user->id_rol)
            ->where('id_permiso', $idPermiso)
            ->exists();

        if (!$tienePermiso) {
            return back()->with('autorizacion', 'No tiene permiso para ingresar');
        }

        return $next($request);
    }
}
