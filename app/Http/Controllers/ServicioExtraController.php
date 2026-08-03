<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportaPdf;
use App\Models\ServicioExtra;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ServicioExtraController extends Controller
{
    use ExportaPdf;

    public function index()
    {
        $datos = ServicioExtra::get();
        $datos = $datos->map->toShow();

        return view("servicioExtra.index", compact('datos'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'required|string|max:255',
                'precio' => 'required|numeric|min:0',
                'estado' => 'required|numeric',
            ], $this->rules);

            $nuevo = [
                'nombre' => $request->input('nombre'),
                'descripcion' => $request->input('descripcion'),
                'precio' => $request->input('precio'),
                'estado' => $request->input('estado'),
            ];

            ServicioExtra::create($nuevo);
        }
        catch (ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.servicio_extra');
    }

    public function update(Request $request)
    {
        try {
            $modificar = $request->validate([
                'id' => 'required|exists:servicio_extras,id',
                'nombre' => 'sometimes|string|max:255',
                'descripcion' => 'sometimes|string|max:255',
                'precio' => 'sometimes|numeric|min:0',
                'estado' => 'sometimes|numeric',
            ], $this->rules);

            $dato = ServicioExtra::find($request->id);

            if (!$dato) {
                return back()->with('error', 'No se encontró el servicio extra.');
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

        return redirect()->route('mostrar.servicio_extra');
    }

    public function destroy(Request $request)
    {
        $dato = ServicioExtra::find($request->inputIdEliminar);

        if (!$dato) {
            return back()->with('error', 'No se encontró el servicio extra.');
        }

        $dato->update(['estado' => 0]);

        return redirect()->route('mostrar.servicio_extra');
    }

    public function exportarPdf(Request $request)
    {
        $consulta = ServicioExtra::query();

        if ($request->filled('ids')) {
            $consulta->whereIn('id', $request->input('ids'));
        }

        $filas = $consulta->get()->map(fn (ServicioExtra $servicio) => [
            $servicio->id,
            $servicio->nombre,
            $servicio->descripcion,
            $servicio->precio,
            $servicio->estado == 1 ? 'Activo' : 'Inactivo',
        ])->all();

        return $this->generarPdf(
            'Reporte de Servicios Extras',
            ['ID', 'Nombre', 'Descripción', 'Precio', 'Estado'],
            $filas,
            'servicios-extras.pdf'
        );
    }

    private $rules = [
        'nombre.required' => 'El nombre es obligatorio.',
        'nombre.string' => 'El nombre debe ser texto.',
        'nombre.max' => 'El nombre no puede exceder 255 caracteres.',

        'descripcion.required' => 'La descripción es obligatoria.',
        'descripcion.string' => 'La descripción debe ser texto.',
        'descripcion.max' => 'La descripción no puede exceder 255 caracteres.',

        'precio.required' => 'El precio es obligatorio.',
        'precio.numeric' => 'El precio debe ser un número.',
        'precio.min' => 'El precio no puede ser negativo.',

        'estado.required' => 'El estado es obligatorio.',
        'estado.numeric' => 'El estado debe ser un número.',
    ];
}
