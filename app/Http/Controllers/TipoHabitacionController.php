<?php

namespace App\Http\Controllers;

use App\Models\TipoHabitacion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TipoHabitacionController extends Controller
{
    public function index()
    {
        $datos = TipoHabitacion::get();
        $datos = $datos->map->toShow();

        return view("tipoHabitacion.index", compact('datos'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'required|string|max:255',
                'cant_cama' => 'required|integer|min:1',
                'precio' => 'required|numeric|min:0',
                'estado' => 'required|numeric',
            ], $this->rules);

            $nuevo = [
                'nombre' => $request->input('nombre'),
                'descripcion' => $request->input('descripcion'),
                'cant_cama' => $request->input('cant_cama'),
                'precio' => $request->input('precio'),
                'estado' => $request->input('estado'),
            ];

            TipoHabitacion::create($nuevo);
        }
        catch(ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.tipo_habitacion')->with('success', 'Tipo de habitación creado correctamente.');
    }

    public function update(Request $request)
    {
        try {
            $modificar = $request->validate([
                'id' => 'required|exists:tipo_habitacions,id',
                'nombre' => 'sometimes|string|max:255',
                'descripcion' => 'sometimes|string|max:255',
                'cant_cama' => 'sometimes|integer|min:1',
                'precio' => 'sometimes|numeric|min:0',
                'estado' => 'sometimes|numeric',
            ], $this->rules);

            $dato = TipoHabitacion::find($request->id);

            if (!$dato) {
                return back()->with('error', 'El tipo de habitación no existe.');
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

        return redirect()->route('mostrar.tipo_habitacion')->with('success', 'Tipo de habitación actualizado correctamente.');
    }

    public function destroy(Request $request)
    {
        try {
            $dato = TipoHabitacion::find($request->inputIdEliminar);
            if ($dato) {
                $dato->delete();
                return redirect()->route('mostrar.tipo_habitacion')->with('success', 'Tipo de habitación eliminado correctamente.');
            }
            return redirect()->route('mostrar.tipo_habitacion')->with('error', 'El tipo de habitación no existe.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el tipo de habitación: ' . $e->getMessage());
        }
    }

    private $rules = [
        'nombre.required' => 'El nombre es obligatorio.',
        'nombre.string' => 'El nombre debe ser texto.',

        'descripcion.required' => 'La descripción es obligatoria.',
        'descripcion.string' => 'La descripción debe ser texto.',

        'cant_cama.required' => 'La cantidad de camas es obligatoria.',
        'cant_cama.integer' => 'La cantidad de camas debe ser un número entero.',
        'cant_cama.min' => 'Debe haber al menos una cama.',

        'precio.required' => 'El precio es obligatorio.',
        'precio.numeric' => 'El precio debe ser un número.',
        'precio.min' => 'El precio no puede ser negativo.',

        'estado.required' => 'El estado es obligatorio.',
        'estado.numeric' => 'El estado debe ser un número.',
    ];
}
