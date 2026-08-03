<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificacionController extends Controller
{
    public function marcarLeidas(Request $request)
    {
        if (!Auth::guard($request->session()->get('auth_guard'))->check()) {
            return redirect()->route('login');
        }

        Notificacion::where('leida', false)->update(['leida' => true]);

        return back();
    }
}
