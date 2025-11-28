<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index()
    {
        $datos = Cliente::get();       
        $datos = $datos->map->toShow();
      
        return view("cliente.index",compact('datos'));
    }

    public function store(Request $request)
    {
        //
    }

    public function update(Request $request, Cliente $cliente)
    {
        //
    }

    public function destroy(Cliente $cliente)
    {
        //
    }
}
