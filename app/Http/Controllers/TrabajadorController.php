<?php

namespace App\Http\Controllers;

use App\Models\Trabajador;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TrabajadorController extends Controller
{
    public function index()
    {
        $datos = Trabajador::get();
        $datos = $datos->map->toShow();
        $personas = Persona::get();

        return view("trabajador.index", compact('datos','personas'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'cargo' => 'required|string|max:255',
                'salario' => 'required|string|max:255',
                'estado' => 'required|numeric',
                'id_persona' => 'required|exists:personas,id',
            ], $this->rules);

            $nuevo = [
                'cargo' => $request->input('cargo'),
                'salario' => $request->input('salario'),
                'estado' => $request->input('estado'),
                'id_persona' => $request->input('id_persona'),
            ];

            Trabajador::create($nuevo);
        }
        catch(ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.trabajador')->with('success', 'Trabajador creado correctamente.');
    }

    public function update(Request $request)
    {
        try {
            $modificar = $request->validate([
                'id' => 'required|exists:trabajadors,id',
                'cargo' => 'sometimes|string|max:255',
                'salario' => 'sometimes|string|max:255',
                'estado' => 'sometimes|numeric',
                'id_persona' => 'sometimes|exists:personas,id',
            ], $this->rules);

            $dato = Trabajador::find($request->id);

            if (!$dato) {
                return back()->with('error', 'El trabajador no existe.');
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

        return redirect()->route('mostrar.trabajador')->with('success', 'Trabajador actualizado correctamente.');
    }

    public function destroy(Request $request)
    {
        try {
            $dato = Trabajador::find($request->inputIdEliminar);
            if ($dato) {
                $dato->delete();
                return redirect()->route('mostrar.trabajador')->with('success', 'Trabajador eliminado correctamente.');
            }
            return redirect()->route('mostrar.trabajador')->with('error', 'El trabajador no existe.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el trabajador: ' . $e->getMessage());
        }
    }

    private $rules = [
        'cargo.required' => 'El cargo es obligatorio.',
        'cargo.string' => 'El cargo debe ser texto.',

        'salario.required' => 'El salario es obligatorio.',
        'salario.string' => 'El salario debe ser texto.',

        'estado.required' => 'El estado es obligatorio.',
        'estado.numeric' => 'El estado debe ser un número.',

        'id_persona.required' => 'Debe seleccionar una persona.',
        'id_persona.exists' => 'La persona seleccionada no existe.',
    ];
}
