<?php

namespace App\Http\Controllers;

use App\Models\Habitacion;
use App\Models\Pago;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // No pertenece a un solo módulo (persona/reserva/etc.), así que solo exige
    // estar logueado en cualquiera de los 3 guards, no un permiso específico.
    public function index(Request $request)
    {
        if (!Auth::guard($request->session()->get('auth_guard'))->check()) {
            return redirect()->route('login');
        }

        $hoy = Carbon::today();

        // El comando programado (app:recalcular-disponibilidad-habitaciones a las
        // 11:00) depende de un cron del sistema operativo que no está configurado en
        // este entorno, así que se reutiliza la misma lógica al vuelo cada vez que se
        // carga el dashboard: primero se cierran las reservas cuya fecha de salida ya
        // pasó sin check-in ni check-out manual (deja de estar "pendiente" para
        // siempre y avisa por notificación), y luego se recalcula la disponibilidad de
        // las habitaciones en base a esos estados ya corregidos.
        Reserva::autoCheckoutVencidos();

        $habitaciones = Habitacion::with('tipoHabitacion')->orderBy('numero_habitacion')->get();
        $habitaciones->each(fn (Habitacion $habitacion) => $habitacion->actualizarDisponibilidad());

        $totalHabitaciones = $habitaciones->count();
        $habitacionesOcupadas = $habitaciones->where('estado', 0)->count();
        $habitacionesDisponibles = $totalHabitaciones - $habitacionesOcupadas;

        // "Llegadas de hoy" son huéspedes a los que hay que esperar: ya confirmaron su
        // pago (estado_estadia = confirmada) pero todavía no llegaron al hotel. Una
        // reserva pendiente (pago aún no cubierto) no debería anunciarse como llegada
        // confirmada, y una que ya hizo check-in ya no está "por llegar" sino que ya
        // ingresó correctamente.
        $llegadasHoy = Reserva::where('estado', 1)
            ->where('estado_estadia', Reserva::ESTADIA_CONFIRMADA)
            ->whereDate('fecha_inicio', $hoy)
            ->with(['cliente.persona', 'habitaciones'])
            ->get();

        $salidasHoy = Reserva::where('estado', 1)
            ->where('estado_estadia', '!=', Reserva::ESTADIA_CHECK_OUT)
            ->whereDate('fecha_fin', $hoy)
            ->with(['cliente.persona', 'habitaciones'])
            ->get();

        $ingresosMes = (float) Pago::where('estado', 1)
            ->whereMonth('fecha', $hoy->month)
            ->whereYear('fecha', $hoy->year)
            ->sum('monto');

        // Calendario de ocupación del mes: se arma una vez, con TODAS las habitaciones,
        // para que el filtro por defecto ("Todas") no necesite pedir cada habitación por
        // separado. Una reserva que ya hizo check-out no se incluye: la estadía ya
        // terminó, no es una ocupación vigente que valga la pena seguir mostrando.
        $primerDiaMes = $hoy->copy()->startOfMonth();
        $ultimoDiaMes = $hoy->copy()->endOfMonth();

        $reservasDelMes = Reserva::where('estado', 1)
            ->where('estado_estadia', '!=', Reserva::ESTADIA_CHECK_OUT)
            ->where('fecha_inicio', '<=', $ultimoDiaMes)
            ->where('fecha_fin', '>=', $primerDiaMes)
            ->with(['cliente.persona', 'habitaciones'])
            ->get()
            ->flatMap(function (Reserva $reserva) {
                return $reserva->habitaciones->map(function (Habitacion $habitacion) use ($reserva) {
                    $persona = optional(optional($reserva->cliente)->persona);
                    $nombreCliente = trim(($persona->nombre ?? '') . ' ' . ($persona->apellido ?? '')) ?: 'Cliente';

                    return [
                        'id_reserva' => $reserva->id,
                        'id_habitacion' => $habitacion->id,
                        'numero_habitacion' => $habitacion->numero_habitacion,
                        'cliente' => $nombreCliente,
                        'fecha_inicio' => Carbon::parse($reserva->fecha_inicio)->format('Y-m-d'),
                        'fecha_fin' => Carbon::parse($reserva->fecha_fin)->format('Y-m-d'),
                    ];
                });
            })
            ->values();

        return view('dashboard.index', compact(
            'totalHabitaciones',
            'habitacionesOcupadas',
            'habitacionesDisponibles',
            'llegadasHoy',
            'salidasHoy',
            'ingresosMes',
            'habitaciones',
            'reservasDelMes'
        ));
    }
}
