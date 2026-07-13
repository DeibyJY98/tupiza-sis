<?php

namespace App\Http\Controllers;

use App\Models\ServicioExtra;
use App\Models\HabitacionReserva;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ServicioExtraController extends Controller
{
    public function index()
    {
        $datos = ServicioExtra::get();
        $datos = $datos->map->toShow();

        return view("ServicioExtra.index", compact('datos'));

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
        catch(ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.servicio_extra')->with('success', 'Servicio extra creado correctamente.');
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
                return back()->with('error', 'El servicio extra no existe.');
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

        return redirect()->route('mostrar.servicio_extra')->with('success', 'Servicio extra actualizado correctamente.');
    }

    public function destroy(Request $request)
    {
        try {
            $dato = ServicioExtra::find($request->inputIdEliminar);
            if ($dato) {
                $dato->delete();
                return redirect()->route('mostrar.servicio_extra')->with('success', 'Servicio extra eliminado correctamente.');
            }
            return redirect()->route('mostrar.servicio_extra')->with('error', 'El servicio extra no existe.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el servicio extra: ' . $e->getMessage());
        }
    }

    // RF16: asociar/desasociar un servicio extra a una habitación reservada
    public function asociar(Request $request)
    {
        try {
            $request->validate([
                'id_habitacion_reserva' => 'required|exists:habitacion_reservas,id',
                'id_servicio_extra' => 'required|exists:servicio_extras,id',
            ], $this->rulesAsociacion);

            $habitacionReserva = HabitacionReserva::find($request->id_habitacion_reserva);
            $habitacionReserva->serviciosExtras()->syncWithoutDetaching([$request->id_servicio_extra]);
        }
        catch(ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.servicio_extra')->with('success', 'Servicio extra asociado correctamente.');
    }

    public function desasociar(Request $request)
    {
        try {
            $request->validate([
                'id_habitacion_reserva' => 'required|exists:habitacion_reservas,id',
                'id_servicio_extra' => 'required|exists:servicio_extras,id',
            ], $this->rulesAsociacion);

            $habitacionReserva = HabitacionReserva::find($request->id_habitacion_reserva);
            $habitacionReserva->serviciosExtras()->detach($request->id_servicio_extra);
        }
        catch(ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.servicio_extra')->with('success', 'Servicio extra desasociado correctamente.');
    }

    private $rules = [
        'nombre.required' => 'El nombre es obligatorio.',
        'nombre.string' => 'El nombre debe ser texto.',

        'descripcion.required' => 'La descripción es obligatoria.',
        'descripcion.string' => 'La descripción debe ser texto.',

        'precio.required' => 'El precio es obligatorio.',
        'precio.numeric' => 'El precio debe ser un número.',
        'precio.min' => 'El precio no puede ser negativo.',

        'estado.required' => 'El estado es obligatorio.',
        'estado.numeric' => 'El estado debe ser un número.',
    ];

    private $rulesAsociacion = [
        'id_habitacion_reserva.required' => 'Debe indicar la reserva de habitación.',
        'id_habitacion_reserva.exists' => 'La reserva de habitación indicada no existe.',

        'id_servicio_extra.required' => 'Debe seleccionar un servicio extra.',
        'id_servicio_extra.exists' => 'El servicio extra seleccionado no existe.',
    ];
}
