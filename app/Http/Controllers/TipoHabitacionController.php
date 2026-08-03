<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportaPdf;
use App\Models\TipoHabitacion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TipoHabitacionController extends Controller
{
    use ExportaPdf;

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
                'cant_cama' => 'required|numeric|min:1',
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
        catch (ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.tipo_habitacion');
    }

    public function update(Request $request)
    {
        try {
            $modificar = $request->validate([
                'id' => 'required|exists:tipo_habitacions,id',
                'nombre' => 'sometimes|string|max:255',
                'descripcion' => 'sometimes|string|max:255',
                'cant_cama' => 'sometimes|numeric|min:1',
                'precio' => 'sometimes|numeric|min:0',
                'estado' => 'sometimes|numeric',
            ], $this->rules);

            $dato = TipoHabitacion::find($request->id);

            if (!$dato) {
                return back()->with('error', 'No se encontró el tipo de habitación.');
            }

            $dato->update($modificar);
        }
        catch (ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.tipo_habitacion');
    }

    public function destroy(Request $request)
    {
        $dato = TipoHabitacion::find($request->inputIdEliminar);

        if (!$dato) {
            return back()->with('error', 'No se encontró el tipo de habitación.');
        }

        $dato->update(['estado' => 0]);

        return redirect()->route('mostrar.tipo_habitacion');
    }

    public function exportarPdf(Request $request)
    {
        $consulta = TipoHabitacion::query();

        if ($request->filled('ids')) {
            $consulta->whereIn('id', $request->input('ids'));
        }

        $filas = $consulta->get()->map(fn (TipoHabitacion $tipo) => [
            $tipo->id,
            $tipo->nombre,
            $tipo->descripcion,
            $tipo->cant_cama,
            $tipo->precio,
            $tipo->estado == 1 ? 'Activo' : 'Inactivo',
        ])->all();

        return $this->generarPdf(
            'Reporte de Tipos de Habitación',
            ['ID', 'Nombre', 'Descripción', 'Camas', 'Precio', 'Estado'],
            $filas,
            'tipos-habitacion.pdf'
        );
    }

    private $rules = [
        'nombre.required' => 'El nombre es obligatorio.',
        'nombre.string' => 'El nombre debe ser texto.',
        'nombre.max' => 'El nombre no puede exceder 255 caracteres.',

        'descripcion.required' => 'La descripción es obligatoria.',
        'descripcion.string' => 'La descripción debe ser texto.',
        'descripcion.max' => 'La descripción no puede exceder 255 caracteres.',

        'cant_cama.required' => 'La cantidad de camas es obligatoria.',
        'cant_cama.numeric' => 'La cantidad de camas debe ser un número.',
        'cant_cama.min' => 'Debe haber al menos 1 cama.',

        'precio.required' => 'El precio es obligatorio.',
        'precio.numeric' => 'El precio debe ser un número.',
        'precio.min' => 'El precio no puede ser negativo.',

        'estado.required' => 'El estado es obligatorio.',
        'estado.numeric' => 'El estado debe ser un número.',
    ];
}
