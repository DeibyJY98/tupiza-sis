<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportaPdf;
use App\Models\Caracteristica;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CaracteristicaController extends Controller
{
    use ExportaPdf;

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
        catch (ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.caracteristica');
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
                return back()->with('error', 'No se encontró la característica.');
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

        return redirect()->route('mostrar.caracteristica');
    }

    public function destroy(Request $request)
    {
        $dato = Caracteristica::find($request->inputIdEliminar);

        if (!$dato) {
            return back()->with('error', 'No se encontró la característica.');
        }

        $dato->update(['estado' => 0]);

        return redirect()->route('mostrar.caracteristica');
    }

    public function exportarPdf(Request $request)
    {
        $consulta = Caracteristica::query();

        if ($request->filled('ids')) {
            $consulta->whereIn('id', $request->input('ids'));
        }

        $filas = $consulta->get()->map(fn (Caracteristica $caracteristica) => [
            $caracteristica->id,
            $caracteristica->nombre,
            $caracteristica->estado == 1 ? 'Activo' : 'Inactivo',
        ])->all();

        return $this->generarPdf(
            'Reporte de Características',
            ['ID', 'Nombre', 'Estado'],
            $filas,
            'caracteristicas.pdf'
        );
    }

    private $rules = [
        'nombre.required' => 'El nombre es obligatorio.',
        'nombre.string' => 'El nombre debe ser texto.',
        'nombre.max' => 'El nombre no puede exceder 255 caracteres.',

        'estado.required' => 'El estado es obligatorio.',
        'estado.numeric' => 'El estado debe ser un número.',
    ];
}
