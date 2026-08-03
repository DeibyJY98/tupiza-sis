<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportaPdf;
use App\Models\Cliente;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClienteController extends Controller
{
    use ExportaPdf;

    public function index()
    {
        $datos = Cliente::get();
        $datos = $datos->map->toShow();

        $personas = Persona::get();

        return view("cliente.index", compact('datos', 'personas'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nit' => 'required|numeric',
                'razon_social' => 'required|string|max:255',
                'estado' => 'required|numeric',
                'id_persona' => 'required|exists:personas,id',
            ], $this->rules);

            $nuevo = [
                'nit' => $request->input('nit'),
                'razon_social' => $request->input('razon_social'),
                'estado' => $request->input('estado'),
                'id_persona' => $request->input('id_persona'),
            ];

            Cliente::create($nuevo);
        }
        catch (ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.cliente');
    }

    public function update(Request $request)
    {
        try {
            $modificar = $request->validate([
                'id' => 'required|exists:clientes,id',
                'nit' => 'sometimes|numeric',
                'razon_social' => 'sometimes|string|max:255',
                'estado' => 'sometimes|numeric',
                'id_persona' => 'sometimes|exists:personas,id',
            ], $this->rules);

            $dato = Cliente::find($request->id);

            if (!$dato) {
                return back()->with('error', 'No se encontró el cliente.');
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

        return redirect()->route('mostrar.cliente');
    }

    public function destroy(Request $request)
    {
        $dato = Cliente::find($request->inputIdEliminar);

        if (!$dato) {
            return back()->with('error', 'No se encontró el cliente.');
        }

        $dato->update(['estado' => 0]);

        return redirect()->route('mostrar.cliente');
    }

    public function exportarPdf(Request $request)
    {
        $consulta = Cliente::with('persona');

        if ($request->filled('ids')) {
            $consulta->whereIn('id', $request->input('ids'));
        }

        $filas = $consulta->get()->map(fn (Cliente $cliente) => [
            $cliente->id,
            trim(optional($cliente->persona)->nombre . ' ' . optional($cliente->persona)->apellido),
            $cliente->nit,
            $cliente->razon_social,
            $cliente->estado == 1 ? 'Activo' : 'Inactivo',
        ])->all();

        return $this->generarPdf(
            'Reporte de Clientes',
            ['ID', 'Nombre', 'Nit', 'Razón Social', 'Estado'],
            $filas,
            'clientes.pdf'
        );
    }

    private $rules = [
        'nit.required' => 'El NIT es obligatorio.',
        'nit.numeric' => 'El NIT debe ser un número.',

        'razon_social.required' => 'La razón social es obligatoria.',
        'razon_social.string' => 'La razón social debe ser texto.',
        'razon_social.max' => 'La razón social no puede exceder 255 caracteres.',

        'estado.required' => 'El estado es obligatorio.',
        'estado.numeric' => 'El estado debe ser un número.',

        'id_persona.required' => 'Debe seleccionar una persona.',
        'id_persona.exists' => 'La persona seleccionada no existe.',
    ];
}
