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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    $trabajadorActual = $this->trabajadorDelUsuarioActual();

    $datos = $datos->map->toShow();

    return view("reserva.index",compact('datos','clientes','trabajadores','habitaciones','serviciosExtras','trabajadorActual'));
  }

  protected $reservaService;

  public function __construct(ReservaService $reservaService){
      $this->reservaService = $reservaService;
  }

  // El usuario logueado se identifica como trabajador a través de su persona
  // (id_persona). Si el rol actual no tiene un trabajador asociado (ej. un cliente
  // gestionando su propia reserva) devuelve null y el formulario recae en la
  // selección manual de siempre.
  private function trabajadorDelUsuarioActual(): ?Trabajador
  {
    $usuario = Auth::guard(session('auth_guard'))->user();

    if (!$usuario || !$usuario->id_persona) {
      return null;
    }

    return Trabajador::where('id_persona', $usuario->id_persona)->first();
  }

  public function store(Request $request){
    try {
      // Si el usuario logueado tiene un trabajador asociado, ese id manda siempre
      // (no se confía en lo que llegue del formulario); si no, se exige la
      // selección manual de siempre.
      $trabajadorSesion = $this->trabajadorDelUsuarioActual();

      $request->validate([
        // El costo ya no se confía al formulario: se recalcula en el servidor.
        'costo_total' => 'nullable|numeric|min:0',
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        'estado' => 'required|numeric|in:0,1',
        'id_trabajador' => $trabajadorSesion ? 'nullable|exists:trabajadors,id' : 'required|exists:trabajadors,id',
        'id_cliente' => 'required|exists:clientes,id',
        'id_habitacion' => 'required|exists:habitacions,id',
        'servicios_extra' => 'sometimes|array',
        'servicios_extra.*' => 'exists:servicio_extras,id',
      ], $this->rules);

      $idsServiciosExtra = $request->input('servicios_extra', []) ?? [];

      // Todo el registro (validar solapamiento con bloqueo + reserva + habitación +
      // servicios extras) ocurre en UNA transacción: si algo falla, no queda una
      // reserva "huérfana" sin habitación ni costo mal calculado.
      DB::transaction(function () use ($request, $trabajadorSesion, $idsServiciosExtra) {
        // Valida solapamiento con bloqueo sobre la habitación (evita que dos
        // reservas concurrentes se crucen)
        $this->reservaService->validarDisponibilidadHabitacion(
            $request->input('id_habitacion'),
            $request->input('fecha_inicio'),
            $request->input('fecha_fin')
        );

        // Costo total recalculado en el servidor (nunca el que llegó del formulario)
        $costoTotal = $this->reservaService->calcularCostoTotal(
            $request->input('id_habitacion'),
            $request->input('fecha_inicio'),
            $request->input('fecha_fin'),
            $idsServiciosExtra
        );

        $nuevo = Reserva::create([
          'costo_total' => $costoTotal,
          'fecha_inicio' => $request->input('fecha_inicio'),
          'fecha_fin' => $request->input('fecha_fin'),
          'estado' => $request->input('estado'),
          'id_trabajador' => $trabajadorSesion->id ?? $request->input('id_trabajador'),
          'id_cliente' => $request->input('id_cliente')
        ]);

        $habitacionReserva = HabitacionReserva::create([
          'monto' => $costoTotal,
          'id_reserva' => $nuevo->id,
          'id_habitacion' => $request->input('id_habitacion'),
        ]);

        foreach ($idsServiciosExtra as $idServicioExtra) {
          HabitacionServicioExtra::create([
            'id_habitacion_reserva' => $habitacionReserva->id,
            'id_servicio_extra' => $idServicioExtra,
          ]);
        }
      });
    }
    catch(ValidationException $e){
        $mensajes = collect($e->errors())->flatten()->join(' ');

        return back()->with('error', $mensajes);
    }
    catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }

    return redirect()->route('mostrar.reserva')->with('success', 'Reserva registrada correctamente');
  }

  public function update(Request $request){
      try {
          $modificar = $request->validate([
              'costo_total' => 'nullable|numeric|min:0', // se recalcula en el servidor
              'fecha_inicio' => 'sometimes|date',
              'fecha_fin' => 'sometimes|date|after_or_equal:fecha_inicio',
              'estado' => 'sometimes|numeric|in:0,1',
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
              DB::transaction(function () use ($request, $dato, &$modificar) {
                  // Habitación efectiva: la enviada en el formulario o, si no se envió,
                  // la que la reserva ya tiene asociada
                  $idHabitacion = $request->input('id_habitacion')
                      ?: $dato->habitacionReservas()->value('id_habitacion');

                  // Si cambian fechas o habitación, validar solapamiento con bloqueo
                  if ($request->hasAny(['fecha_inicio', 'fecha_fin', 'id_habitacion'])) {
                      $this->reservaService->validarDisponibilidadHabitacion(
                          $idHabitacion,
                          $request->input('fecha_inicio', $dato->fecha_inicio),
                          $request->input('fecha_fin', $dato->fecha_fin),
                          $dato->id
                      );
                  }

                  // Si cambian fechas, habitación o servicios, recalcular el costo en el servidor
                  if ($request->hasAny(['fecha_inicio', 'fecha_fin', 'id_habitacion', 'servicios_extra'])) {
                      $idsServiciosExtra = $request->has('servicios_extra')
                          ? $request->input('servicios_extra', [])
                          : $dato->habitacionReservas->flatMap->serviciosExtras->pluck('id')->all();

                      $modificar['costo_total'] = $this->reservaService->calcularCostoTotal(
                          $idHabitacion,
                          $request->input('fecha_inicio', $dato->fecha_inicio),
                          $request->input('fecha_fin', $dato->fecha_fin),
                          $idsServiciosExtra
                      );
                  }

                  $dato->update($modificar);

                  if ($request->has('id_habitacion')) {
                      // sincronizar habitación: eliminar relaciones previas y crear nueva
                      HabitacionReserva::where('id_reserva', $dato->id)->delete();

                      $habitacionReserva = HabitacionReserva::create([
                          'monto' => $modificar['costo_total'] ?? $dato->costo_total,
                          'id_reserva' => $dato->id,
                          'id_habitacion' => $request->input('id_habitacion'),
                      ]);

                      foreach ($request->input('servicios_extra', []) as $idServicioExtra) {
                          HabitacionServicioExtra::create([
                              'id_habitacion_reserva' => $habitacionReserva->id,
                              'id_servicio_extra' => $idServicioExtra,
                          ]);
                      }
                  } elseif ($request->hasAny(['fecha_inicio', 'fecha_fin'])) {
                      // Sin cambio de habitación pero con fechas nuevas: actualizar el monto del pivot
                      HabitacionReserva::where('id_reserva', $dato->id)
                          ->update(['monto' => $modificar['costo_total'] ?? $dato->costo_total]);
                  }
              });

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
