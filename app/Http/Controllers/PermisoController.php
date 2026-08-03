<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportaPdf;
use App\Models\Permiso;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PermisoController extends Controller
{
    use ExportaPdf;

    public function index()
    {
        $datos = Permiso::get();
        $datos = $datos->map->toShow();

        return view("permiso.index", compact('datos'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:255|unique:permisos,nombre',
            ], $this->rules);

            $nuevo = [
                'nombre' => $request->input('nombre'),
            ];

            Permiso::create($nuevo);
        }
        catch (ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.permiso');
    }

    public function update(Request $request)
    {
        try {
            $modificar = $request->validate([
                'id' => 'required|exists:permisos,id',
                'nombre' => 'sometimes|string|max:255|unique:permisos,nombre,'.$request->id,
            ], $this->rules);

            $dato = Permiso::find($request->id);

            if (!$dato) {
                return back()->with('error', 'No se encontró el permiso.');
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

        return redirect()->route('mostrar.permiso');
    }

    public function destroy(Request $request)
    {
        $dato = Permiso::find($request->inputIdEliminar);

        if (!$dato) {
            return back()->with('error', 'No se encontró el permiso.');
        }

        $dato->delete();

        return redirect()->route('mostrar.permiso');
    }

    public function exportarPdf(Request $request)
    {
        $consulta = Permiso::query();

        if ($request->filled('ids')) {
            $consulta->whereIn('id', $request->input('ids'));
        }

        $filas = $consulta->get()->map(fn (Permiso $permiso) => [
            $permiso->id,
            $permiso->nombre,
        ])->all();

        return $this->generarPdf(
            'Reporte de Permisos',
            ['ID', 'Nombre'],
            $filas,
            'permisos.pdf'
        );
    }

    private $rules = [
        'nombre.required' => 'El nombre del permiso es obligatorio.',
        'nombre.string' => 'El nombre debe ser texto.',
        'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
        'nombre.unique' => 'Ya existe un permiso con ese nombre.',
    ];
}
