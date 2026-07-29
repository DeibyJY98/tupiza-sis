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
        try{
            $request->validate([
                'id'            => 'required|numeric',
                'nit'           => 'required|numeric',
                'razon_social'  => 'required|string|max:255',
                'estado'        => 'required|numeric',
                'id_persona'    => 'required|numeric'
            ], $this->rules);
            
            $nuevo = [

            ];
            
            Cliente::create($nuevo);
        }
        catch(ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
        return redirect()->route('mostrar.cliente');
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
