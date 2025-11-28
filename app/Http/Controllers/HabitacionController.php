<?php

namespace App\Http\Controllers;

use App\Models\Habitacion;
use App\Models\TipoHabitacion;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class HabitacionController extends Controller
{
  public function index()
  {
    $datos = Habitacion::all()->map->toShow();
    $tipos = TipoHabitacion::all()->map(function($tipo) {
      return [
        'id' => $tipo->id,
        'nombre' => $tipo->nombre,
        'precio' => $tipo->precio
      ];
    });
      
    return view('habitacion.index', compact('datos', 'tipos'));
  }

  public function store(Request $request)
  {
    try {
      $request->validate([
        'numero_habitacion' => 'required|integer|unique:habitacions',
        'planta' => 'required|integer|min:1',
        'estado' => 'required',
        'id_tipo_habitacion' => 'sometimes|max:4096'
      ], $this->rules);

      //dd($request->all());
      $nuevo = [
        "numero_habitacion" => $request->numero_habitacion,
        "planta" => $request->planta,
        "estado" => $request->estado,
        "id_tipo_habitacion" => $request->id_tipo_habitacion,        
      ];

      $nuevo = Habitacion::create($nuevo);
        
    } 
    catch(ValidationException $e){
      $mensajes = collect($e->errors())->flatten()->join(' ');
        
      return back()->with('error', $mensajes);
    }
    catch (\Exception $e) {
      return back()->with('error', $e->getMessage());
    }
    return redirect()->route('mostrar.habitacion');
  }

  public function update(Request $request)
  {
    try {
      $modificar = $request->validate([
        'id'                => 'required',
        'numero_habitacion' => 'required|integer',
        'planta'            => 'required|integer|min:1',
        'estado'            => 'required',
        'id_tipo_habitacion' => 'sometimes|nullable|exists:tipo_habitacions,id',
      ], $this->rules);  

      $dato = Habitacion::find($request->id);

      // Si viene id_tipo_habitacion en el request, añadirlo a los campos a modificar
      if ($request->has('id_tipo_habitacion')) {
        $modificar['id_tipo_habitacion'] = $request->input('id_tipo_habitacion');
      }

      $dato->update($modificar);
      //dd($dato);
    } 
    catch(ValidationException $e){
      $mensajes = collect($e->errors())->flatten()->join(' ');
      //  dd($mensajes);
      return back()->with('error', $mensajes);
    }
    catch (\Exception $e) {
      //  dd($e->getMessage());
      return back()->with('error', $e->getMessage());
    }
    return redirect()->route('mostrar.habitacion');
  }

  public function destroy(Request $request)
  {        
    $datos= Habitacion::find($request->inputIdEliminar);
    $datos->delete();
    
    return redirect()->route('mostrar.habitacion');
  }

  private $rules = [
    'numero_habitacion.required' => 'El número de habitación es obligatorio.',
    'numero_habitacion.integer' => 'Debe ingresar un número válido.',
    'numero_habitacion.unique' => 'El número de habitación ya existe.',

    'planta.required' => 'Debe especificar la cantidad de camas.',
    'planta.integer' => 'La cantidad debe ser un número entero.',
    'planta.min' => 'Debe haber al menos una cama.',

    'estado.boolean' => 'El valor de disponibilidad no es válido.',
  ];
}
