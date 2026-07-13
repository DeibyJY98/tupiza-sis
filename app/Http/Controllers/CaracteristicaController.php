<?php

namespace App\Http\Controllers;

use App\Models\Caracteristica;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CaracteristicaController extends Controller
{
    public function index()
    {
        $datos = Caracteristica::get();
        $datos = $datos->map->toShow();

        return view("caracteristica.index", compact('datos'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:255',
                'estado' => 'required|numeric',
            ], $this->rules);

            $nuevo = [
                'nombre' => $request->input('nombre'),
                'estado' => $request->input('estado'),
            ];

            Caracteristica::create($nuevo);
        }
        catch(ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.caracteristica')->with('success', 'Característica creada correctamente.');
    }

    public function update(Request $request)
    {
        try {
            $modificar = $request->validate([
                'id' => 'required|exists:caracteristicas,id',
                'nombre' => 'sometimes|string|max:255',
                'estado' => 'sometimes|numeric',
            ], $this->rules);

            $dato = Caracteristica::find($request->id);

            if (!$dato) {
                return back()->with('error', 'La característica no existe.');
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

        return redirect()->route('mostrar.caracteristica')->with('success', 'Característica actualizada correctamente.');
    }

    public function destroy(Request $request)
    {
        try {
            $dato = Caracteristica::find($request->inputIdEliminar);
            if ($dato) {
                $dato->delete();
                return redirect()->route('mostrar.caracteristica')->with('success', 'Característica eliminada correctamente.');
            }
            return redirect()->route('mostrar.caracteristica')->with('error', 'La característica no existe.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar la característica: ' . $e->getMessage());
        }
    }

    private $rules = [
        'nombre.required' => 'El nombre es obligatorio.',
        'nombre.string' => 'El nombre debe ser texto.',

        'estado.required' => 'El estado es obligatorio.',
        'estado.numeric' => 'El estado debe ser un número.',
    ];
}
