<?php

namespace App\Http\Controllers;

use App\Exceptions\ReservaException;
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
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReservaController extends Controller
{
  use ExportaPdf;

  public function index(){
    $datos = Reserva::orderByDesc('created_at')->get();
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
        'fecha_inicio' => 'required|date|after_or_equal:today',
        'fecha_fin' => 'required|date|after:fecha_inicio',
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
    catch (ReservaException $e) {
        // Mensaje de una regla de negocio propia: seguro para mostrar tal cual.
        return back()->with('error', $e->getMessage());
    }
    catch (\Exception $e) {
        // Cualquier otra excepción (BD, etc.) no se muestra al usuario tal cual
        // para no filtrar detalles internos; se registra para poder investigarla.
        Log::error('Error al registrar la reserva: ' . $e->getMessage(), ['exception' => $e]);
        return back()->with('error', 'Ocurrió un error al registrar la reserva. Intenta nuevamente.');
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

                  // Solo se exige que fecha_inicio no sea pasada cuando de verdad está
                  // cambiando: si únicamente se extiende fecha_fin de una reserva que ya
                  // está en curso (fecha_inicio ya pasó y no se toca), no debe rechazarse.
                  $fechaInicioCambio = $request->filled('fecha_inicio')
                      && !Carbon::parse($request->input('fecha_inicio'))->isSameDay($dato->fecha_inicio);

                  // Si cambian fechas o habitación, validar solapamiento con bloqueo
                  if ($request->hasAny(['fecha_inicio', 'fecha_fin', 'id_habitacion'])) {
                      $this->reservaService->validarDisponibilidadHabitacion(
                          $idHabitacion,
                          $request->input('fecha_inicio', $dato->fecha_inicio),
                          $request->input('fecha_fin', $dato->fecha_fin),
                          $dato->id,
                          $fechaInicioCambio
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
                      // Cambia la habitación: se recrea el pivot completo (habitación + servicios extras)
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
                  } elseif ($request->hasAny(['fecha_inicio', 'fecha_fin', 'servicios_extra'])) {
                      // Sin cambio de habitación: se actualiza el monto del pivot existente y,
                      // si cambiaron los servicios extras, se vuelven a sincronizar ahí mismo
                      // (antes solo se revisaban las fechas, así que editar nada más los
                      // servicios extras no actualizaba ni el monto ni la asociación real).
                      $idHabitacionReserva = $dato->habitacionReservas()->value('id');

                      if ($idHabitacionReserva) {
                          HabitacionReserva::whereKey($idHabitacionReserva)
                              ->update(['monto' => $modificar['costo_total'] ?? $dato->costo_total]);

                          if ($request->has('servicios_extra')) {
                              HabitacionServicioExtra::where('id_habitacion_reserva', $idHabitacionReserva)->delete();

                              foreach ($request->input('servicios_extra', []) as $idServicioExtra) {
                                  HabitacionServicioExtra::create([
                                      'id_habitacion_reserva' => $idHabitacionReserva,
                                      'id_servicio_extra' => $idServicioExtra,
                                  ]);
                              }
                          }
                      }
                  }
              });

              return response()->json([
                  'success' => true,
                  'message' => 'Reserva actualizada correctamente'
              ]);
          }
          catch (ReservaException $e) {
              return response()->json([
                  'success' => false,
                  'message' => $e->getMessage()
              ]);
          }
          catch (\Exception $e) {
              Log::error('Error al actualizar la reserva #' . $request->id . ': ' . $e->getMessage(), ['exception' => $e]);
              return response()->json([
                  'success' => false,
                  'message' => 'Ocurrió un error al actualizar la reserva. Intenta nuevamente.'
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
          Log::error('Error al actualizar la reserva: ' . $e->getMessage(), ['exception' => $e]);
          return response()->json([
              'success' => false,
              'message' => 'Ocurrió un error al actualizar la reserva. Intenta nuevamente.'
          ]);
      }
  }

  // Solo se permite el día de la llegada, y no si ya se hizo check-in/check-out
  // o si la reserva está cancelada.
  public function checkIn($id)
  {
      try {
          $reserva = Reserva::find($id);

          if (!$reserva) {
              throw new ReservaException('No se encontró la reserva.');
          }

          if ((int) $reserva->estado !== 1) {
              throw new ReservaException('No se puede hacer check-in de una reserva cancelada.');
          }

          if (!Carbon::parse($reserva->fecha_inicio)->isToday()) {
              throw new ReservaException('El check-in solo se puede registrar el día de llegada de la reserva.');
          }

          if (!in_array($reserva->estado_estadia, [Reserva::ESTADIA_PENDIENTE, Reserva::ESTADIA_CONFIRMADA], true)) {
              throw new ReservaException('Esta reserva ya tiene un check-in registrado.');
          }

          $reserva->update(['estado_estadia' => Reserva::ESTADIA_CHECK_IN]);

          return back()->with('success', 'Check-in registrado correctamente');
      }
      catch (ReservaException $e) {
          return back()->with('error', $e->getMessage());
      }
      catch (\Exception $e) {
          Log::error('Error al hacer check-in de la reserva #' . $id . ': ' . $e->getMessage(), ['exception' => $e]);
          return back()->with('error', 'Ocurrió un error al procesar el check-in. Intenta nuevamente.');
      }
  }

  // Requiere que ya se haya hecho check-in. Libera la habitación de inmediato,
  // sin esperar al schedule diario de las 11am.
  public function checkOut($id)
  {
      try {
          $reserva = Reserva::find($id);

          if (!$reserva) {
              throw new ReservaException('No se encontró la reserva.');
          }

          if ($reserva->estado_estadia !== Reserva::ESTADIA_CHECK_IN) {
              throw new ReservaException('Esta reserva no tiene un check-in registrado.');
          }

          $reserva->update(['estado_estadia' => Reserva::ESTADIA_CHECK_OUT]);

          $habitacionReserva = HabitacionReserva::where('id_reserva', $reserva->id)->first();
          $habitacionReserva?->habitacion?->actualizarDisponibilidad();

          return back()->with('success', 'Check-out registrado correctamente');
      }
      catch (ReservaException $e) {
          return back()->with('error', $e->getMessage());
      }
      catch (\Exception $e) {
          Log::error('Error al hacer check-out de la reserva #' . $id . ': ' . $e->getMessage(), ['exception' => $e]);
          return back()->with('error', 'Ocurrió un error al procesar el check-out. Intenta nuevamente.');
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
          Log::error('Error al eliminar la reserva #' . $request->inputIdEliminar . ': ' . $e->getMessage(), ['exception' => $e]);
          return redirect()->route('mostrar.reserva')->with('error', 'Ocurrió un error al eliminar la reserva. Intenta nuevamente.');
      }
  }

  public function getFechasOcupadas(Request $request, $habitacion_id){
      try {
          // ?excluir=ID: al editar una reserva, sus propias noches no deben contar como ocupadas
          $idReservaExcluir = $request->query('excluir');

          $habitacionReservas = HabitacionReserva::where('id_habitacion', $habitacion_id)
              ->whereHas('reserva', function ($query) use ($idReservaExcluir) {
                  $query->where('estado', 1); // solo reservas activas
                  if ($idReservaExcluir) {
                      $query->where('id', '!=', $idReservaExcluir);
                  }
              })
              ->with('reserva.cliente.persona')
              ->get();

          $fechasOcupadas = $habitacionReservas
              ->map(function ($hr) {
                  $fechas = [];
                  $inicio = Carbon::parse($hr->reserva->fecha_inicio);
                  $fin = Carbon::parse($hr->reserva->fecha_fin);

                  // Noches ocupadas: desde el ingreso hasta la víspera de la salida
                  // (el día de salida queda libre para un nuevo ingreso)
                  for ($date = $inicio; $date->lt($fin); $date->addDay()) {
                      $fechas[] = $date->format('Y-m-d');
                  }

                  return $fechas;
              })
              ->flatten()
              ->unique()
              ->values();

          // Detalle por reserva (P2.2: para mostrar huésped y # de reserva en el
          // calendario del dashboard). Es aditivo: fechas_ocupadas no cambia, así que
          // el formulario de reservas que ya consume este endpoint sigue funcionando igual.
          $reservas = $habitacionReservas->map(function ($hr) {
              $persona = optional(optional($hr->reserva->cliente)->persona);
              $nombreCliente = trim(($persona->nombre ?? '') . ' ' . ($persona->apellido ?? '')) ?: 'Cliente';

              return [
                  'id_reserva' => $hr->reserva->id,
                  'fecha_inicio' => Carbon::parse($hr->reserva->fecha_inicio)->format('Y-m-d'),
                  'fecha_fin' => Carbon::parse($hr->reserva->fecha_fin)->format('Y-m-d'),
                  'cliente' => $nombreCliente,
              ];
          })->values();

          return response()->json([
              'fechas_ocupadas' => $fechasOcupadas,
              'fecha_minima' => Carbon::today()->format('Y-m-d'),
              'reservas' => $reservas,
          ]);
      } catch (\Exception $e) {
          Log::error('Error al obtener las fechas ocupadas de la habitación #' . $habitacion_id . ': ' . $e->getMessage(), ['exception' => $e]);
          return response()->json(['error' => 'Ocurrió un error al consultar la disponibilidad.'], 500);
      }
  }

  // P2.1: habitaciones libres para un rango de fechas dado, sin importar si hoy
  // están ocupadas o no (una reserva futura no depende del estado de hoy).
  public function habitacionesDisponibles(Request $request)
  {
      try {
          $request->validate([
              'desde' => 'required|date',
              'hasta' => 'required|date|after_or_equal:desde',
          ]);

          $habitaciones = $this->reservaService->habitacionesDisponiblesEntre(
              $request->input('desde'),
              $request->input('hasta'),
              $request->input('excluir_reserva')
          );

          return response()->json([
              'habitaciones' => $habitaciones->map(fn (Habitacion $habitacion) => [
                  'id' => $habitacion->id,
                  'numero_habitacion' => $habitacion->numero_habitacion,
                  'precio' => optional($habitacion->tipoHabitacion)->precio ?? 0,
              ])->values(),
          ]);
      } catch (ValidationException $e) {
          return response()->json(['error' => collect($e->errors())->flatten()->join(' ')], 422);
      } catch (\Exception $e) {
          Log::error('Error al consultar habitaciones disponibles: ' . $e->getMessage(), ['exception' => $e]);
          return response()->json(['error' => 'Ocurrió un error al consultar la disponibilidad.'], 500);
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
      self::etiquetaEstadoEstadia($reserva->estado_estadia),
    ])->all();

    return $this->generarPdf(
      'Reporte de Reservas',
      ['ID', 'Fecha Inicio', 'Fecha Fin', 'Costo Total', 'Trabajador', 'Cliente', 'Habitación(es)', 'Servicios Extra', 'Estado', 'Estadía'],
      $filas,
      'reservas.pdf'
    );
  }

  public static function etiquetaEstadoEstadia(?string $estadoEstadia): string
  {
    return match ($estadoEstadia) {
      Reserva::ESTADIA_CONFIRMADA => 'Confirmada',
      Reserva::ESTADIA_CHECK_IN => 'Check-in',
      Reserva::ESTADIA_CHECK_OUT => 'Check-out',
      default => 'Pendiente',
    };
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
