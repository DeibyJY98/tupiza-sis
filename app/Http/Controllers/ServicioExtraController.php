<?php

namespace App\Http\Controllers;

use App\Models\ServicioExtra;
use Illuminate\Http\Request;

class ServicioExtraController extends Controller
{
    public function index()
    {
        $datos = ServicioExtra::get();
        $datos = $datos->map->toShow();
        
        return view("ServicioExtra.index", compact('datos'));

    }

    public function store(Request $request)
    {
        //
    }

    public function update(Request $request, ServicioExtra $servicioExtra)
    {
        //
    }

    public function destroy(ServicioExtra $servicioExtra)
    {
        //
    }
}
