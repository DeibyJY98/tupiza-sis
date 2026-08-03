<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportaPdf;
use App\Models\Reserva;
use App\Models\Trabajador;
use App\Models\Cliente;
use App\Models\Habitacion;
use App\Models\HabitacionReserva;
use App\Models\HabitacionServicioExtra;
use App\Models\ServicioExtra;
use App\Services\ReservaService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ReservaController extends Controller
{
  use ExportaPdf;

  public function index(){
    $datos = Reserva::get();
    $habitaciones = Habitacion::get();
    $trabajadores = Trabajador::get();
    $clientes = Cliente::get();
    $serviciosExtras = ServicioExtra::where('estado', 1)->get();

    $datos = $datos->map->toShow();

    return view("reserva.index",compact('datos','clientes','trabajadores','habitaciones','serviciosExtras'));
  }

  protected $reservaService;

  public function __construct(ReservaService $reservaService){
      $this->reservaService = $reservaService;
  }

  public function store(Request $request){
    try {
      $request->validate([
        //'id' => 'required|string|max:255|unique:reservas,id',
        'costo_total' => 'required|numeric|min:0',
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        'estado' => 'required|numeric',
        'id_trabajador' => 'required|exists:trabajadors,id',
        'id_cliente' => 'required|exists:clientes,id',
        'id_habitacion' => 'required|exists:habitacions,id',
        'servicios_extra' => 'sometimes|array',
        'servicios_extra.*' => 'exists:servicio_extras,id',
      ], $this->rules);

      // Validar disponibilidad de la habitación
      $this->reservaService->validarDisponibilidadHabitacion(
          $request->input('id_habitacion'),
          $request->input('fecha_inicio'),
          $request->input('fecha_fin')
      );
    
      $nuevo = [
        'costo_total' => $request->input('costo_total'),
        'fecha_inicio' => $request->input('fecha_inicio'),
        'fecha_fin' => $request->input('fecha_fin'),
        'estado' => $request->input('estado'),
        'id_trabajador' => $request->input('id_trabajador'),
        'id_cliente' => $request->input('id_cliente')
      ];

      $nuevo = Reserva::create($nuevo);
      // si se envía una habitación, crear la relación en la tabla pivot
      if ($request->filled('id_habitacion')) {
        try {
          $habitacionReserva = HabitacionReserva::create([
            'monto' => $nuevo->costo_total ?? 0,
            'id_reserva' => $nuevo->id,
            'id_habitacion' => $request->input('id_habitacion'),
        ]);

          foreach ($request->input('servicios_extra', []) as $idServicioExtra) {
            HabitacionServicioExtra::create([
              'id_habitacion_reserva' => $habitacionReserva->id,
              'id_servicio_extra' => $idServicioExtra,
            ]);
          }
        }
        catch (\Exception $e) {
          // no detener el flujo por un error en el pivot; loguear y continuar
          return back()->with('error', $e->getMessage());
        }
      }
        
    } 
    catch(ValidationException $e){
        $mensajes = collect($e->errors())->flatten()->join(' ');
      
        return back()->with('error', $mensajes);
    }
    catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }

    return redirect()->route('mostrar.reserva');
  }

  public function update(Request $request){
      try {
          $modificar = $request->validate([      
              'costo_total' => 'sometimes|numeric|min:0',
              'fecha_inicio' => 'sometimes|date',
              'fecha_fin' => 'sometimes|date|after_or_equal:fecha_inicio',
              'estado' => 'sometimes|numeric',
              'id_trabajador' => 'sometimes|exists:trabajadors,id',
              'id_cliente' => 'sometimes|exists:clientes,id',
              'id_habitacion' => 'sometimes|exists:habitacions,id',
              'servicios_extra' => 'sometimes|array',
              'servicios_extra.*' => 'exists:servicio_extras,id',
          ], $this->rules);

          $dato = Reserva::where('id', $request->id)->first();
          
          if (!$dato) {
              return response()->json([
                  'success' => false,
                  'message' => 'No se encontró la reserva con el identificador proporcionado.'
              ]);
          }

          try {
              if ($request->has('id_habitacion') && ($request->has('fecha_inicio') || $request->has('fecha_fin'))) {
                  $this->reservaService->validarDisponibilidadHabitacion(
                      $request->input('id_habitacion'),
                      $request->input('fecha_inicio'),
                      $request->input('fecha_fin'),
                      $request->input('id')
                  );
              }
              
              $dato->update($modificar);
              // sincronizar habitación: eliminar relaciones previas y crear nueva si se envía id_habitacion
              if ($request->has('id_habitacion')) {
                  HabitacionReserva::where('id_reserva', $dato->id)->delete();
                  $habitacionReserva = HabitacionReserva::create([
                      'monto' => $modificar['costo_total'] ?? $dato->costo_total ?? 0,
                      'id_reserva' => $dato->id,
                      'id_habitacion' => $request->input('id_habitacion'),
                  ]);

                  foreach ($request->input('servicios_extra', []) as $idServicioExtra) {
                      HabitacionServicioExtra::create([
                          'id_habitacion_reserva' => $habitacionReserva->id,
                          'id_servicio_extra' => $idServicioExtra,
                      ]);
                  }
              }

              return response()->json([
                  'success' => true,
                  'message' => 'Reserva actualizada correctamente'
              ]);
          } 
          catch (\Exception $e) {
              return response()->json([
                  'success' => false,
                  'message' => $e->getMessage()
              ]);
          }
      } 
      catch(ValidationException $e){
          $mensajes = collect($e->errors())->flatten()->join(' ');
          return response()->json([
              'success' => false,
              'message' => $mensajes
          ]);
      }
      catch (\Exception $e) {
          return response()->json([
              'success' => false,
              'message' => $e->getMessage()
          ]);
      }
  }

  public function destroy(Request $request){        
      try {
          $datos = Reserva::find($request->inputIdEliminar);
          if ($datos) {
              // Liberar la habitación antes de cancelar la reserva
              $this->reservaService->liberarHabitacion($datos->id);
              $datos->update(['estado' => 0]);
          }
          return redirect()->route('mostrar.reserva')->with('success', 'Reserva eliminada correctamente');
      } catch (\Exception $e) {
          return redirect()->route('mostrar.reserva')->with('error', 'Error al eliminar la reserva: ' . $e->getMessage());
      }
  }

  public function getFechasOcupadas($habitacion_id){
      try {
          $fechasOcupadas = HabitacionReserva::where('id_habitacion', $habitacion_id)
              ->whereHas('reserva', function ($query) {
                  $query->where('estado', 1); // solo reservas activas
              })
              ->with('reserva')
              ->get()
              ->map(function ($hr) {
                  $fechas = [];
                  $inicio = Carbon::parse($hr->reserva->fecha_inicio);
                  $fin = Carbon::parse($hr->reserva->fecha_fin);
                  
                  // Generar array con todas las fechas entre inicio y fin
                  for ($date = $inicio; $date->lte($fin); $date->addDay()) {
                      $fechas[] = $date->format('Y-m-d');
                  }
                  
                  return $fechas;
              })
              ->flatten()
              ->unique()
              ->values();

          return response()->json([
              'fechas_ocupadas' => $fechasOcupadas,
              'fecha_minima' => Carbon::today()->format('Y-m-d')
          ]);
      } catch (\Exception $e) {
          return response()->json(['error' => $e->getMessage()], 500);
      }
  }

  public function exportarPdf(Request $request)
  {
    $consulta = Reserva::with(['cliente.persona', 'trabajador.persona', 'habitaciones', 'habitacionReservas.serviciosExtras']);

    if ($request->filled('ids')) {
      $consulta->whereIn('id', $request->input('ids'));
    }

    $filas = $consulta->get()->map(fn (Reserva $reserva) => [
      $reserva->id,
      \Illuminate\Support\Carbon::parse($reserva->fecha_inicio)->format('d/m/Y'),
      \Illuminate\Support\Carbon::parse($reserva->fecha_fin)->format('d/m/Y'),
      $reserva->costo_total,
      trim(optional(optional($reserva->trabajador)->persona)->nombre . ' ' . optional(optional($reserva->trabajador)->persona)->apellido),
      trim(optional(optional($reserva->cliente)->persona)->nombre . ' ' . optional(optional($reserva->cliente)->persona)->apellido),
      $reserva->habitaciones->pluck('numero_habitacion')->join(', '),
      $reserva->habitacionReservas->flatMap->serviciosExtras->pluck('nombre')->join(', '),
      $reserva->estado == 1 ? 'Completado' : 'Cancelado',
    ])->all();

    return $this->generarPdf(
      'Reporte de Reservas',
      ['ID', 'Fecha Inicio', 'Fecha Fin', 'Costo Total', 'Trabajador', 'Cliente', 'Habitación(es)', 'Servicios Extra', 'Estado'],
      $filas,
      'reservas.pdf'
    );
  }

  private $rules = [
    'costo_total.required' => 'El costo es obligatorio.',
    'costo_total.numeric' => 'El costo debe ser un número.',
    'costo_total.min' => 'El costo no puede ser negativo.',
    'costo_total.max' => 'El costo excede el límite permitido.',

    //'id.required' => 'El id es obligatorio.',
    //'id.unique' => 'El id ya existe.',

    'fecha_inicio.required' => 'La fecha es obligatoria.',
    'fecha_inicio.date' => 'Debe ingresar una fecha válida.',

    'fecha_fin.required' => 'La fecha de fin es obligatoria.',
    'fecha_fin.date' => 'Debe ingresar una fecha válida.',

    'id_trabajador.required' => 'Debe seleccionar un trabajador.',
    'id_trabajador.exists' => 'El trabajador seleccionado no existe.',

    'id_cliente.required' => 'Debe seleccionar un usuario.',
    'id_cliente.exists' => 'El usuario seleccionado no existe.',
    
    'id_habitacion.required' => 'Debe seleccionar una habitación.',
    'id_habitacion.exists' => 'La habitación seleccionada no existe.',
  ];
}
