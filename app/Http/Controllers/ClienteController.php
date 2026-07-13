<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClienteController extends Controller
{
    public function index()
    {
        $datos = Cliente::get();
        $datos = $datos->map->toShow();
        $personas = Persona::get();

        return view("cliente.index",compact('datos','personas'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nit' => 'required|integer',
                'estado' => 'required|numeric',
                'id_persona' => 'required|exists:personas,id',
            ], $this->rules);

            $nuevo = [
                'nit' => $request->input('nit'),
                'estado' => $request->input('estado'),
                'id_persona' => $request->input('id_persona'),
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

        return redirect()->route('mostrar.cliente')->with('success', 'Cliente creado correctamente.');
    }

    public function update(Request $request)
    {
        try {
            $modificar = $request->validate([
                'id' => 'required|exists:clientes,id',
                'nit' => 'sometimes|integer',
                'estado' => 'sometimes|numeric',
                'id_persona' => 'sometimes|exists:personas,id',
            ], $this->rules);

            $dato = Cliente::find($request->id);

            if (!$dato) {
                return back()->with('error', 'El cliente no existe.');
            }

            $dato->update($modificar);
        }
        catch(ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.cliente')->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Request $request)
    {
        try {
            $dato = Cliente::find($request->inputIdEliminar);
            if ($dato) {
                $dato->delete();
                return redirect()->route('mostrar.cliente')->with('success', 'Cliente eliminado correctamente.');
            }
            return redirect()->route('mostrar.cliente')->with('error', 'El cliente no existe.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el cliente: ' . $e->getMessage());
        }
    }

    private $rules = [
        'nit.required' => 'El NIT es obligatorio.',
        'nit.integer' => 'El NIT debe ser numérico.',

        'estado.required' => 'El estado es obligatorio.',
        'estado.numeric' => 'El estado debe ser un número.',

        'id_persona.required' => 'Debe seleccionar una persona.',
        'id_persona.exists' => 'La persona seleccionada no existe.',
    ];
}
