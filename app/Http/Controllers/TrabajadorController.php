<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportaPdf;
use App\Models\Trabajador;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TrabajadorController extends Controller
{
    use ExportaPdf;

    public function index()
    {
        $datos = Trabajador::get();
        $datos = $datos->map->toShow();

        $personas = Persona::get();

        return view("trabajador.index", compact('datos', 'personas'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'cargo' => 'required|string|max:255',
                'salario' => 'required|numeric|min:0',
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
        catch (ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.trabajador');
    }

    public function update(Request $request)
    {
        try {
            $modificar = $request->validate([
                'id' => 'required|exists:trabajadors,id',
                'cargo' => 'sometimes|string|max:255',
                'salario' => 'sometimes|numeric|min:0',
                'estado' => 'sometimes|numeric',
                'id_persona' => 'sometimes|exists:personas,id',
            ], $this->rules);

            $dato = Trabajador::find($request->id);

            if (!$dato) {
                return back()->with('error', 'No se encontró el trabajador.');
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

        return redirect()->route('mostrar.trabajador');
    }

    public function destroy(Request $request)
    {
        $dato = Trabajador::find($request->inputIdEliminar);

        if (!$dato) {
            return back()->with('error', 'No se encontró el trabajador.');
        }

        $dato->update(['estado' => 0]);

        return redirect()->route('mostrar.trabajador');
    }

    public function exportarPdf(Request $request)
    {
        $consulta = Trabajador::with('persona');

        if ($request->filled('ids')) {
            $consulta->whereIn('id', $request->input('ids'));
        }

        $filas = $consulta->get()->map(fn (Trabajador $trabajador) => [
            $trabajador->id,
            trim(optional($trabajador->persona)->nombre . ' ' . optional($trabajador->persona)->apellido),
            $trabajador->cargo,
            $trabajador->salario,
            $trabajador->estado == 1 ? 'Activo' : 'Inactivo',
        ])->all();

        return $this->generarPdf(
            'Reporte de Trabajadores',
            ['ID', 'Nombre', 'Cargo', 'Salario', 'Estado'],
            $filas,
            'trabajadores.pdf'
        );
    }

    private $rules = [
        'cargo.required' => 'El cargo es obligatorio.',
        'cargo.string' => 'El cargo debe ser texto.',
        'cargo.max' => 'El cargo no puede exceder 255 caracteres.',

        'salario.required' => 'El salario es obligatorio.',
        'salario.numeric' => 'El salario debe ser un número.',
        'salario.min' => 'El salario no puede ser negativo.',

        'estado.required' => 'El estado es obligatorio.',
        'estado.numeric' => 'El estado debe ser un número.',

        'id_persona.required' => 'Debe seleccionar una persona.',
        'id_persona.exists' => 'La persona seleccionada no existe.',
    ];
}
