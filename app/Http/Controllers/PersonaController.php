<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportaPdf;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PersonaController extends Controller
{
    use ExportaPdf;

    public function index()
    {
        $datos = Persona::get();
        $datos = $datos->map->toShow();
        
        return view("persona.index", compact('datos'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'cedula' => 'required|string|max:255|unique:personas,cedula',
                'celular' => 'required|string|max:255',
                'correo' => 'required|email|max:255',
                'estado' => 'required|numeric'
            ], $this->rules);

            $nuevo = [
                'nombre' => $request->input('nombre'),
                'apellido' => $request->input('apellido'),
                'cedula' => $request->input('cedula'),
                'celular' => $request->input('celular'),
                'correo' => $request->input('correo'),
                'estado' => $request->input('estado')
            ];

            Persona::create($nuevo);
        }
        catch(ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.persona');
    }

    public function update(Request $request)
    {
        try {
            $modificar = $request->validate([
                'id' => 'required|exists:personas,id',
                'nombre' => 'sometimes|string|max:255',
                'apellido' => 'sometimes|string|max:255',
                'cedula' => 'sometimes|string|max:255|unique:personas,cedula,'.$request->id,
                'celular' => 'sometimes|string|max:255',
                'correo' => 'sometimes|email|max:255',
                'estado' => 'sometimes|numeric'
            ], $this->rules);

            $dato = Persona::find($request->id);
            
            if (!$dato) {
                return back()->with('error', 'No se encontró la persona.');
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

        return redirect()->route('mostrar.persona');
    }

    public function destroy(Request $request)
    {
        $datos = Persona::find($request->inputIdEliminar);
        $datos->update(['estado' => 0]);
        return redirect()->route('mostrar.persona');
    }

    public function exportarPdf(Request $request)
    {
        $consulta = Persona::query();

        if ($request->filled('ids')) {
            $consulta->whereIn('id', $request->input('ids'));
        }

        $filas = $consulta->get()->map(fn (Persona $persona) => [
            $persona->id,
            $persona->nombre,
            $persona->apellido,
            $persona->cedula,
            $persona->celular,
            $persona->correo,
            $persona->estado == 1 ? 'Activo' : 'Inactivo',
        ])->all();

        return $this->generarPdf(
            'Reporte de Personas',
            ['ID', 'Nombre', 'Apellido', 'Cédula', 'Celular', 'Correo', 'Estado'],
            $filas,
            'personas.pdf'
        );
    }

    private $rules = [
        'nombre.required' => 'El nombre es obligatorio.',
        'nombre.string' => 'El nombre debe ser texto.',
        'nombre.max' => 'El nombre no puede exceder 255 caracteres.',

        'apellido.required' => 'El apellido es obligatorio.',
        'apellido.string' => 'El apellido debe ser texto.',
        'apellido.max' => 'El apellido no puede exceder 255 caracteres.',

        'cedula.required' => 'La cédula es obligatoria.',
        'cedula.string' => 'La cédula debe ser texto.',
        'cedula.max' => 'La cédula no puede exceder 255 caracteres.',
        'cedula.unique' => 'Esta cédula ya está registrada.',

        'celular.required' => 'El celular es obligatorio.',
        'celular.string' => 'El celular debe ser texto.',
        'celular.max' => 'El celular no puede exceder 255 caracteres.',

        'correo.required' => 'El correo es obligatorio.',
        'correo.email' => 'Debe ingresar un correo válido.',
        'correo.max' => 'El correo no puede exceder 255 caracteres.',

        'estado.required' => 'El estado es obligatorio.',
        'estado.numeric' => 'El estado debe ser un número.'
    ];
}
